<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    /**
     * Instantiate the controller.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
    }

    /**
     * Display a listing of posts.
     */
    public function index(): AnonymousResourceCollection
    {
        $posts = Post::with(['user', 'comments.user', 'likes.user'])
            ->latest()
            ->paginate(15);

        return PostResource::collection($posts);
    }

    /**
     * Store a newly created post.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'post_media' => 'nullable|string',
            'category' => 'required|in:General,Type1 and LADA,gestational,advices',
        ]);

        $post = $request->user()->posts()->create($validated);
        $post->load(['user', 'comments.user', 'likes.user']);

        return response()->json(
            new PostResource($post),
            201
        );
    }

    /**
     * Display the specified post.
     */
    public function show(Post $post): PostResource
    {
        $post->load(['user', 'comments.user', 'likes.user']);
        return new PostResource($post);
    }

    /**
     * Update the specified post.
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'post_media' => 'nullable|string',
            'category' => 'sometimes|required|in:General,Type1 and LADA,gestational,advices',
        ]);

        $post->update($validated);
        $post->load(['user', 'comments.user', 'likes.user']);

        return response()->json(new PostResource($post));
    }

    /**
     * Remove the specified post.
     */
    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
