<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PropertyFeature extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'category',
        'houzez_slug',
        'description',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Properties using this feature / amenity.
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(
            Property::class,
            'property_property_feature'
        )->withTimestamps();
    }
}
