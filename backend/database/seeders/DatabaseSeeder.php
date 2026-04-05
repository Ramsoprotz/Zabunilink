<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use App\Models\Plan;
use App\Models\Tender;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Notification templates (email + SMS)
        $this->call(NotificationTemplateSeeder::class);

        // Admin user
        $admin = User::create([
            'name' => 'ZabuniLink CTO',
            'email' => 'cto@zabunilink.com',
            'phone' => '+255700000000',
            'business_name' => 'ZabuniLink',
            'role' => 'admin',
            'password' => Hash::make('change-me-after-seeding'),
            'email_verified_at' => now(),
        ]);

        // Demo user
        $user = User::create([
            'name' => 'John Mwalimu',
            'email' => 'john@example.com',
            'phone' => '+255712345678',
            'business_name' => 'Mwalimu Enterprises',
            'role' => 'user',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Plans
        Plan::create([
            'name' => 'Basic',
            'tier' => 1,
            'description' => 'Access to tender listings, search, filtering, and notifications. Perfect for businesses that want to discover and track tenders.',
            'monthly_price' => 29900,
            'quarterly_price' => 79900,
            'semi_annual_price' => 149900,
            'annual_price' => 279900,
            'features' => [
                'Full tender listings access',
                'Search & filter tenders',
                'Favorite tenders',
                'Email notifications',
                'SMS notifications',
                'Push notifications',
            ],
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Pro',
            'tier' => 2,
            'description' => 'Everything in Basic plus professional tender application support. We handle the paperwork, compliance, and submission for you.',
            'monthly_price' => 99900,
            'quarterly_price' => 269900,
            'semi_annual_price' => 499900,
            'annual_price' => 899900,
            'features' => [
                'Everything in Basic',
                'Tender application support',
                'Document preparation',
                'Compliance handling',
                'Dedicated support',
                'Application tracking',
                'Priority notifications',
            ],
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Business',
            'tier' => 3,
            'description' => 'Full platform access including posting your own tenders and managing applications. Ideal for organizations that both find and publish tenders.',
            'monthly_price' => 199900,
            'quarterly_price' => 539900,
            'semi_annual_price' => 999900,
            'annual_price' => 1799900,
            'features' => [
                'Everything in Pro',
                'Post & manage tenders',
                'Receive applications',
                'Shortlist & award contracts',
                'Upload tender documents',
                'Business analytics dashboard',
                'Priority listing placement',
            ],
            'is_active' => true,
        ]);

        // Categories
        $categories = [
            ['name' => 'Construction', 'slug' => 'construction', 'description' => 'Building and infrastructure projects'],
            ['name' => 'IT & Technology', 'slug' => 'it-technology', 'description' => 'Software, hardware, and technology services'],
            ['name' => 'Healthcare', 'slug' => 'healthcare', 'description' => 'Medical supplies and healthcare services'],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'Educational materials and services'],
            ['name' => 'Agriculture', 'slug' => 'agriculture', 'description' => 'Agricultural supplies and services'],
            ['name' => 'Transport & Logistics', 'slug' => 'transport-logistics', 'description' => 'Transportation and logistics services'],
            ['name' => 'Energy & Mining', 'slug' => 'energy-mining', 'description' => 'Energy production and mining operations'],
            ['name' => 'Water & Sanitation', 'slug' => 'water-sanitation', 'description' => 'Water supply and sanitation projects'],
            ['name' => 'Consultancy', 'slug' => 'consultancy', 'description' => 'Professional consulting services'],
            ['name' => 'Supplies & Equipment', 'slug' => 'supplies-equipment', 'description' => 'General supplies and equipment procurement'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }

        // Locations (Tanzania Regions)
        $locations = [
            ['name' => 'Dar es Salaam', 'slug' => 'dar-es-salaam', 'region' => 'Coastal'],
            ['name' => 'Dodoma', 'slug' => 'dodoma', 'region' => 'Central'],
            ['name' => 'Arusha', 'slug' => 'arusha', 'region' => 'Northern'],
            ['name' => 'Mwanza', 'slug' => 'mwanza', 'region' => 'Lake'],
            ['name' => 'Mbeya', 'slug' => 'mbeya', 'region' => 'Southern Highlands'],
            ['name' => 'Morogoro', 'slug' => 'morogoro', 'region' => 'Eastern'],
            ['name' => 'Tanga', 'slug' => 'tanga', 'region' => 'Coastal'],
            ['name' => 'Kilimanjaro', 'slug' => 'kilimanjaro', 'region' => 'Northern'],
            ['name' => 'Zanzibar', 'slug' => 'zanzibar', 'region' => 'Island'],
            ['name' => 'Iringa', 'slug' => 'iringa', 'region' => 'Southern Highlands'],
            ['name' => 'Mtwara', 'slug' => 'mtwara', 'region' => 'Southern'],
            ['name' => 'Lindi', 'slug' => 'lindi', 'region' => 'Southern'],
            ['name' => 'Kagera', 'slug' => 'kagera', 'region' => 'Lake'],
            ['name' => 'Kigoma', 'slug' => 'kigoma', 'region' => 'Western'],
            ['name' => 'Tabora', 'slug' => 'tabora', 'region' => 'Western'],
            ['name' => 'Rukwa', 'slug' => 'rukwa', 'region' => 'Western'],
            ['name' => 'Singida', 'slug' => 'singida', 'region' => 'Central'],
            ['name' => 'Shinyanga', 'slug' => 'shinyanga', 'region' => 'Lake'],
            ['name' => 'Songwe', 'slug' => 'songwe', 'region' => 'Southern Highlands'],
            ['name' => 'Geita', 'slug' => 'geita', 'region' => 'Lake'],
            ['name' => 'Simiyu', 'slug' => 'simiyu', 'region' => 'Lake'],
            ['name' => 'Njombe', 'slug' => 'njombe', 'region' => 'Southern Highlands'],
            ['name' => 'Ruvuma', 'slug' => 'ruvuma', 'region' => 'Southern'],
            ['name' => 'Katavi', 'slug' => 'katavi', 'region' => 'Western'],
            ['name' => 'Pwani', 'slug' => 'pwani', 'region' => 'Coastal'],
            ['name' => 'Manyara', 'slug' => 'manyara', 'region' => 'Northern'],
        ];

        foreach ($locations as $loc) {
            Location::create($loc);
        }

        // Sample Tenders
        $tenders = [
            [
                'title' => 'Construction of District Hospital in Dodoma',
                'reference_number' => 'TND/2026/001',
                'description' => 'Invitation to tender for the construction of a 200-bed district hospital in Dodoma region. The project includes main building, outpatient department, laboratory, and staff quarters.',
                'organization' => 'Ministry of Health',
                'category_id' => 1,
                'location_id' => 2,
                'type' => 'government',
                'value' => 5000000000,
                'deadline' => now()->addDays(30),
                'published_date' => now(),
                'status' => 'open',
                'requirements' => 'Registered contractor Class I. Minimum 10 years experience in hospital construction. Valid tax clearance certificate.',
                'contact_info' => 'procurement@health.go.tz',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Supply of IT Equipment to Public Schools',
                'reference_number' => 'TND/2026/002',
                'description' => 'Procurement of 5,000 laptops and 500 projectors for public secondary schools across Tanzania.',
                'organization' => 'Ministry of Education',
                'category_id' => 2,
                'location_id' => 1,
                'type' => 'government',
                'value' => 2500000000,
                'deadline' => now()->addDays(21),
                'published_date' => now(),
                'status' => 'open',
                'requirements' => 'Authorized dealer of recognized IT brands. Previous experience in bulk IT supply.',
                'contact_info' => 'procurement@education.go.tz',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Medical Supplies for Regional Hospitals',
                'reference_number' => 'TND/2026/003',
                'description' => 'Annual supply of pharmaceutical products and medical consumables to 10 regional hospitals.',
                'organization' => 'Medical Stores Department',
                'category_id' => 3,
                'location_id' => 1,
                'type' => 'government',
                'value' => 1800000000,
                'deadline' => now()->addDays(14),
                'published_date' => now()->subDays(5),
                'status' => 'open',
                'requirements' => 'TFDA registered supplier. ISO certification required. Minimum 5 years in medical supplies.',
                'contact_info' => 'tender@msd.go.tz',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Road Rehabilitation - Arusha to Moshi Highway',
                'reference_number' => 'TND/2026/004',
                'description' => 'Rehabilitation and expansion of 80km highway section between Arusha and Moshi including drainage and safety features.',
                'organization' => 'TANROADS',
                'category_id' => 1,
                'location_id' => 3,
                'type' => 'government',
                'value' => 15000000000,
                'deadline' => now()->addDays(45),
                'published_date' => now(),
                'status' => 'open',
                'requirements' => 'Class I Road Contractor. Joint ventures accepted. Performance bond of 10% required.',
                'contact_info' => 'procurement@tanroads.go.tz',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Agricultural Equipment Supply - Mwanza Region',
                'reference_number' => 'TND/2026/005',
                'description' => 'Supply of 200 tractors and farming implements for smallholder farmers cooperative program.',
                'organization' => 'Ministry of Agriculture',
                'category_id' => 5,
                'location_id' => 4,
                'type' => 'government',
                'value' => 800000000,
                'deadline' => now()->addDays(25),
                'published_date' => now()->subDays(3),
                'status' => 'open',
                'requirements' => 'Authorized agricultural equipment dealer. After-sales service capability required.',
                'contact_info' => 'procurement@agriculture.go.tz',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Office Building Renovation - Vodacom HQ',
                'reference_number' => 'VDC/2026/001',
                'description' => 'Complete renovation of 3-floor office space including modern workspace design, networking infrastructure, and HVAC system.',
                'organization' => 'Vodacom Tanzania',
                'category_id' => 1,
                'location_id' => 1,
                'type' => 'private',
                'value' => 950000000,
                'deadline' => now()->addDays(20),
                'published_date' => now()->subDays(2),
                'status' => 'open',
                'requirements' => 'Minimum Class III contractor. Experience in commercial office renovation.',
                'contact_info' => 'procurement@vodacom.co.tz',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Water Supply System - Mbeya Rural District',
                'reference_number' => 'TND/2026/006',
                'description' => 'Design and construction of gravity-fed water supply system serving 15 villages in Mbeya rural district.',
                'organization' => 'Ministry of Water',
                'category_id' => 8,
                'location_id' => 5,
                'type' => 'government',
                'value' => 3200000000,
                'deadline' => now()->addDays(35),
                'published_date' => now(),
                'status' => 'open',
                'requirements' => 'Experience in rural water supply projects. Registered with ERB.',
                'contact_info' => 'procurement@maji.go.tz',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Consultancy for Solar Energy Project Feasibility',
                'reference_number' => 'TND/2026/007',
                'description' => 'Consultancy services for feasibility study of 50MW solar power plant in Dodoma region.',
                'organization' => 'TANESCO',
                'category_id' => 9,
                'location_id' => 2,
                'type' => 'government',
                'value' => 450000000,
                'deadline' => now()->addDays(18),
                'published_date' => now()->subDays(7),
                'status' => 'open',
                'requirements' => 'Registered consulting firm. Minimum 3 similar projects completed.',
                'contact_info' => 'procurement@tanesco.co.tz',
                'created_by' => $admin->id,
            ],
        ];

        foreach ($tenders as $tender) {
            Tender::create($tender);
        }

        // Create notification preference for demo user
        $user->notificationPreference()->create([
            'email_enabled' => true,
            'sms_enabled' => true,
            'push_enabled' => true,
        ]);
    }
}
