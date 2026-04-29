<?php

namespace App\Http\Controllers\Api;

use App\Events\DeleteMessageEvent;
use App\Events\MessageSent;
use App\Events\UpdateMessageEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * Send a new message.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'voice' => 'nullable|file|mimes:mp3,wav,m4a|max:10240',
            'video' => 'nullable|file|mimes:mp4,avi,mov,mkv,flv,wmv,webm,m4v,3gp,mpg,mpeg,ts,m3u8,mts,m2ts,mxf,ogv,vob,f4v,asf,rm,rmvb,divx,dv,m2v,mts,mpeg1,mpeg2|max:102400',
        ]);

        if (!$request->filled('message') && !$request->hasFile('image') && !$request->hasFile('voice') && !$request->hasFile('video')) {
            return response()->json(['message' => 'Message, image, voice, or video is required.'], 422);
        }

        $sender = Auth::user();
        $receiverId = $request->receiver_id;

        if ($sender->id == $receiverId) {
            return response()->json(['message' => 'You cannot send a message to yourself.'], 403);
        }

        // Check Block
        $restrictedIds = $sender->getRestrictedUserIds();
        if (in_array($receiverId, $restrictedIds)) {
            return response()->json(['message' => 'You cannot send messages to this user due to block restrictions.'], 403);
        }

        // Check Friendship
        $isFriend = Friendship::where(function ($query) use ($sender, $receiverId) {
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

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'message' => $request->message,
            'image_url' => $imageUrl,
            'voice_url' => $voiceUrl,
            'video_url' => $videoUrl,
        ]);

        // Broadcast Event
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => new MessageResource($message)
        ], 201);
    }

    /**
     * Get paginated chat history.
     */
    public function getMessages(Request $request, $receiverId): JsonResponse
    {
        $sender = Auth::user();

        // Check Block
        $restrictedIds = $sender->getRestrictedUserIds();
        if (in_array($receiverId, $restrictedIds)) {
            return response()->json(['message' => 'Access denied due to block restrictions.'], 403);
        }

        $messages = Message::where(function ($query) use ($sender, $receiverId) {
            $query->where('sender_id', $sender->id)->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($sender, $receiverId) {
            $query->where('sender_id', $receiverId)->where('receiver_id', $sender->id);
        })->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'data' => MessageResource::collection($messages)->response()->getData(true)
        ], 200);
    }

    /**
     * Update an existing message within 10 minutes.
     */
    public function updateMessage(Request $request, $id): JsonResponse
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $message = Message::findOrFail($id);

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
            'data' => new MessageResource($message)
        ], 200);
    }

    /**
     * Delete a message within 10 minutes.
     */
    public function deleteMessage($id): JsonResponse
    {
        $message = Message::findOrFail($id);

        if ($message->sender_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($message->created_at->diffInMinutes(now()) > 10) {
            return response()->json(['message' => 'You can only delete a message within 10 minutes of sending.'], 403);
        }

        $messageId = $message->id;
        $senderId = $message->sender_id;
        $receiverId = $message->receiver_id;

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
}
