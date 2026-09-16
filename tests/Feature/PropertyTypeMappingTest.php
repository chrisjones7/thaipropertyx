<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\TaxonomyMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyTypeMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_property_type_mapping_can_normalize_condominiums_to_condo(): void
    {
        TaxonomyMapping::create([
            'agency_id' => null,
            'source_type' => 'houzez',
            'source_taxonomy' => 'property_type',
            'source_slug' => 'condominiums',
            'target_taxonomy' => 'property_type',
            'target_id' => null,
            'target_value' => 'condo',
            'active' => true,
        ]);

        $mapping = TaxonomyMapping::query()
            ->whereNull('agency_id')
            ->where('source_type', 'houzez')
            ->where('source_taxonomy', 'property_type')
            ->where('source_slug', 'condominiums')
            ->where('target_taxonomy', 'property_type')
            ->where('active', true)
            ->first();

        $this->assertNotNull($mapping);
        $this->assertSame('condo', $mapping->target_value);
    }

    public function test_agency_specific_mapping_takes_priority_over_global_mapping(): void
    {
        $agency = Agency::create([
            'name' => 'Arnold Property Test',
            'slug' => 'arnold-property-test',
            'status' => 'active',
            'verified' => false,
        ]);

        TaxonomyMapping::create([
            'agency_id' => null,
            'source_type' => 'houzez',
            'source_taxonomy' => 'property_type',
            'source_slug' => 'condos',
            'target_taxonomy' => 'property_type',
            'target_id' => null,
            'target_value' => 'apartment',
            'active' => true,
        ]);

        TaxonomyMapping::create([
            'agency_id' => $agency->id,
            'source_type' => 'houzez',
            'source_taxonomy' => 'property_type',
            'source_slug' => 'condos',
            'target_taxonomy' => 'property_type',
            'target_id' => null,
            'target_value' => 'condo',
            'active' => true,
        ]);

        $mapping = TaxonomyMapping::query()
            ->where('source_type', 'houzez')
            ->where('source_taxonomy', 'property_type')
            ->where('source_slug', 'condos')
            ->where('target_taxonomy', 'property_type')
            ->where('active', true)
            ->where('agency_id', $agency->id)
            ->first();

        if (!$mapping) {
            $mapping = TaxonomyMapping::query()
                ->where('source_type', 'houzez')
                ->where('source_taxonomy', 'property_type')
                ->where('source_slug', 'condos')
                ->where('target_taxonomy', 'property_type')
                ->where('active', true)
                ->whereNull('agency_id')
                ->first();
        }

        $this->assertNotNull($mapping);
        $this->assertSame('condo', $mapping->target_value);
        $this->assertSame($agency->id, $mapping->agency_id);
    }
}