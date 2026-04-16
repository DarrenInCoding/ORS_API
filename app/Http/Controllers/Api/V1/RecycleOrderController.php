<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\RecycleOrderResource;
use App\Models\RecycleOrder;
use App\Services\OrderService;
use App\Services\PricingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecycleOrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OrderService $orderService,
        protected PricingService $pricingService,
    ) {}

    /**
     * List orders (filtered by role).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = RecycleOrder::with(['customer', 'branch', 'items.category', 'payment']);

        // Role-based filtering
        if ($user->isCustomer()) {
            $query->forCustomer($user->id);
        } elseif ($user->isBranchManager()) {
            $branchIds = $user->managedBranch ? [$user->managedBranch->id] : [];
            $query->whereIn('branch_id', $branchIds);
        } elseif ($user->isStaff()) {
            $branchIds = $user->assignedBranches->pluck('id')->toArray();
            $query->whereIn('branch_id', $branchIds);
        }
        // Admin sees all

        // Filter by status
        if ($request->has('status')) {
            $status = OrderStatus::tryFrom($request->status);
            if ($status) $query->withStatus($status);
        }

        // Filter by branch
        if ($request->has('branch_id')) {
            $query->forBranch($request->branch_id);
        }

        // Filter by date range
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        // Search by order number
        if ($request->has('search')) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }

        $orders = $query->latest()->paginate($request->per_page ?? 15);

        return $this->paginated($orders);
    }

    /**
     * Create a new recycle order.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder(
            $request->validated(),
            $request->user()->id
        );

        return $this->created(
            new RecycleOrderResource($order),
            'Recycle order created successfully'
        );
    }

    /**
     * Show a specific order.
     */
    public function show(Request $request, RecycleOrder $order): JsonResponse
    {
        $user = $request->user();

        // Authorization: customer can only see their own orders
        if ($user->isCustomer() && $order->customer_id !== $user->id) {
            return $this->forbidden('You can only view your own orders');
        }

        $order->load(['customer', 'branch', 'handler', 'items.category', 'payment']);

        return $this->success(new RecycleOrderResource($order));
    }

    /**
     * Update order status (staff/manager/admin).
     */
    public function updateStatus(UpdateOrderStatusRequest $request, RecycleOrder $order): JsonResponse
    {
        $newStatus = OrderStatus::from($request->status);

        try {
            $updatedOrder = $this->orderService->updateStatus(
                $order,
                $newStatus,
                $request->validated()
            );

            return $this->success(
                new RecycleOrderResource($updatedOrder),
                "Order status updated to {$newStatus->label()}"
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Cancel an order (customer or admin).
     */
    public function cancel(Request $request, RecycleOrder $order): JsonResponse
    {
        $user = $request->user();

        // Customer can only cancel their own pending orders
        if ($user->isCustomer()) {
            if ($order->customer_id !== $user->id) {
                return $this->forbidden('You can only cancel your own orders');
            }
            if ($order->status !== OrderStatus::PENDING) {
                return $this->error('You can only cancel pending orders', 422);
            }
        }

        try {
            $updatedOrder = $this->orderService->updateStatus(
                $order,
                OrderStatus::CANCELLED,
                ['staff_notes' => $request->reason ?? 'Cancelled by ' . ($user->isCustomer() ? 'customer' : 'admin')]
            );

            return $this->success(
                new RecycleOrderResource($updatedOrder),
                'Order cancelled successfully'
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Get price estimate without creating order.
     */
    public function estimate(Request $request): JsonResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => ['required', 'exists:recyclable_categories,id'],
            'items.*.estimated_weight' => ['nullable', 'numeric', 'min:0'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $estimate = $this->pricingService->getEstimate($request->items);

        return $this->success($estimate);
    }
}
