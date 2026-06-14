<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FriendshipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * دالة موحدة لإرسال طلب الصداقة مع الفحوصات الأمنية والإشعارات
     */
    public function sendRequest(Request $request, int $id): JsonResponse
    {
        $sender = $request->user();

        // 1. الفحوصات الأمنية
        if ($sender->id === $id) {
            return response()->json(['message' => 'You cannot send a friend request to yourself.'], 403);
        }

        $restrictedIds = $sender->getRestrictedUserIds();
        if (in_array($id, $restrictedIds)) {
            return response()->json(['message' => 'Action forbidden due to block settings.'], 403);
        }

        // 2. التحقق من وجود طلب سابق أو صداقة
        $exists = Friendship::where(function ($query) use ($sender, $id) {
            $query->where('user_id', $sender->id)->where('friend_id', $id);
        })->orWhere(function ($query) use ($sender, $id) {
            $query->where('user_id', $id)->where('friend_id', $sender->id);
        })->exists();

        if ($exists) {
            return response()->json(['message' => 'A friend request or friendship already exists.'], 409);
        }

        // 3. تنفيذ الطلب داخل Transaction لضمان سلامة البيانات
        DB::transaction(function () use ($sender, $id) {
            Friendship::create([
                'user_id' => $sender->id,
                'friend_id' => $id,
                'status' => 'pending',
            ]);

            // إرسال الإشعار
            Notification::create([
                'user_id' => $id,
                'title' => 'New Friend Request 👥',
                'message' => $sender->first_name . ' sent you a friend request.',
                'type' => 'friend_request',
                'reference_id' => $sender->id,
            ]);
        });

        return response()->json(['message' => 'Friend request sent successfully.'], 201);
    }

    public function acceptRequest(Request $request, int $senderId): JsonResponse
    {
        $receiver = $request->user();

        $friendship = Friendship::where('user_id', $senderId)
            ->where('friend_id', $receiver->id)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json(['message' => 'Pending friend request not found.'], 404);
        }

        DB::transaction(function () use ($friendship, $receiver, $senderId) {
            $friendship->update(['status' => 'accepted']);

            Notification::create([
                'user_id' => $senderId,
                'title' => 'تم قبول طلب الصداقة ✨',
                'message' => $receiver->first_name . ' وافق على طلب الصداقة الخاص بك.',
                'type' => 'friend_request',
                'reference_id' => $receiver->id,
            ]);
        });

        return response()->json(['message' => 'Friend request accepted.']);
    }

    public function removeFriend(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $friendship = Friendship::where(function ($query) use ($userId, $id) {
            $query->where('user_id', $userId)->where('friend_id', $id);
        })->orWhere(function ($query) use ($userId, $id) {
            $query->where('user_id', $id)->where('friend_id', $userId);
        })->first();

        if (!$friendship) {
            return response()->json(['message' => 'Friendship not found.'], 404);
        }
        $friendship->delete();

        return response()->json(['message' => 'Friend removed successfully.']);
    }

    public function cancelRequest(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $friendship = Friendship::where('user_id', $userId)
            ->where('friend_id', $id)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json(['message' => 'Pending friend request not found.'], 404);
        }

        $friendship->delete();

        return response()->json(['message' => 'Friend request cancelled successfully.']);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $user = $request->user();
        $restrictedUserIds = $user->getRestrictedUserIds();
        $friendIds = $user->friends()->pluck('id')->toArray();

        $excludeIds = array_unique(array_merge([$user->id], $friendIds, $restrictedUserIds));

        $suggestions = User::whereNotIn('id', $excludeIds)
            ->inRandomOrder()
            ->limit(10)
            ->get(['id', 'first_name', 'last_name']);

        return response()->json($suggestions);
    }

//    public function getFriends(Request $request): JsonResponse
// {
//     $user = $request->user();

//     $friends = Friendship::where('status', 'accepted')
//         ->where(function ($query) use ($user) {
//             $query->where('user_id', $user->id)
//                   ->orWhere('friend_id', $user->id);
//         })
//         // أضفنا profile_pic هنا في العلاقات
//         ->with([
//             'user:id,first_name,last_name,profile_picture,diabetes_type',
//             'friend:id,first_name,last_name,profile_picture,diabetes_type'
//         ])
//         ->get()
//         ->map(function ($friendship) use ($user) {
//             return $friendship->user_id === $user->id 
//                 ? $friendship->friend 
//                 : $friendship->user;
//         });

//     return response()->json([
//         'count' => $friends->count(),
//         'data' => $friends
//     ]);
// }

public function getFriends(Request $request)
{
    $request->validate([
        'query' => 'required|string|min:1',
    ]);

    $searchQuery = $request->input('query');
    $user = $request->user();

    // بنجيب الصداقات اللي فيها الشخص الحالي و الـ status بتاعها accepted
    $friends = \App\Models\Friendship::where('status', 'accepted')
        ->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('friend_id', $user->id);
        })
        ->with(['user:id,first_name,last_name,profile_picture,diabetes_type', 'friend:id,first_name,last_name,profile_picture,diabetes_type'])
        ->get()
        // بنحول الـ Friendship لـ User Object بتاع الصديق
        ->map(function ($friendship) use ($user) {
            return ($friendship->user_id === $user->id) ? $friendship->friend : $friendship->user;
        })
        // بنفلتر هنا بالاسم اللي المستخدم كتبه
        ->filter(function ($friend) use ($searchQuery) {
            $fullName = strtolower($friend->first_name . ' ' . $friend->last_name);
            return str_contains($fullName, strtolower($searchQuery));
        })
        ->values();

    return response()->json([
        'success' => true,
        'results' => $friends // بيرجع قائمة الأصدقاء اللي ينفع تفتح معاهم شات
    ]);
}
}