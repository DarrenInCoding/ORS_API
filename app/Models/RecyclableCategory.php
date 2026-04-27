<?php

namespace App\Models;

use App\Models\InventoryRecord;
use App\Models\RecycleOrderItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class RecyclableCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image',
        'unit',
        'price_per_unit',
        'min_quantity',
        'is_active',
        'sort_order',
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'price_per_unit' => 'decimal:2',
            'min_quantity' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // ── Relationships ──────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(RecyclableCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(RecyclableCategory::class, 'parent_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(RecycleOrderItem::class, 'category_id');
    }

    public function inventoryRecords(): HasMany
    {
        return $this->hasMany(InventoryRecord::class, 'category_id');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id');
    }
}
