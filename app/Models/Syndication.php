<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Syndication extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'source_agency_id',
        'target_agency_id',
        'status',
        'approved_at',
        'activated_at',
        'revoked_at',
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
            'approved_at' => 'datetime',
            'activated_at' => 'datetime',
            'revoked_at' => 'datetime',
            'listing_agency_split' => 'decimal:2',
            'cooperating_agency_split' => 'decimal:2',
            'sale_price_commission_percent' => 'decimal:2',
            'fixed_commission_amount' => 'decimal:2',
        ];
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function sourceAgency()
    {
        return $this->belongsTo(Agency::class, 'source_agency_id');
    }

    public function targetAgency()
    {
        return $this->belongsTo(Agency::class, 'target_agency_id');
    }
}