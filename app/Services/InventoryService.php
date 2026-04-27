<?php

namespace App\Services;

use App\Enums\InventoryType;
use App\Models\InventoryRecord;
use App\Models\RecycleOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Record stock in from a completed order.
     */
    public function stockInFromOrder(RecycleOrder $order): void
    {
        $order->load('items.category');

        foreach ($order->items as $item) {
            $quantity = $item->actual_weight ?? $item->estimated_weight ?? 0;

            if ($item->category && $item->category->unit === 'unit') {
                $quantity = $item->quantity;
            }

            $this->addRecord(
                branchId: $order->branch_id,
                categoryId: $item->category_id,
                type: InventoryType::STOCK_IN,
                quantity: $quantity,
                unit: $item->category->unit ?? 'kg',
                notes: "From order #{$order->order_number}",
                orderId: $order->id,
                recordedBy: Auth::id(),
            );
        }
    }

    /**
     * Add an inventory record and update running balance.
     */
    public function addRecord(
        int $branchId,
        int $categoryId,
        InventoryType $type,
        float $quantity,
        string $unit = 'kg',
        ?string $notes = null,
        ?int $orderId = null,
        ?int $recordedBy = null,
    ): InventoryRecord {
        return DB::transaction(function () use ($branchId, $categoryId, $type, $quantity, $unit, $notes, $orderId, $recordedBy) {
            // Get current balance
            $lastRecord = InventoryRecord::where('branch_id', $branchId)
                ->where('category_id', $categoryId)
                ->orderByDesc('id')
                ->first();

            $currentBalance = $lastRecord ? (float) $lastRecord->running_balance : 0;

            // Calculate new balance
            $newBalance = match ($type) {
                InventoryType::STOCK_IN => $currentBalance + $quantity,
                InventoryType::STOCK_OUT => $currentBalance - $quantity,
                InventoryType::ADJUSTMENT => $quantity, // absolute value
            };

            return InventoryRecord::create([
                'branch_id' => $branchId,
                'category_id' => $categoryId,
                'recycle_order_id' => $orderId,
                'type' => $type,
                'quantity' => $quantity,
                'unit' => $unit,
                'running_balance' => max(0, $newBalance),
                'notes' => $notes,
                'recorded_by' => $recordedBy,
            ]);
        });
    }

    /**
     * Get current stock levels for a branch.
     */
    public function getBranchStock(int $branchId): array
    {
        return InventoryRecord::where('branch_id', $branchId)
            ->select('category_id')
            ->selectRaw('MAX(id) as latest_id')
            ->groupBy('category_id')
            ->get()
            ->map(function ($record) {
                $latest = InventoryRecord::with('category')->find($record->latest_id);
                return [
                    'category_id' => $latest->category_id,
                    'category_name' => $latest->category?->name,
                    'unit' => $latest->unit,
                    'current_stock' => (float) $latest->running_balance,
                ];
            })
            ->toArray();
    }
}
