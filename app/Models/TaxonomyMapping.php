<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxonomyMapping extends Model
{
    protected $fillable = [
        'source_type',
        'source_taxonomy',
        'source_slug',
        'target_taxonomy',
        'target_id',
        'notes',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'target_id' => 'integer',
            'active' => 'boolean',
        ];
    }
}