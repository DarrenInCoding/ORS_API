<?php

namespace App\Models;

use App\Enums\InventoryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRecord extends Model
{
    protected $fillable = [
        'branch_id',
        'category_id',
        'recycle_order_id',
        'type',
        'quantity',
        'unit',
        'running_balance',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => InventoryType::class,
            'quantity' => 'decimal:2',
            'running_balance' => 'decimal:2',
        ];
    }

    // ── Relationships ──────────────────────────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RecyclableCategory::class, 'category_id');
    }

    public function recycleOrder(): BelongsTo
    {
        return $this->belongsTo(RecycleOrder::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeStockIn($query)
    {
        return $query->where('type', InventoryType::STOCK_IN);
    }

    public function scopeStockOut($query)
    {
        return $query->where('type', InventoryType::STOCK_OUT);
    }
}
