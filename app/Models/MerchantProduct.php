<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantProduct extends Model
{
    protected $fillable = [
        'merchant_id', 'store_product_id', 'quantity',
        'custom_price', 'custom_ok_price', 'custom_discount', 'margin_percent'
    ];

    protected $casts = [
        'custom_price' => 'decimal:2',
        'custom_ok_price' => 'decimal:2',
        'custom_discount' => 'integer',
        'margin_percent' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function storeProduct(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class);
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->custom_ok_price ?? $this->storeProduct->ok_price;
    }

    public function getEffectiveDiscountAttribute(): int
    {
        return $this->custom_discount ?? $this->storeProduct->discount_percent;
    }

    public function getProfitAmountAttribute(): ?float
    {
        $cost = $this->storeProduct->cost_price;
        $sell = $this->effective_price;
        if ($cost && $sell > 0) {
            return round($sell - $cost, 2);
        }
        return null;
    }
}