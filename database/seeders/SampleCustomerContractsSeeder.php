<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\CustomerContractItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SampleCustomerContractsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding sample customer contracts...');

        // Get some existing customers for demo
        $customers = Customer::where('status', 'active')->limit(3)->get();

        if ($customers->isEmpty()) {
            $this->command->warn('  No active customers found. Skipping contract seeding.');
            return;
        }

        DB::transaction(function () use ($customers) {
            foreach ($customers as $index => $customer) {
                $discountTiers = [
                    0 => ['discount' => 5, 'credit' => 5000, 'terms' => 15],   // Bronze
                    1 => ['discount' => 10, 'credit' => 15000, 'terms' => 30], // Silver
                    2 => ['discount' => 15, 'credit' => 50000, 'terms' => 45], // Gold
                ];

                $tier = $discountTiers[$index] ?? $discountTiers[0];

                $contract = CustomerContract::updateOrCreate(
                    ['customer_id' => $customer->id],
                    [
                        'contract_number' => 'CTR-' . now()->format('Y') . '-' . str_pad($customer->id, 4, '0', STR_PAD_LEFT),
                        'name' => $customer->company_name . ' Annual Contract',
                        'start_date' => now()->startOfYear(),
                        'end_date' => now()->endOfYear(),
                        'credit_limit' => $tier['credit'],
                        'payment_terms_days' => $tier['terms'],
                        'discount_percent' => $tier['discount'],
                        'status' => 'active',
                        'notes' => 'Auto-generated sample contract',
                        'approved_by' => 1, // Admin user
                        'approved_at' => now(),
                    ]
                );

                // Add contract items with service-specific discounts
                $services = [
                    'standard' => $tier['discount'],
                    'express' => $tier['discount'] - 2, // Less discount on express
                    'economy' => $tier['discount'] + 3, // More discount on economy
                ];

                foreach ($services as $service => $discount) {
                    CustomerContractItem::updateOrCreate(
                        [
                            'contract_id' => $contract->id,
                            'service_level' => $service,
                            'weight_from' => 0,
                        ],
                        [
                            'zone' => null, // Applies to all zones
                            'weight_to' => null,
                            'base_rate' => null, // Use tariff base rate
                            'per_kg_rate' => null, // Use tariff per kg rate
                            'discount_percent' => max(0, $discount),
                            'active' => true,
                        ]
                    );
                }

                $this->command->info("  Created contract for: {$customer->company_name} ({$tier['discount']}% discount)");
            }
        });

        $this->command->info('Sample customer contracts seeded!');
    }
}
