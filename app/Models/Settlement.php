<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    protected $fillable = [
        'merchant_id', 'period_start', 'period_end',
        'total_sales', 'total_commission', 'total_refunds', 'net_payable',
        'status', 'paid_at', 'payment_reference'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_sales' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'total_refunds' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'پیش‌نویس',
            'confirmed' => 'تایید شده',
            'paid' => 'پرداخت شده',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'draft' => 'gray',
            'confirmed' => 'warning',
            'paid' => 'success',
            default => 'gray',
        };
    }
}