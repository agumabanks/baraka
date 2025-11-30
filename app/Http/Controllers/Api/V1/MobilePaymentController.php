<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Services\Payments\MobileMoneyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MobilePaymentController extends Controller
{
    /**
     * Initiate mobile money payment for invoice
     */
    public function initiatePayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'invoice_id' => 'required_without:shipment_id|exists:invoices,id',
            'shipment_id' => 'required_without:invoice_id|exists:shipments,id',
            'phone_number' => 'required|string|min:9|max:15',
            'provider' => 'required|in:mtn_momo,orange_money,airtel_money',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $provider = $data['provider'];
        $mobileMoneyService = new MobileMoneyService($provider);

        if (!$mobileMoneyService->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => "Payment provider {$provider} is not configured",
            ], 400);
        }

        // Determine amount and reference
        if (!empty($data['invoice_id'])) {
            $invoice = Invoice::findOrFail($data['invoice_id']);
            $amount = $data['amount'] ?? $invoice->balance_due;
            $currency = $invoice->currency ?? 'USD';
            $reference = "INV-{$invoice->id}";
            $description = "Payment for Invoice #{$invoice->invoice_number}";
        } else {
            $shipment = Shipment::findOrFail($data['shipment_id']);
            $amount = $data['amount'] ?? $shipment->cod_amount ?? $shipment->price_amount;
            $currency = $shipment->price_currency ?? 'USD';
            $reference = "SHP-{$shipment->id}";
            $description = "Payment for Shipment {$shipment->tracking_number}";
        }

        // Request payment
        $result = $mobileMoneyService->requestPayment(
            $data['phone_number'],
            $amount,
            $currency,
            $reference,
            $description
        );

        if ($result['success']) {
            // Record transaction
            $mobileMoneyService->recordTransaction(
                'collection',
                $amount,
                $currency,
                $result['transaction_id'],
                'pending',
                $data['shipment_id'] ?? null,
                auth()->user()?->branch_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment request sent to your phone',
                'transaction_id' => $result['transaction_id'],
                'status' => 'pending',
                'provider' => $provider,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error'] ?? 'Payment request failed',
        ], 400);
    }

    /**
     * Check payment status
     */
    public function checkStatus(Request $request, string $transactionId): JsonResponse
    {
        $data = $request->validate([
            'provider' => 'required|in:mtn_momo,orange_money,airtel_money',
        ]);

        $mobileMoneyService = new MobileMoneyService($data['provider']);

        if ($data['provider'] === 'mtn_momo') {
            $result = $mobileMoneyService->checkMtnTransactionStatus($transactionId);
        } else {
            // Other providers...
            $result = ['success' => false, 'error' => 'Status check not implemented for this provider'];
        }

        return response()->json($result);
    }

    /**
     * Handle payment callback/webhook
     */
    public function callback(Request $request, string $provider): JsonResponse
    {
        Log::info("Mobile money callback received", [
            'provider' => $provider,
            'payload' => $request->all(),
        ]);

        // Verify callback authenticity (provider-specific)
        // Update transaction status
        // Update invoice/shipment payment status

        $payload = $request->all();

        if ($provider === 'mtn_momo') {
            $referenceId = $payload['externalId'] ?? $payload['financialTransactionId'] ?? null;
            $status = strtolower($payload['status'] ?? 'unknown');
            
            if ($referenceId && $status === 'successful') {
                // Mark payment as complete
                // This would update the related invoice or shipment
            }
        }

        return response()->json(['received' => true]);
    }

    /**
     * Get available payment providers for country
     */
    public function getProviders(Request $request): JsonResponse
    {
        $countryCode = $request->get('country', 'CD'); // Default to DRC

        $providers = MobileMoneyService::getProvidersForCountry($countryCode);

        return response()->json([
            'success' => true,
            'country' => $countryCode,
            'providers' => $providers,
        ]);
    }
}
