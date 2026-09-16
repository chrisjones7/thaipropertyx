<?php

namespace Database\Seeders;

use App\Models\PropertyFeature;
use App\Models\PropertyLabel;
use App\Models\TaxonomyMapping;
use Illuminate\Database\Seeder;

class TaxonomyMappingSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Houzez Property Feature Mappings
        |--------------------------------------------------------------------------
        |
        | Houzez installations may use shorter or different slugs from the
        | canonical TPX taxonomy. These mappings translate incoming Houzez
        | feature slugs into the corresponding TPX PropertyFeature records.
        |
        */

        $featureMappings = [
            'air-conditioning' => 'air-conditioning',
            'gym' => 'fitness-gym',
            'barbeque' => 'bbq-area',
            'laundry' => 'laundry-area',
            'outdoor-shower' => 'outdoor-shower',
            'refrigerator' => 'refrigerator',
            'sauna' => 'sauna',
            'swimming-pool' => 'swimming-pool',
        ];

        foreach ($featureMappings as $houzezSlug => $tpxSlug) {
            $feature = PropertyFeature::where('slug', $tpxSlug)->first();

            if (!$feature) {
                continue;
            }

            TaxonomyMapping::updateOrCreate(
                [
                    'source_type' => 'houzez',
                    'source_taxonomy' => 'property_feature',
                    'source_slug' => $houzezSlug,
                ],
                [
                    'target_taxonomy' => 'property_feature',
                    'target_id' => $feature->id,
                    'active' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Houzez Property Label Mappings
        |--------------------------------------------------------------------------
        */

        $labelMappings = [
            'reduced-price' => 'reduced-price',
            'hot-offer' => 'hot-offer',
            'new-listing' => 'new-listing',
            'exclusive' => 'exclusive',
            'close-to-beach' => 'close-to-beach',
            'close-to-golf-course' => 'close-to-golf-course',
            'sea-view' => 'sea-view',
            'investment-opportunity' => 'investment-opportunity',
            'close-to-mrt' => 'close-to-mrt',
            'close-to-bts' => 'close-to-bts',
        ];

        foreach ($labelMappings as $houzezSlug => $tpxSlug) {
            $label = PropertyLabel::where('slug', $tpxSlug)->first();

            if (!$label) {
                continue;
            }

            TaxonomyMapping::updateOrCreate(
                [
                    'source_type' => 'houzez',
                    'source_taxonomy' => 'property_label',
                    'source_slug' => $houzezSlug,
                ],
                [
                    'target_taxonomy' => 'property_label',
                    'target_id' => $label->id,
                    'active' => true,
                ]
            );
        }
    }
}