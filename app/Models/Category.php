<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'name', 'slug', 'level', 'parent_ids', 'color', 'display_order', 'last_synced_at'
    ];

    protected $casts = [
        'parent_ids' => 'array',
        'level' => 'integer',
        'display_order' => 'integer',
        'last_synced_at' => 'datetime',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->where('level', $this->level + 1);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product')
            ->withTimestamps();
    }

    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    public function scopeRoot($query)
    {
        return $query->where('level', 1);
    }

    public function getIsRootAttribute(): bool
    {
        return $this->level === 1;
    }

    public function getIsLeafAttribute(): bool
    {
        return $this->level === 2;
    }
}