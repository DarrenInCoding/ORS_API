<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReportService $reportService,
    ) {}

    /**
     * Admin dashboard overview.
     */
    public function adminDashboard(): JsonResponse
    {
        return $this->success($this->reportService->getAdminDashboard());
    }

    /**
     * Branch dashboard overview.
     */
    public function branchDashboard(Request $request, int $branchId): JsonResponse
    {
        return $this->success($this->reportService->getBranchDashboard($branchId));
    }

    /**
     * Collection reports.
     */
    public function collectionReport(Request $request): JsonResponse
    {
        $request->validate([
            'period' => ['nullable', 'string', 'in:daily,weekly,monthly'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $report = $this->reportService->getCollectionReport(
            period: $request->period ?? 'daily',
            branchId: $request->branch_id,
            startDate: $request->start_date,
            endDate: $request->end_date,
        );

        return $this->success($report);
    }

    /**
     * Order statistics.
     */
    public function orderStats(Request $request): JsonResponse
    {
        return $this->success(
            $this->reportService->getOrderStats($request->branch_id)
        );
    }

    /**
     * Revenue statistics.
     */
    public function revenueStats(Request $request): JsonResponse
    {
        return $this->success(
            $this->reportService->getRevenueStats($request->branch_id)
        );
    }
}
