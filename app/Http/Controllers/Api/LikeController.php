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
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

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

            return response()->json([
                'message' => 'Comment liked successfully',
                'liked' => true,
                'like' => new LikeResource($like),
            ], 201);
        }
    }
}
