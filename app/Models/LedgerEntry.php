<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends Model
{
    protected $fillable = [
        'merchant_id', 'order_id', 'type', 'amount', 'balance_after',
        'description', 'meta', 'occurred_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'sale' => 'فروش',
            'commission' => 'کمیسیون',
            'refund' => 'مرجوعی',
            'adjustment' => 'تنظیم',
            'payout' => 'تسویه',
            'fee' => 'کارمزد',
            default => $this->type,
        };
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->amount > 0;
    }
}