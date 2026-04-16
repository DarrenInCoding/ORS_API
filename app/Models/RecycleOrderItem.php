<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecycleOrderItem extends Model
{
    protected $fillable = [
        'recycle_order_id',
        'category_id',
        'estimated_weight',
        'actual_weight',
        'quantity',
        'price_per_unit',
        'subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'estimated_weight' => 'decimal:2',
            'actual_weight' => 'decimal:2',
            'price_per_unit' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function ($item) {
            $item->calculateSubtotal();
        });
    }

    // ── Relationships ──────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(RecycleOrder::class, 'recycle_order_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RecyclableCategory::class, 'category_id');
    }

    // ── Helpers ────────────────────────────────────────────

    public function calculateSubtotal(): void
    {
        $weight = $this->actual_weight ?? $this->estimated_weight ?? 0;

        if ($this->category && $this->category->unit === 'unit') {
            $this->subtotal = $this->quantity * $this->price_per_unit;
        } else {
            $this->subtotal = $weight * $this->price_per_unit;
        }
    }
}
