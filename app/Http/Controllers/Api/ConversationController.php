<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversationController extends Controller
{
    /**
     * Get all conversations for the authenticated user.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $conversations = $user->conversations() 
            ->with(['user1:id,first_name,last_name', 'user2:id,first_name,last_name'])
            ->with('latestMessage')
            ->orderByDesc('last_updated')
            ->paginate(15);

        return response()->json([
            'data' => $conversations
        ], 200);
    }

    /**
     * Get a specific conversation with its messages.
     */
    public function show($id): JsonResponse
    {
        $conversation = Conversation::findOrFail($id);
        $userId = Auth::id();

        // Ensure the user is part of this conversation
        if ($conversation->user1_id !== $userId && $conversation->user2_id !== $userId) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Load conversation with both users and all messages
        $conversation->load([
            'user1:id,first_name,last_name',
            'user2:id,first_name,last_name',
            'messages' => function ($query) {
                $query->with('sender:id,first_name,last_name')->orderBy('created_at', 'desc');
            }
        ]);

        return response()->json([
            'data' => $conversation
        ], 200);
    }
    public function searchChatFriends(Request $request)
{
    $request->validate(['query' => 'required|string|min:1']);
    $searchQuery = strtolower($request->input('query'));
    $user = $request->user();

    // 1. نجيب كل الـ User IDs اللي بيننا وبينهم صداقة مقبولة
    $friendIds = \App\Models\Friendship::where('status', 'accepted')
        ->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('friend_id', $user->id);
        })
        ->get()
        ->map(function ($f) use ($user) {
            return ($f->user_id == $user->id) ? $f->friend_id : $f->user_id;
        });

    $chatUserIds = \App\Models\Conversation::where('user1_id', $user->id)
        ->orWhere('user2_id', $user->id)
        ->get()
        ->map(function ($c) use ($user) {
            return ($c->user1_id == $user->id) ? $c->user2_id : $c->user1_id;
        });

    $allRelevantIds = $friendIds->concat($chatUserIds)->unique();

    $results = \App\Models\User::whereIn('id', $allRelevantIds)
        ->where(function($q) use ($searchQuery) {
            $q->where('first_name', 'LIKE', "%{$searchQuery}%")
              ->orWhere('last_name', 'LIKE', "%{$searchQuery}%");
        })
        ->select('id', 'first_name', 'last_name', 'profile_picture', 'diabetes_type')
        ->get()
        ->map(function ($user) {
            return [
                'id'              => $user->id,
                'first_name'      => $user->first_name,
                'last_name'       => $user->last_name,
'profile_picture' => $user->profile_picture 
    ? asset('storage/' . str_replace('storage/', '', $user->profile_picture)) 
    : null,
                    'diabetes_type'   => $user->diabetes_type,
            ];
        });

    return response()->json(['success' => true, 'results' => $results]);
}}