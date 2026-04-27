<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\RecycleOrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecycleOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'customer_id',
        'branch_id',
        'handled_by',
        'type',
        'status',
        'scheduled_at',
        'completed_at',
        'total_weight',
        'total_amount',
        'customer_notes',
        'staff_notes',
        'rejection_reason',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_weight' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'pickup_latitude' => 'decimal:8',
            'pickup_longitude' => 'decimal:8',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $lastOrder = self::whereDate('created_at', today())
            ->orderByDesc('id')
            ->first();

        $sequence = $lastOrder
            ? (int) substr($lastOrder->order_number, -4) + 1
            : 1;

        return sprintf('ORD-%s-%04d', $date, $sequence);
    }

    // ── Relationships ──────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecycleOrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    // ── Helpers ────────────────────────────────────────────

    public function calculateTotals(): void
    {
        $this->total_weight = $this->items->sum(function ($item) {
            return $item->actual_weight ?? $item->estimated_weight ?? 0;
        });

        $this->total_amount = $this->items->sum('subtotal');
    }

    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return $this->status->canTransitionTo($newStatus);
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeWithStatus($query, OrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', OrderStatus::COMPLETED);
    }
}
