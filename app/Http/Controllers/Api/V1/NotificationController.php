<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display the authenticated user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->appNotifications()
            ->with('actor')
            ->latest()
            ->paginate($this->perPage($request, 30));

        $unread = $request->user()
            ->appNotifications()
            ->whereNull('read_at')
            ->count();

        return $this->paginated(
            'Notifications retrieved successfully.',
            $notifications,
            NotificationResource::class,
            ['unread_count' => $unread],
        );
    }

    /**
     * Mark a single notification as read.
     */
    public function read(Request $request, int $notification): JsonResponse
    {
        $item = $request->user()
            ->appNotifications()
            ->with('actor')
            ->findOrFail($notification);

        if ($item->read_at === null) {
            $item->update(['read_at' => now()]);
        }

        return $this->success('Notification marked as read.', (new NotificationResource($item))->resolve($request));
    }

    /**
     * Mark every notification as read.
     */
    public function readAll(Request $request): JsonResponse
    {
        $request->user()
            ->appNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success('All notifications marked as read.');
    }

    /**
     * Remove a notification.
     */
    public function destroy(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->delete();

        return $this->success('Notification deleted successfully.');
    }
}
