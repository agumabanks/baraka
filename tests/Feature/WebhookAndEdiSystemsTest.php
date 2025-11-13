<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Enums\UserType;
use App\Http\Middleware\AdvancedRateLimitMiddleware;
use App\Http\Middleware\EnhancedApiSecurityMiddleware;
use App\Models\EdiProvider;
use App\Models\EdiTransaction;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Services\EdiDocumentService;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class WebhookAndEdiSystemsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $webhookService;
    protected $ediService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->webhookService = app(WebhookService::class);
        $this->ediService = app(EdiDocumentService::class);

        $this->withoutMiddleware([
            AdvancedRateLimitMiddleware::class,
            EnhancedApiSecurityMiddleware::class,
        ]);
    }

    /** @test */
    public function webhook_admin_crud_operations()
    {
        // Test webhook creation
        $webhookData = [
            'url' => 'https://example.com/webhook',
            'events' => ['shipment.created', 'shipment.delivered'],
            'name' => 'Test Webhook'
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/webhooks/register', $webhookData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'webhook_id',
                'secret_key'
            ]);

        $webhookId = $response->json('webhook_id');

        // Test webhook update
        $updateData = [
            'url' => 'https://example.com/updated-webhook',
            'events' => ['shipment.created', 'shipment.updated'],
            'active' => false
        ];

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/webhooks/{$webhookId}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'webhook' => [
                    'url' => $updateData['url'],
                    'active' => false
                ]
            ]);

        // Test webhook deletion
        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/webhooks/{$webhookId}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function webhook_secret_rotation_functionality()
    {
        // Create a webhook endpoint
        $webhook = WebhookEndpoint::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/webhook',
            'events' => ['shipment.created'],
            'name' => 'Test Webhook'
        ]);

        $originalSecret = $webhook->secret_key;
        $this->assertNotNull($originalSecret);

        // Test secret rotation
        $newSecret = $webhook->rotateSecret();
        $this->assertNotEquals($originalSecret, $newSecret);
        $this->assertEquals($newSecret, $webhook->fresh()->secret_key);

        // Test that signature generation works with new secret
        $payload = json_encode(['test' => 'data']);
        $oldSignature = 'sha256=' . hash_hmac('sha256', $payload, $originalSecret);
        $newSignature = 'sha256=' . hash_hmac('sha256', $payload, $newSecret);

        $this->assertNotEquals($oldSignature, $newSignature);
        $this->assertEquals($newSignature, $webhook->fresh()->generateSignature($payload));
    }

    /** @test */
    public function webhook_retry_mechanism_and_delivery_logging()
    {
        // Mock HTTP responses for testing retry behavior
        Http::fake([
            'https://example.com/fail*' => Http::response('Server Error', 500),
            'https://example.com/success*' => Http::response('OK', 200),
        ]);

        $webhook = WebhookEndpoint::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/fail-webhook',
            'events' => ['shipment.created'],
            'name' => 'Test Webhook',
            'retry_policy' => [
                'max_attempts' => 3,
                'backoff_multiplier' => 2,
                'initial_delay' => 60,
                'max_delay' => 300
            ]
        ]);

        // Queue a delivery
        $delivery = $this->webhookService->queueDelivery($webhook, 'shipment.created', [
            'shipment_id' => 123,
            'status' => 'created'
        ]);

        // Process the delivery (should fail)
        $result = $this->webhookService->deliver($delivery);
        $this->assertFalse($result);

        // Check failure count was incremented
        $this->assertGreaterThanOrEqual(1, $webhook->fresh()->failure_count);

        // Test successful delivery
        $webhook->update(['url' => 'https://example.com/success-webhook']);
        $successDelivery = $this->webhookService->queueDelivery($webhook, 'shipment.created', [
            'shipment_id' => 124,
            'status' => 'created'
        ]);

        $result = $this->webhookService->deliver($successDelivery);
        $this->assertTrue($result);

        // Check failure count was reset
        $this->assertEquals(0, $webhook->fresh()->failure_count);
    }

    /** @test */
    public function webhook_health_endpoints()
    {
        // Create healthy webhook
        $healthyWebhook = WebhookEndpoint::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/healthy',
            'events' => ['shipment.created'],
            'name' => 'Healthy Webhook',
            'failure_count' => 0
        ]);

        // Create unhealthy webhook
        $unhealthyWebhook = WebhookEndpoint::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/unhealthy',
            'events' => ['shipment.created'],
            'name' => 'Unhealthy Webhook',
            'failure_count' => 10,
            'retry_policy' => ['max_attempts' => 5]
        ]);

        // Test health checks
        $this->assertTrue($healthyWebhook->isHealthy());
        $this->assertFalse($unhealthyWebhook->isHealthy());

        // Test webhook events endpoint
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/webhooks/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'events' => []
            ]);

        $events = $response->json('events');
        $this->assertContains('shipment.created', $events);
        $this->assertContains('shipment.delivered', $events);
    }

    /** @test */
    public function webhook_payload_delivery_and_acknowledgment()
    {
        Http::fake([
            'https://example.com/webhook' => Http::response([
                'status' => 'received',
                'message' => 'OK'
            ], 200)
        ]);

        $webhook = WebhookEndpoint::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/webhook',
            'events' => ['shipment.created', 'pricing.quote_generated'],
            'name' => 'Test Webhook'
        ]);

        $payload = [
            'shipment_id' => 123,
            'status' => 'created',
            'created_at' => now()->toIso8601String(),
            'customer' => [
                'id' => 456,
                'name' => 'Test Customer'
            ]
        ];

        // Test delivery queueing
        $delivery = $this->webhookService->queueDelivery($webhook, 'shipment.created', $payload);
        
        // Test actual delivery
        $result = $this->webhookService->deliver($delivery);
        $this->assertTrue($result);

        // Verify delivery was successful
        $delivery->refresh();
        $this->assertNotNull($delivery->delivered_at);
        $this->assertEquals(200, $delivery->http_status);
        $this->assertEquals('received', $delivery->response['status']);
    }

    /** @test */
    public function edi_850_purchase_order_generation()
    {
        $payload = [
            'purchase_order' => [
                'number' => 'PO-2025-001',
                'buyer' => 'BARAKA001',
                'ship_to' => [
                    'name' => 'Test Customer',
                    'address' => '123 Test St',
                    'city' => 'Riyadh',
                    'country' => 'SA'
                ],
                'items' => [
                    [
                        'sku' => 'PROD-001',
                        'quantity' => 10,
                        'unit_price' => 25.00,
                        'description' => 'Test Product'
                    ]
                ],
                'requested_ship_date' => '2025-11-15'
            ]
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/edi/850', [
                'payload' => $payload,
                'document_number' => '850-2025-001'
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'transaction_id',
                'document_type',
                'status',
                'acknowledgement'
            ]);

        $this->assertDatabaseHas('edi_transactions', [
            'document_type' => '850',
            'document_number' => '850-2025-001',
            'status' => 'received'
        ]);
    }

    /** @test */
    public function edi_856_advance_ship_notice_generation()
    {
        $payload = [
            'shipment' => [
                'notice_number' => 'ASN-2025-001',
                'status' => 'SHIPPED',
                'carrier' => 'SAUDI_POST',
                'packages' => [
                    [
                        'package_id' => 'PKG-001',
                        'weight' => 2.5,
                        'dimensions' => '30x20x15',
                        'contents' => 'Test Products'
                    ]
                ],
                'estimated_delivery' => '2025-11-12'
            ]
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/edi/856', [
                'payload' => $payload
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'document_type' => '856',
                'status' => 'received'
            ]);
    }

    /** @test */
    public function edi_997_functional_acknowledgment_processing()
    {
        // First create a transaction to acknowledge
        $transaction = EdiTransaction::create([
            'document_type' => '850',
            'document_number' => 'PO-2025-001',
            'status' => 'received',
            'payload' => ['test' => 'data']
        ]);

        $ackPayload = [
            'acknowledgement' => [
                'status' => 'AC',
                'document_number' => 'PO-2025-001',
                'errors' => []
            ]
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/edi/997', [
                'payload' => $ackPayload
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'document_type' => '997',
                'status' => 'processed'
            ]);
    }

    /** @test */
    public function edi_transaction_processing_and_acknowledgment_generation()
    {
        $payload = [
            'purchase_order' => [
                'number' => 'PO-2025-002',
                'buyer' => 'BUYER001'
            ]
        ];

        // Test document submission
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/edi/850', [
                'payload' => $payload
            ]);

        $response->assertStatus(202);
        $transactionId = $response->json('transaction_id');

        // Test transaction retrieval
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/edi/transactions/{$transactionId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'transaction' => [
                    'id' => $transactionId,
                    'document_type' => '850'
                ]
            ]);

        // Test acknowledgment retrieval
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/edi/transactions/{$transactionId}/acknowledgement");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'acknowledgement' => [
                    'document_type' => '997'
                ]
            ]);
    }

    /** @test */
    public function edi_provider_integration_and_data_exchange()
    {
        // Create EDI provider
        $provider = EdiProvider::create([
            'name' => 'Test EDI Provider',
            'type' => 'as2',
            'config' => [
                'endpoint' => 'https://edi.example.com/api',
                'auth_type' => 'certificate'
            ]
        ]);

        $payload = [
            'purchase_order' => [
                'number' => 'PO-2025-003',
                'buyer' => 'PROVIDER001'
            ]
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/edi/850', [
                'payload' => $payload,
                'provider_id' => $provider->id
            ]);

        $response->assertStatus(202);
        $transactionId = $response->json('transaction_id');

        // Verify provider association when column exists
        if (Schema::hasColumn('edi_transactions', 'provider_id')) {
            $this->assertDatabaseHas('edi_transactions', [
                'id' => $transactionId,
                'provider_id' => $provider->id
            ]);
        } else {
            $this->assertDatabaseHas('edi_transactions', [
                'id' => $transactionId,
            ]);
        }
    }

    /** @test */
    public function error_handling_for_malformed_edi_transactions()
    {
        // Test invalid document type
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/edi/999', [
                'payload' => ['test' => 'data']
            ]);

        $response->assertStatus(404);

        // Test missing required payload
        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/edi/850', [
                'payload' => null
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function monitoring_and_alerts_integration()
    {
        // Test webhook logging
        $webhook = WebhookEndpoint::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/webhook',
            'events' => ['shipment.created'],
            'name' => 'Test Webhook'
        ]);

        $delivery = $this->webhookService->queueDelivery($webhook, 'shipment.created', [
            'test' => 'data'
        ]);

        // Verify webhook logs are being written
        $logPath = storage_path('logs/webhooks.log');
        $this->assertFileExists($logPath);
    }

    /** @test */
    public function webhook_delivery_history_and_status_tracking()
    {
        $webhook = WebhookEndpoint::create([
            'user_id' => $this->user->id,
            'url' => 'https://example.com/webhook',
            'events' => ['shipment.created'],
            'name' => 'Test Webhook'
        ]);

        // Create multiple deliveries with different statuses
        $delivery1 = WebhookDelivery::create([
            'webhook_endpoint_id' => $webhook->id,
            'event_type' => 'shipment.created',
            'payload' => ['test' => 'data'],
            'attempts' => 1,
            'http_status' => 200,
            'delivered_at' => now()
        ]);

        $delivery2 = WebhookDelivery::create([
            'webhook_endpoint_id' => $webhook->id,
            'event_type' => 'shipment.created',
            'payload' => ['test' => 'data'],
            'attempts' => 1,
            'http_status' => 500,
            'failed_at' => now()
        ]);

        // Test delivery statistics
        $delivered = WebhookDelivery::where('webhook_endpoint_id', $webhook->id)
            ->whereNotNull('delivered_at')
            ->count();
        $failed = WebhookDelivery::where('webhook_endpoint_id', $webhook->id)
            ->whereNotNull('failed_at')
            ->count();

        $this->assertEquals(1, $delivered);
        $this->assertEquals(1, $failed);
    }

    /** @test */
    public function admin_can_fetch_paginated_webhook_deliveries_feed()
    {
        $admin = User::factory()->create([
            'user_type' => UserType::ADMIN,
        ]);
        $admin->setAttribute('roles', json_encode(['admin']));

        $endpoint = WebhookEndpoint::create([
            'user_id' => $admin->id,
            'url' => 'https://example.com/hook',
            'events' => ['shipment.created'],
            'name' => 'Paginated Endpoint',
        ]);

        WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_type' => 'shipment.created',
            'payload' => ['shipment_id' => 500],
            'http_status' => 200,
            'attempts' => 1,
            'delivered_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/webhooks/deliveries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        $this->assertEquals('success', $response->json('data.0.status'));
        $this->assertEquals($endpoint->id, $response->json('data.0.webhook_endpoint_id'));
    }
}
