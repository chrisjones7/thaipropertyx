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
        | Global Houzez Property Type Mappings
        |--------------------------------------------------------------------------
        |
        | These mappings normalize common Houzez property-type terminology
        | into the canonical TPX property types.
        |
        | Agency-specific mappings can override these global defaults.
        |
        */

        $propertyTypeMappings = [
            'apartment'    => 'apartment',
            'apartments'   => 'apartment',

            'condo'        => 'condo',
            'condos'       => 'condo',
            'condominium'  => 'condo',
            'condominiums' => 'condo',

            'house'        => 'house',
            'houses'       => 'house',

            'villa'        => 'villa',
            'villas'       => 'villa',
        ];

        foreach ($propertyTypeMappings as $houzezSlug => $tpxType) {
            TaxonomyMapping::updateOrCreate(
                [
                    'agency_id' => null,
                    'source_type' => 'houzez',
                    'source_taxonomy' => 'property_type',
                    'source_slug' => $houzezSlug,
                ],
                [
                    'target_taxonomy' => 'property_type',
                    'target_id' => null,
                    'target_value' => $tpxType,
                    'notes' => 'Global TPX property type mapping',
                    'active' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Global Houzez Property Feature Mappings
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
                    'agency_id' => null,
                    'source_type' => 'houzez',
                    'source_taxonomy' => 'property_feature',
                    'source_slug' => $houzezSlug,
                ],
                [
                    'target_taxonomy' => 'property_feature',
                    'target_id' => $feature->id,
                    'target_value' => null,
                    'notes' => 'Global TPX property feature mapping',
                    'active' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Global Houzez Property Label Mappings
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
                    'agency_id' => null,
                    'source_type' => 'houzez',
                    'source_taxonomy' => 'property_label',
                    'source_slug' => $houzezSlug,
                ],
                [
                    'target_taxonomy' => 'property_label',
                    'target_id' => $label->id,
                    'target_value' => null,
                    'notes' => 'Global TPX property label mapping',
                    'active' => true,
                ]
            );
        }
    }
}