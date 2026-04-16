<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\RecycleOrder;
use App\Models\Payment;

class NotificationService
{
    /**
     * Send notification when order is created.
     */
    public function notifyOrderCreated(RecycleOrder $order): void
    {
        // Notify customer
        $this->create(
            userId: $order->customer_id,
            title: 'Order Submitted',
            message: "Your recycle order #{$order->order_number} has been submitted successfully.",
            type: 'order_update',
            actionType: 'order',
            actionId: $order->id,
        );

        // Notify branch manager & staff
        $branch = $order->branch()->with('manager', 'staff')->first();

        if ($branch?->manager_id) {
            $this->create(
                userId: $branch->manager_id,
                title: 'New Recycle Order',
                message: "A new recycle order #{$order->order_number} has been received.",
                type: 'order_update',
                actionType: 'order',
                actionId: $order->id,
            );
        }
    }

    /**
     * Send notification when order status changes.
     */
    public function notifyOrderStatusChanged(RecycleOrder $order): void
    {
        $statusLabel = $order->status->label();

        $this->create(
            userId: $order->customer_id,
            title: "Order {$statusLabel}",
            message: "Your recycle order #{$order->order_number} has been updated to: {$statusLabel}.",
            type: 'order_update',
            actionType: 'order',
            actionId: $order->id,
            data: ['status' => $order->status->value],
        );
    }

    /**
     * Send notification when payment is processed.
     */
    public function notifyPaymentProcessed(Payment $payment): void
    {
        $statusLabel = $payment->status->label();

        $this->create(
            userId: $payment->user_id,
            title: "Payment {$statusLabel}",
            message: "Payment #{$payment->payment_number} of RM " . number_format($payment->amount, 2) . " has been {$statusLabel}.",
            type: 'payment',
            actionType: 'payment',
            actionId: $payment->id,
            data: ['status' => $payment->status->value, 'amount' => $payment->amount],
        );
    }

    /**
     * Create a notification record.
     */
    public function create(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        ?string $actionType = null,
        ?int $actionId = null,
        ?array $data = null,
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_type' => $actionType,
            'action_id' => $actionId,
            'data' => $data,
        ]);
    }
}
