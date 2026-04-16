<?php

namespace App\Services;

use App\Models\RecyclableCategory;
use App\Models\RecycleOrder;
use App\Models\RecycleOrderItem;

class PricingService
{
    /**
     * Calculate subtotal for an order item.
     */
    public function calculateItemSubtotal(RecycleOrderItem $item): float
    {
        $weight = $item->actual_weight ?? $item->estimated_weight ?? 0;
        $category = $item->category;

        if ($category && $category->unit === 'unit') {
            return $item->quantity * $item->price_per_unit;
        }

        return $weight * $item->price_per_unit;
    }

    /**
     * Recalculate order totals.
     */
    public function recalculateOrderTotals(RecycleOrder $order): void
    {
        $order->load('items');
        $order->calculateTotals();
        $order->save();
    }

    /**
     * Get price estimate for given items.
     */
    public function getEstimate(array $items): array
    {
        $totalEstimate = 0;
        $breakdown = [];

        foreach ($items as $itemData) {
            $category = RecyclableCategory::find($itemData['category_id']);
            if (!$category) continue;

            $weight = $itemData['estimated_weight'] ?? 0;
            $quantity = $itemData['quantity'] ?? 1;

            if ($category->unit === 'unit') {
                $subtotal = $quantity * $category->price_per_unit;
            } else {
                $subtotal = $weight * $category->price_per_unit;
            }

            $breakdown[] = [
                'category' => $category->name,
                'unit' => $category->unit,
                'price_per_unit' => (float) $category->price_per_unit,
                'weight' => (float) $weight,
                'quantity' => $quantity,
                'subtotal' => round($subtotal, 2),
            ];

            $totalEstimate += $subtotal;
        }

        return [
            'items' => $breakdown,
            'total_estimate' => round($totalEstimate, 2),
        ];
    }
}
