<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyDuplicateMatch extends Model
{
    protected $fillable = [
        'property_id',
        'matching_property_id',
        'matching_images',
        'classification',
        'review_status',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'matching_images' => 'integer',
            'last_checked_at' => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function matchingProperty(): BelongsTo
    {
        return $this->belongsTo(
            Property::class,
            'matching_property_id'
        );
    }
}
