<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'name',
        'client_id',
        'secret_hash',
        'status',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = [
        'secret_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
