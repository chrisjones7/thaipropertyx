<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertySource extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'agency_id',
        'source_type',
        'external_id',
        'external_url',
        'feed_name',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
        ];
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}