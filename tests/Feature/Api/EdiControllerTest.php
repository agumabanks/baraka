<?php

namespace Tests\Feature\Api;

use App\Models\EdiTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EdiControllerTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--path' => 'database/migrations/2014_10_11_000000_create_users_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2025_09_13_150500_create_edi_providers_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_11_011519_create_edi_mappings_table.php']);
        Artisan::call('migrate', ['--path' => 'database/migrations/2025_11_11_011525_create_edi_transactions_table.php']);
    }

    protected function tearDown(): void
    {
        Artisan::call('migrate:reset', ['--path' => 'database/migrations/2025_11_11_011525_create_edi_transactions_table.php']);
        Artisan::call('migrate:reset', ['--path' => 'database/migrations/2025_11_11_011519_create_edi_mappings_table.php']);
        Artisan::call('migrate:reset', ['--path' => 'database/migrations/2025_09_13_150500_create_edi_providers_table.php']);
        Artisan::call('migrate:reset', ['--path' => 'database/migrations/2014_10_11_000000_create_users_table.php']);

        parent::tearDown();
    }

    public function test_purchase_order_submission_generates_acknowledgement(): void
    {
        $user = User::factory()->create();
        $payload = [
            'payload' => [
                'purchase_order' => [
                    'number' => 'PO-' . $this->faker->randomNumber(5, true),
                    'buyer' => ['name' => 'Test Buyer'],
                    'items' => [
                        ['sku' => 'ABC123', 'qty' => 10],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/v1/edi/850', $payload);

        $response->assertStatus(202)
            ->assertJsonStructure(['success', 'transaction_id', 'acknowledgement' => ['document_type', 'control_number']]);

        $this->assertDatabaseCount('edi_transactions', 1);
        $transaction = EdiTransaction::first();
        $this->assertNotNull($transaction->ack_payload);
    }
}
