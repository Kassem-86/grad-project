<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Fetch the authenticated user's notifications.
     */
    public function index(Request $request)
    {
        $user = auth('sanctum')->user();

        // Fetch notifications ordered by newest
        $notifications = Notification::where('user_id', $user->id)
        ->with(['sender:id,first_name,last_name,profile_picture'])
            ->latest()
            ->paginate(20);

        // Get total unread count
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $resourceCollection = NotificationResource::collection($notifications)->response()->getData(true);

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $resourceCollection['data'],
            'links' => $resourceCollection['links'] ?? null,
            'meta' => $resourceCollection['meta'] ?? null,
        ]);

        
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $user = auth('sanctum')->user();

        $notification = Notification::where('user_id', $user->id)
            ->where('notification_id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,                                                      
                'message' => 'Notification not found or unauthorized'
            ], 404);
        }

        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => new NotificationResource($notification)
        ]);
    }

    /**
     * Bulk update all unread notifications for the user to read.
     */
    public function markAllAsRead()
    {
        $user = auth('sanctum')->user();

        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Delete a specific notification.
     */
    public function destroy($id)
    {
        $user = auth('sanctum')->user();

        // ابحث عن الإشعار الخاص باليوزر الحالي فقط لضمان الأمان
        $notification = Notification::where('user_id', $user->id)
            ->where('notification_id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found or unauthorized'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }
}
