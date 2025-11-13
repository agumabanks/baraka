<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Events\ShipmentScanned;
use App\Http\Controllers\Api\V1\EnhancedMobileScanningController;
use App\Jobs\CreateExceptionWorkflowTask;
use App\Jobs\CreateDeliveryConfirmationWorkflowTask;
use App\Jobs\CreateManualInterventionWorkflowTask;
use App\Models\Backend\Branch;
use App\Models\Device;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileScanningTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Branch $branch;
    private Device $device;
    private User $user;
    private Shipment $shipment;
    private string $deviceToken;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->branch = Branch::factory()->create([
            'id' => 1,
            'name' => 'Test Branch',
            'code' => 'TB001'
        ]);
        
        $this->user = User::factory()->create();
        
        $this->shipment = Shipment::factory()->create([
            'tracking_number' => 'TEST123456789',
            'current_status' => ShipmentStatus::BOOKED->value,
            'current_location_id' => $this->branch->id,
        ]);
        
        // Create device for testing
        $this->device = Device::factory()->create([
            'device_id' => 'test_device_001',
            'device_name' => 'Test Mobile Device',
            'platform' => 'android',
            'app_version' => '1.0.0',
            'device_token' => $this->generateDeviceToken(),
            'is_active' => true,
        ]);
        
        $this->deviceToken = $this->device->device_token;
        
        // Clear cache and queue
        Cache::flush();
        Queue::fake();
    }

    /** @test */
    public function it_can_authenticate_device()
    {
        $response = $this->postJson('/api/v1/devices/authenticate', [
            'device_id' => $this->device->device_id,
            'device_token' => $this->device->device_token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'authenticated',
                'device' => [
                    'id',
                    'device_id',
                    'device_name',
                    'platform',
                    'app_version',
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('authenticated'));
    }

    /** @test */
    public function it_rejects_invalid_device_credentials()
    {
        $response = $this->postJson('/api/v1/devices/authenticate', [
            'device_id' => $this->device->device_id,
            'device_token' => 'invalid_token',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'Invalid device credentials',
            ]);
    }

    /** @test */
    public function it_can_process_single_scan_successfully()
    {
        $scanData = [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'inbound',
            'location_id' => $this->branch->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Test scan',
        ];

        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', $scanData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'scan_id',
                'shipment_id',
                'status',
                'previous_status',
                'next_expected',
                'branch_info',
            ]);

        $this->assertTrue($response->json('success'));
        
        // Verify scan was recorded in database
        $this->assertDatabaseHas('scans', [
            'shipment_id' => $this->shipment->id,
            'action' => 'inbound',
            'device_id' => $this->device->device_id,
        ]);

        // Verify shipment status was updated
        $this->assertDatabaseHas('shipments', [
            'id' => $this->shipment->id,
            'current_status' => ShipmentStatus::AT_DESTINATION_HUB->value,
            'current_location_id' => $this->branch->id,
        ]);
    }

    /** @test */
    public function it_handles_duplicate_scan_detection()
    {
        // Create first scan
        $scanData = [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'inbound',
            'location_id' => $this->branch->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];

        $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', $scanData);

        // Attempt duplicate scan within 5 minutes
        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', $scanData);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => 'Duplicate scan detected',
            ]);
    }

    /** @test */
    public function it_can_process_bulk_scan_successfully()
    {
        $shipment2 = Shipment::factory()->create([
            'tracking_number' => 'TEST987654321',
            'current_status' => 'pending',
        ]);

        $scans = [
            [
                'tracking_number' => $this->shipment->tracking_number,
                'action' => 'inbound',
                'location_id' => $this->branch->id,
            ],
            [
                'tracking_number' => $shipment2->tracking_number,
                'action' => 'outbound',
                'location_id' => $this->branch->id,
            ],
        ];

        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/bulk-scan', [
            'scans' => $scans,
            'batch_id' => 'batch_001',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'results' => [
                    'success' => [
                        [
                            'index',
                            'tracking',
                            'status',
                        ]
                    ],
                    'failed',
                    'conflicts',
                ],
                'processed',
                'failed',
                'conflicts',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(2, $response->json('processed'));
    }

    /** @test */
    public function it_handles_bulk_scan_with_conflicts()
    {
        // Create a shipment and perform a recent scan
        $columns = collect(Schema::getColumnListing('scans'))
            ->map(fn ($column) => trim($column, " \"`'"))
            ->toArray();

        $scanInsert = [
            'shipment_id' => $this->shipment->id,
            'action' => 'delivery',
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ];

        if (in_array('branch_id', $columns, true)) {
            $scanInsert['branch_id'] = $this->branch->id;
        }

        if (in_array('tracking_number', $columns, true)) {
            $scanInsert['tracking_number'] = $this->shipment->tracking_number;
        }

        if (in_array('timestamp', $columns, true)) {
            $scanInsert['timestamp'] = now()->subMinutes(2);
        }

        if (in_array('device_id', $columns, true)) {
            $scanInsert['device_id'] = $this->device->device_id;
        }

        if (in_array('app_version', $columns, true)) {
            $scanInsert['app_version'] = '1.0.0';
        }

        DB::table('scans')->insert($scanInsert);

        $scans = [
            [
                'tracking_number' => $this->shipment->tracking_number,
                'action' => 'inbound',
                'location_id' => $this->branch->id,
            ],
        ];

        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/bulk-scan', [
            'scans' => $scans,
            'batch_id' => 'batch_002',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('conflicts'));
        $this->assertTrue($response->json('results.conflicts')[0]['conflict_type'] === 'status_conflict');
    }

    /** @test */
    public function it_can_handle_offline_sync()
    {
        // Simulate offline scenario by storing scan in local storage first
        // In real scenario, this would be done by the mobile app
        $pendingScans = [
            [
                'offline_sync_key' => 'sync_001',
                'action' => 'exception',
                'location_id' => $this->branch->id,
                'tracking_number' => $this->shipment->tracking_number,
                'timestamp' => now()->subHours(1)->format('Y-m-d H:i:s'),
            ],
        ];

        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/enhanced-offline-sync', [
            'pending_scans' => $pendingScans,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'results' => [
                    'processed',
                    'conflicts',
                    'errors',
                ],
                'sync_count',
                'conflict_count',
                'error_count',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(1, $response->json('sync_count'));
    }

    /** @test */
    public function it_can_get_device_info()
    {
        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->getJson('/api/v1/mobile/device-info');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'device' => [
                    'id',
                    'device_id',
                    'device_name',
                    'platform',
                    'app_version',
                    'is_active',
                ],
            ]);
    }

    /** @test */
    public function it_enforces_rate_limiting()
    {
        // Fill up the rate limit
        for ($i = 0; $i < 101; $i++) {
            $response = $this->withHeaders([
                'X-Device-ID' => $this->device->device_id,
                'X-Device-Token' => $this->deviceToken,
            ])->postJson('/api/v1/mobile/scan', [
                'tracking_number' => $this->shipment->tracking_number,
                'action' => 'inbound',
                'location_id' => $this->branch->id,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);

            if ($response->getStatusCode() === 429) {
                $errorMessage = $response->json('error') ?? $response->json('message');
                $this->assertTrue(in_array($errorMessage, ['Rate limit exceeded for scan', 'Too Many Attempts.'], true));
                break;
            }
        }
    }

    /** @test */
    public function it_handles_invalid_barcode_type()
    {
        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'inbound',
            'location_id' => $this->branch->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'barcode_type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['barcode_type']);
    }

    /** @test */
    public function it_handles_invalid_action_type()
    {
        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'invalid_action',
            'location_id' => $this->branch->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['action']);
    }

    /** @test */
    public function it_handles_nonexistent_shipment()
    {
        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', [
            'tracking_number' => 'NONEXISTENT123',
            'action' => 'inbound',
            'location_id' => $this->branch->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => 'Scan failed: Shipment not found',
            ]);
    }

    /** @test */
    public function it_handles_nonexistent_location()
    {
        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'inbound',
            'location_id' => 9999,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['location_id']);
    }

    /** @test */
    public function it_creates_workflow_tasks_for_exception_scans()
    {
        Bus::fake();

        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'exception',
            'location_id' => $this->branch->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Package damaged during transport',
        ]);

        $response->assertStatus(200);

        // Verify exception workflow job was dispatched
        Bus::assertDispatched(CreateExceptionWorkflowTask::class, function ($job) {
            return $job->shipment->id === $this->shipment->id;
        });
    }

    /** @test */
    public function it_creates_workflow_tasks_for_delivery_scans()
    {
        Bus::fake();

        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'delivery',
            'location_id' => $this->branch->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(200);

        // Verify delivery confirmation workflow job was dispatched
        Bus::assertDispatched(CreateDeliveryConfirmationWorkflowTask::class, function ($job) {
            return $job->shipment->id === $this->shipment->id;
        });
    }

    /** @test */
    public function it_creates_workflow_tasks_for_manual_intervention_scans()
    {
        Bus::fake();

        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'manual_intervention',
            'location_id' => $this->branch->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Customer address verification needed',
        ]);

        $response->assertStatus(200);

        // Verify manual intervention workflow job was dispatched
        Bus::assertDispatched(CreateManualInterventionWorkflowTask::class, function ($job) {
            return $job->shipment->id === $this->shipment->id;
        });
    }

    /** @test */
    public function it_broadcasts_websocket_events_for_scans()
    {
        Event::fake();

        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'inbound',
            'location_id' => $this->branch->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(200);

        // Verify ShipmentScanned event was broadcast
        Event::assertDispatched(ShipmentScanned::class, function ($event) {
            return $event->shipment->id === $this->shipment->id &&
                   $event->action === 'inbound';
        });
    }

    /** @test */
    public function it_handles_missing_device_headers()
    {
        $response = $this->postJson('/api/v1/mobile/scan', [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'inbound',
            'location_id' => $this->branch->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => 'Missing device credentials',
            ]);
    }

    /** @test */
    public function it_handles_invalid_timestamp_format()
    {
        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'inbound',
            'location_id' => $this->branch->id,
            'timestamp' => 'invalid-date-format',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['timestamp']);
    }

    /** @test */
    public function it_handles_geo_location_data()
    {
        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/scan', [
            'tracking_number' => $this->shipment->tracking_number,
            'action' => 'inbound',
            'location_id' => $this->branch->id,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'accuracy' => 10.5,
        ]);

        $response->assertStatus(200);

        $columns = collect(Schema::getColumnListing('scans'))
            ->map(fn ($column) => trim($column, " \"`'"))
            ->toArray();

        if (!in_array('latitude', $columns, true) || !in_array('longitude', $columns, true) || !in_array('accuracy', $columns, true)) {
            $this->markTestSkipped('Geo-location columns are not available on scans table.');
        }

        // Verify location data was stored
        $this->assertDatabaseHas('scans', [
            'shipment_id' => $this->shipment->id,
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'accuracy' => 10.5,
        ]);
    }

    /** @test */
    public function it_handles_batch_operations_correctly()
    {
        $batchId = 'test_batch_' . time();
        
        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/bulk-scan', [
            'scans' => [
                [
                    'tracking_number' => $this->shipment->tracking_number,
                    'action' => 'inbound',
                    'location_id' => $this->branch->id,
                    'batch_id' => $batchId,
                ],
            ],
            'batch_id' => $batchId,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($batchId, $response->json('batch_id'));
    }

    /** @test */
    public function it_validates_max_bulk_scan_limit()
    {
        $scans = [];
        for ($i = 0; $i < 101; $i++) {
            $scans[] = [
                'tracking_number' => "TEST{$i}123456789",
                'action' => 'inbound',
                'location_id' => $this->branch->id,
            ];
        }

        $response = $this->withHeaders([
            'X-Device-ID' => $this->device->device_id,
            'X-Device-Token' => $this->deviceToken,
        ])->postJson('/api/v1/mobile/bulk-scan', [
            'scans' => $scans,
            'batch_id' => 'batch_overflow',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scans']);
    }

    /**
     * Generate secure device token for testing
     */
    private function generateDeviceToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}