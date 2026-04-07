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

        if ($user->id === $id) {
            return response()->json(['message' => 'You cannot send a friend request to yourself.'], 403);
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
        $friendship = Friendship::where('user_id', $id)
            ->where('friend_id', $request->user()->id)
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
}
