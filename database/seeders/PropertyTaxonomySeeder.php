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
        /*
        |--------------------------------------------------------------------------
        | Property Features / Amenities
        |--------------------------------------------------------------------------
        |
        | These form TPX's standard property-feature vocabulary.
        | houzez_slug provides the default mapping hint for Houzez.
        | Individual Houzez connectors can override mappings later.
        |
        */

        $features = [

            // Outdoor & Leisure
            ['Private Pool', 'Outdoor & Leisure'],
            ['Communal Pool', 'Outdoor & Leisure'],
            ['Private Garden', 'Outdoor & Leisure'],
            ['Communal Garden', 'Outdoor & Leisure'],
            ['Balcony', 'Outdoor & Leisure'],
            ['Terrace', 'Outdoor & Leisure'],
            ['Roof Terrace', 'Outdoor & Leisure'],
            ['BBQ Area', 'Outdoor & Leisure'],
            ['Beach Access', 'Outdoor & Leisure'],

            // Building / Development
            ['Clubhouse', 'Building / Development'],
            ['Fitness / Gym', 'Building / Development'],
            ['Elevator', 'Building / Development'],
            ['Reception', 'Building / Development'],
            ['Lobby', 'Building / Development'],
            ['Co-working Space', 'Building / Development'],
            ['Children\'s Play Area', 'Building / Development'],

            // Security
            ['24-Hour Security', 'Security'],
            ['CCTV', 'Security'],
            ['Gated Community', 'Security'],
            ['Key Card Access', 'Security'],
            ['Security Alarm', 'Security'],

            // Interior
            ['Air Conditioning', 'Interior'],
            ['Fully Equipped Kitchen', 'Interior'],
            ['Western Kitchen', 'Interior'],
            ['Thai Kitchen', 'Interior'],
            ['Built-in Wardrobes', 'Interior'],
            ['Storage Room', 'Interior'],
            ['Laundry Area', 'Interior'],
            ['Bathtub', 'Interior'],
            ['Jacuzzi', 'Interior'],

            // Parking & Access
            ['Covered Parking', 'Parking & Access'],
            ['Garage', 'Parking & Access'],
            ['Electric Gate', 'Parking & Access'],
            ['Wheelchair Access', 'Parking & Access'],

            // Utilities
            ['Solar Panels', 'Utilities'],
            ['Private Water Supply', 'Utilities'],
            ['Government Water', 'Utilities'],
            ['Government Electricity', 'Utilities'],
            ['High-Speed Internet', 'Utilities'],

            // Views
            ['Sea View', 'Views'],
            ['Mountain View', 'Views'],
            ['Pool View', 'Views'],
            ['Garden View', 'Views'],
            ['Golf Course View', 'Views'],
            ['City View', 'Views'],

            // Lifestyle
            ['Pet Friendly', 'Lifestyle'],
        ];

        foreach ($features as $index => [$name, $category]) {
            $slug = Str::slug($name);

            PropertyFeature::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'category' => $category,
                    'houzez_slug' => $slug,
                    'sort_order' => ($index + 1) * 10,
                    'active' => true,
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Property Labels
        |--------------------------------------------------------------------------
        */

        $labels = [
            'Reduced Price',
            'Hot Offer',
            'New Listing',
            'Exclusive',
            'Close to Beach',
            'Close to Golf Course',
            'Sea View',
            'Investment Opportunity',
        ];

        foreach ($labels as $index => $name) {
            $slug = Str::slug($name);

            PropertyLabel::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'houzez_slug' => $slug,
                    'sort_order' => ($index + 1) * 10,
                    'active' => true,
                ]
            );
        }
    }
}
