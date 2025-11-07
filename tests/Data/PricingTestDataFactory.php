<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PromotionalCampaign;
use App\Models\Contract;
use App\Models\Branch;
use App\Models\APIKey;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Test Data Factory for Enhanced Logistics Pricing System
 * 
 * Provides comprehensive test data generation for all pricing system components:
 * - Customer data with different tiers and profiles
 * - Promotional campaigns with various configurations
 * - Contracts with different types and statuses
 * - API keys with varying permissions
 * - Mock data for external services
 */
class PricingTestDataFactory extends Factory
{
    /**
     * Create a comprehensive customer for testing
     */
    public function createCustomer(array $overrides = []): array
    {
        $customerTypes = ['basic', 'standard', 'silver', 'gold', 'platinum'];
        $customerType = $overrides['customer_type'] ?? $this->faker->randomElement($customerTypes);
        
        $baseData = [
            'name' => $this->faker->company,
            'email' => $this->faker->unique()->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'country' => $this->faker->countryCode,
            'customer_type' => $customerType,
            'total_shipments' => $this->faker->numberBetween(0, 1000),
            'total_revenue' => $this->faker->randomFloat(2, 0, 100000),
            'customer_since' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'suspended']),
            'is_premium' => in_array($customerType, ['gold', 'platinum']),
            'billing_address' => $this->faker->address,
            'shipping_address' => $this->faker->address,
            'payment_terms' => $this->faker->randomElement(['net_15', 'net_30', 'net_45', 'prepaid']),
            'discount_eligible' => $this->faker->boolean(80),
            'volume_commitment' => $this->faker->numberBetween(0, 500),
            'preferred_carrier' => $this->faker->randomElement(['fedex', 'ups', 'dhl', 'own_fleet']),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];

        return array_merge($baseData, $overrides);
    }

    /**
     * Create a promotional campaign for testing
     */
    public function createPromotionalCampaign(array $overrides = []): array
    {
        $campaignTypes = ['percentage', 'fixed_amount', 'free_shipping', 'bogo', 'milestone_bonus'];
        $targetAudiences = ['all', 'new_customers', 'vip', 'volume_customers', 'loyalty_tier'];
        
        $baseData = [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'promo_code' => strtoupper($this->faker->lexify('????') . $this->faker->numberBetween(100, 999)),
            'campaign_type' => $this->faker->randomElement($campaignTypes),
            'value' => $this->faker->numberBetween(5, 50),
            'is_active' => $this->faker->boolean(80),
            'effective_from' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'effective_to' => $this->faker->dateTimeBetween('+1 week', '+3 months'),
            'usage_limit' => $this->faker->numberBetween(100, 10000),
            'usage_count' => $this->faker->numberBetween(0, 100),
            'customer_eligibility' => [
                'customer_types' => $this->faker->randomElements(['basic', 'standard', 'silver', 'gold', 'platinum'], 2),
                'minimum_order_value' => $this->faker->numberBetween(50, 500),
                'minimum_shipments' => $this->faker->numberBetween(0, 10),
                'applicable_services' => $this->faker->randomElements(['standard', 'express', 'overnight'], 2)
            ],
            'stacking_allowed' => $this->faker->boolean(30),
            'maximum_discount_amount' => $this->faker->numberBetween(100, 1000),
            'discount_cap_percentage' => $this->faker->numberBetween(20, 50),
            'target_audience' => $this->faker->randomElement($targetAudiences),
            'geographic_scope' => [
                'countries' => $this->faker->randomElements(['KE', 'UG', 'RW', 'TZ', 'BI'], 2),
                'regions' => $this->faker->randomElements(['east_africa', 'great_lakes'], 1)
            ],
            'auto_activate' => $this->faker->boolean(40),
            'created_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];

        return array_merge($baseData, $overrides);
    }

    /**
     * Create a contract for testing
     */
    public function createContract(array $overrides = []): array
    {
        $contractTypes = ['standard', 'volume', 'enterprise', 'government', 'non_profit'];
        $statuses = ['draft', 'pending_approval', 'active', 'expired', 'terminated', 'renewed'];
        
        $baseData = [
            'name' => $this->faker->words(2, true) . ' Contract',
            'contract_type' => $this->faker->randomElement($contractTypes),
            'status' => $this->faker->randomElement($statuses),
            'start_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'end_date' => $this->faker->dateTimeBetween('+6 months', '+2 years'),
            'auto_renewal_terms' => [
                'auto_renewal' => $this->faker->boolean(60),
                'notice_period_days' => $this->faker->numberBetween(30, 90),
                'extension_duration_days' => $this->faker->numberBetween(365, 730)
            ],
            'volume_commitment' => $this->faker->numberBetween(50, 1000),
            'volume_commitment_period' => $this->faker->randomElement(['monthly', 'quarterly', 'annually']),
            'current_volume' => $this->faker->numberBetween(0, 500),
            'pricing_tier' => $this->faker->randomElement(['bronze', 'silver', 'gold', 'platinum']),
            'discount_percentage' => $this->faker->numberBetween(5, 25),
            'service_level_agreement' => [
                'delivery_time_guarantee' => $this->faker->numberBetween(24, 120) . ' hours',
                'success_rate_target' => $this->faker->numberBetween(95, 99) . '%',
                'compensation_rules' => 'Standard SLA compensation applies'
            ],
            'payment_terms' => [
                'payment_method' => $this->faker->randomElement(['credit_card', 'bank_transfer', 'invoice']),
                'net_terms' => $this->faker->randomElement([15, 30, 45]),
                'late_payment_fee' => $this->faker->numberBetween(1, 5) . '%'
            ],
            'special_conditions' => $this->faker->sentence,
            'contract_value' => $this->faker->randomFloat(2, 10000, 500000),
            'renewal_discount' => $this->faker->numberBetween(2, 10),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];

        return array_merge($baseData, $overrides);
    }

    /**
     * Create an API key for testing
     */
    public function createAPIKey(array $overrides = []): array
    {
        $permissions = [
            'pricing:read', 'pricing:write', 'quotes:read', 'quotes:write',
            'contracts:read', 'contracts:write', 'promotions:read', 'promotions:write',
            'admin:read', 'admin:write', 'reports:read', 'billing:read'
        ];
        
        $baseData = [
            'name' => $this->faker->words(2, true) . ' API Key',
            'key' => 'sk_test_' . Str::random(48),
            'permissions' => $this->faker->randomElements($permissions, 3),
            'is_active' => $this->faker->boolean(85),
            'expires_at' => $this->faker->dateTimeBetween('+1 month', '+1 year'),
            'rate_limit_per_hour' => $this->faker->numberBetween(100, 10000),
            'last_used_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'usage_count' => $this->faker->numberBetween(0, 1000),
            'scopes' => [
                'allowed_ips' => $this->faker->randomElements([
                    '192.168.1.0/24',
                    '10.0.0.0/8',
                    $this->faker->ipv4
                ], 2),
                'allowed_endpoints' => $this->faker->randomElements([
                    '/api/v1/pricing/*',
                    '/api/v1/contracts/*',
                    '/api/v1/promotions/*'
                ], 2)
            ],
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];

        return array_merge($baseData, $overrides);
    }

    /**
     * Create bulk test data for performance testing
     */
    public function createBulkTestData(string $dataType, int $count = 1000): array
    {
        $data = [];
        
        switch ($dataType) {
            case 'customers':
                for ($i = 0; $i < $count; $i++) {
                    $data[] = $this->createCustomer();
                }
                break;
                
            case 'promotions':
                for ($i = 0; $i < $count; $i++) {
                    $data[] = $this->createPromotionalCampaign();
                }
                break;
                
            case 'contracts':
                for ($i = 0; $i < $count; $i++) {
                    $data[] = $this->createContract();
                }
                break;
                
            case 'api_keys':
                for ($i = 0; $i < $count; $i++) {
                    $data[] = $this->createAPIKey();
                }
                break;
                
            default:
                throw new \InvalidArgumentException("Unknown data type: {$dataType}");
        }
        
        return $data;
    }

    /**
     * Create edge case data for testing boundary conditions
     */
    public function createEdgeCaseData(string $scenario): array
    {
        switch ($scenario) {
            case 'extreme_weights':
                return [
                    'weight_kg' => 0.01, // Minimum weight
                    'pieces' => 1000,    // Maximum pieces
                    'dimensions' => [
                        'length_cm' => 500,  // Very large dimension
                        'width_cm' => 500,
                        'height_cm' => 500
                    ]
                ];
                
            case 'extreme_values':
                return [
                    'declared_value' => 0.01, // Minimum value
                    'total_amount' => 999999.99, // Maximum value
                    'discount_amount' => 999999.99
                ];
                
            case 'boundary_dates':
                return [
                    'effective_from' => Carbon::now()->subCentury(),
                    'effective_to' => Carbon::now()->addCentury(),
                    'expires_at' => Carbon::now()->subSecond()
                ];
                
            case 'special_characters':
                return [
                    'name' => 'Test "Customer" with \'quotes\' and special chars: @#$%',
                    'email' => 'test+special@domain.com',
                    'promo_code' => 'SPECIAL-CHARS_123!@#$%^&*()'
                ];
                
            case 'unicode_data':
                return [
                    'name' => '客户名称 áéíóú ñü ÑÜ 日本語 한국어',
                    'description' => 'É Test description with unicode characters: ♠♥♦♣'
                ];
                
            default:
                throw new \InvalidArgumentException("Unknown edge case scenario: {$scenario}");
        }
    }

    /**
     * Create realistic test scenarios
     */
    public function createTestScenario(string $scenarioName): array
    {
        switch ($scenarioName) {
            case 'premium_customer_quote':
                return [
                    'customer' => $this->createCustomer(['customer_type' => 'platinum', 'total_shipments' => 500]),
                    'shipment_data' => [
                        'weight_kg' => 25.5,
                        'pieces' => 3,
                        'dimensions' => ['length_cm' => 40, 'width_cm' => 30, 'height_cm' => 20],
                        'declared_value' => 1500.00,
                        'service_level' => 'express',
                        'special_handling' => 'fragile'
                    ],
                    'promotions' => $this->createPromotionalCampaign([
                        'customer_eligibility' => ['customer_types' => ['platinum']],
                        'campaign_type' => 'percentage',
                        'value' => 15
                    ])
                ];
                
            case 'bulk_shipment_discount':
                return [
                    'customer' => $this->createCustomer(['customer_type' => 'gold', 'total_shipments' => 200]),
                    'shipments' => array_map(function($i) {
                        return [
                            'weight_kg' => rand(5, 50),
                            'pieces' => rand(1, 10),
                            'destination' => ['UG', 'RW', 'TZ'][array_rand(['UG', 'RW', 'TZ'])]
                        ];
                    }, range(1, 100)),
                    'contract' => $this->createContract([
                        'contract_type' => 'volume',
                        'volume_commitment' => 1000,
                        'current_volume' => 800
                    ])
                ];
                
            case 'promotion_stacking_test':
                return [
                    'customer' => $this->createCustomer(['customer_type' => 'gold']),
                    'promotions' => [
                        $this->createPromotionalCampaign(['stacking_allowed' => true]),
                        $this->createPromotionalCampaign(['stacking_allowed' => false])
                    ],
                    'order_value' => 500.00
                ];
                
            case 'contract_compliance_scenario':
                return [
                    'customer' => $this->createCustomer(['customer_type' => 'enterprise']),
                    'contract' => $this->createContract([
                        'contract_type' => 'enterprise',
                        'status' => 'active',
                        'volume_commitment' => 5000,
                        'current_volume' => 4500
                    ]),
                    'compliance_requirements' => [
                        ['name' => 'Delivery Time', 'target' => '95%', 'current' => '92%'],
                        ['name' => 'Success Rate', 'target' => '99%', 'current' => '98%']
                    ]
                ];
                
            default:
                throw new \InvalidArgumentException("Unknown test scenario: {$scenarioName}");
        }
    }

    /**
     * Create mock external service responses
     */
    public function createExternalServiceMock(string $serviceType): array
    {
        switch ($serviceType) {
            case 'carrier_rates':
                return [
                    'fedex' => [
                        'standard' => ['rate' => 45.00, 'delivery_time' => '3-5 days'],
                        'express' => ['rate' => 65.00, 'delivery_time' => '1-2 days'],
                        'overnight' => ['rate' => 85.00, 'delivery_time' => 'next day']
                    ],
                    'ups' => [
                        'standard' => ['rate' => 42.00, 'delivery_time' => '3-5 days'],
                        'express' => ['rate' => 62.00, 'delivery_time' => '1-2 days'],
                        'overnight' => ['rate' => 82.00, 'delivery_time' => 'next day']
                    ],
                    'dhl' => [
                        'standard' => ['rate' => 48.00, 'delivery_time' => '3-5 days'],
                        'express' => ['rate' => 68.00, 'delivery_time' => '1-2 days'],
                        'overnight' => ['rate' => 88.00, 'delivery_time' => 'next day']
                    ]
                ];
                
            case 'fuel_surcharge':
                return [
                    'current_index' => 110.5,
                    'base_index' => 100.0,
                    'surcharge_rate' => 8.4,
                    'last_updated' => now()->toISOString(),
                    'source' => 'EIA'
                ];
                
            case 'competitor_pricing':
                return [
                    'route' => 'KE-UG',
                    'competitors' => [
                        'competitor_a' => ['rate' => 50.00, 'service_level' => 'standard'],
                        'competitor_b' => ['rate' => 47.00, 'service_level' => 'standard'],
                        'competitor_c' => ['rate' => 52.00, 'service_level' => 'express']
                    ],
                    'analysis' => [
                        'average_rate' => 49.67,
                        'market_position' => 'competitive',
                        'price_range' => ['min' => 47.00, 'max' => 52.00]
                    ]
                ];
                
            case 'payment_gateway':
                return [
                    'transaction_id' => 'txn_' . Str::random(20),
                    'status' => 'succeeded',
                    'amount' => 156.78,
                    'currency' => 'USD',
                    'processing_time' => 1.2,
                    'gateway' => 'stripe'
                ];
                
            default:
                throw new \InvalidArgumentException("Unknown external service type: {$serviceType}");
        }
    }
}