<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipEnquiry extends Model
{
    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'business_type',
        'website_url',
        'website_platform',
        'wordpress_theme',
        'location',
        'message',
        'status',
        'admin_notes',
        'read_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}