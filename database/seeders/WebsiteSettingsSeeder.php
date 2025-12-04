<?php

namespace Database\Seeders;

use App\Models\Backend\GeneralSettings;
use App\Support\SystemSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class WebsiteSettingsSeeder extends Seeder
{
    /**
     * Seed professional DHL-grade website settings for the landing page.
     */
    public function run(): void
    {
        $settings = GeneralSettings::first();
        
        if (!$settings) {
            $settings = GeneralSettings::create([
                'name' => 'Baraka Logistics',
                'currency' => 'UGX',
            ]);
        }
        
        $details = $settings->details ?? [];
        
        // Professional website/landing page settings
        $details['website'] = [
            // SEO & Meta
            'site_title' => 'Baraka Logistics',
            'site_tagline' => "Africa's Premier International Courier & Logistics Partner",
            'site_description' => "Baraka Logistics delivers world-class express courier, freight forwarding, and supply chain solutions connecting Africa to Europe, Asia, Americas and 220+ destinations worldwide. Same-day, next-day, and international express shipping.",
            'site_keywords' => 'international courier Africa, express shipping Uganda, freight forwarding East Africa, DHL alternative Africa, logistics Europe Africa, air freight, sea freight, customs clearance, e-commerce fulfillment, B2B logistics, cross-border shipping',
            'og_image' => '/images/baraka-og-image.jpg',
            
            // Hero Section
            'hero_title' => 'Connecting Africa to the World',
            'hero_subtitle' => 'DHL-grade express courier and logistics solutions serving 220+ destinations across Africa, Europe, Asia, and the Americas. Experience reliability, speed, and precision with every shipment.',
            'hero_background' => '/images/hero-logistics-bg.jpg',
            'hero_cta_primary_text' => 'Get Instant Quote',
            'hero_cta_primary_url' => '/client/register',
            'hero_cta_secondary_text' => 'Track Your Shipment',
            'hero_cta_secondary_url' => '/tracking',
            'hero_show_tracking_widget' => true,
            
            // Features Section
            'features_enabled' => true,
            'features_title' => 'Why Leading Businesses Choose Baraka',
            'features_subtitle' => 'Enterprise-grade logistics infrastructure with the personal touch of a dedicated partner',
            'features' => [
                ['icon' => 'globe-americas', 'title' => 'Global Network', 'description' => '220+ countries & territories covered with strategic hubs in Kampala, Nairobi, Dubai, Amsterdam, and London'],
                ['icon' => 'clock-history', 'title' => 'Express Delivery', 'description' => 'Same-day delivery within East Africa, 24-48hr to Europe, 48-72hr worldwide with guaranteed SLAs'],
                ['icon' => 'shield-check', 'title' => 'Fully Insured', 'description' => 'Comprehensive cargo insurance up to $100,000 with real-time proof of delivery and chain of custody'],
                ['icon' => 'geo-alt', 'title' => 'Live GPS Tracking', 'description' => 'GPS-enabled tracking with SMS/email notifications at every milestone - from pickup to doorstep'],
                ['icon' => 'file-earmark-check', 'title' => 'Customs Expertise', 'description' => 'Licensed customs brokerage with 99.8% clearance success rate across all African ports of entry'],
                ['icon' => 'headset', 'title' => '24/7 Support', 'description' => 'Dedicated account managers and round-the-clock customer support in English, French & Swahili'],
            ],
            
            // Services Section
            'services_enabled' => true,
            'services_title' => 'Comprehensive Logistics Solutions',
            'services_subtitle' => 'From documents to freight - we move what matters most to your business',
            'services' => [
                ['icon' => 'lightning-charge', 'title' => 'Express Courier', 'description' => 'Time-critical document and parcel delivery with same-day, next-day, and priority options across Africa and worldwide', 'price' => 'From UGX 25,000'],
                ['icon' => 'airplane', 'title' => 'Air Freight', 'description' => 'Scheduled and charter air cargo services to Europe, Asia, Middle East and Americas with customs-cleared delivery', 'price' => 'From $4.50/kg'],
                ['icon' => 'water', 'title' => 'Sea Freight', 'description' => 'FCL and LCL ocean freight with port-to-door service, container tracking, and competitive rates for bulk shipments', 'price' => 'From $800/CBM'],
                ['icon' => 'truck', 'title' => 'Road Freight', 'description' => 'Cross-border trucking across East, Central and Southern Africa with bonded transit and real-time fleet tracking', 'price' => 'Custom Quote'],
                ['icon' => 'box-seam', 'title' => 'E-Commerce Fulfillment', 'description' => 'End-to-end fulfillment for online sellers: warehousing, pick-pack, last-mile delivery and returns management', 'price' => 'From UGX 5,000/order'],
                ['icon' => 'buildings', 'title' => 'Contract Logistics', 'description' => 'Dedicated supply chain solutions for enterprises: 3PL, inventory management, distribution and reverse logistics', 'price' => 'Custom Quote'],
            ],
            
            // Statistics Section
            'stats_enabled' => true,
            'stats_background' => '/images/stats-bg.jpg',
            'stats' => [
                ['value' => '2M+', 'label' => 'Shipments Delivered'],
                ['value' => '220+', 'label' => 'Countries Served'],
                ['value' => '99.2%', 'label' => 'On-Time Delivery'],
                ['value' => '50+', 'label' => 'Branch Locations'],
            ],
            
            // About Section
            'about_enabled' => true,
            'about_title' => "Africa's Fastest-Growing Logistics Company",
            'about_content' => "Founded in Kampala, Baraka Logistics has grown from a local courier service to East Africa's leading international logistics provider. We combine global reach with deep local expertise, offering DHL-grade service quality at competitive African pricing.\n\nOur state-of-the-art operations center processes over 10,000 shipments daily, with automated sorting, real-time tracking, and AI-powered route optimization. We've built strategic partnerships with major airlines, shipping lines, and customs authorities to ensure seamless cross-border movement.\n\nWhether you're an e-commerce entrepreneur shipping to Europe, a manufacturer importing machinery from China, or a corporation managing complex supply chains across Africa - Baraka delivers with precision, transparency, and care.",
            'about_image' => '/images/about-baraka-hub.jpg',
            'about_points' => [
                'ISO 9001:2015 & AEO Certified Operations',
                'Strategic hubs in 5 continents for fastest routing',
                'Integrated customs brokerage in 25 African countries',
                'Dedicated key account management for enterprise clients',
                'Carbon-neutral shipping options available',
                'Multi-currency billing (USD, EUR, GBP, UGX, KES)',
            ],
            
            // Testimonials Section
            'testimonials_enabled' => true,
            'testimonials_title' => "Trusted by Africa's Leading Businesses",
            'testimonials' => [
                [
                    'name' => 'Sarah Nakamya',
                    'company' => 'E-Commerce Entrepreneur',
                    'content' => "Baraka handles all my international shipments with incredible efficiency. Their real-time tracking and proactive communication have transformed how I run my online business. My customers love the delivery updates!",
                    'rating' => 5,
                ],
                [
                    'name' => 'Jean-Pierre Habimana',
                    'company' => 'Rwanda Trading Co.',
                    'content' => "We've been shipping coffee exports to Europe through Baraka for 3 years. Their customs expertise and air freight reliability are unmatched in the region. They understand agricultural export requirements perfectly.",
                    'rating' => 5,
                ],
                [
                    'name' => 'Mohammed Al-Hassan',
                    'company' => 'Import/Export Business',
                    'content' => "Baraka's door-to-door service from Dubai to Kampala is faster and more reliable than any carrier we've used. The tracking is exceptional and their customer service team is always responsive.",
                    'rating' => 5,
                ],
                [
                    'name' => 'Grace Achieng',
                    'company' => 'Fashion Retailer',
                    'content' => "As an e-commerce business shipping to customers across Africa, Baraka's fulfillment service has been a game-changer. Returns are handled seamlessly and my inventory is always accurate.",
                    'rating' => 5,
                ],
            ],
            
            // Contact Section
            'contact_enabled' => true,
            'contact_title' => 'Get Started Today',
            'contact_subtitle' => 'Request a quote, schedule a pickup, or speak with our logistics experts',
            'contact_email' => 'info@baraka.sanaa.ug',
            'contact_phone' => '+256 312 000 000',
            'contact_whatsapp' => '+256 700 000 000',
            'contact_address' => "Baraka Logistics Hub\nPlot 45 Jinja Road, Industrial Area\nKampala, Uganda",
            'contact_map_embed' => '',
            'contact_hours' => 'Operations: 24/7 | Customer Service: Mon-Sat 7AM-9PM EAT',
            
            // Social Links
            'social_facebook' => 'https://facebook.com/barakalogistics',
            'social_twitter' => 'https://twitter.com/barakalogistics',
            'social_instagram' => 'https://instagram.com/barakalogistics',
            'social_linkedin' => 'https://linkedin.com/company/baraka-logistics',
            'social_youtube' => '',
            'social_tiktok' => '',
            
            // Footer
            'footer_about' => "Baraka Logistics is East Africa's leading international courier and logistics company, connecting businesses across Africa to 220+ destinations worldwide. We combine global reach with local expertise.",
            'footer_copyright' => '© ' . date('Y') . ' Baraka Logistics. All rights reserved. Licensed by Uganda Revenue Authority.',
            'footer_links' => [
                ['title' => 'Track Shipment', 'url' => '/tracking'],
                ['title' => 'Get a Quote', 'url' => '/client/register'],
                ['title' => 'Services', 'url' => '/#solutions'],
                ['title' => 'About Us', 'url' => '/#about'],
                ['title' => 'Contact', 'url' => '/#contact'],
                ['title' => 'Terms of Service', 'url' => '/terms'],
                ['title' => 'Privacy Policy', 'url' => '/privacy'],
            ],
            
            // Analytics (leave empty for admin to configure)
            'google_analytics_id' => '',
            'google_tag_manager_id' => '',
            'facebook_pixel_id' => '',
            'hotjar_id' => '',
            
            // Advanced
            'custom_css' => '',
            'custom_js_head' => '',
            'custom_js_body' => '',
            'robots_txt' => "User-agent: *\nAllow: /\nSitemap: https://baraka.sanaa.ug/sitemap.xml",
            'maintenance_mode' => false,
            'maintenance_message' => "We're currently performing scheduled maintenance. Please check back shortly.",
        ];
        
        $settings->details = $details;
        $settings->save();
        
        // Clear cache
        Cache::forget('settings');
        SystemSettings::flush();
        
        $this->command->info('Website settings seeded successfully with professional DHL-grade content!');
    }
}
