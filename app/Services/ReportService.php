<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\RecyclableCategory;
use App\Models\RecycleOrder;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReportService
{
    /**
     * Get admin dashboard statistics.
     */
    public function getAdminDashboard(): array
    {
        return [
            'total_users' => User::count(),
            'total_customers' => User::where('role', 'customer')->count(),
            'total_staff' => User::whereIn('role', ['staff', 'branch_manager'])->count(),
            'total_branches' => Branch::count(),
            'active_branches' => Branch::where('is_active', true)->count(),
            'total_categories' => RecyclableCategory::count(),
            'orders' => $this->getOrderStats(),
            'revenue' => $this->getRevenueStats(),
            'recent_orders' => RecycleOrder::with(['customer', 'branch'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn($order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer' => $order->customer?->name,
                    'branch' => $order->branch?->name,
                    'status' => $order->status->value,
                    'total_amount' => (float) $order->total_amount,
                    'created_at' => $order->created_at->toISOString(),
                ]),
        ];
    }

    /**
     * Get branch dashboard statistics.
     */
    public function getBranchDashboard(int $branchId): array
    {
        return [
            'orders' => $this->getOrderStats($branchId),
            'revenue' => $this->getRevenueStats($branchId),
            'pending_orders' => RecycleOrder::where('branch_id', $branchId)
                ->where('status', OrderStatus::PENDING)
                ->count(),
            'today_orders' => RecycleOrder::where('branch_id', $branchId)
                ->whereDate('created_at', today())
                ->count(),
            'inventory_summary' => app(InventoryService::class)->getBranchStock($branchId),
            'recent_orders' => RecycleOrder::with(['customer'])
                ->where('branch_id', $branchId)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn($order) => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer' => $order->customer?->name,
                    'status' => $order->status->value,
                    'total_amount' => (float) $order->total_amount,
                    'created_at' => $order->created_at->toISOString(),
                ]),
        ];
    }

    /**
     * Get order statistics.
     */
    public function getOrderStats(?int $branchId = null): array
    {
        $query = RecycleOrder::query();
        if ($branchId) $query->where('branch_id', $branchId);

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', OrderStatus::PENDING)->count(),
            'accepted' => (clone $query)->where('status', OrderStatus::ACCEPTED)->count(),
            'in_progress' => (clone $query)->where('status', OrderStatus::IN_PROGRESS)->count(),
            'completed' => (clone $query)->where('status', OrderStatus::COMPLETED)->count(),
            'rejected' => (clone $query)->where('status', OrderStatus::REJECTED)->count(),
            'cancelled' => (clone $query)->where('status', OrderStatus::CANCELLED)->count(),
            'today' => (clone $query)->whereDate('created_at', today())->count(),
            'this_week' => (clone $query)->whereBetween('created_at', [
                now()->startOfWeek(), now()->endOfWeek()
            ])->count(),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)->count(),
        ];
    }

    /**
     * Get revenue statistics.
     */
    public function getRevenueStats(?int $branchId = null): array
    {
        $paymentQuery = Payment::where('status', PaymentStatus::COMPLETED);
        if ($branchId) {
            $paymentQuery->whereHas('order', fn($q) => $q->where('branch_id', $branchId));
        }

        return [
            'total' => (float) (clone $paymentQuery)->sum('amount'),
            'today' => (float) (clone $paymentQuery)->whereDate('paid_at', today())->sum('amount'),
            'this_week' => (float) (clone $paymentQuery)->whereBetween('paid_at', [
                now()->startOfWeek(), now()->endOfWeek()
            ])->sum('amount'),
            'this_month' => (float) (clone $paymentQuery)
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
        ];
    }

    /**
     * Get collection report by period.
     */
    public function getCollectionReport(string $period = 'daily', ?int $branchId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate) : now()->subDays(30);
        $end = $endDate ? Carbon::parse($endDate) : now();

        $query = RecycleOrder::where('status', OrderStatus::COMPLETED)
            ->whereBetween('completed_at', [$start, $end]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $groupFormat = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return $query
            ->selectRaw("DATE_FORMAT(completed_at, '{$groupFormat}') as period")
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(total_weight) as total_weight')
            ->selectRaw('SUM(total_amount) as total_amount')
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn($item) => [
                'period' => $item->period,
                'total_orders' => $item->total_orders,
                'total_weight' => (float) $item->total_weight,
                'total_amount' => (float) $item->total_amount,
            ])
            ->toArray();
    }
}
