<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryRequest;
use App\Http\Resources\InventoryRecordResource;
use App\Enums\InventoryType;
use App\Models\InventoryRecord;
use App\Services\InventoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected InventoryService $inventoryService,
    ) {}

    /**
     * List inventory records.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryRecord::with(['branch', 'category', 'recorder']);

        if ($request->has('branch_id')) {
            $query->forBranch($request->branch_id);
        }

        if ($request->has('category_id')) {
            $query->forCategory($request->category_id);
        }

        if ($request->has('type')) {
            $type = InventoryType::tryFrom($request->type);
            if ($type) $query->where('type', $type);
        }

        $records = $query->latest()->paginate($request->per_page ?? 15);

        return $this->paginated($records);
    }

    /**
     * Create a manual inventory record.
     */
    public function store(StoreInventoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $record = $this->inventoryService->addRecord(
            branchId: $data['branch_id'],
            categoryId: $data['category_id'],
            type: InventoryType::from($data['type']),
            quantity: $data['quantity'],
            unit: $data['unit'] ?? 'kg',
            notes: $data['notes'] ?? null,
            recordedBy: $request->user()->id,
        );

        $record->load(['branch', 'category', 'recorder']);

        return $this->created(
            new InventoryRecordResource($record),
            'Inventory record created successfully'
        );
    }

    /**
     * Get current stock levels for a branch.
     */
    public function branchStock(int $branchId): JsonResponse
    {
        $stock = $this->inventoryService->getBranchStock($branchId);

        return $this->success($stock);
    }

    /**
     * Show a specific inventory record.
     */
    public function show(InventoryRecord $inventoryRecord): JsonResponse
    {
        $inventoryRecord->load(['branch', 'category', 'recycleOrder', 'recorder']);

        return $this->success(new InventoryRecordResource($inventoryRecord));
    }
}
