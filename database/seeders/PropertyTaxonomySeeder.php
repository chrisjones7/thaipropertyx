<?php

namespace Database\Seeders;

use App\Models\PropertyFeature;
use App\Models\PropertyLabel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PropertyTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            // Outdoor & Leisure
            ['name' => 'Private Pool', 'category' => 'Outdoor & Leisure'],
            ['name' => 'Communal Pool', 'category' => 'Outdoor & Leisure'],
            ['name' => 'Private Garden', 'category' => 'Outdoor & Leisure'],
            ['name' => 'Communal Garden', 'category' => 'Outdoor & Leisure'],
            ['name' => 'Landscaped Gardens', 'category' => 'Outdoor & Leisure'],
            ['name' => 'Balcony', 'category' => 'Outdoor & Leisure'],
            ['name' => 'Terrace', 'category' => 'Outdoor & Leisure'],
            ['name' => 'Roof Terrace', 'category' => 'Outdoor & Leisure'],
            ['name' => 'BBQ Area', 'category' => 'Outdoor & Leisure'],
            ['name' => 'Beach Access', 'category' => 'Outdoor & Leisure'],
            ['name' => 'Outdoor Shower', 'category' => 'Outdoor & Leisure'],
            ['name' => 'Swimming Pool', 'category' => 'Outdoor & Leisure'],

            // Building / Development
            ['name' => 'Clubhouse', 'category' => 'Building / Development'],
            ['name' => 'Fitness / Gym', 'category' => 'Building / Development'],
            ['name' => 'Elevator', 'category' => 'Building / Development'],
            ['name' => 'Reception', 'category' => 'Building / Development'],
            ['name' => 'Lobby', 'category' => 'Building / Development'],
            ['name' => 'Co-working Space', 'category' => 'Building / Development'],
            ['name' => "Children's Play Area", 'category' => 'Building / Development'],
            ['name' => 'Sauna', 'category' => 'Building / Development'],

            // Security
            ['name' => '24-Hour Security', 'category' => 'Security'],
            ['name' => 'Security', 'category' => 'Security'],
            ['name' => 'CCTV', 'category' => 'Security'],
            ['name' => 'Gated Community', 'category' => 'Security'],
            ['name' => 'Key Card Access', 'category' => 'Security'],
            ['name' => 'Security Alarm', 'category' => 'Security'],

            // Interior
            ['name' => 'Air Conditioning', 'category' => 'Interior'],
['name' => 'Fully Furnished', 'category' => 'Interior'],
['name' => 'Partly Furnished', 'category' => 'Interior'],
['name' => 'Unfurnished', 'category' => 'Interior'],
            ['name' => 'Fully Equipped Kitchen', 'category' => 'Interior'],
            ['name' => 'Western Kitchen', 'category' => 'Interior'],
            ['name' => 'Thai Kitchen', 'category' => 'Interior'],
            ['name' => 'Built-in Wardrobes', 'category' => 'Interior'],
            ['name' => 'Storage Room', 'category' => 'Interior'],
            ['name' => 'Laundry Area', 'category' => 'Interior'],
            ['name' => 'Bathtub', 'category' => 'Interior'],
            ['name' => 'Jacuzzi', 'category' => 'Interior'],
            ['name' => 'Refrigerator', 'category' => 'Interior'],

            // Parking & Access
            ['name' => 'Covered Parking', 'category' => 'Parking & Access'],
            ['name' => 'Garage', 'category' => 'Parking & Access'],
            ['name' => 'Electric Gate', 'category' => 'Parking & Access'],
            ['name' => 'Wheelchair Access', 'category' => 'Parking & Access'],

            // Utilities
            ['name' => 'Solar Panels', 'category' => 'Utilities'],
            ['name' => 'Private Water Supply', 'category' => 'Utilities'],
            ['name' => 'Government Water', 'category' => 'Utilities'],
            ['name' => 'Government Electricity', 'category' => 'Utilities'],
            ['name' => 'High-Speed Internet', 'category' => 'Utilities'],

            // Views
            ['name' => 'Sea View', 'category' => 'Views'],
            ['name' => 'Mountain View', 'category' => 'Views'],
            ['name' => 'Pool View', 'category' => 'Views'],
            ['name' => 'Garden View', 'category' => 'Views'],
            ['name' => 'Golf Course View', 'category' => 'Views'],
            ['name' => 'City View', 'category' => 'Views'],

            // Lifestyle
            ['name' => 'Pet Friendly', 'category' => 'Lifestyle'],
        ];

        foreach ($features as $index => $feature) {
            $slug = Str::slug($feature['name']);

            PropertyFeature::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $feature['name'],
                    'category' => $feature['category'],
                    'houzez_slug' => $slug,
                    'active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }

        $labels = [
            'Reduced Price',
            'Hot Offer',
            'New Listing',
            'Exclusive',
            'Close to Beach',
            'Close to Golf Course',
            'Sea View',
            'Investment Opportunity',
            'Close to MRT',
            'Close to BTS',
        ];

        foreach ($labels as $index => $name) {
            $slug = Str::slug($name);

            PropertyLabel::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'houzez_slug' => $slug,
                    'active' => true,
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }
    }
}