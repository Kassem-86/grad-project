<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// تعديل القناة لتصبح chat.{conversationId}
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    // هنا يجب أن نتأكد أن اليوزر الحالي طرف في هذه المحادثة
    return Conversation::where('id', $conversationId)
        ->where(function ($query) use ($user) {
            $query->where('user1_id', $user->id)
                  ->orWhere('user2_id', $user->id);
        })->exists();
});