<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AdminUpdateUserRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * List all users (Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($request->per_page ?? 15);

        return $this->paginated($users);
    }

    /**
     * Get a specific user (Admin only).
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['socialAccounts', 'managedBranch', 'assignedBranches']);

        return $this->success(new UserResource($user));
    }

    /**
     * Update current user's profile.
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return $this->success(new UserResource($user->fresh()), 'Profile updated successfully');
    }

    /**
     * Admin update user (role, status, etc.).
     */
    public function adminUpdate(AdminUpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        $user->update($data);

        return $this->success(new UserResource($user->fresh()), 'User updated successfully');
    }

    /**
     * Admin delete (soft-delete) a user.
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return $this->error('You cannot delete your own account', 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return $this->success(message: 'User deleted successfully');
    }
}
