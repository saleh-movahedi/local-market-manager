<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $appends = ['thumbnail_url', 'medium_image_url'];

    protected $fillable = [
        'name', 'slug', 'description', 'image_url', 'master_product_id',
        'doc_rank_t', 'is_bundle', 'attributes'
    ];

    protected $casts = [
        'is_bundle' => 'boolean',
        'attributes' => 'array',
        'doc_rank_t' => 'integer',
    ];

    public function storeProducts(): HasMany
    {
        return $this->hasMany(StoreProduct::class);
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'store_products')
            ->withPivot('price', 'ok_price', 'discount_percent', 'quantity', 'supply_status', 'max_order_limit', 'cost_price', 'vat_rate', 'last_synced_at')
            ->withTimestamps();
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get thumbnail image URL (small size for tables/lists to save bandwidth)
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->image_url) {
            return null;
        }

        // Replace size parameters with small thumbnail size
        // Handle various formats: rs:fill/size:W:H/quality:Q or rs:fill/size:W:H
        return preg_replace(
            '/rs:fill\/size:\d+:\d+(?:\/quality:\d+)?/',
            'rs:fill/size:142:142/quality:90',
            $this->image_url
        );
    }

    /**
     * Get medium image URL for detail views
     */
    public function getMediumImageUrlAttribute(): ?string
    {
        if (!$this->image_url) {
            return null;
        }

        return preg_replace(
            '/rs:fill\/size:\d+:\d+(?:\/quality:\d+)?/',
            'rs:fill/size:231:231/quality:100',
            $this->image_url
        );
    }
}