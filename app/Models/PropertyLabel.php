<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PropertyLabel extends Model
{
    protected $fillable = [
        'name',
        'slug',
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
     * Properties using this label.
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(
            Property::class,
            'property_property_label'
        )->withTimestamps();
    }
}