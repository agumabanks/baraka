<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = \App\Models\UnifiedBranch::all();
        $managers = \App\Models\User::where('user_type', \App\Enums\UserType::ADMIN)
            ->orWhere('user_type', \App\Enums\UserType::INCHARGE)
            ->get();

        if ($branches->isEmpty()) {
            $this->command->error('No branches found. Please run UnifiedBranchesSeeder first.');
            return;
        }

        $companyNames = [
            'Al-Futtaim Trading Co.', 'Riyadh Electronics LLC', 'Jeddah Fashion House',
            'Saudi Tech Solutions', 'Desert Rose Trading', 'Peninsula Imports',
            'Modern Home Furniture', 'Gulf Medical Supplies', 'Arabian Spices Co.',
            'Kingdom Books & Stationery', 'Red Sea Textiles', 'Najd Automotive Parts',
            'Oasis Garden Center', 'Star Electronics Store', 'Golden Sands Trading',
            'Heritage Crafts Gallery', 'Citywide Hardware', 'Platinum Jewelry Co.',
            'Fresh Market Supplies', 'Digital World Technology', 'Elite Fashion Boutique',
            'Royal Perfumes House', 'Metro Office Supplies', 'Summit Construction Materials',
            'Crystal Home Decor', 'Sunrise Pharmacy', 'Velocity Sports Equipment',
            'Horizon IT Services', 'Prime Auto Accessories', 'Elegant Furniture Gallery',
        ];

        $customerTypes = ['vip', 'vip', 'regular', 'regular', 'regular', 'regular', 'prospect'];
        $industries = [
            'Retail', 'E-commerce', 'Manufacturing', 'Healthcare', 'Technology',
            'Automotive', 'Food & Beverage', 'Fashion', 'Electronics', 'Construction'
        ];

        $customerCount = 0;

        foreach ($companyNames as $index => $companyName) {
            $customerType = $customerTypes[array_rand($customerTypes)];
            $industry = $industries[array_rand($industries)];
            $branch = $branches->random();
            $manager = $managers->random();

            $customer = \App\Models\Customer::create([
                'customer_code' => 'CUST-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                'company_name' => $companyName,
                'contact_person' => $this->generateArabicName(),
                'email' => strtolower(str_replace([' ', '.', '&'], '', $companyName)) . '@example.com',
                'phone' => '+9661' . rand(1000000, 9999999),
                'mobile' => '+9665' . rand(10000000, 99999999),
                'fax' => rand(0, 1) ? '+9661' . rand(1000000, 9999999) : null,
                'billing_address' => $this->generateAddress(),
                'shipping_address' => $this->generateAddress(),
                'city' => $this->getRandomCity(),
                'state' => 'N/A',
                'postal_code' => rand(10000, 99999),
                'country' => 'Saudi Arabia',
                'tax_id' => '3' . rand(100000000, 999999999),
                'registration_number' => 'CR-' . rand(1000000, 9999999),
                'industry' => $industry,
                'company_size' => ['Small', 'Medium', 'Large', 'Enterprise'][rand(0, 3)],
                'annual_revenue' => rand(100000, 10000000),
                'credit_limit' => $customerType === 'vip' ? rand(50000, 200000) : rand(10000, 50000),
                'current_balance' => 0,
                'payment_terms' => ['net_15', 'net_30', 'net_60', 'cod'][rand(0, 3)],
                'discount_rate' => $customerType === 'vip' ? rand(5, 15) : rand(0, 5),
                'currency' => 'SAR',
                'customer_type' => $customerType,
                'segment' => $customerType === 'vip' ? 'High-Value' : 'Standard',
                'source' => ['Referral', 'Website', 'Sales', 'Marketing'][rand(0, 3)],
                'priority_level' => $customerType === 'vip' ? 1 : 3,
                'communication_channels' => json_encode(['email', 'sms', 'whatsapp']),
                'notification_preferences' => json_encode([
                    'shipment_updates' => true,
                    'delivery_notifications' => true,
                    'promotional_offers' => $customerType !== 'vip',
                ]),
                'preferred_language' => rand(0, 1) ? 'ar' : 'en',
                'account_manager_id' => $manager->id ?? null,
                'primary_branch_id' => $branch->id,
                'sales_rep_id' => $manager->id ?? null,
                'status' => 'active',
                'last_contact_date' => now()->subDays(rand(1, 90)),
                'last_shipment_date' => $customerType === 'prospect' ? null : now()->subDays(rand(1, 30)),
                'customer_since' => now()->subMonths(rand(1, 36)),
                'notes' => $customerType === 'vip' ? 'VIP customer - high priority service' : null,
                'total_shipments' => $customerType === 'prospect' ? 0 : rand(10, 500),
                'total_spent' => $customerType === 'prospect' ? 0 : rand(5000, 500000),
                'average_order_value' => $customerType === 'prospect' ? 0 : rand(100, 2000),
                'complaints_count' => rand(0, 5),
                'satisfaction_score' => $customerType === 'prospect' ? null : rand(35, 50) / 10,
                'kyc_verified' => $customerType !== 'prospect',
                'kyc_verified_at' => $customerType !== 'prospect' ? now()->subMonths(rand(1, 24)) : null,
                'compliance_flags' => json_encode([]),
            ]);

            $customerCount++;
        }

        $this->command->info("Created {$customerCount} customers");
    }

    private function generateArabicName(): string
    {
        $firstNames = ['Ahmed', 'Mohammed', 'Fatima', 'Sara', 'Abdullah', 'Nora', 'Khalid', 'Layla', 'Omar', 'Aisha'];
        $lastNames = ['Al-Rashid', 'Al-Zahrani', 'Al-Ghamdi', 'Al-Otaibi', 'Al-Malki', 'Al-Qahtani', 'Al-Harbi', 'Al-Dosari'];
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    private function generateAddress(): string
    {
        $streets = ['King Fahd Road', 'Prince Sultan Street', 'Olaya Street', 'Tahlia Street', 'Madinah Road'];
        $districts = ['Al-Malaz', 'Al-Olaya', 'Al-Murjan', 'Al-Hamra', 'Al-Andalus'];
        return $streets[array_rand($streets)] . ', ' . $districts[array_rand($districts)];
    }

    private function getRandomCity(): string
    {
        $cities = ['Riyadh', 'Jeddah', 'Dammam', 'Khobar', 'Mecca', 'Medina'];
        return $cities[array_rand($cities)];
    }
}
