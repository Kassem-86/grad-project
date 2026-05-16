<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CommentController extends Controller
{

    /**
     * Get all comments for a post and include the parent post's like status.
     */
    public function index(Request $request, Post $post)
    {
        // Explicitly use Sanctum guard to retrieve authenticated user from token
        $user = auth('sanctum')->user();

        $query = $post->comments()
            ->with(['user', 'likes.user']);

        // Add per-comment like status when user is authenticated
        if ($user) {
            $query->withExists(['likes as is_liked' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }]);
        } else {
            // For guests, set is_liked to false
            $query->selectRaw('comments.*, false as is_liked');
        }

        $comments = $query->latest()->paginate(20);

        // Determine parent post like status for the current user
        if ($user) {
            $post->is_liked = (bool) $post->likes()->where('user_id', $user->id)->exists();
        } else {
            $post->is_liked = false;
        }

        // Build response: include paginated comments data and pagination meta/links
        $commentsData = CommentResource::collection($comments)->response()->getData(true);

        $response = [
            'post_id' => $post->id,
            'is_liked' => (bool) $post->is_liked,
            'comments' => $commentsData['data'],
        ];

        if (isset($commentsData['links'])) {
            $response['links'] = $commentsData['links'];
        }
        if (isset($commentsData['meta'])) {
            $response['meta'] = $commentsData['meta'];
        }

        return response()->json($response);
    }

    /**
     * Store a newly created comment.
     */
    public function store(Request $request, Post $post)
    {
        $request->validate([
            'comment_text' => 'required|string',
        ]);

        $comment = $post->comments()->create([
            'comment_text' => $request->comment_text,
            'user_id' => auth('sanctum')->id(),
        ]);
        $comment->load(['user', 'likes.user']);
        
        // Set is_liked to false since it's a new comment
        $comment->is_liked = false;

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => new CommentResource($comment)
        ], 201);
    }
    /**
     * Delete a comment.
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);
        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }



    /**
     * Update the specified comment.
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $validated = $request->validate([
            'comment_text' => 'required|string',
        ]);

        $comment->update($validated);

        $comment->load(['user', 'likes.user']);
        
        // Calculate is_liked based on whether the authenticated user has liked the comment
        $user = $request->user();
        $comment->is_liked = $user ? (bool) $comment->likes()->where('user_id', $user->id)->exists() : false;

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => new CommentResource($comment)
        ]);
    }
}
