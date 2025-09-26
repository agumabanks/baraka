<?php

namespace App\Http\Controllers\Portal;

use App\Enums\DeliveryType as DeliveryTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\FrontWeb\Service;
use App\Models\Backend\Packaging;
use App\Models\Backend\Parcel;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;

class CustomerPortalController extends Controller
{
    public function index()
    {
        return view('frontend.portal.index');
    }

    public function createShipment()
    {
        $deliveryCategories = Deliverycategory::where('status', 1)->get();
        $deliveryTypes = $this->availableDeliveryTypes();
        $packagingTypes = Packaging::where('status', 1)->get();

        return view('frontend.portal.create_shipment', compact('deliveryCategories', 'deliveryTypes', 'packagingTypes'));
    }

    public function storeShipment(Request $request)
    {
        $availableDeliveryTypes = $this->availableDeliveryTypes();
        $deliveryTypeIds = $availableDeliveryTypes->pluck('id')->all();

        $request->validate([
            'pickup_phone' => 'required|string|max:20',
            'pickup_address' => 'required|string|max:255',
            'customer_name' => 'required|string|max:100',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string|max:255',
            'category_id' => 'required|exists:deliverycategories,id',
            'delivery_type_id' => ['required', Rule::in($deliveryTypeIds)],
            'cash_collection' => 'required|numeric|min:0',
            'invoice_no' => 'nullable|string|max:50',
            'packaging_id' => 'nullable|exists:packagings,id',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $parcel = new Parcel();
            $parcel->merchant_id = auth()->id();
            $parcel->pickup_phone = $request->pickup_phone;
            $parcel->pickup_address = $request->pickup_address;
            $parcel->customer_name = $request->customer_name;
            $parcel->customer_phone = $request->customer_phone;
            $parcel->customer_address = $request->customer_address;
            $parcel->category_id = $request->category_id;
            $parcel->delivery_type_id = $request->delivery_type_id;
            $parcel->cash_collection = $request->cash_collection;
            $parcel->invoice_no = $request->invoice_no;
            $parcel->packaging_id = $request->packaging_id;
            $parcel->note = $request->note;
            $parcel->status = 1; // Pending status
            $parcel->save();

            return redirect()->route('portal.index')->with('success', 'Shipment created successfully!');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Failed to create shipment. Please try again.');
        }
    }

    public function createShipmentFromPast()
    {
        $pastShipments = Parcel::where('merchant_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get();

        return view('frontend.portal.create_shipment_from_past', compact('pastShipments'));
    }

    public function createShipmentFromFavorite()
    {
        $favoriteShipments = Parcel::where('merchant_id', auth()->id())
            ->where('is_favorite', true)
            ->get();

        return view('frontend.portal.create_shipment_from_favorite', compact('favoriteShipments'));
    }

    public function getRateQuote()
    {
        return view('frontend.portal.rate_quote');
    }

    public function schedulePickup()
    {
        return view('frontend.portal.schedule_pickup');
    }

    public function uploadShipmentFile()
    {
        return view('frontend.portal.upload_shipment_file');
    }

    public function orderSupplies()
    {
        $supplies = Packaging::where('status', 1)->get();

        return view('frontend.portal.order_supplies', compact('supplies'));
    }

    public function deliveryServices()
    {
        $services = Service::where('status', 1)->get();
        return view('frontend.portal.delivery_services', compact('services'));
    }

    public function optionalServices()
    {
        return view('frontend.portal.optional_services');
    }

    public function customsServices()
    {
        return view('frontend.portal.customs_services');
    }

    public function surcharges()
    {
        return view('frontend.portal.surcharges');
    }

    public function solutions()
    {
        return view('frontend.portal.solutions');
    }

    public function learn()
    {
        return view('frontend.portal.learn');
    }

    public function aboutMyBarakaPlus()
    {
        return view('frontend.portal.about_mybaraka_plus');
    }

    public function whatsNew()
    {
        return view('frontend.portal.whats_new');
    }

    protected function availableDeliveryTypes(): Collection
    {
        $definitions = collect([
            DeliveryTypeEnum::SAMEDAY => 'same_day',
            DeliveryTypeEnum::NEXTDAY => 'next_day',
            DeliveryTypeEnum::SUBCITY => 'sub_city',
            DeliveryTypeEnum::OUTSIDECITY => 'outside_City',
        ]);

        $enabled = Config::whereIn('key', $definitions->values())->pluck('value', 'key');

        return $definitions
            ->map(function (string $configKey, int $id) use ($enabled) {
                if ((int) ($enabled[$configKey] ?? 0) !== 1) {
                    return null;
                }

                return new Fluent([
                    'id' => $id,
                    'key' => $configKey,
                    'name' => trans('deliveryType.' . $configKey),
                ]);
            })
            ->filter()
            ->values();
    }
}
