<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\RecyclableCategory;
use App\Models\RecycleOrder;
use App\Models\RecycleOrderItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Create a new recycle order with items.
     */
    public function createOrder(array $data, int $customerId): RecycleOrder
    {
        return DB::transaction(function () use ($data, $customerId) {
            $order = RecycleOrder::create([
                'customer_id' => $customerId,
                'branch_id' => $data['branch_id'],
                'type' => $data['type'] ?? 'drop_off',
                'status' => OrderStatus::PENDING,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
                'pickup_address' => $data['pickup_address'] ?? null,
                'pickup_latitude' => $data['pickup_latitude'] ?? null,
                'pickup_longitude' => $data['pickup_longitude'] ?? null,
            ]);

            foreach ($data['items'] as $itemData) {
                $category = RecyclableCategory::findOrFail($itemData['category_id']);

                $item = new RecycleOrderItem([
                    'category_id' => $category->id,
                    'estimated_weight' => $itemData['estimated_weight'] ?? null,
                    'quantity' => $itemData['quantity'] ?? 1,
                    'price_per_unit' => $category->price_per_unit,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $order->items()->save($item);
            }

            $order->load('items');
            $order->calculateTotals();
            $order->save();

            // Send notification
            $this->notificationService->notifyOrderCreated($order);

            return $order->load(['items.category', 'branch', 'customer']);
        });
    }

    /**
     * Update order status with business logic.
     */
    public function updateStatus(RecycleOrder $order, OrderStatus $newStatus, array $data = []): RecycleOrder
    {
        if (!$order->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$order->status->value} to {$newStatus->value}"
            );
        }

        return DB::transaction(function () use ($order, $newStatus, $data) {
            $order->status = $newStatus;

            if (isset($data['staff_notes'])) {
                $order->staff_notes = $data['staff_notes'];
            }

            if ($newStatus === OrderStatus::REJECTED && isset($data['rejection_reason'])) {
                $order->rejection_reason = $data['rejection_reason'];
            }

            if ($newStatus === OrderStatus::ACCEPTED && auth()->check()) {
                $order->handled_by = auth()->id();
            }

            // Update actual weights if provided
            if (isset($data['items']) && in_array($newStatus, [OrderStatus::IN_PROGRESS, OrderStatus::COMPLETED])) {
                foreach ($data['items'] as $itemData) {
                    $item = RecycleOrderItem::find($itemData['id']);
                    if ($item && $item->recycle_order_id === $order->id) {
                        $item->actual_weight = $itemData['actual_weight'] ?? $item->actual_weight;
                        $item->save();
                    }
                }
                $order->load('items');
                $order->calculateTotals();
            }

            if ($newStatus === OrderStatus::COMPLETED) {
                $order->completed_at = now();

                // Add to inventory
                $this->inventoryService->stockInFromOrder($order);
            }

            $order->save();

            // Send notification
            $this->notificationService->notifyOrderStatusChanged($order);

            return $order->load(['items.category', 'branch', 'customer', 'handler', 'payment']);
        });
    }
}
