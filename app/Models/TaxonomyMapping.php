<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxonomyMapping extends Model
{
    protected $fillable = [
        'agency_id',
        'source_type',
        'source_taxonomy',
        'source_slug',
        'target_taxonomy',
        'target_id',
        'target_value',
        'notes',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'agency_id' => 'integer',
            'target_id' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}