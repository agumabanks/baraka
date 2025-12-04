<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\Invoice;
use App\Models\ClientAddress;
use App\Models\Backend\Branch;
use App\Services\RatingService;
use App\Support\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ClientPortalController extends Controller
{
    protected function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    public function shipments(Request $request): View
    {
        $customer = $this->customer();
        
        $shipments = $customer->shipments()
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, function($q, $s) {
                $q->where(function($query) use ($s) {
                    $query->where('tracking_number', 'like', "%{$s}%")
                        ->orWhere('awb_number', 'like', "%{$s}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        $stats = [
            'total' => $customer->shipments()->count(),
            'in_transit' => $customer->shipments()->whereIn('status', ['booked', 'picked_up', 'in_transit', 'out_for_delivery'])->count(),
            'delivered' => $customer->shipments()->where('status', 'delivered')->count(),
        ];

        return view('client.shipments.index', compact('shipments', 'stats'));
    }

    public function showShipment(Shipment $shipment): View
    {
        if ($shipment->customer_id !== $this->customer()->id) {
            abort(403);
        }

        $shipment->load(['originBranch', 'destinationBranch']);

        return view('client.shipments.show', compact('shipment'));
    }

    public function createShipment(): View
    {
        $branches = Branch::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'city', 'country']);

        $customer = $this->customer();
        $addresses = $customer->clientAddresses ?? collect();

        return view('client.shipments.create', [
            'branches' => $branches,
            'addresses' => $addresses,
            'customer' => $customer,
            'currency' => SystemSettings::defaultCurrency(),
        ]);
    }

    public function storeShipment(Request $request, RatingService $ratingService): RedirectResponse
    {
        $customer = $this->customer();

        $data = $request->validate([
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id',
            'service_level' => 'required|in:economy,standard,express,priority',
            'weight' => 'required|numeric|min:0.1',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'pieces' => 'required|integer|min:1',
            'declared_value' => 'nullable|numeric|min:0',
            'description' => 'required|string|max:500',
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:50',
            'delivery_address' => 'required|string|max:500',
            'special_instructions' => 'nullable|string|max:500',
        ]);

        $quote = $ratingService->quote([
            'origin_branch_id' => $data['origin_branch_id'],
            'destination_branch_id' => $data['destination_branch_id'],
            'service_level' => $data['service_level'],
            'weight' => $data['weight'],
            'length' => $data['length'] ?? 0,
            'width' => $data['width'] ?? 0,
            'height' => $data['height'] ?? 0,
            'pieces' => $data['pieces'],
            'declared_value' => $data['declared_value'] ?? 0,
            'customer_id' => $customer->id,
        ]);

        if (!empty($quote['errors'])) {
            return back()->withInput()->with('error', implode(', ', $quote['errors']));
        }

        $shipment = Shipment::create([
            'customer_id' => $customer->id,
            'origin_branch_id' => $data['origin_branch_id'],
            'destination_branch_id' => $data['destination_branch_id'],
            'tracking_number' => Shipment::generateTrackingNumber(),
            'awb_number' => Shipment::generateAwbNumber(),
            'service_level' => $data['service_level'],
            'weight' => $data['weight'],
            'volumetric_weight' => $quote['volumetric_weight'] ?? null,
            'chargeable_weight' => $quote['chargeable_weight'] ?? $data['weight'],
            'length' => $data['length'],
            'width' => $data['width'],
            'height' => $data['height'],
            'pieces' => $data['pieces'],
            'declared_value' => $data['declared_value'],
            'description' => $data['description'],
            'total_amount' => $quote['total'],
            'base_rate' => $quote['base_rate'] ?? 0,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'source' => 'customer_portal',
            'metadata' => [
                'receiver_name' => $data['receiver_name'],
                'receiver_phone' => $data['receiver_phone'],
                'delivery_address' => $data['delivery_address'],
                'special_instructions' => $data['special_instructions'] ?? null,
                'sender_name' => $customer->contact_person,
                'sender_phone' => $customer->phone,
                'sender_address' => $customer->billing_address,
            ],
        ]);

        return redirect()
            ->route('client.shipments.show', $shipment)
            ->with('success', 'Shipment created successfully! Tracking: ' . $shipment->tracking_number);
    }

    public function tracking(Request $request): View
    {
        $shipment = null;
        $awb = $request->get('awb');

        if ($awb) {
            $shipment = Shipment::where('tracking_number', $awb)
                ->orWhere('awb_number', $awb)
                ->first();
        }

        return view('client.tracking', compact('shipment', 'awb'));
    }

    public function quotes(Request $request): View
    {
        $branches = Branch::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'city', 'country']);

        return view('client.quotes', [
            'branches' => $branches,
            'currency' => SystemSettings::defaultCurrency(),
        ]);
    }

    public function calculateQuote(Request $request, RatingService $ratingService)
    {
        $data = $request->validate([
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id',
            'weight' => 'required|numeric|min:0.1',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
        ]);

        $quotes = [];
        $serviceLevels = ['economy', 'standard', 'express', 'priority'];

        foreach ($serviceLevels as $level) {
            $quote = $ratingService->quote([
                'origin_branch_id' => $data['origin_branch_id'],
                'destination_branch_id' => $data['destination_branch_id'],
                'service_level' => $level,
                'weight' => $data['weight'],
                'length' => $data['length'] ?? 0,
                'width' => $data['width'] ?? 0,
                'height' => $data['height'] ?? 0,
                'customer_id' => $this->customer()->id,
            ]);

            if (empty($quote['errors'])) {
                $quotes[$level] = $quote;
            }
        }

        return response()->json(['quotes' => $quotes]);
    }

    public function addresses(): View
    {
        $addresses = $this->customer()->clientAddresses ?? collect();

        return view('client.addresses', compact('addresses'));
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => 'required|string|max:100',
            'contact_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        $data['customer_id'] = $this->customer()->id;

        if ($data['is_default'] ?? false) {
            ClientAddress::where('customer_id', $this->customer()->id)->update(['is_default' => false]);
        }

        ClientAddress::create($data);

        return back()->with('success', 'Address added successfully.');
    }

    public function deleteAddress(ClientAddress $address): RedirectResponse
    {
        if ($address->customer_id !== $this->customer()->id) {
            abort(403);
        }

        $address->delete();

        return back()->with('success', 'Address deleted.');
    }

    public function invoices(): View
    {
        $invoices = $this->customer()->invoices()
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('client.invoices', compact('invoices'));
    }

    public function profile(): View
    {
        return view('client.profile', ['customer' => $this->customer()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'contact_person' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'mobile' => 'nullable|string|max:50',
            'billing_address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        $this->customer()->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => 'required|current_password:customer',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->customer()->update(['password' => $data['password']]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function support(): View
    {
        return view('client.support', ['customer' => $this->customer()]);
    }
}
