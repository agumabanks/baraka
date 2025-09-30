<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backend\Hub;
use App\Models\Backend\Parcel;
use App\Models\RateCard;
use App\Models\Shipment;
use App\Models\User;
use App\Services\Gs1LabelGenerator;
use App\Services\SsccGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BookingWizardController extends Controller
{
    /**
     * Show the booking wizard
     */
    public function index(): View
    {
        $this->authorize('viewAny', \App\Models\Shipment::class);
        $branches = Hub::active()->get();
        $rateCards = RateCard::active()->get();

        return view('admin.booking-wizard.index', compact('branches', 'rateCards'));
    }

    /**
     * Step 1: Booking wizard entry
     * - GET: show UI
     * - POST (API): create/select customer
     */
    public function step1(Request $request)
    {
        if ($request->isMethod('get')) {
            $this->authorize('create', \App\Models\Shipment::class);
            $branches = Hub::active()->get();
            $rateCards = RateCard::active()->get();

            return view('admin.booking-wizard.index', compact('branches', 'rateCards'));
        }

        $request->validate([
            'customer_id' => 'nullable|exists:users,id',
            'name' => 'required_if:customer_id,null|string|max:255',
            'email' => 'required_if:customer_id,null|email|max:255',
            'phone' => 'required_if:customer_id,null|string|max:20',
            'pickup_address' => 'nullable|string',
            'delivery_address' => 'nullable|string',
        ]);

        $customer = $request->customer_id
            ? User::find($request->customer_id)
            : User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'user_type' => 'customer',
                'password' => bcrypt('temp123'), // Temporary password
                'pickup_address' => $request->pickup_address,
                'delivery_address' => $request->delivery_address,
            ]);

        return response()->json([
            'success' => true,
            'customer' => $customer,
            'next_step' => 2,
        ]);
    }

    /**
     * Step 2: Shipment details
     */
    public function step2(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:users,id',
            'origin_branch_id' => 'required|exists:hubs,id',
            'dest_branch_id' => 'required|exists:hubs,id|different:origin_branch_id',
            'service_level' => 'required|in:STANDARD,EXPRESS,ECONOMY',
            'incoterm' => 'required|in:DAP,DDP',
            'declared_value' => 'nullable|numeric|min:0',
        ]);

        // Store in session for step 3
        session([
            'booking_customer_id' => $request->customer_id,
            'booking_origin_branch_id' => $request->origin_branch_id,
            'booking_dest_branch_id' => $request->dest_branch_id,
            'booking_service_level' => $request->service_level,
            'booking_incoterm' => $request->incoterm,
            'booking_declared_value' => $request->declared_value,
        ]);

        return response()->json([
            'success' => true,
            'next_step' => 3,
        ]);
    }

    /**
     * Step 3: Parcel details
     */
    public function step3(Request $request): JsonResponse
    {
        $request->validate([
            'parcels' => 'required|array|min:1',
            'parcels.*.weight_kg' => 'required|numeric|min:0.1|max:1000',
            'parcels.*.length_cm' => 'nullable|numeric|min:1|max:300',
            'parcels.*.width_cm' => 'nullable|numeric|min:1|max:300',
            'parcels.*.height_cm' => 'nullable|numeric|min:1|max:300',
            'parcels.*.contents' => 'nullable|string|max:500',
            'parcels.*.declared_value' => 'nullable|numeric|min:0',
        ]);

        // Store parcels in session
        session(['booking_parcels' => $request->parcels]);

        // Calculate pricing
        $pricing = $this->calculatePricing($request->parcels);

        return response()->json([
            'success' => true,
            'pricing' => $pricing,
            'next_step' => 4,
        ]);
    }

    /**
     * Step 4: Confirm and generate labels
     */
    public function step4(Request $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // Create shipment
            $shipment = Shipment::create([
                'customer_id' => session('booking_customer_id'),
                'origin_branch_id' => session('booking_origin_branch_id'),
                'dest_branch_id' => session('booking_dest_branch_id'),
                'service_level' => session('booking_service_level'),
                'incoterm' => session('booking_incoterm'),
                'price_amount' => session('booking_total_price'),
                'currency' => 'EUR',
                'current_status' => \App\Enums\ShipmentStatus::CREATED,
                'created_by' => auth()->id(),
            ]);

            // Create parcels with SSCC
            $parcels = [];
            foreach (session('booking_parcels') as $parcelData) {
                $sscc = SsccGenerator::generate();

                $parcel = Parcel::create([
                    'shipment_id' => $shipment->id,
                    'sscc' => $sscc,
                    'tracking_id' => $this->generateTrackingId(),
                    'customer_name' => $shipment->customer->name,
                    'customer_phone' => $shipment->customer->phone,
                    'customer_address' => $shipment->customer->delivery_address ?? $shipment->customer->pickup_address,
                    'weight' => $parcelData['weight_kg'],
                    'length' => $parcelData['length_cm'] ?? null,
                    'width' => $parcelData['width_cm'] ?? null,
                    'height' => $parcelData['height_cm'] ?? null,
                    'contents' => $parcelData['contents'] ?? 'General Goods',
                    'declared_value' => $parcelData['declared_value'] ?? null,
                ]);

                $parcels[] = $parcel;
            }

            // Generate labels
            $labelPdf = Gs1LabelGenerator::generateBulkLabels(collect($parcels));

            // Save labels to storage
            $labelPath = 'labels/shipment_'.$shipment->id.'.pdf';
            Storage::put($labelPath, $labelPdf);

            DB::commit();

            // Clear session
            session()->forget(['booking_customer_id', 'booking_origin_branch_id', 'booking_dest_branch_id',
                'booking_service_level', 'booking_incoterm', 'booking_parcels', 'booking_total_price']);

            return response()->json([
                'success' => true,
                'shipment' => $shipment->load(['parcels', 'customer']),
                'label_url' => Storage::url($labelPath),
                'next_step' => 5,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create shipment: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Step 5: Handover (create ARRIVE scan)
     */
    public function step5(Request $request): JsonResponse
    {
        $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
        ]);

        $shipment = Shipment::find($request->shipment_id);

        // Create ARRIVE scan events for all parcels
        foreach ($shipment->parcels as $parcel) {
            \App\Models\ScanEvent::create([
                'sscc' => $parcel->sscc,
                'type' => \App\Enums\ScanType::ARRIVE,
                'branch_id' => $shipment->origin_branch_id,
                'user_id' => auth()->id(),
                'occurred_at' => now(),
                'note' => 'Handover from booking wizard',
            ]);
        }

        // Update shipment status
        $shipment->update(['current_status' => \App\Enums\ShipmentStatus::HANDED_OVER]);

        return response()->json([
            'success' => true,
            'message' => 'Shipment handed over successfully',
            'tracking_url' => route('tracking.show', $shipment->tracking_number),
        ]);
    }

    /**
     * Download labels
     */
    public function downloadLabels(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $request->validate([
            'shipment_id' => 'required|exists:shipments,id',
        ]);

        $labelPath = 'labels/shipment_'.$request->shipment_id.'.pdf';

        if (! Storage::exists($labelPath)) {
            abort(404, 'Labels not found');
        }

        return Storage::download($labelPath, 'shipment_'.$request->shipment_id.'_labels.pdf');
    }

    /**
     * Calculate pricing for parcels
     */
    private function calculatePricing(array $parcels): array
    {
        $rateCard = RateCard::active()
            ->where('origin_country', 'DE') // Default to Germany
            ->where('dest_country', 'CD')   // Default to DRC
            ->first();

        if (! $rateCard) {
            return ['error' => 'No rate card found'];
        }

        $subtotal = 0;
        $details = [];

        foreach ($parcels as $parcel) {
            $weight = $parcel['weight_kg'];
            $baseRate = ($rateCard->zone_matrix['A'] ?? 10) * $weight; // Zone A default
            $fuelSurcharge = $baseRate * ($rateCard->fuel_surcharge_percent / 100);

            $parcelTotal = $baseRate + $fuelSurcharge;
            $subtotal += $parcelTotal;

            $details[] = [
                'weight' => $weight,
                'base_rate' => $baseRate,
                'fuel_surcharge' => $fuelSurcharge,
                'total' => $parcelTotal,
            ];
        }

        $tax = $subtotal * 0.1; // 10% tax
        $total = $subtotal + $tax;

        session(['booking_total_price' => $total]);

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
            'currency' => 'EUR',
            'parcel_details' => $details,
        ];
    }

    /**
     * Generate tracking ID
     */
    private function generateTrackingId(): string
    {
        do {
            $trackingId = 'BK'.date('Y').strtoupper(substr(md5(microtime()), 0, 8));
        } while (Parcel::where('tracking_id', $trackingId)->exists());

        return $trackingId;
    }
}
