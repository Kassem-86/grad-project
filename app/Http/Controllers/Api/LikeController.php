<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LikeResource;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Instantiate the controller.
     */
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum');
    // }

    /**
     * Toggle like on a post.
     */
   /**
     * Toggle like on a post.
     */
    public function togglePost(Request $request, Post $post): JsonResponse
    {
        $like = Like::where('user_id', $request->user()->id)
            ->where('likeable_id', $post->id)
            ->where('likeable_type', Post::class)
            ->first();

        if ($like) {
            // Unlike
            $like->delete();
            return response()->json(['message' => 'Post unliked successfully', 'liked' => false]);
        } else {
            // Like
            $like = $post->likes()->create([
                'user_id' => $request->user()->id,
            ]);
            $like->load('user');

            // 🚀 اِنده الميثود بتاعتك هنا عشان الإشعار يتبعت أوتوماتيك
            $this->togglePostNotification($request, $post);

            return response()->json([
                'message' => 'Post liked successfully',
                'liked' => true,
                'like' => new LikeResource($like),
            ], 201);
        }
    }

    /**
     * Toggle like on a comment.
     */
    public function toggleComment(Request $request, Comment $comment): JsonResponse
    {
        $like = Like::where('user_id', $request->user()->id)
            ->where('likeable_id', $comment->id)
            ->where('likeable_type', Comment::class)
            ->first();

        if ($like) {
            // Unlike
            $like->delete();
            return response()->json(['message' => 'Comment unliked successfully', 'liked' => false]);
        } else {
            // Like
            $like = $comment->likes()->create([
                'user_id' => $request->user()->id,
            ]);
            $like->load('user');

            // 🚀 اِنده الميثود بتاعتك هنا عشان إشعار لايك الكومنت يشتغل!
            $this->toggleCommentNotification($request, $comment);

            return response()->json([
                'message' => 'Comment liked successfully',
                'liked' => true,
                'like' => new LikeResource($like),
            ], 201);
        }
    }
    /**
     * Get all users who liked a post.
     */
    public function getPostLikes(Post $post): JsonResponse
    {
        // بنسحب اليوزرز من خلال علاقة الـ likes
        $users = $post->likes()->with('user')->get()->pluck('user');

        return response()->json([
            'post_id' => $post->id,
            'total_likes' => $users->count(),
            'users' => $users // ممكن تستخدم UserResource هنا عشان الداتا تبقى أنظف
        ]);
    }

    /**
     * Get all users who liked a comment.
     */
    public function getCommentLikes(Comment $comment): JsonResponse
    {
        $users = $comment->likes()->with('user')->get()->pluck('user');

        return response()->json([
            'comment_id' => $comment->id,
            'total_likes' => $users->count(),
            'users' => $users
        ]);
    }

    /**
     * Trigger notification for post owner (only if liker is not the post owner)
     */
    public function togglePostNotification(Request $request, Post $post): void
    {
        if ((int) $post->user_id !== (int) $request->user()->id) {
            \App\Models\Notification::create([
                'user_id' => $post->user_id,
                'title' => 'Post Liked',
                'message' => $request->user()->first_name . ' liked your post.',
                'type' => 'community',
                'reference_id' => $post->id,
            ]);
        }
    }

    /**
     * Trigger notification for comment owner (only if liker is not the comment owner)
     */
    public function toggleCommentNotification(Request $request, Comment $comment): void
    {
        if ((int) $comment->user_id !== (int) $request->user()->id) {
            \App\Models\Notification::create([
                'user_id' => $comment->user_id,
                'title' => 'Comment Liked',
                'message' => $request->user()->first_name . ' liked your comment.',
                'type' => 'community',
                'reference_id' => $comment->id,
            ]);
        }
    }

    /**
     * Trigger notification for post owner (only if commenter is not the post owner)
     */
    public function togglePostNotificationComment(Request $request, Post $post): void
    {
        if ((int) $post->user_id !== (int) auth('sanctum')->id()) {
            \App\Models\Notification::create([
                'user_id' => $post->user_id,
                'title' => 'New Comment',
                'message' => auth()->user()->first_name . ' commented on your post.',
                'type' => 'community',
                'reference_id' => $post->id,
            ]);
        }
    }
}


