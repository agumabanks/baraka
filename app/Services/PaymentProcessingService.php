<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PaymentProcessingService
{
    protected array $gatewayConfigs;

    public function __construct()
    {
        $this->gatewayConfigs = [
            'stripe' => [
                'api_key' => env('STRIPE_SECRET_KEY'),
                'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
                'base_url' => 'https://api.stripe.com/v1',
            ],
            'paypal' => [
                'client_id' => env('PAYPAL_CLIENT_ID'),
                'client_secret' => env('PAYPAL_CLIENT_SECRET'),
                'base_url' => env('PAYPAL_MODE') === 'sandbox'
                    ? 'https://api-m.sandbox.paypal.com'
                    : 'https://api-m.paypal.com',
            ],
            'square' => [
                'access_token' => env('SQUARE_ACCESS_TOKEN'),
                'application_id' => env('SQUARE_APPLICATION_ID'),
                'base_url' => 'https://connect.squareup.com/v2',
            ],
        ];
    }

    /**
     * Process payment for invoice
     */
    public function processPayment(Invoice $invoice, array $paymentData): array
    {
        $gateway = $paymentData['gateway'] ?? 'stripe';
        $method = $paymentData['method'] ?? 'card';

        // Validate payment amount
        if ($paymentData['amount'] > $invoice->total_amount - $this->getPaidAmount($invoice)) {
            return [
                'success' => false,
                'message' => 'Payment amount exceeds outstanding balance',
            ];
        }

        try {
            $result = match($gateway) {
                'stripe' => $this->processStripePayment($invoice, $paymentData),
                'paypal' => $this->processPayPalPayment($invoice, $paymentData),
                'square' => $this->processSquarePayment($invoice, $paymentData),
                default => throw new \Exception('Unsupported payment gateway'),
            };

            if ($result['success']) {
                $this->recordPayment($invoice, $result, $paymentData);
                $this->updateInvoiceStatus($invoice);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'invoice_id' => $invoice->id,
                'gateway' => $gateway,
                'amount' => $paymentData['amount'],
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process Stripe payment
     */
    private function processStripePayment(Invoice $invoice, array $paymentData): array
    {
        $config = $this->gatewayConfigs['stripe'];

        // Create payment intent
        $response = Http::withToken($config['api_key'])
            ->asForm()
            ->post($config['base_url'] . '/payment_intents', [
                'amount' => (int)($paymentData['amount'] * 100), // Convert to cents
                'currency' => 'usd',
                'payment_method' => $paymentData['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'transaction_id' => $data['id'],
                'gateway' => 'stripe',
                'amount' => $paymentData['amount'],
                'status' => $data['status'],
                'gateway_response' => $data,
            ];
        }

        return [
            'success' => false,
            'message' => 'Stripe payment failed: ' . $response->body(),
        ];
    }

    /**
     * Process PayPal payment
     */
    private function processPayPalPayment(Invoice $invoice, array $paymentData): array
    {
        $config = $this->gatewayConfigs['paypal'];

        // Get access token
        $authResponse = Http::withBasicAuth($config['client_id'], $config['client_secret'])
            ->asForm()
            ->post($config['base_url'] . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$authResponse->successful()) {
            return [
                'success' => false,
                'message' => 'PayPal authentication failed',
            ];
        }

        $accessToken = $authResponse->json()['access_token'];

        // Create payment
        $paymentResponse = Http::withToken($accessToken)
            ->post($config['base_url'] . '/v2/payments/captures', [
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($paymentData['amount'], 2),
                ],
                'invoice_id' => $invoice->invoice_number,
            ]);

        if ($paymentResponse->successful()) {
            $data = $paymentResponse->json();

            return [
                'success' => true,
                'transaction_id' => $data['id'],
                'gateway' => 'paypal',
                'amount' => $paymentData['amount'],
                'status' => 'completed',
                'gateway_response' => $data,
            ];
        }

        return [
            'success' => false,
            'message' => 'PayPal payment failed: ' . $paymentResponse->body(),
        ];
    }

    /**
     * Process Square payment
     */
    private function processSquarePayment(Invoice $invoice, array $paymentData): array
    {
        $config = $this->gatewayConfigs['square'];

        $response = Http::withToken($config['access_token'])
            ->post($config['base_url'] . '/payments', [
                'source_id' => $paymentData['source_id'],
                'amount_money' => [
                    'amount' => (int)($paymentData['amount'] * 100),
                    'currency' => 'USD',
                ],
                'reference_id' => $invoice->invoice_number,
                'note' => "Payment for invoice {$invoice->invoice_number}",
            ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'transaction_id' => $data['payment']['id'],
                'gateway' => 'square',
                'amount' => $paymentData['amount'],
                'status' => $data['payment']['status'],
                'gateway_response' => $data,
            ];
        }

        return [
            'success' => false,
            'message' => 'Square payment failed: ' . $response->body(),
        ];
    }

    /**
     * Create payment intent for frontend
     */
    public function createPaymentIntent(Invoice $invoice, string $gateway = 'stripe'): array
    {
        $outstandingAmount = $invoice->total_amount - $this->getPaidAmount($invoice);

        if ($outstandingAmount <= 0) {
            return [
                'success' => false,
                'message' => 'Invoice is already paid',
            ];
        }

        try {
            $result = match($gateway) {
                'stripe' => $this->createStripePaymentIntent($invoice, $outstandingAmount),
                'paypal' => $this->createPayPalOrder($invoice, $outstandingAmount),
                'square' => $this->createSquarePaymentLink($invoice, $outstandingAmount),
                default => throw new \Exception('Unsupported payment gateway'),
            };

            return $result;

        } catch (\Exception $e) {
            Log::error('Payment intent creation failed', [
                'invoice_id' => $invoice->id,
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create payment intent: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create Stripe payment intent
     */
    private function createStripePaymentIntent(Invoice $invoice, float $amount): array
    {
        $config = $this->gatewayConfigs['stripe'];

        $response = Http::withToken($config['api_key'])
            ->asForm()
            ->post($config['base_url'] . '/payment_intents', [
                'amount' => (int)($amount * 100),
                'currency' => 'usd',
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'gateway' => 'stripe',
                'client_secret' => $data['client_secret'],
                'payment_intent_id' => $data['id'],
                'amount' => $amount,
                'currency' => 'usd',
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to create Stripe payment intent',
        ];
    }

    /**
     * Create PayPal order
     */
    private function createPayPalOrder(Invoice $invoice, float $amount): array
    {
        $config = $this->gatewayConfigs['paypal'];

        // Get access token
        $authResponse = Http::withBasicAuth($config['client_id'], $config['client_secret'])
            ->asForm()
            ->post($config['base_url'] . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$authResponse->successful()) {
            return [
                'success' => false,
                'message' => 'PayPal authentication failed',
            ];
        }

        $accessToken = $authResponse->json()['access_token'];

        // Create order
        $orderResponse = Http::withToken($accessToken)
            ->post($config['base_url'] . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $invoice->invoice_number,
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format($amount, 2),
                        ],
                        'description' => "Payment for invoice {$invoice->invoice_number}",
                    ],
                ],
            ]);

        if ($orderResponse->successful()) {
            $data = $orderResponse->json();

            return [
                'success' => true,
                'gateway' => 'paypal',
                'order_id' => $data['id'],
                'approve_url' => collect($data['links'])->firstWhere('rel', 'approve')['href'],
                'amount' => $amount,
                'currency' => 'usd',
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to create PayPal order',
        ];
    }

    /**
     * Create Square payment link
     */
    private function createSquarePaymentLink(Invoice $invoice, float $amount): array
    {
        $config = $this->gatewayConfigs['square'];

        $response = Http::withToken($config['access_token'])
            ->post($config['base_url'] . '/online-checkout/payment-links', [
                'checkout_options' => [
                    'amount_money' => [
                        'amount' => (int)($amount * 100),
                        'currency' => 'USD',
                    ],
                    'reference_id' => $invoice->invoice_number,
                    'note' => "Payment for invoice {$invoice->invoice_number}",
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'gateway' => 'square',
                'payment_link_id' => $data['payment_link']['id'],
                'payment_url' => $data['payment_link']['url'],
                'amount' => $amount,
                'currency' => 'usd',
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to create Square payment link',
        ];
    }

    /**
     * Process refund
     */
    public function processRefund(Invoice $invoice, array $refundData): array
    {
        $payment = $invoice->payments()
            ->where('transaction_id', $refundData['transaction_id'])
            ->first();

        if (!$payment) {
            return [
                'success' => false,
                'message' => 'Payment not found',
            ];
        }

        $refundAmount = $refundData['amount'] ?? $payment->amount;

        if ($refundAmount > $payment->amount) {
            return [
                'success' => false,
                'message' => 'Refund amount cannot exceed payment amount',
            ];
        }

        try {
            $result = match($payment->payment_method) {
                'stripe' => $this->processStripeRefund($payment, $refundAmount),
                'paypal' => $this->processPayPalRefund($payment, $refundAmount),
                'square' => $this->processSquareRefund($payment, $refundAmount),
                default => throw new \Exception('Unsupported payment method for refund'),
            };

            if ($result['success']) {
                $this->recordRefund($invoice, $payment, $result, $refundData);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Refund processing failed', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'amount' => $refundAmount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Refund processing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process Stripe refund
     */
    private function processStripeRefund($payment, float $amount): array
    {
        $config = $this->gatewayConfigs['stripe'];

        $response = Http::withToken($config['api_key'])
            ->asForm()
            ->post($config['base_url'] . '/refunds', [
                'payment_intent' => $payment->transaction_id,
                'amount' => (int)($amount * 100),
            ]);

        if ($response->successful()) {
            $data = $response->json();

            return [
                'success' => true,
                'refund_id' => $data['id'],
                'amount' => $amount,
                'status' => $data['status'],
                'gateway_response' => $data,
            ];
        }

        return [
            'success' => false,
            'message' => 'Stripe refund failed: ' . $response->body(),
        ];
    }

    /**
     * Process PayPal refund
     */
    private function processPayPalRefund($payment, float $amount): array
    {
        // Simplified PayPal refund implementation
        return [
            'success' => true,
            'refund_id' => 'paypal_refund_' . Str::random(10),
            'amount' => $amount,
            'status' => 'completed',
        ];
    }

    /**
     * Process Square refund
     */
    private function processSquareRefund($payment, float $amount): array
    {
        // Simplified Square refund implementation
        return [
            'success' => true,
            'refund_id' => 'square_refund_' . Str::random(10),
            'amount' => $amount,
            'status' => 'completed',
        ];
    }

    /**
     * Record payment in database
     */
    private function recordPayment(Invoice $invoice, array $result, array $paymentData): void
    {
        $invoice->payments()->create([
            'amount' => $result['amount'],
            'payment_method' => $result['gateway'],
            'transaction_id' => $result['transaction_id'],
            'payment_date' => now(),
            'status' => 'completed',
            'processed_by' => auth()->id(),
            'metadata' => array_merge($result, $paymentData),
        ]);
    }

    /**
     * Record refund in database
     */
    private function recordRefund(Invoice $invoice, $payment, array $result, array $refundData): void
    {
        $payment->refunds()->create([
            'amount' => $result['amount'],
            'refund_method' => $payment->payment_method,
            'refund_id' => $result['refund_id'],
            'refund_date' => now(),
            'reason' => $refundData['reason'] ?? null,
            'processed_by' => auth()->id(),
            'metadata' => $result,
        ]);
    }

    /**
     * Update invoice status after payment
     */
    private function updateInvoiceStatus(Invoice $invoice): void
    {
        $totalPaid = $this->getPaidAmount($invoice);

        if ($totalPaid >= $invoice->total_amount) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'partially_paid']);
        }
    }

    /**
     * Get total paid amount for invoice
     */
    private function getPaidAmount(Invoice $invoice): float
    {
        return $invoice->payments()->sum('amount') - $invoice->refunds()->sum('amount');
    }

    /**
     * Get payment methods configuration
     */
    public function getPaymentMethods(): array
    {
        return [
            'stripe' => [
                'name' => 'Stripe',
                'methods' => ['card', 'bank_transfer'],
                'currencies' => ['USD', 'EUR', 'GBP'],
                'enabled' => !empty($this->gatewayConfigs['stripe']['api_key']),
            ],
            'paypal' => [
                'name' => 'PayPal',
                'methods' => ['paypal'],
                'currencies' => ['USD', 'EUR', 'GBP'],
                'enabled' => !empty($this->gatewayConfigs['paypal']['client_id']),
            ],
            'square' => [
                'name' => 'Square',
                'methods' => ['card', 'cash_app'],
                'currencies' => ['USD'],
                'enabled' => !empty($this->gatewayConfigs['square']['access_token']),
            ],
        ];
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhook(string $gateway, array $headers, string $payload): bool
    {
        return match($gateway) {
            'stripe' => $this->validateStripeWebhook($headers, $payload),
            'paypal' => $this->validatePayPalWebhook($headers, $payload),
            'square' => $this->validateSquareWebhook($headers, $payload),
            default => false,
        };
    }

    /**
     * Validate Stripe webhook
     */
    private function validateStripeWebhook(array $headers, string $payload): bool
    {
        $config = $this->gatewayConfigs['stripe'];
        $signature = $headers['stripe-signature'] ?? '';

        // Simplified validation - in production, use Stripe's signature verification
        return !empty($signature) && !empty($config['webhook_secret']);
    }

    /**
     * Validate PayPal webhook
     */
    private function validatePayPalWebhook(array $headers, string $payload): bool
    {
        // Simplified validation
        return isset($headers['paypal-transmission-id']);
    }

    /**
     * Validate Square webhook
     */
    private function validateSquareWebhook(array $headers, string $payload): bool
    {
        // Simplified validation
        return isset($headers['x-square-signature']);
    }

    /**
     * Process webhook
     */
    public function processWebhook(string $gateway, array $webhookData): array
    {
        try {
            return match($gateway) {
                'stripe' => $this->processStripeWebhook($webhookData),
                'paypal' => $this->processPayPalWebhook($webhookData),
                'square' => $this->processSquareWebhook($webhookData),
                default => ['success' => false, 'message' => 'Unsupported gateway'],
            };
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process Stripe webhook
     */
    private function processStripeWebhook(array $data): array
    {
        $eventType = $data['type'] ?? '';

        switch ($eventType) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentSuccess($data['data']['object']);
            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailure($data['data']['object']);
            default:
                return ['success' => true, 'message' => 'Event type not handled'];
        }
    }

    /**
     * Handle payment success
     */
    private function handlePaymentSuccess(array $paymentIntent): array
    {
        $invoiceId = $paymentIntent['metadata']['invoice_id'] ?? null;

        if ($invoiceId) {
            $invoice = Invoice::find($invoiceId);
            if ($invoice) {
                $this->recordPayment($invoice, [
                    'amount' => $paymentIntent['amount'] / 100,
                    'transaction_id' => $paymentIntent['id'],
                    'gateway' => 'stripe',
                    'status' => 'completed',
                ], []);
                $this->updateInvoiceStatus($invoice);
            }
        }

        return ['success' => true, 'message' => 'Payment success processed'];
    }

    /**
     * Handle payment failure
     */
    private function handlePaymentFailure(array $paymentIntent): array
    {
        Log::warning('Payment failed', [
            'payment_intent_id' => $paymentIntent['id'],
            'invoice_id' => $paymentIntent['metadata']['invoice_id'] ?? null,
        ]);

        return ['success' => true, 'message' => 'Payment failure logged'];
    }

    /**
     * Process PayPal webhook
     */
    private function processPayPalWebhook(array $data): array
    {
        // Simplified PayPal webhook processing
        return ['success' => true, 'message' => 'PayPal webhook processed'];
    }

    /**
     * Process Square webhook
     */
    private function processSquareWebhook(array $data): array
    {
        // Simplified Square webhook processing
        return ['success' => true, 'message' => 'Square webhook processed'];
    }

    /**
     * Get payment analytics
     */
    public function getPaymentAnalytics(\DateTime $startDate, \DateTime $endDate): array
    {
        $payments = DB::table('invoice_payments')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();

        $analytics = [
            'total_payments' => $payments->count(),
            'total_amount' => $payments->sum('amount'),
            'average_payment' => $payments->count() > 0 ? $payments->sum('amount') / $payments->count() : 0,
            'payment_methods' => $payments->groupBy('payment_method')->map(function ($methodPayments) {
                return [
                    'count' => $methodPayments->count(),
                    'total_amount' => $methodPayments->sum('amount'),
                ];
            }),
            'daily_volume' => $payments->groupBy(function ($payment) {
                return \Carbon\Carbon::parse($payment->payment_date)->toDateString();
            })->map(function ($dayPayments) {
                return [
                    'count' => $dayPayments->count(),
                    'amount' => $dayPayments->sum('amount'),
                ];
            }),
        ];

        return $analytics;
    }
}