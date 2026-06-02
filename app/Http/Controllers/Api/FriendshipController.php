<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function sendRequest(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // 1. ممنوع تبعت لنفسك
        if ($user->id === $id) {
            return response()->json(['message' => 'You cannot send a friend request to yourself.'], 403);
        }

        // 2. التأكد إن مفيش بلوك بين الطرفين (سواء هو حظرك أو أنت حظرته)
        $restrictedIds = $user->getRestrictedUserIds();
        if (in_array($id, $restrictedIds)) {
            return response()->json(['message' => 'Action forbidden due to block settings.'], 403);
        }

        User::findOrFail($id);

        $exists = Friendship::where(function ($query) use ($user, $id) {
            $query->where('user_id', $user->id)
                  ->where('friend_id', $id);
        })->orWhere(function ($query) use ($user, $id) {
            $query->where('user_id', $id)
                  ->where('friend_id', $user->id);
        })->exists();

        if ($exists) {
            return response()->json(['message' => 'A friend request or friendship already exists.'], 409);
        }

        Friendship::create([
            'user_id' => $user->id,
            'friend_id' => $id,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Friend request sent successfully.'], 201);
    }

    public function acceptRequest(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // تشيك سريع برضه عند القبول (زيادة أمان)
        if (in_array($id, $user->getRestrictedUserIds())) {
            return response()->json(['message' => 'Action forbidden due to block settings.'], 403);
        }

        $friendship = Friendship::where('user_id', $id)
            ->where('friend_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $friendship) {
            return response()->json(['message' => 'Pending friend request not found.'], 404);
        }

        $friendship->update(['status' => 'accepted']);

        return response()->json(['message' => 'Friend request accepted.']);
    }

    public function removeFriend(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;

        $friendship = Friendship::where(function ($query) use ($userId, $id) {
            $query->where('user_id', $userId)
                ->where('friend_id', $id);
        })->orWhere(function ($query) use ($userId, $id) {
            $query->where('user_id', $id)
                ->where('friend_id', $userId);
        })->first();

        if (! $friendship) {
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

        if (! $friendship) {
            return response()->json(['message' => 'Pending friend request not found.'], 404);
        }

        $friendship->delete();

        return response()->json(['message' => 'Friend request cancelled successfully.']);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $user = $request->user();
        $restrictedUserIds = $user->getRestrictedUserIds();

        // Get friend IDs
        $friendIds = $user->friends()->pluck('id')->toArray();

        // Exclude self, friends, and restricted users
        $excludeIds = array_unique(array_merge([$user->id], $friendIds, $restrictedUserIds));

        $suggestions = User::whereNotIn('id', $excludeIds)
            ->inRandomOrder()
            ->limit(10)
            ->get(['id', 'first_name', 'last_name']);

        return response()->json($suggestions);
    }


    public function sendFriendRequest(Request $request)
{
    $sender = auth()->user();
    $receiverId = $request->receiver_id;

    // 1. كود إرسال طلب الصداقة العادي بتاعك في الداتابيز
    $friendship = Friendship::create([
        'user_id' => $sender->id,
        'friend_id' => $receiverId,
        'status' => 'pending'
    ]);

    // 2. سطر الإشعار السحري لطلب الصداقة 🚀
    \App\Models\Notification::create([
        'user_id' => $receiverId, // المستلم: الشخص اللي جاله طلب الصداقة
        'title' => 'New Friend Request 👥',
        'message' => $sender->first_name . ' sent you a friend request.',
        'type' => 'friend_request',
        'reference_id' => $sender->id, // الـ reference هنا هو الـ ID بتاع اللي بعت، عشان لما يضغط عليه يفتح بروفايله علطول
    ]);

    return response()->json(['message' => 'Friend request sent successfully']);
}

public function acceptFriendRequest(Request $request, $senderId)
{
    $receiver = auth()->user();

    // 1. كود تحديث الحالة لـ accepted عندك في الداتابيز
    $friendship = Friendship::where('user_id', $senderId)
                            ->where('friend_id', $receiver->id)
                            ->update(['status' => 'accepted']);

    // 2. إشعار لليوزر الأولاني إن تامر وافق على طلبك 🚀
    \App\Models\Notification::create([
        'user_id' => $senderId, // المستقبل: الشخص اللي كان باعت الطلب في الأول
        'title' => 'تم قبول طلب الصداقة ✨',
        'message' => $receiver->first_name . ' وافق على طلب الصداقة الخاص بك.',
        'type' => 'friend_request', // بتفضل نفس النوع عشان الأندرويد يفتح البروفايل برضه
        'reference_id' => $receiver->id, // بيوديه لبروفايل الشخص اللي وافق
    ]);

    return response()->json(['message' => 'Friend request accepted']);
}
}