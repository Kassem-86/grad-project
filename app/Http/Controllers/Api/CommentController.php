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
     * Get all comments for a post.
     */
    public function index(Post $post): AnonymousResourceCollection
    {
        $comments = $post->comments()
            ->with(['user', 'likes.user'])
            ->latest()
            ->paginate(20);

        return CommentResource::collection($comments);
    }

    /**
     * Store a newly created comment.
     */
  public function store(Request $request, Post $post)
{
    // 1. Validate الـ data اللي جاية
    $request->validate([
        'comment_text' => 'required|string',
    ]);

    // 2. سجل الكومنت واربطه بالـ User اللي عامل Login حالياً
    $comment = $post->comments()->create([
        'comment_text' => $request->comment_text,
        'user_id' => auth()->id(), // ✅ ده السطر السحري اللي بيجيب ID صاحب الكومنت
    ]);

    return response()->json([
        'message' => 'Comment added successfully',
        'comment' => $comment
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

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => new CommentResource($comment)
        ]);
    }
}
