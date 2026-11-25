<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreProduct extends Model
{
    protected $fillable = [
        'store_id', 'product_id', 'price', 'ok_price', 'discount_percent',
        'quantity', 'supply_status', 'max_order_limit', 'cost_price',
        'vat_rate', 'last_synced_at'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'ok_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'discount_percent' => 'integer',
        'quantity' => 'integer',
        'max_order_limit' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function merchantProducts(): HasMany
    {
        return $this->hasMany(MerchantProduct::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('supply_status', 'available');
    }

    public function getMarginPercentAttribute(): ?float
    {
        if ($this->cost_price && $this->ok_price > 0) {
            return round((($this->ok_price - $this->cost_price) / $this->ok_price) * 100, 2);
        }
        return null;
    }

    public function getProfitAmountAttribute(): ?float
    {
        if ($this->cost_price && $this->ok_price > 0) {
            return round($this->ok_price - $this->cost_price, 2);
        }
        return null;
    }
}