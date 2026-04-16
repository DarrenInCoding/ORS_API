<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number',
        'recycle_order_id',
        'user_id',
        'amount',
        'payment_method',
        'status',
        'reference_number',
        'notes',
        'paid_at',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }
        });
    }

    public static function generatePaymentNumber(): string
    {
        $date = now()->format('Ymd');
        $lastPayment = self::whereDate('created_at', today())
            ->orderByDesc('id')
            ->first();

        $sequence = $lastPayment
            ? (int) substr($lastPayment->payment_number, -4) + 1
            : 1;

        return sprintf('PAY-%s-%04d', $date, $sequence);
    }

    // ── Relationships ──────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(RecycleOrder::class, 'recycle_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }
}
