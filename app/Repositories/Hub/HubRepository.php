<?php

namespace App\Repositories\Hub;

use App\Models\Backend\Hub;
use App\Models\Backend\Parcel;
use Carbon\Carbon;

class HubRepository implements HubInterface
{
    public function all()
    {
        return Hub::orderByDesc('id')->paginate(10);
    }

    public function filter($request)
    {
        return Hub::where(function ($query) use ($request) {

            if ($request->name) {
                $query->where('name', 'like', '%'.$request->name.'%');
            }
            if ($request->phone) {
                $query->where('phone', 'like', '%'.$request->phone.'%');
            }

            if (request()->date) {
                $date = explode('To', request()->date);
                if (is_array($date)) {
                    $from = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('created_at', [$from, $to]);
                }
            }

        })->orderByDesc('id')->paginate(10);
    }

    public function hubs()
    {
        return Hub::all();
    }

    public function get($id)
    {
        return Hub::find($id);
    }

    public function store($request)
    {
        try {
            $hub = new Hub;
            $hub->name = $request->name;
            $hub->phone = $request->phone;
            $hub->address = $request->address;
            $hub->hub_lat = $request->lat;
            $hub->hub_long = $request->long;
            $hub->status = $request->status;
            $hub->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update($id, $request)
    {
        try {
            $hub = Hub::find($id);
            $hub->name = $request->name;
            $hub->phone = $request->phone;
            $hub->address = $request->address;
            $hub->hub_lat = $request->lat;
            $hub->hub_long = $request->long;
            $hub->status = $request->status;
            $hub->save();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $hub = Hub::findOrFail($id);

            // Preflight dependency checks to avoid FK violations
            $deps = [];
            if (\App\Models\User::where('hub_id', $id)->exists()) {
                $deps[] = 'users';
            }
            if (class_exists('App\\Models\\AwbStock') && \App\Models\AwbStock::where('hub_id', $id)->exists()) {
                $deps[] = 'awb_stocks';
            }
            if (class_exists('App\\Models\\Manifest')) {
                if (\App\Models\Manifest::where('origin_branch_id', $id)->exists()) {
                    $deps[] = 'manifests(origin)';
                }
                if (\App\Models\Manifest::where('destination_branch_id', $id)->exists()) {
                    $deps[] = 'manifests(destination)';
                }
            }
            if (class_exists('App\\Models\\Shipment')) {
                if (\App\Models\Shipment::where('origin_branch_id', $id)->exists()) {
                    $deps[] = 'shipments(origin)';
                }
                if (\App\Models\Shipment::where('dest_branch_id', $id)->exists()) {
                    $deps[] = 'shipments(destination)';
                }
            }

            if (! empty($deps)) {
                throw new \RuntimeException('Cannot delete hub: it is referenced by '.implode(', ', $deps).'. Please reassign or remove dependencies first.');
            }

            $hub->delete();

            return true;
        } catch (\Throwable $e) {
            // Let caller handle messaging; return false to indicate failure
            throw $e;
        }
    }

    public function parcelFilter($request, $id)
    {
        $hub_id = $id;

        return Parcel::where(['hub_id' => $hub_id])->orderByDesc('id')->where(function ($query) use ($request) {
            if ($request->parcel_date) {
                $date = explode('To', $request->parcel_date);
                if (is_array($date)) {
                    $from = Carbon::parse(trim($date[0]))->startOfDay()->toDateTimeString();
                    $to = Carbon::parse(trim($date[1]))->endOfDay()->toDateTimeString();
                    $query->whereBetween('updated_at', [$from, $to]);
                }
            }
        });
    }
}
