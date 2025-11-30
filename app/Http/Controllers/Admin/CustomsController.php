<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\Customs\CustomsClearanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomsController extends Controller
{
    public function __construct(
        protected CustomsClearanceService $customsService
    ) {}

    /**
     * Customs clearance dashboard
     */
    public function index(Request $request): View
    {
        $branchId = $request->get('branch_id');

        $summary = $this->customsService->getClearanceSummary($branchId);
        $pendingShipments = $this->customsService->getPendingClearance($branchId);
        $awaitingDuty = $this->customsService->getAwaitingDutyPayment($branchId);

        return view('admin.customs.index', [
            'summary' => $summary,
            'pendingShipments' => $pendingShipments,
            'awaitingDuty' => $awaitingDuty,
            'branchFilter' => $branchId,
        ]);
    }

    /**
     * Show shipment customs details
     */
    public function show(Shipment $shipment): View
    {
        $shipment->load(['customer', 'originBranch', 'destBranch']);

        return view('admin.customs.show', [
            'shipment' => $shipment,
            'commonDocuments' => CustomsClearanceService::getCommonDocuments(),
        ]);
    }

    /**
     * Place shipment on customs hold
     */
    public function hold(Request $request, Shipment $shipment): RedirectResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->customsService->placeOnCustomsHold(
            $shipment,
            $data['reason'],
            $request->user()
        );

        return back()->with('success', 'Shipment placed on customs hold.');
    }

    /**
     * Request documents
     */
    public function requestDocuments(Request $request, Shipment $shipment): RedirectResponse
    {
        $data = $request->validate([
            'documents' => 'required|array|min:1',
            'documents.*' => 'string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->customsService->requestDocuments(
            $shipment,
            $data['documents'],
            $data['notes'] ?? null,
            $request->user()
        );

        return back()->with('success', 'Document request sent.');
    }

    /**
     * Assess customs duty
     */
    public function assessDuty(Request $request, Shipment $shipment): RedirectResponse
    {
        $data = $request->validate([
            'hs_code' => 'nullable|string|max:20',
            'duty_amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'currency' => 'required|string|size:3',
        ]);

        $this->customsService->assessDuty(
            $shipment,
            $data['duty_amount'],
            $data['currency'],
            $data['hs_code'] ?? null,
            $data['tax_amount'] ?? null,
            $request->user()
        );

        return back()->with('success', 'Customs duty assessed.');
    }

    /**
     * Record duty payment
     */
    public function recordPayment(Request $request, Shipment $shipment): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'payment_reference' => 'required|string|max:100',
        ]);

        $this->customsService->recordDutyPayment(
            $shipment,
            $data['amount'],
            $data['payment_method'],
            $data['payment_reference'],
            $request->user()
        );

        return back()->with('success', 'Duty payment recorded.');
    }

    /**
     * Clear shipment
     */
    public function clear(Request $request, Shipment $shipment): RedirectResponse
    {
        $data = $request->validate([
            'clearance_number' => 'nullable|string|max:50',
        ]);

        $this->customsService->clearShipment(
            $shipment,
            $data['clearance_number'] ?? null,
            $request->user()
        );

        return back()->with('success', 'Shipment cleared through customs.');
    }

    /**
     * Record inspection
     */
    public function recordInspection(Request $request, Shipment $shipment): RedirectResponse
    {
        $data = $request->validate([
            'result' => 'required|in:passed,failed,conditional',
            'notes' => 'nullable|string|max:1000',
            'findings' => 'nullable|array',
        ]);

        $this->customsService->recordInspection(
            $shipment,
            $data['result'],
            $data['notes'] ?? null,
            $data['findings'] ?? null,
            $request->user()
        );

        return back()->with('success', 'Inspection recorded.');
    }
}
