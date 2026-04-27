<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Requests\Payment\UpdatePaymentStatusRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\RecycleOrder;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    /**
     * List payments (filtered by role).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Payment::with(['order', 'user', 'processor']);

        if ($user->isCustomer()) {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status')) {
            $status = PaymentStatus::tryFrom($request->status);
            if ($status) $query->where('status', $status);
        }

        if ($request->has('search')) {
            $query->where('payment_number', 'like', "%{$request->search}%");
        }

        $payments = $query->latest()->paginate($request->per_page ?? 15);

        return $this->paginated($payments);
    }

    /**
     * Show a specific payment.
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();

        if ($user->isCustomer() && $payment->user_id !== $user->id) {
            return $this->forbidden('You can only view your own payments');
        }

        $payment->load(['order.items.category', 'user', 'processor']);

        return $this->success(new PaymentResource($payment));
    }

    /**
     * Process payment for a completed order.
     */
    public function processPayment(ProcessPaymentRequest $request, RecycleOrder $order): JsonResponse
    {
        // Check if order is completed
        if ($order->status->value !== 'completed') {
            return $this->error('Payment can only be processed for completed orders', 422);
        }

        // Check if payment already exists
        if ($order->payment) {
            return $this->error('Payment already exists for this order', 422);
        }

        $payment = Payment::create([
            'recycle_order_id' => $order->id,
            'user_id' => $order->customer_id,
            'amount' => $order->total_amount,
            'payment_method' => $request->payment_method,
            'status' => PaymentStatus::PROCESSING,
            'reference_number' => $request->reference_number,
            'notes' => $request->notes,
            'processed_by' => $request->user()->id,
        ]);

        $payment->load(['order', 'user', 'processor']);

        $this->notificationService->notifyPaymentProcessed($payment);

        return $this->created(
            new PaymentResource($payment),
            'Payment created and processing'
        );
    }

    /**
     * Update payment status.
     */
    public function updateStatus(UpdatePaymentStatusRequest $request, Payment $payment): JsonResponse
    {
        $newStatus = PaymentStatus::from($request->status);

        $payment->status = $newStatus;

        if ($request->has('reference_number')) {
            $payment->reference_number = $request->reference_number;
        }

        if ($request->has('notes')) {
            $payment->notes = $request->notes;
        }

        if ($newStatus === PaymentStatus::COMPLETED) {
            $payment->paid_at = now();
        }

        $payment->save();

        $this->notificationService->notifyPaymentProcessed($payment);

        return $this->success(
            new PaymentResource($payment->fresh()->load(['order', 'user'])),
            "Payment status updated to {$newStatus->label()}"
        );
    }
}
