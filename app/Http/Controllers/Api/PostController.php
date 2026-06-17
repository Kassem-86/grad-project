<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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
        $user = $request->user();

        $query = Post::with('user', 'images')
            ->when($category, function ($query, $category) {
                return $query->where('category', $category);
            });

        // For authenticated users: add like status and blocked user restrictions
        if ($user) {
            $restrictedIds = $user->getRestrictedUserIds();
            
            $query->whereNotIn('user_id', $restrictedIds)
                ->withExists(['likes as is_liked' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                }]);
        } else {
            // For unauthenticated users: set is_liked to false by default
            $query->selectRaw('posts.*, false as is_liked');
        }

// بدلاً من ->latest()
$posts = $query->orderBy('created_at', 'desc')
               ->orderBy('id', 'desc') // إضافة الترتيب بالـ ID لضمان عدم التكرار
               ->paginate(10);
        // Build the response with category metadata
        $resourceCollection = PostResource::collection($posts)->response()->getData(true);
        
        $response = $resourceCollection;
        
        // Add category post count to response metadata if category is specified
        if ($category) {
            $categoryPostCount = Post::where('category', $category)->count();
            $response['meta']['category'] = $category;
            $response['meta']['category_posts_count'] = $categoryPostCount;
        }

        return response()->json($response);
    }

   
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB per image
            'category' => ['required', Rule::in(Post::CATEGORIES)],
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
                    'user_id' => $request->user()->id,
                    'image_path' => $path,
                ]);
            }
        }

        $post->refresh()->load(['user', 'images', 'comments.user', 'likes.user']);

        return response()->json(
            new PostResource($post),
            201
        );
    }

    /**
     * Display the specified post.
     */
    public function show(Request $request, Post $post): PostResource
    {
        $post->load(['user', 'images', 'comments.user', 'likes.user']);
        
        // Add is_liked for authenticated users
        if ($request->user()) {
            $post->is_liked = $post->likes()
                ->where('user_id', $request->user()->id)
                ->exists();
        } else {
            $post->is_liked = false;
        }
        
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
            'category' => ['sometimes', 'required', Rule::in(Post::CATEGORIES)],
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
            ->withExists(['likes as is_liked' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->latest()
            ->paginate(10);

        return PostResource::collection($posts);
    }

    public function userPosts(Request $request, User $user)
    {
        // Check block restrictions for authenticated users
        if ($request->user()) {
            $restrictedIds = $request->user()->getRestrictedUserIds();
            
            if (in_array($user->id, $restrictedIds)) {
                return response()->json([
                    'message' => 'This profile is not available.'
                ], 403);
            }
        }

        // Fetch user's posts with like status
        $query = Post::with(['user', 'images'])
            ->withCount(['comments', 'likes'])
            ->where('user_id', $user->id);
        
        // Add like status for authenticated users
        if ($request->user()) {
            $query->withExists(['likes as is_liked' => function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            }]);
        } else {
            $query->selectRaw('posts.*, false as is_liked');
        }

        $posts = $query->latest()->paginate(10);

        return PostResource::collection($posts);
    }
}