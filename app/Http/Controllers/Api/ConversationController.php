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
    $request->validate([
        'query' => 'required|string|min:1', // الحروف اللي بيبحث بيها
    ]);

    $user = $request->user();
    $searchQuery = $request->input('query');

    // سحب المحادثات اللي اليوزر الحالي طرف فيها، ومفلترة باسم الصاحب
    $conversations = \App\Models\Conversation::where(function($q) use ($user) {
            $q->where('user_one_id', $user->id)
              ->orWhere('user_two_id', $user->id);
        })
        ->get()
        ->map(function($conversation) use ($user, $searchQuery) {
            // تحديد مين الطرف التاني في المحادثة (الصاحب)
            $friendId = ($conversation->user_one_id == $user->id) ? $conversation->user_two_id : $conversation->user_one_id;
            $friend = \App\Models\User::find($friendId);

            if ($friend) {
                $fullName = strtolower($friend->first_name . ' ' . $friend->last_name);
                // تشييك لو اسم الصاحب جواه حروف البحث
                if (str_contains($fullName, strtolower($searchQuery))) {
                    return [
                        'conversation_id' => $conversation->id,
                        'friend_id'       => $friend->id,
                        'friend_name'     => $friend->first_name . ' ' . $friend->last_name,
                        'last_message'    => $conversation->last_message ?? '', // لو مخزن آخر رسالة
                        'updated_at'      => $conversation->updated_at,
                    ];
                }
            }
            return null;
        })
        ->filter() // شيل أي محادثة متطابقتش مع البحث
        ->values();

    return response()->json([
        'success' => true,
        'results' => $conversations
    ], 200);
}
}
