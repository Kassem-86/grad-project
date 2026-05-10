<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Instantiate the controller.
     */
    // public function __construct()
    // {
    //     $this->middleware('auth:sanctum')->only(['store', 'update', 'destroy']);
    // }

    
    public function index(Request $request)
    {
        $category = $request->query('category');

        // If no authenticated user, return public posts (still allow optional category filter)
        if (!$request->user()) {
            $posts = Post::with('user', 'images')
                ->when($category, function ($query, $category) {
                    return $query->where('category', $category);
                })
                ->latest()
                ->paginate(10);

            return PostResource::collection($posts);
        }

        // Authenticated: apply blocked-user restrictions and optional category filter
        $restrictedIds = $request->user()->getRestrictedUserIds();

        $posts = Post::with('user', 'images')
            ->when($category, function ($query, $category) {
                return $query->where('category', $category);
            })
            ->whereNotIn('user_id', $restrictedIds)
            ->latest()
            ->paginate(10);

        return PostResource::collection($posts);
    }

   
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'category' => 'required|in:General,Type1 and LADA,Type2,gestational,advices',
        ]);

        $post = $request->user()->posts()->create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'category' => $validated['category'],
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('posts', 'public');
                PostImage::create([
                    'post_id' => $post->id,
                    'image_path' => $path,
                ]);
            }
        }

        $post->load(['user', 'images', 'comments.user', 'likes.user']);

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
        $post->load(['user', 'images', 'comments.user', 'likes.user']);
        return new PostResource ($post);
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
            'category' => 'sometimes|required|in:General,Type1 and LADA,Type2,gestational,advices',
        ]);

        $post->update($validated);
        $post->load(['user', 'images', 'comments.user', 'likes.user']);

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

    /**
     * Return posts belonging to the authenticated user.
     */
    public function myPosts(Request $request)
    {
        $user = $request->user();

        $posts = Post::with('user', 'images')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return PostResource::collection($posts);
    }
}
