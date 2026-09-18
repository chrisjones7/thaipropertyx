<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'legal_name',
        'registration_number',
        'email',
        'phone',
        'website',
        'logo',
        'description',
        'address_line_1',
        'address_line_2',
        'district',
        'province',
        'postcode',
        'country',
        'latitude',
        'longitude',
        'status',
        'verified',
        'verified_at',

        // Default property sharing / commission terms
        'sharing_enabled',
        'commission_model',
        'listing_agency_split',
        'cooperating_agency_split',
        'sale_price_commission_percent',
        'fixed_commission_amount',
        'commission_currency',
        'vat_treatment',
        'commission_payable_when',
        'sharing_terms',
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'verified_at' => 'datetime',
            'sharing_enabled' => 'boolean',
            'listing_agency_split' => 'decimal:2',
            'cooperating_agency_split' => 'decimal:2',
            'sale_price_commission_percent' => 'decimal:2',
            'fixed_commission_amount' => 'decimal:2',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function properties()
    {
        return $this->hasMany(Property::class);
    }

    public function propertySources()
    {
        return $this->hasMany(PropertySource::class);
    }

    public function apiClients()
    {
        return $this->hasMany(ApiClient::class);
    }

    public function outgoingSyndications()
    {
        return $this->hasMany(Syndication::class, 'source_agency_id');
    }

    public function incomingSyndications()
    {
        return $this->hasMany(Syndication::class, 'target_agency_id');
    }
}
