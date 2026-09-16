<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    protected $fillable = [
        'agency_id',
        'reference',
        'title',
        'slug',
        'description',
        'development',

        // Legacy compatibility
        'listing_type',
        'price',

        // Sale & rental
        'for_sale',
        'for_rent',
        'sale_price',
        'rental_price',
        'rental_period',
        'currency',

        // Property details
        'property_type',
        'bedrooms',
        'bathrooms',
        'furnishing',
        'year_built',
        'parking_spaces',

        // Ownership / tenure
        'tenure',
        'lease_term_years',
        'foreign_quota',

        // Fees
        'common_fee',
        'common_fee_period',

        // Size
        'land_size',
        'building_size',
        'size_unit',

        // Location
        'address',
        'subdistrict',
        'district',
        'province',
        'postcode',
        'country',
        'latitude',
        'longitude',

        // Publishing
        'status',
        'featured',
        'published_at',
        'expires_at',

        // TPX Exchange
        'exchange_available',
        'exchange_available_at',
        'exchange_withdrawn_at',
    ];

    protected function casts(): array
    {
        return [
            'for_sale' => 'boolean',
            'for_rent' => 'boolean',

            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'rental_price' => 'decimal:2',

            'bedrooms' => 'integer',
            'bathrooms' => 'integer',
            'year_built' => 'integer',
            'parking_spaces' => 'integer',
            'lease_term_years' => 'integer',

            'land_size' => 'decimal:2',
            'building_size' => 'decimal:2',

            'foreign_quota' => 'boolean',
            'common_fee' => 'decimal:2',

            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',

            'featured' => 'boolean',

            'published_at' => 'datetime',
            'expires_at' => 'datetime',

            // TPX Exchange
            'exchange_available' => 'boolean',
            'exchange_available_at' => 'datetime',
            'exchange_withdrawn_at' => 'datetime',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(PropertyMedia::class);
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(PropertyMedia::class)
            ->where('media_type', 'image')
            ->where('is_primary', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyMedia::class)
            ->where('media_type', 'image')
            ->orderBy('sort_order');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(
            PropertyFeature::class,
            'property_property_feature'
        )->withTimestamps();
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(
            PropertyLabel::class,
            'property_property_label'
        )->withTimestamps();
    }

    public function sources(): HasMany
    {
        return $this->hasMany(PropertySource::class);
    }

    public function syndications(): HasMany
    {
        return $this->hasMany(Syndication::class);
    }
}