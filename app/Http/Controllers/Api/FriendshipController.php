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
}