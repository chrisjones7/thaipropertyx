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
    ];

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'verified_at' => 'datetime',
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