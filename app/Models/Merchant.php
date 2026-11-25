<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

class Merchant extends Model
{
    use HasRoles;

    protected $fillable = [
        'name', 'address', 'phone', 'code', 'settings', 'is_active'
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(MerchantProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function ledger(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function storeProducts(): BelongsToMany
    {
        return $this->belongsToMany(StoreProduct::class, 'merchant_products')
            ->withPivot('quantity', 'custom_price', 'custom_ok_price', 'custom_discount', 'margin_percent')
            ->withTimestamps();
    }

    public function currentBalance(): float
    {
        return $this->ledger()->latest('occurred_at')->first()?->balance_after ?? 0;
    }

    public function getMarginPercentAttribute(): ?float
    {
        return $this->settings['margin_percent'] ?? null;
    }
}