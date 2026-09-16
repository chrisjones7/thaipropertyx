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
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'activated_at' => 'datetime',
            'revoked_at' => 'datetime',
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