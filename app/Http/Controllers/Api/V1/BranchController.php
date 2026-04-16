<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\AssignStaffRequest;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BranchController extends Controller
{
    use ApiResponse;

    /**
     * List all branches.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Branch::with('manager');

        // Active only filter
        if ($request->boolean('active_only', false)) {
            $query->active();
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by city
        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        // Nearby branches
        if ($request->has('latitude') && $request->has('longitude')) {
            $query->nearby(
                (float) $request->latitude,
                (float) $request->longitude,
                (float) ($request->radius ?? 25)
            );
        }

        $branches = $query->latest()->paginate($request->per_page ?? 15);

        return $this->paginated($branches);
    }

    /**
     * Store a new branch.
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('branches', 'public');
        }

        $branch = Branch::create($data);

        return $this->created(
            new BranchResource($branch->load('manager')),
            'Branch created successfully'
        );
    }

    /**
     * Show a specific branch.
     */
    public function show(Branch $branch): JsonResponse
    {
        $branch->load(['manager', 'staff']);
        $branch->loadCount(['recycleOrders', 'staff']);

        return $this->success(new BranchResource($branch));
    }

    /**
     * Update a branch.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($branch->image) {
                Storage::disk('public')->delete($branch->image);
            }
            $data['image'] = $request->file('image')->store('branches', 'public');
        }

        $branch->update($data);

        return $this->success(
            new BranchResource($branch->fresh()->load('manager')),
            'Branch updated successfully'
        );
    }

    /**
     * Delete a branch (soft delete).
     */
    public function destroy(Branch $branch): JsonResponse
    {
        $branch->delete();

        return $this->success(message: 'Branch deleted successfully');
    }

    /**
     * Assign staff to a branch.
     */
    public function assignStaff(AssignStaffRequest $request, Branch $branch): JsonResponse
    {
        $branch->staff()->syncWithoutDetaching([
            $request->user_id => [
                'position' => $request->position,
                'assigned_at' => now()->toDateString(),
            ]
        ]);

        return $this->success(
            new BranchResource($branch->fresh()->load('staff')),
            'Staff assigned successfully'
        );
    }

    /**
     * Remove staff from a branch.
     */
    public function removeStaff(Branch $branch, int $userId): JsonResponse
    {
        $branch->staff()->detach($userId);

        return $this->success(
            new BranchResource($branch->fresh()->load('staff')),
            'Staff removed successfully'
        );
    }

    /**
     * Get branch staff list.
     */
    public function staff(Branch $branch): JsonResponse
    {
        $staff = $branch->staff()->with('assignedBranches')->get();

        return $this->success($staff->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role->value,
            'position' => $user->pivot->position,
            'assigned_at' => $user->pivot->assigned_at,
        ]));
    }
}
