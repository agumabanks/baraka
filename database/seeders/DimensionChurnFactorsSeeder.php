<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ETL\DimensionChurnFactors;

class DimensionChurnFactorsSeeder extends Seeder
{
    public function run()
    {
        $churnFactors = [
            [
                'factor_key' => 'CF001',
                'factor_name' => 'Days Since Last Shipment',
                'factor_category' => 'behavioral',
                'factor_description' => 'Number of days since customer last created a shipment',
                'weight_in_model' => 0.25,
                'is_predictive' => true,
                'is_preventable' => true,
                'typical_impact_range' => '30-180 days',
                'recommended_intervention' => 'Send re-engagement emails and special offers',
                'monitoring_threshold' => 30.0,
                'factor_type' => 'recency',
                'data_source' => 'fact_shipments',
                'calculation_method' => 'DATEDIFF(CURRENT_DATE, MAX(shipment_date))',
                'last_updated' => now(),
                'is_active' => true,
            ],
            [
                'factor_key' => 'CF002',
                'factor_name' => 'Shipment Frequency Decline',
                'factor_category' => 'behavioral',
                'factor_description' => 'Decrease in shipment frequency compared to historical average',
                'weight_in_model' => 0.20,
                'is_predictive' => true,
                'is_preventable' => true,
                'typical_impact_range' => '50-80% decline',
                'recommended_intervention' => 'Investigate reasons and offer value-added services',
                'monitoring_threshold' => 0.5,
                'factor_type' => 'frequency',
                'data_source' => 'fact_shipments',
                'calculation_method' => 'AVG(30_day_frequency) / AVG(90_day_frequency)',
                'last_updated' => now(),
                'is_active' => true,
            ],
            [
                'factor_key' => 'CF003',
                'factor_name' => 'Credit Utilization',
                'factor_category' => 'financial',
                'factor_description' => 'Percentage of available credit being used',
                'weight_in_model' => 0.15,
                'is_predictive' => true,
                'is_preventable' => true,
                'typical_impact_range' => '70-100%',
                'recommended_intervention' => 'Review credit terms and payment options',
                'monitoring_threshold' => 0.7,
                'factor_type' => 'monetary',
                'data_source' => 'dimension_clients',
                'calculation_method' => 'current_balance / credit_limit',
                'last_updated' => now(),
                'is_active' => true,
            ],
            [
                'factor_key' => 'CF004',
                'factor_name' => 'Support Ticket Complaints',
                'factor_category' => 'service',
                'factor_description' => 'Number of support complaints in recent period',
                'weight_in_model' => 0.15,
                'is_predictive' => true,
                'is_preventable' => true,
                'typical_impact_range' => '3-10 complaints',
                'recommended_intervention' => 'Proactive customer service outreach',
                'monitoring_threshold' => 3.0,
                'factor_type' => 'satisfaction',
                'data_source' => 'fact_customer_sentiment',
                'calculation_method' => 'COUNT(complaints WHERE sentiment_score < 0)',
                'last_updated' => now(),
                'is_active' => true,
            ],
            [
                'factor_key' => 'CF005',
                'factor_name' => 'Payment Delays',
                'factor_category' => 'financial',
                'factor_description' => 'Number of late payments in recent period',
                'weight_in_model' => 0.10,
                'is_predictive' => true,
                'is_preventable' => true,
                'typical_impact_range' => '2-5 delays',
                'recommended_intervention' => 'Payment reminder system and alternative payment methods',
                'monitoring_threshold' => 2.0,
                'factor_type' => 'monetary',
                'data_source' => 'fact_financial_transactions',
                'calculation_method' => 'COUNT(transactions WHERE payment_date > due_date)',
                'last_updated' => now(),
                'is_active' => true,
            ],
        ];

        foreach ($churnFactors as $factor) {
            DimensionChurnFactors::create($factor);
        }
    }
}
