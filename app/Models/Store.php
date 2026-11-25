<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'name', 'code', 'url', 'meta', 'is_active', 'last_synced_at'
    ];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(StoreProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}