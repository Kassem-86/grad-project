<?php

namespace App\Http\Controllers\Api;

use App\Events\DeleteMessageEvent;
use App\Events\MessageSeen;
use App\Events\MessageSent;
use App\Events\UpdateMessageEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Models\Friendship;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    /**
     * Get old messages when opening a chat.
     */
    public function index($receiver_id): JsonResponse
    {
        $authId = Auth::id();

        // Find the conversation ID between the two users
        $conversation = Conversation::where(function ($query) use ($authId, $receiver_id) {
            $query->where('user1_id', $authId)->where('user2_id', $receiver_id);
        })->orWhere(function ($query) use ($authId, $receiver_id) {
            $query->where('user1_id', $receiver_id)->where('user2_id', $authId);
        })->first();

        if (!$conversation) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        // Fetch paginated messages sorted by created_at ascending
        $messages = \App\Models\ChatMessage::where('conversation_id', $conversation->id)
            ->with(['sender:id,first_name,last_name,profile_picture']) // بنجيب الصورة مع بيانات المرسل
            ->orderBy('created_at', 'asc')
            ->paginate(50); // or any limit you prefer

        return response()->json($messages);
    }

    /**
     * Store a new message in a conversation.
     */public function store(Request $request): JsonResponse
    {
        // Robust validation with custom rules
        $validator = Validator::make($request->all(), [
            'receiver_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ((int)$value === Auth::id()) {
                        $fail('You cannot send a message to yourself.');
                    }
                }
            ],
            'message' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'voice' => 'nullable|file|mimes:mp3,wav,m4a|max:10240',
            'video' => 'nullable|file|mimes:mp4,avi,mov,mkv,flv,wmv,webm,m4v,3gp,mpg,mpeg,ts,m3u8,mts,m2ts,mxf,ogv,vob,f4v,asf,rm,rmvb,divx,dv,m2v,mts,mpeg1,mpeg2|max:102400',
        ]);

        // Custom validation: message is required unless an image, voice, or video is sent
        $validator->after(function ($validator) use ($request) {
            if (!$request->filled('message') && !$request->hasFile('image') && !$request->hasFile('voice') && !$request->hasFile('video')) {
                $validator->errors()->add('content', 'At least one of the following is required: message, image, voice, or video.');
            }
        });

        // Return clean JSON error response if validation fails (422 Unprocessable Entity)
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $sender = Auth::user();
        $receiverId = $request->receiver_id;

        // Check Block
        $restrictedIds = $sender->getRestrictedUserIds();
        if (in_array($receiverId, $restrictedIds)) {
            return response()->json(['message' => 'You cannot send messages to this user due to block restrictions.'], 403);
        }

        // Check Friendship
        // ملحوظة: تأكد من عمل import لموديل الـ Friendship فوق لو مش معمول
        $isFriend = \App\Models\Friendship::where(function ($query) use ($sender, $receiverId) {
            $query->where('user_id', $sender->id)->where('friend_id', $receiverId);
        })->orWhere(function ($query) use ($sender, $receiverId) {
            $query->where('user_id', $receiverId)->where('friend_id', $sender->id);
        })->where('status', 'accepted')->exists();

        if (!$isFriend) {
            return response()->json(['message' => 'You can only message accepted friends.'], 403);
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('chats/images', 'public');
        }

        $voiceUrl = null;
        if ($request->hasFile('voice')) {
            $voiceUrl = $request->file('voice')->store('chats/voices', 'public');
        }

        $videoUrl = null;
        if ($request->hasFile('video')) {
            $videoUrl = $request->file('video')->store('chats/videos', 'public');
        }

        // Find or create conversation symmetrically
        // ملحوظة: تأكد من عمل import لموديل الـ Conversation فوق لو مش معمول
        $user1Id = min($sender->id, $receiverId);
        $user2Id = max($sender->id, $receiverId);

        $conversation = \App\Models\Conversation::firstOrCreate(
            ['user1_id' => $user1Id, 'user2_id' => $user2Id],
            ['last_updated' => now()]
        );
        
        $conversation->update(['last_updated' => now()]);

        // ملحوظة: تأكد من عمل import لموديل الـ ChatMessage فوق لو مش معمول
        $message = \App\Models\ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $sender->id,
            'message'         => $request->message,
            'image_url'       => $imageUrl,
            'voice_url'       => $voiceUrl,
            'video_url'       => $videoUrl,
            'is_read'         => false,

        ]);

        broadcast(new \App\Events\MessageSent($message))->toOthers();

        // 🚀 بداية كود إطلاق إشعار الشات الذكي 🚀
        // بنحدد نص الرسالة بناءً على المدخلات عشان الإشعار يظهر واضح
        $notificationBody = '';
        if ($request->filled('message')) {
            $notificationBody = \Illuminate\Support\Str::limit($request->message, 50);
        } elseif ($request->hasFile('image')) {
            $notificationBody = 'sent an image 📷';
        } elseif ($request->hasFile('voice')) {
            $notificationBody = 'sent a voice message 🎵';
        } elseif ($request->hasFile('video')) {
            $notificationBody = 'sent a video 🎥';
        }

        // حفظ الإشعار بنوع chat والـ reference_id هو رقم المحادثة (conversation_id)
        \App\Models\Notification::create([
            'user_id' => $receiverId, // المستلم
            'title' => 'New Message from ' . $sender->first_name,
            'message' => $notificationBody,
            'type' => 'chat',
            'reference_id' => $conversation->id, // الأندرويد والرياكت هيفتحوا الـ Conversation ID ده علطول
        ]);
        // 🚀 نهاية كود الإشعار 🚀

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => clone $message->load('sender:id,first_name,last_name,profile_picture')
        ], 201);
    }
    /**
     * Update an existing message within 10 minutes.
     */
    public function updateMessage(Request $request, $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $message = ChatMessage::findOrFail($id);

        if ($message->sender_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($message->created_at->diffInMinutes(now()) > 10) {
            return response()->json(['message' => 'You can only edit a message within 10 minutes of sending.'], 403);
        }

        $message->update([
            'message' => $request->message,
        ]);

        broadcast(new UpdateMessageEvent($message))->toOthers();

        return response()->json([
            'message' => 'Message updated successfully',
            'data' => $message
        ], 200);
    }

    /**
     * Delete a message within 10 minutes.
     */
    public function deleteMessage($id): JsonResponse
    {
        $message = ChatMessage::findOrFail($id);

        if ($message->sender_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($message->created_at->diffInMinutes(now()) > 10) {
            return response()->json(['message' => 'You can only delete a message within 10 minutes of sending.'], 403);
        }

        $messageId = $message->id;
        $senderId = $message->sender_id;
        
        // Receiver ID can be derived from conversation
        $conversation = $message->conversation;
        $receiverId = $conversation->user1_id === $senderId ? $conversation->user2_id : $conversation->user1_id;

        if ($message->image_url) {
            Storage::disk('public')->delete($message->image_url);
        }
        if ($message->voice_url) {
            Storage::disk('public')->delete($message->voice_url);
        }
        if ($message->video_url) {
            Storage::disk('public')->delete($message->video_url);
        }

        $message->delete();

        broadcast(new DeleteMessageEvent($messageId, $senderId, $receiverId))->toOthers();

        return response()->json(['message' => 'Message deleted successfully.'], 200);
    }

    /**
     * Mark all unread messages in a conversation as read.
     */
    public function markAsRead(Request $request, $conversationId): JsonResponse
    {
        $conversation = Conversation::findOrFail($conversationId);
        $userId = Auth::id();

        if ($conversation->user1_id !== $userId && $conversation->user2_id !== $userId) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        
        $otherUserId = $conversation->user1_id === $userId ? $conversation->user2_id : $conversation->user1_id;

        $messageCount = ChatMessage::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                // we dropped read_at in ChatMessage, we just use is_read
            ]);

        if ($messageCount > 0) {
            broadcast(new MessageSeen($userId, $otherUserId, $messageCount))->toOthers();
        }

        return response()->json([
            'message' => 'Messages marked as read',
            'read_count' => $messageCount,
        ], 200);
    }


    /**
 * Trigger notification for the message receiver.
 */
public function triggerChatNotification($receiverId, $senderName, $chatRoomId, $messageText): void
{
    // تأكيد عشان اليوزر ميبعتش إشعار لنفسه لو باصى الداتا غلط
    if ((int) $receiverId !== (int) auth('sanctum')->id()) {
        \App\Models\Notification::create([
            'user_id' => $receiverId, // المستلم اللي هيجيله الإشعار
            'title' => 'رسالة جديدة 💬',
            'message' => $senderName . ': ' . \Illuminate\Support\Str::limit($messageText, 50), // عشان يظهر أول جزء من الرسالة
            'type' => 'chat',
            'reference_id' => $chatRoomId, // الـ ID بتاع أوضة الشات عشان الأندرويد يفتحها علطول
        ]);
    }
}
}
