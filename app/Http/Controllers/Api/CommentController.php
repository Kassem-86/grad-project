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
        // Get the authenticated user using the request
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Authorize using explicit integer casting comparison
        $userId = (int) $user->id;
        $commentUserId = (int) $comment->user_id;
        
        if ($userId !== $commentUserId) {
            return response()->json(['message' => 'This action is unauthorized.'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }

    /**
     * Update the specified comment.
     */
   public function update(Request $request, Comment $comment): JsonResponse
{
    // 1. شيلنا الـ authorize القديمة وعملنا التيك يدوي عشان نهرب من حوار الـ 403
    if ((int) $request->user()->id !== (int) $comment->user_id) {
        return response()->json([
            'message' => 'This action is unauthorized.'
        ], 403);
    }

    // 2. الـ Validation بتاعك عادي جداً
    $validated = $request->validate([
        'comment_text' => 'required|string',
    ]);

    // 3. تحديث الكومنت
    $comment->update($validated);

    // 4. تحميل البيانات عشان الـ React
    $comment->load(['user', 'likes']);
    
    $user = $request->user();
    $comment->is_liked = $user ? (bool) $comment->likes->contains('user_id', $user->id) : false;

    return response()->json([
        'message' => 'Comment updated successfully',
        'comment' => new CommentResource($comment)
    ]);
}}
