<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\Shipment;
use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MobileMoneyService
{
    protected string $provider;
    protected array $config;
    protected ?string $accessToken = null;

    public function __construct(string $provider = null)
    {
        $this->provider = $provider ?? config('mobile_money.default_provider');
        $this->config = config("mobile_money.providers.{$this->provider}", []);
    }

    /**
     * Check if provider is enabled and configured
     */
    public function isAvailable(): bool
    {
        return ($this->config['enabled'] ?? false) 
            && !empty($this->config['subscription_key'] ?? $this->config['client_id']);
    }

    /**
     * Get available providers for a country
     */
    public static function getProvidersForCountry(string $countryCode): array
    {
        $providers = [];
        foreach (config('mobile_money.providers', []) as $key => $config) {
            if ($config['enabled'] && in_array($countryCode, $config['countries'] ?? [])) {
                $providers[$key] = $config['name'];
            }
        }
        return $providers;
    }

    /**
     * Request payment from customer (Collection)
     */
    public function requestPayment(
        string $phoneNumber,
        float $amount,
        string $currency,
        string $reference,
        string $description = null
    ): array {
        $externalId = $this->generateTransactionId();

        try {
            if ($this->provider === 'mtn_momo') {
                return $this->mtnRequestToPay($phoneNumber, $amount, $currency, $externalId, $description);
            } elseif ($this->provider === 'orange_money') {
                return $this->orangeRequestPayment($phoneNumber, $amount, $currency, $externalId, $description);
            }

            return [
                'success' => false,
                'error' => 'Unsupported provider',
            ];
        } catch (\Exception $e) {
            Log::error("Mobile Money payment request failed", [
                'provider' => $this->provider,
                'phone' => $phoneNumber,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * MTN MoMo Request to Pay
     */
    protected function mtnRequestToPay(
        string $phone,
        float $amount,
        string $currency,
        string $externalId,
        ?string $description
    ): array {
        $referenceId = Str::uuid()->toString();

        // Get access token
        $token = $this->getMtnAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Failed to authenticate with MTN'];
        }

        $baseUrl = $this->config['base_url'];
        $product = $this->config['collection_product'];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Reference-Id' => $referenceId,
            'X-Target-Environment' => $this->config['sandbox'] ? 'sandbox' : 'production',
            'Ocp-Apim-Subscription-Key' => $this->config['subscription_key'],
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/{$product}/v1_0/requesttopay", [
            'amount' => (string) $amount,
            'currency' => $currency,
            'externalId' => $externalId,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $this->formatPhoneNumber($phone),
            ],
            'payerMessage' => $description ?? 'Payment for shipment',
            'payeeNote' => 'Baraka Courier payment',
        ]);

        if ($response->status() === 202) {
            return [
                'success' => true,
                'status' => 'pending',
                'transaction_id' => $referenceId,
                'external_id' => $externalId,
                'provider' => 'mtn_momo',
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Payment request failed',
            'status_code' => $response->status(),
        ];
    }

    /**
     * Check MTN MoMo transaction status
     */
    public function checkMtnTransactionStatus(string $referenceId): array
    {
        $token = $this->getMtnAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Auth failed'];
        }

        $baseUrl = $this->config['base_url'];
        $product = $this->config['collection_product'];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Target-Environment' => $this->config['sandbox'] ? 'sandbox' : 'production',
            'Ocp-Apim-Subscription-Key' => $this->config['subscription_key'],
        ])->get("{$baseUrl}/{$product}/v1_0/requesttopay/{$referenceId}");

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'status' => strtolower($data['status'] ?? 'unknown'),
                'amount' => $data['amount'] ?? null,
                'currency' => $data['currency'] ?? null,
                'payer' => $data['payer']['partyId'] ?? null,
                'reason' => $data['reason'] ?? null,
            ];
        }

        return [
            'success' => false,
            'error' => 'Failed to check status',
        ];
    }

    /**
     * Orange Money payment request
     */
    protected function orangeRequestPayment(
        string $phone,
        float $amount,
        string $currency,
        string $externalId,
        ?string $description
    ): array {
        $token = $this->getOrangeAccessToken();
        if (!$token) {
            return ['success' => false, 'error' => 'Failed to authenticate with Orange'];
        }

        $baseUrl = $this->config['base_url'];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ])->post("{$baseUrl}/orange-money-webpay/dev/v1/webpayment", [
            'merchant_key' => $this->config['merchant_key'],
            'currency' => $currency,
            'order_id' => $externalId,
            'amount' => $amount,
            'return_url' => $this->config['callback_url'],
            'cancel_url' => $this->config['callback_url'] . '?status=cancelled',
            'notif_url' => $this->config['callback_url'],
            'lang' => 'fr',
            'reference' => $description ?? 'Baraka payment',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'status' => 'pending',
                'transaction_id' => $data['pay_token'] ?? $externalId,
                'payment_url' => $data['payment_url'] ?? null,
                'external_id' => $externalId,
                'provider' => 'orange_money',
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('message') ?? 'Payment request failed',
        ];
    }

    /**
     * Get MTN access token
     */
    protected function getMtnAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $baseUrl = $this->config['base_url'];
        $product = $this->config['collection_product'];

        $response = Http::withBasicAuth(
            $this->config['api_user'],
            $this->config['api_key']
        )->withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->config['subscription_key'],
        ])->post("{$baseUrl}/{$product}/token/");

        if ($response->successful()) {
            $this->accessToken = $response->json('access_token');
            return $this->accessToken;
        }

        Log::error('MTN MoMo token request failed', [
            'status' => $response->status(),
            'response' => $response->body(),
        ]);

        return null;
    }

    /**
     * Get Orange access token
     */
    protected function getOrangeAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::asForm()
            ->withBasicAuth($this->config['client_id'], $this->config['client_secret'])
            ->post($this->config['base_url'] . '/oauth/v3/token', [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->successful()) {
            $this->accessToken = $response->json('access_token');
            return $this->accessToken;
        }

        return null;
    }

    /**
     * Format phone number to international format
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Add country code if not present
        if (strlen($phone) === 9) {
            // Assume DRC if 9 digits
            $phone = '243' . $phone;
        } elseif (strlen($phone) === 10 && str_starts_with($phone, '0')) {
            $phone = '243' . substr($phone, 1);
        }

        return $phone;
    }

    /**
     * Generate unique transaction ID
     */
    protected function generateTransactionId(): string
    {
        return 'BRK-' . strtoupper(Str::random(8)) . '-' . time();
    }

    /**
     * Record payment transaction
     */
    public function recordTransaction(
        string $type,
        float $amount,
        string $currency,
        string $reference,
        string $status,
        ?int $shipmentId = null,
        ?int $branchId = null,
        array $metadata = []
    ): FinancialTransaction {
        return FinancialTransaction::create([
            'transaction_id' => $reference,
            'type' => $type,
            'transactable_type' => $shipmentId ? Shipment::class : null,
            'transactable_id' => $shipmentId,
            'branch_id' => $branchId,
            'user_id' => auth()->id(),
            'amount' => $amount,
            'currency' => $currency,
            'direction' => $type === 'collection' ? 'credit' : 'debit',
            'payment_method' => $this->provider,
            'payment_reference' => $reference,
            'status' => $status,
            'description' => "Mobile money {$type} via {$this->config['name']}",
            'metadata' => $metadata,
        ]);
    }
}
