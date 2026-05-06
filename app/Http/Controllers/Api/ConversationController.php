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
}
