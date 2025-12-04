<?php

namespace App\Http\Controllers\Branch;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Branch\Concerns\ResolvesBranch;
use App\Http\Controllers\Controller;
use App\Models\BranchAlert;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\BranchContext;
use App\Support\BranchCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ExceptionController extends Controller
{
    use ResolvesBranch;

    /**
     * Exception dashboard with categories and metrics
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $categoryFilter = $request->get('category');
        $severityFilter = $request->get('severity');
        $statusFilter = $request->get('status', 'open');

        // Get shipments with exceptions
        $query = Shipment::query()
            ->with(['customer:id,name,company_name', 'assignedWorker.user:id,name', 'originBranch:id,name,code', 'destBranch:id,name,code'])
            ->where(function ($q) use ($branch) {
                $q->where('origin_branch_id', $branch->id)
                    ->orWhere('dest_branch_id', $branch->id);
            })
            ->where('has_exception', true);

        // Filter by resolution status
        if ($statusFilter === 'open') {
            $query->whereNull('exception_resolved_at');
        } elseif ($statusFilter === 'resolved') {
            $query->whereNotNull('exception_resolved_at');
        }

        // Filter by category if column exists
        if ($categoryFilter && Schema::hasColumn('shipments', 'exception_category')) {
            $query->where('exception_category', $categoryFilter);
        }

        // Filter by severity if column exists
        if ($severityFilter && Schema::hasColumn('shipments', 'exception_severity')) {
            $query->where('exception_severity', $severityFilter);
        }

        $exceptions = $query->orderByDesc('updated_at')->paginate(20);

        // Calculate stats
        $baseQuery = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->where('has_exception', true);

        $stats = [
            'total_open' => (clone $baseQuery)->whereNull('exception_resolved_at')->count(),
            'total_resolved_today' => (clone $baseQuery)
                ->whereDate('exception_resolved_at', today())
                ->count(),
            'critical' => Schema::hasColumn('shipments', 'exception_severity')
                ? (clone $baseQuery)->where('exception_severity', 'critical')->whereNull('exception_resolved_at')->count()
                : 0,
            'high' => Schema::hasColumn('shipments', 'exception_severity')
                ? (clone $baseQuery)->where('exception_severity', 'high')->whereNull('exception_resolved_at')->count()
                : 0,
            'avg_resolution_hours' => $this->getAverageResolutionTime($branch),
        ];

        // Categories for filter dropdown
        $categories = [
            'address_issue' => 'Address Issue',
            'damaged' => 'Damaged',
            'delayed' => 'Delayed',
            'delivery_failed' => 'Delivery Failed',
            'lost' => 'Lost/Missing',
            'customs_hold' => 'Customs Hold',
            'payment_issue' => 'Payment Issue',
            'recipient_unavailable' => 'Recipient Unavailable',
            'wrong_shipment' => 'Wrong Shipment',
            'other' => 'Other',
        ];

        $severities = [
            'critical' => 'Critical',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
        ];

        return view('branch.exceptions.index', [
            'branch' => $branch,
            'branchOptions' => $this->branchOptions($user),
            'exceptions' => $exceptions,
            'stats' => $stats,
            'categories' => $categories,
            'severities' => $severities,
            'categoryFilter' => $categoryFilter,
            'severityFilter' => $severityFilter,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Flag a shipment as having an exception
     */
    public function flag(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        $validated = $request->validate([
            'shipment_id' => 'required|integer|exists:shipments,id',
            'category' => 'required|string|max:50',
            'severity' => 'required|in:critical,high,medium,low',
            'description' => 'required|string|max:1000',
            'root_cause' => 'nullable|string|max:500',
            'notify_customer' => 'boolean',
        ]);

        $shipment = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })->findOrFail($validated['shipment_id']);

        DB::beginTransaction();
        try {
            // Update shipment
            $updateData = [
                'has_exception' => true,
                'exception_flagged_at' => now(),
                'exception_flagged_by' => $user->id,
            ];

            if (Schema::hasColumn('shipments', 'exception_category')) {
                $updateData['exception_category'] = $validated['category'];
            }
            if (Schema::hasColumn('shipments', 'exception_severity')) {
                $updateData['exception_severity'] = $validated['severity'];
            }
            if (Schema::hasColumn('shipments', 'exception_description')) {
                $updateData['exception_description'] = $validated['description'];
            }
            if (Schema::hasColumn('shipments', 'exception_root_cause')) {
                $updateData['exception_root_cause'] = $validated['root_cause'];
            }

            $shipment->update($updateData);

            // Create branch alert
            BranchAlert::create([
                'branch_id' => $branch->id,
                'alert_type' => 'EXCEPTION',
                'severity' => strtoupper($validated['severity']),
                'status' => 'OPEN',
                'title' => ucfirst(str_replace('_', ' ', $validated['category'])) . ' - ' . $shipment->tracking_number,
                'message' => $validated['description'],
                'context' => [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'category' => $validated['category'],
                    'root_cause' => $validated['root_cause'],
                ],
                'triggered_at' => now(),
            ]);

            // Log activity
            activity()
                ->performedOn($shipment)
                ->causedBy($user)
                ->withProperties([
                    'category' => $validated['category'],
                    'severity' => $validated['severity'],
                    'description' => $validated['description'],
                ])
                ->log('Exception flagged');

            DB::commit();
            BranchCache::flushForBranch($branch->id);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Exception flagged successfully']);
            }

            return back()->with('success', 'Exception flagged for shipment ' . $shipment->tracking_number);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to flag exception: ' . $e->getMessage());
        }
    }

    /**
     * Update exception details
     */
    public function update(Request $request, Shipment $shipment): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        // Verify shipment belongs to branch
        if (!in_array($branch->id, [$shipment->origin_branch_id, $shipment->dest_branch_id])) {
            abort(403, 'Shipment does not belong to your branch');
        }

        $validated = $request->validate([
            'category' => 'nullable|string|max:50',
            'severity' => 'nullable|in:critical,high,medium,low',
            'description' => 'nullable|string|max:1000',
            'root_cause' => 'nullable|string|max:500',
            'action_taken' => 'nullable|string|max:1000',
        ]);

        $updateData = [];

        if (isset($validated['category']) && Schema::hasColumn('shipments', 'exception_category')) {
            $updateData['exception_category'] = $validated['category'];
        }
        if (isset($validated['severity']) && Schema::hasColumn('shipments', 'exception_severity')) {
            $updateData['exception_severity'] = $validated['severity'];
        }
        if (isset($validated['description']) && Schema::hasColumn('shipments', 'exception_description')) {
            $updateData['exception_description'] = $validated['description'];
        }
        if (isset($validated['root_cause']) && Schema::hasColumn('shipments', 'exception_root_cause')) {
            $updateData['exception_root_cause'] = $validated['root_cause'];
        }
        if (isset($validated['action_taken']) && Schema::hasColumn('shipments', 'exception_action_taken')) {
            $updateData['exception_action_taken'] = $validated['action_taken'];
        }

        $shipment->update($updateData);

        activity()
            ->performedOn($shipment)
            ->causedBy($user)
            ->withProperties($validated)
            ->log('Exception updated');

        BranchCache::flushForBranch($branch->id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Exception updated']);
        }

        return back()->with('success', 'Exception updated');
    }

    /**
     * Resolve an exception
     */
    public function resolve(Request $request, Shipment $shipment): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        // Verify shipment belongs to branch
        if (!in_array($branch->id, [$shipment->origin_branch_id, $shipment->dest_branch_id])) {
            abort(403, 'Shipment does not belong to your branch');
        }

        $validated = $request->validate([
            'resolution' => 'required|string|max:1000',
            'resolution_type' => 'required|in:resolved,escalated,rerouted,returned,cancelled',
            'notify_customer' => 'boolean',
            'new_status' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $updateData = [
                'exception_resolved_at' => now(),
                'exception_resolved_by' => $user->id,
            ];

            if (Schema::hasColumn('shipments', 'exception_resolution')) {
                $updateData['exception_resolution'] = $validated['resolution'];
            }
            if (Schema::hasColumn('shipments', 'exception_resolution_type')) {
                $updateData['exception_resolution_type'] = $validated['resolution_type'];
            }

            // If not escalated, clear the exception flag
            if ($validated['resolution_type'] !== 'escalated') {
                $updateData['has_exception'] = false;
            }

            $shipment->update($updateData);

            // Resolve related alerts
            BranchAlert::where('branch_id', $branch->id)
                ->where('alert_type', 'EXCEPTION')
                ->where('status', 'OPEN')
                ->whereJsonContains('context->shipment_id', $shipment->id)
                ->update([
                    'status' => 'RESOLVED',
                    'resolved_at' => now(),
                    'resolved_by' => $user->id,
                    'resolution_notes' => $validated['resolution'],
                ]);

            // Update shipment status if requested
            if (!empty($validated['new_status'])) {
                $newStatus = ShipmentStatus::fromString($validated['new_status']);
                if ($newStatus) {
                    app(\App\Services\Logistics\ShipmentLifecycleService::class)->transition($shipment, $newStatus, [
                        'performed_by_user' => $user,
                        'notes' => 'Status updated during exception resolution: ' . $validated['resolution'],
                    ]);
                }
            }

            activity()
                ->performedOn($shipment)
                ->causedBy($user)
                ->withProperties([
                    'resolution' => $validated['resolution'],
                    'resolution_type' => $validated['resolution_type'],
                ])
                ->log('Exception resolved');

            DB::commit();
            BranchCache::flushForBranch($branch->id);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Exception resolved']);
            }

            return back()->with('success', 'Exception resolved for ' . $shipment->tracking_number);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to resolve exception: ' . $e->getMessage());
        }
    }

    /**
     * Escalate exception to HQ/Admin
     */
    public function escalate(Request $request, Shipment $shipment): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        if (!in_array($branch->id, [$shipment->origin_branch_id, $shipment->dest_branch_id])) {
            abort(403, 'Shipment does not belong to your branch');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'priority' => 'required|in:normal,urgent,critical',
        ]);

        DB::beginTransaction();
        try {
            if (Schema::hasColumn('shipments', 'exception_escalated_at')) {
                $shipment->update([
                    'exception_escalated_at' => now(),
                    'exception_escalated_by' => $user->id,
                    'exception_escalation_reason' => $validated['reason'],
                ]);
            }

            // Create HQ-level alert (branch_id = null for HQ visibility)
            BranchAlert::create([
                'branch_id' => null, // HQ level
                'alert_type' => 'ESCALATION',
                'severity' => strtoupper($validated['priority']),
                'status' => 'OPEN',
                'title' => 'Escalated: ' . $shipment->tracking_number,
                'message' => $validated['reason'],
                'context' => [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'origin_branch_id' => $branch->id,
                    'origin_branch_name' => $branch->name,
                    'escalated_by' => $user->name,
                ],
                'triggered_at' => now(),
            ]);

            activity()
                ->performedOn($shipment)
                ->causedBy($user)
                ->withProperties($validated)
                ->log('Exception escalated to HQ');

            DB::commit();
            BranchCache::flushForBranch($branch->id);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Exception escalated to HQ']);
            }

            return back()->with('success', 'Exception escalated to HQ for ' . $shipment->tracking_number);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to escalate: ' . $e->getMessage());
        }
    }

    /**
     * Get assignment suggestions for resolving exceptions
     */
    public function getSuggestions(Request $request, Shipment $shipment): JsonResponse
    {
        $user = $request->user();
        $this->assertBranchPermission($user);
        $branch = $this->resolveBranch($request);

        if (!in_array($branch->id, [$shipment->origin_branch_id, $shipment->dest_branch_id])) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $category = $shipment->exception_category ?? 'other';

        // Get resolution suggestions based on category
        $suggestions = $this->getResolutionSuggestions($category);

        // Get available workers for assignment
        $workers = \App\Models\Backend\BranchWorker::where('branch_id', $branch->id)
            ->active()
            ->with('user:id,name')
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'name' => $w->user?->name,
                'role' => $w->role?->label() ?? $w->role,
                'current_workload' => $w->assignedShipments()->whereNotIn('current_status', ['DELIVERED', 'CANCELLED'])->count(),
            ]);

        return response()->json([
            'success' => true,
            'suggestions' => $suggestions,
            'workers' => $workers,
        ]);
    }

    /**
     * Get resolution suggestions based on exception category
     */
    private function getResolutionSuggestions(string $category): array
    {
        $suggestions = [
            'address_issue' => [
                'Contact customer to verify address',
                'Update delivery address from customer profile',
                'Schedule redelivery with corrected address',
                'Return to sender if undeliverable',
            ],
            'damaged' => [
                'Document damage with photos',
                'File damage claim',
                'Arrange replacement shipment',
                'Process partial delivery',
                'Return to origin for inspection',
            ],
            'delayed' => [
                'Expedite through priority handling',
                'Notify customer of delay and new ETA',
                'Reroute via faster lane',
                'Compensate customer for delay',
            ],
            'delivery_failed' => [
                'Schedule redelivery',
                'Contact recipient for alternative time',
                'Leave at secure location if authorized',
                'Hold at branch for pickup',
            ],
            'lost' => [
                'Initiate full investigation',
                'Check last known scan location',
                'File loss claim',
                'Arrange replacement shipment',
            ],
            'customs_hold' => [
                'Request additional documentation from sender',
                'Pay customs duties',
                'Submit corrected declaration',
                'Return to origin if clearance impossible',
            ],
            'payment_issue' => [
                'Contact customer for payment',
                'Hold shipment pending payment',
                'Adjust COD amount',
                'Convert to prepaid',
            ],
            'recipient_unavailable' => [
                'Attempt delivery at different time',
                'Leave delivery notice',
                'Contact alternative recipient',
                'Hold for pickup',
            ],
            'wrong_shipment' => [
                'Retrieve incorrect shipment',
                'Dispatch correct shipment',
                'Investigate mix-up source',
                'Notify affected parties',
            ],
            'other' => [
                'Investigate root cause',
                'Escalate to supervisor',
                'Contact customer for clarification',
            ],
        ];

        return $suggestions[$category] ?? $suggestions['other'];
    }

    /**
     * Get average resolution time in hours
     */
    private function getAverageResolutionTime($branch): float
    {
        if (!Schema::hasColumn('shipments', 'exception_flagged_at') || !Schema::hasColumn('shipments', 'exception_resolved_at')) {
            return 0;
        }

        $avg = Shipment::where(function ($q) use ($branch) {
            $q->where('origin_branch_id', $branch->id)->orWhere('dest_branch_id', $branch->id);
        })
            ->whereNotNull('exception_flagged_at')
            ->whereNotNull('exception_resolved_at')
            ->where('exception_resolved_at', '>=', now()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, exception_flagged_at, exception_resolved_at)) as avg_hours')
            ->value('avg_hours');

        return round($avg ?? 0, 1);
    }
}
