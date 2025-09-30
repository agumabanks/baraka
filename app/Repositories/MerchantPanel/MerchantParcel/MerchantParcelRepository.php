<?php

namespace App\Repositories\MerchantPanel\MerchantParcel;

use App\Enums\DeliveryTime;
use App\Enums\DeliveryType;
use App\Enums\ParcelPaymentMethod;
use App\Enums\ParcelStatus;
use App\Enums\Status;
use App\Models\Backend\Deliverycategory;
use App\Models\Backend\DeliveryCharge;
use App\Models\Backend\Merchant;
use App\Models\Backend\Packaging;
use App\Models\Backend\Parcel;
use App\Models\Backend\ParcelEvent;
use App\Models\Config;
use App\Models\MerchantShops;
use App\Models\Subscribe;
use App\Repositories\Wallet\WalletInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MerchantParcelRepository implements MerchantParcelInterface
{
    protected $walletRepo;

    public function __construct(WalletInterface $walletRepo)
    {
        $this->walletRepo = $walletRepo;
    }

    public function all($merchant_id)
    {
        return Parcel::where('merchant_id', $merchant_id)->orderByDesc('id')->paginate(10);
    }

    public function parcelAll($merchant_id)
    {
        return Parcel::with('merchant')->where('merchant_id', $merchant_id)->orderByDesc('id')->get();
    }

    public function deliveryTypes()
    {
        $types = [
            'same_day',
            'next_day',
            'sub_city',
            'outside_City',
        ];

        return Config::whereIn('key', $types)->where('value', 1)->get();
    }

    public function parcelBank($merchant_id)
    {
        return Parcel::where('parcel_bank', 'on')->where('merchant_id', $merchant_id)->orderByDesc('id')->paginate(10);
    }

    public function filter($merchant_id, $request)
    {

        return Parcel::where('merchant_id', $merchant_id)->orderByDesc('id')->where(function ($query) use ($request) {

            if ($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if (is_array($date)) {
                    $from = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('created_at', [$from, $to]);
                }
            }

            if ($request->parcel_status) {
                if ($request->parcel_status == 'in_transit') {
                    $query->whereNotIn('status', [ParcelStatus::DELIVERED, ParcelStatus::RETURN_RECEIVED_BY_MERCHANT]);
                } else {
                    $query->where('status', $request->parcel_status);
                }
            }

            if ($request->parcel_customer) {
                $query->where('customer_name', 'like', '%'.$request->parcel_customer.'%');
            }
            if ($request->parcel_customer_phone) {
                $query->where('customer_phone', 'like', '%'.$request->parcel_customer_phone.'%');
            }
            if ($request->invoice_id) {
                $query->where('invoice_no', 'like', '%'.$request->invoice_id.'%');
            }

        })->paginate(10);
    }

    public function parcelEvents($id)
    {
        return ParcelEvent::where('parcel_id', $id)->orderBy('created_at', 'desc')->get();
    }

    public function get($id)
    {
        return Parcel::find($id);
    }

    public function details($id)
    {
        return Parcel::where('id', $id)->with('merchant', 'merchant.user', 'merchantShop', 'deliveryCategory', 'packaging')->first();
    }

    public function statusUpdate($id, $status_id)
    {
        $parcel = Parcel::find($id);
        $parcel->status = $status_id;
        $parcel->save();

        return true;
    }

    public function getMerchant($id)
    {
        return Merchant::where('user_id', $id)->first();
    }

    public function getShop($id)
    {
        return MerchantShops::where('merchant_id', $id)->first();
    }

    public function getShops($id)
    {
        $merchantShops = [];
        $merchantShop = MerchantShops::where(['merchant_id' => $id, 'default_shop' => Status::ACTIVE])->first();
        $merchantShops[] = $merchantShop;
        $merchantShopArray = MerchantShops::where(['merchant_id' => $id, 'default_shop' => Status::INACTIVE])->get();
        if (! blank($merchantShopArray)) {
            foreach ($merchantShopArray as $shop) {
                $merchantShops[] = $shop;
            }
        }

        return $merchantShops;
    }

    public function deliveryCharges()
    {
        return DeliveryCharge::distinct('category_id')->pluck('category_id');
    }

    public function deliveryCategories()
    {
        return pluck(Deliverycategory::all(), 'obj', 'id');
    }

    public function packaging()
    {
        return Packaging::where('status', Status::ACTIVE)->get();
    }

    public function RandomTrackingID()
    {
        return Str::upper(settings()->par_track_prefix).random_int(11111111, 99999999);
    }

    public function store($request, $merchant_id)
    {

        try {
            $chargeDetails = json_decode($request->chargeDetails);

            $parcel = new Parcel;
            $parcel->merchant_id = $request->merchant_id ?? $merchant_id;
            $parcel->first_hub_id = auth()->user()->hub_id;
            $parcel->hub_id = auth()->user()->hub_id;
            $parcel->category_id = $request->category_id;
            if ($request->weight) {
                $parcel->weight = $request->weight;
            }
            $parcel->invoice_no = $request->invoice_no;
            $parcel->cash_collection = $request->cash_collection;
            if ($request->selling_price) {
                $parcel->selling_price = $request->selling_price;
            }

            $parcel->merchant_shop_id = $request->shop_id;
            $parcel->pickup_phone = $request->pickup_phone;
            $parcel->pickup_address = $request->pickup_address;
            $parcel->pickup_lat = $request->pickup_lat;
            $parcel->pickup_long = $request->pickup_long;

            $parcel->customer_name = $request->customer_name;
            $parcel->customer_phone = $request->customer_phone;
            $parcel->customer_address = $request->customer_address;
            $parcel->customer_lat = $request->lat;
            $parcel->customer_long = $request->long;

            $parcel->delivery_type_id = $request->delivery_type_id;
            // Pickup & Delivery Time
            if ($request->delivery_type_id == DeliveryType::SAMEDAY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d');
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                }
            } elseif ($request->delivery_type_id == DeliveryType::NEXTDAY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +2 day'));
                }
            } elseif ($request->delivery_type_id == DeliveryType::SUBCITY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::SUBCITY.' day'));
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::SUBCITY + 1 .' day'));
                }
            } elseif ($request->delivery_type_id == DeliveryType::OUTSIDECITY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::OUTSIDECITY.' day'));
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::OUTSIDECITY + 1 .' day'));
                }
            }
            // End Pickup & Delivery Time

            $parcel->vat = $chargeDetails->vatTex;
            $parcel->vat_amount = $chargeDetails->VatAmount;
            $parcel->delivery_charge = $chargeDetails->deliveryChargeAmount;

            // merchant cod charge
            $Codmerchant = Auth::user()->merchant;
            $merchantCODCharge = 0;
            if ($request->delivery_type_id == 1 || $request->delivery_type_id == 2) {
                $merchantCODCharge = $Codmerchant->cod_charges['inside_city'];
            } elseif ($request->delivery_type_id == 3) {
                $merchantCODCharge = $Codmerchant->cod_charges['sub_city'];
            } elseif ($request->delivery_type_id == 4) {
                $merchantCODCharge = $Codmerchant->cod_charges['outside_city'];
            }
            // end merchant cod charge
            $parcel->cod_charge = $merchantCODCharge;
            $parcel->cod_amount = $chargeDetails->codChargeAmount;
            $parcel->total_delivery_amount = $chargeDetails->totalDeliveryChargeAmount;
            $parcel->current_payable = $chargeDetails->currentPayable;
            $parcel->note = $request->note;
            $parcel->parcel_bank = $request->parcel_bank;
            $parcel->status = ParcelStatus::PENDING;
            if ($request->packaging_id) {
                $parcel->packaging_id = $request->packaging_id;
                $parcel->packaging_amount = $chargeDetails->packagingAmount;
            }
            if (isset($request->fragileLiquid) && $request->fragileLiquid == 'on') {
                $parcel->liquid_fragile_amount = $chargeDetails->liquidFragileAmount;
            }
            $parcel->tracking_id = $this->RandomTrackingID();
            $parcel->parcel_payment_method = $request->parcel_payment_method ?? ParcelPaymentMethod::COD;
            $parcel->save();

            // wallet
            if ($parcel && $request->parcel_payment_method == ParcelPaymentMethod::PREPAID) {
                $w_merchant = Merchant::find($request->merchant_id);
                $m_user_id = $w_merchant->user_id;
                $w_merchant->wallet_balance = $w_merchant->wallet_balance - $parcel->total_delivery_amount;
                $w_merchant->save();

                $walletExpense = new Request;
                $walletExpense['user_id'] = $m_user_id;
                $walletExpense['merchant_id'] = $request->merchant_id;
                $walletExpense['tracking_id'] = $parcel->tracking_id;
                $walletExpense['amount'] = $parcel->total_delivery_amount;
                $this->walletRepo->expense($walletExpense);
            }
            // end wallet

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function duplicateStore($request, $merchant_id)
    {
        try {
            $chargeDetails = json_decode($request->chargeDetails);
            $duplicate_parcel = $this->get($request->parcel_id);

            $parcel = new Parcel;
            $parcel->merchant_id = $request->merchant_id ?? $merchant_id;
            $parcel->first_hub_id = auth()->user()->hub_id;
            $parcel->hub_id = auth()->user()->hub_id;
            $parcel->category_id = $request->category_id;
            if ($request->weight !== '') {
                $parcel->weight = $request->weight;
            }
            $parcel->invoice_no = $request->invoice_no;
            $parcel->cash_collection = $request->cash_collection;
            if ($request->selling_price) {
                $parcel->selling_price = $request->selling_price;
            }

            $parcel->merchant_shop_id = $request->shop_id;
            $parcel->pickup_phone = $request->pickup_phone;
            $parcel->pickup_address = $request->pickup_address;
            $parcel->pickup_lat = $request->pickup_lat;
            $parcel->pickup_long = $request->pickup_long;

            $parcel->customer_name = $request->customer_name;
            $parcel->customer_phone = $request->customer_phone;
            $parcel->customer_address = $request->customer_address;
            $parcel->customer_lat = $request->lat;
            $parcel->customer_long = $request->long;

            $parcel->delivery_type_id = $request->delivery_type_id;
            $parcel->note = $request->note;
            $parcel->parcel_bank = $request->parcel_bank;
            $parcel->status = ParcelStatus::PENDING;

            // Pickup & Delivery Time
            if ($request->delivery_type_id == DeliveryType::SAMEDAY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d');
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                }
            } elseif ($request->delivery_type_id == DeliveryType::NEXTDAY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +2 day'));
                }
            } elseif ($request->delivery_type_id == DeliveryType::SUBCITY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::SUBCITY.' day'));
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::SUBCITY + 1 .' day'));
                }
            } elseif ($request->delivery_type_id == DeliveryType::OUTSIDECITY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::OUTSIDECITY.' day'));
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::OUTSIDECITY + 1 .' day'));
                }
            }
            // End Pickup & Delivery Time

            if (! blank($chargeDetails)) {
                $parcel->vat = $chargeDetails->vatTex;
                $parcel->vat_amount = $chargeDetails->VatAmount;
                $parcel->delivery_charge = $chargeDetails->deliveryChargeAmount;
                // merchant cod charge
                $Codmerchant = Auth::user()->merchant;
                $merchantCODCharge = 0;
                if ($request->delivery_type_id == 1 || $request->delivery_type_id == 2) {
                    $merchantCODCharge = $Codmerchant->cod_charges['inside_city'];
                } elseif ($request->delivery_type_id == 3) {
                    $merchantCODCharge = $Codmerchant->cod_charges['sub_city'];
                } elseif ($request->delivery_type_id == 4) {
                    $merchantCODCharge = $Codmerchant->cod_charges['outside_city'];
                }
                // end merchant cod charge
                $parcel->cod_charge = $merchantCODCharge;
                $parcel->cod_amount = $chargeDetails->codChargeAmount;
                $parcel->total_delivery_amount = $chargeDetails->totalDeliveryChargeAmount;
                $parcel->current_payable = $chargeDetails->currentPayable;
                if ($request->packaging_id) {
                    $parcel->packaging_id = $request->packaging_id;
                    $parcel->packaging_amount = $chargeDetails->packagingAmount;
                }
                if (isset($request->fragileLiquid) && $request->fragileLiquid == 'on') {
                    $parcel->liquid_fragile_amount = $chargeDetails->liquidFragileAmount;
                } else {
                    $parcel->liquid_fragile_amount = null;
                }
            } else {
                $parcel->vat = $duplicate_parcel->vat;
                $parcel->vat_amount = $duplicate_parcel->vat_amount;
                $parcel->delivery_charge = $duplicate_parcel->delivery_charge;
                $parcel->cod_charge = $duplicate_parcel->cod_charge;
                $parcel->cod_amount = $duplicate_parcel->cod_amount;
                $parcel->total_delivery_amount = $duplicate_parcel->total_delivery_amount;
                $parcel->current_payable = $duplicate_parcel->current_payable;
                if ($request->packaging_id) {
                    $parcel->packaging_id = $request->packaging_id;
                    $parcel->packaging_amount = $duplicate_parcel->packaging_amount;

                }
                $parcel->liquid_fragile_amount = $duplicate_parcel->liquid_fragile_amount;
            }

            $parcel->tracking_id = $this->$this->RandomTrackingID();

            if ($request->parcel_payment_method) {
                $parcel->parcel_payment_method = $request->parcel_payment_method;
            }
            $parcel->save();

            // wallet
            if ($parcel && $request->parcel_payment_method == ParcelPaymentMethod::PREPAID) {
                $w_merchant = Merchant::find($request->merchant_id);
                $m_user_id = $w_merchant->user_id;
                $w_merchant->wallet_balance = $w_merchant->wallet_balance - $parcel->total_delivery_amount;
                $w_merchant->save();

                $walletExpense = new Request;
                $walletExpense['user_id'] = $m_user_id;
                $walletExpense['merchant_id'] = $request->merchant_id;
                $walletExpense['tracking_id'] = $parcel->tracking_id;
                $walletExpense['amount'] = $parcel->total_delivery_amount;
                $this->walletRepo->expense($walletExpense);
            }
            // end wallet

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request, $merchant_id)
    {

        try {
            $chargeDetails = json_decode($request->chargeDetails);

            $parcel = Parcel::find($id);
            $parcel->merchant_id = $request->merchant_id;
            $parcel->category_id = $request->category_id;
            if ($request->weight !== '') {
                $parcel->weight = $request->weight;
            }
            $parcel->invoice_no = $request->invoice_no;
            $parcel->cash_collection = $request->cash_collection;
            if ($request->selling_price) {
                $parcel->selling_price = $request->selling_price;
            }

            $parcel->merchant_shop_id = $request->shop_id;
            $parcel->pickup_phone = $request->pickup_phone;
            $parcel->pickup_address = $request->pickup_address;
            $parcel->pickup_lat = $request->pickup_lat;
            $parcel->pickup_long = $request->pickup_long;

            $parcel->customer_name = $request->customer_name;
            $parcel->customer_phone = $request->customer_phone;
            $parcel->customer_address = $request->customer_address;
            $parcel->customer_lat = $request->lat;
            $parcel->customer_long = $request->long;

            $parcel->delivery_type_id = $request->delivery_type_id;

            // Pickup & Delivery Time
            if ($request->delivery_type_id == DeliveryType::SAMEDAY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d');
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                }
            } elseif ($request->delivery_type_id == DeliveryType::NEXTDAY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +2 day'));
                }
            } elseif ($request->delivery_type_id == DeliveryType::SUBCITY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::SUBCITY.' day'));
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::SUBCITY + 1 .' day'));
                }
            } elseif ($request->delivery_type_id == DeliveryType::OUTSIDECITY) {
                if (date('H') < DeliveryTime::LAST_TIME) {
                    $parcel->pickup_date = date('Y-m-d');
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::OUTSIDECITY.' day'));
                } else {
                    $parcel->pickup_date = date('Y-m-d', strtotime(date('Y-m-d').' +1 day'));
                    $parcel->delivery_date = date('Y-m-d', strtotime(date('Y-m-d').' +'.DeliveryTime::OUTSIDECITY + 1 .' day'));
                }
            }
            // End Pickup & Delivery Time

            $parcel->note = $request->note;
            $parcel->parcel_bank = $request->parcel_bank;

            if (! blank($chargeDetails)) {
                $parcel->vat = $chargeDetails->vatTex;
                $parcel->vat_amount = $chargeDetails->VatAmount;
                $parcel->delivery_charge = $chargeDetails->deliveryChargeAmount;

                // merchant cod charge
                $Codmerchant = Auth::user()->merchant;
                $merchantCODCharge = 0;
                if ($request->delivery_type_id == 1 || $request->delivery_type_id == 2) {
                    $merchantCODCharge = $Codmerchant->cod_charges['inside_city'];
                } elseif ($request->delivery_type_id == 3) {
                    $merchantCODCharge = $Codmerchant->cod_charges['sub_city'];
                } elseif ($request->delivery_type_id == 4) {
                    $merchantCODCharge = $Codmerchant->cod_charges['outside_city'];
                }
                // end merchant cod charge
                $parcel->cod_charge = $merchantCODCharge;
                $parcel->cod_amount = $chargeDetails->codChargeAmount;
                $parcel->total_delivery_amount = $chargeDetails->totalDeliveryChargeAmount;
                $parcel->current_payable = $chargeDetails->currentPayable;

                if (isset($request->fragileLiquid) && $request->fragileLiquid == 'on') {
                    $parcel->liquid_fragile_amount = $chargeDetails->liquidFragileAmount;
                } else {
                    $parcel->liquid_fragile_amount = null;
                }
                if ($request->packaging_id) {
                    $parcel->packaging_id = $request->packaging_id;
                    $parcel->packaging_amount = $chargeDetails->packagingAmount;

                }
            }
            if ($request->parcel_payment_method) {
                $parcel->parcel_payment_method = $request->parcel_payment_method;
            }
            $parcel->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id, $merchant_id)
    {
        return Parcel::destroy($id);
    }

    public function parcelTrack($track_id)
    {

        $parcel = Parcel::where('tracking_id', $track_id)->with(['merchant'])->select('id', 'merchant_id', 'tracking_id', 'created_at')->first();
        $merchant = Merchant::find($parcel->merchant_id);
        $createdEvent = [
            'tracking_id' => $parcel->tracking_id,
            'created_at' => $parcel->created_at,
            'merchant_name' => $merchant->user->name,
            'email' => $merchant->user->email,
            'mobile' => $merchant->user->mobile,
        ];
        if ($parcel) {
            $data = [
                'parcel' => $createdEvent,
                'events' => ParcelEvent::with(['deliveryMan', 'pickupman', 'transferDeliveryman', 'hub', 'user'])->where('parcel_id', $parcel->id)->orderBy('created_at', 'desc')->get(),
            ];

            return $data;
        } else {
            return false;
        }

    }

    public function subscribe($request)
    {

        try {
            $exists = Subscribe::where('email', $request->email)->first();
            if ($exists) {
                return 1;
            } else {
                try {
                    Subscribe::create(['email' => $request->email]);

                    return true;
                } catch (\Throwable $th) {
                    return false;
                }
            }
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function filterExport($merchant_id, $request)
    {

        return Parcel::where('merchant_id', $merchant_id)->orderByDesc('id')->where(function ($query) use ($request) {

            if ($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if (is_array($date)) {
                    $from = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('created_at', [$from, $to]);
                }
            }

            if ($request->parcel_status) {
                $query->where('status', $request->parcel_status);
            }
            if ($request->parcel_customer) {
                $query->where('customer_name', 'like', '%'.$request->parcel_customer.'%');
            }
            if ($request->parcel_customer_phone) {
                $query->where('customer_phone', 'like', '%'.$request->parcel_customer_phone.'%');
            }

        })->get();

    }

    public function parcelExport($request)
    {
        try {
            if ($request->parcel_date !== '' || $request->parcel_status !== '' || $request->parcel_customer !== '' || $request->parcel_customer_phone !== '') {
                $parcels = $this->filterExport(Auth::user()->merchant->id, $request);
            } else {
                $parcels = Parcel::where(['merchant_id' => Auth::user()->merchant->id])->get();
            }

            return $parcels;
        } catch (\Throwable $th) {

            return collect([]);
        }
    }

    public function statusWiseParcelList($status)
    {
        return Parcel::where('merchant_id',Auth::user()->merchant->id)->where('status',$status)->paginate(10);
    }
}
