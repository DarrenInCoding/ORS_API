<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * List user notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->notifications();

        if ($request->boolean('unread_only', false)) {
            $query->unread();
        }

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        $notifications = $query->latest()->paginate($request->per_page ?? 20);

        return $this->paginated($notifications);
    }

    /**
     * Get unread count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->notifications()->unread()->count();

        return $this->success(['unread_count' => $count]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return $this->forbidden();
        }

        $notification->markAsRead();

        return $this->success(new NotificationResource($notification));
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->notifications()->unread()->update(['read_at' => now()]);

        return $this->success(message: 'All notifications marked as read');
    }

    /**
     * Delete a notification.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return $this->forbidden();
        }

        $notification->delete();

        return $this->success(message: 'Notification deleted');
    }
}
