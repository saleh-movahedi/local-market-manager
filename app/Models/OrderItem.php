<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'store_product_id', 'quantity',
        'unit_price', 'unit_ok_price', 'discount_percent', 'line_total'
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'unit_ok_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'discount_percent' => 'integer',
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function storeProduct(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class);
    }

    public function getProfitAmountAttribute(): ?float
    {
        $cost = $this->storeProduct->cost_price;
        $sell = $this->unit_ok_price;
        if ($cost && $sell > 0) {
            return round(($sell - $cost) * $this->quantity, 2);
        }
        return null;
    }
}