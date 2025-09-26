<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivacyController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();

        $payload = [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_e164,
                'address' => $user->address,
                'created_at' => $user->created_at,
            ],
            'shipments' => $user->shipments()->with(['originBranch:id,name','destBranch:id,name','parcels:id,shipment_id,weight_kg'])->latest()->limit(1000)->get(),
        ];

        $filename = 'privacy-export-user-'.$user->id.'-'.now()->format('Ymd_His').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }
}

