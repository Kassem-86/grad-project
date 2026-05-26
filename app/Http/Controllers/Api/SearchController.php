<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Resources\PostResource;
use App\Models\User;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search for users and posts matching the query.
     */
  public function index(Request $request): JsonResponse
{
    $request->validate([
        'query' => 'required|string|min:1|max:255',
    ]);

    $query = $request->input('query');

    // 1. بحث اليوزرز (نفس الـ Logic بتاعك مع إضافة تصفية بسيطة)
    $usersQuery = User::query();
    


    $usersQuery->where(function ($q) use ($query) {
        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"]);
        
        $parts = explode(' ', trim($query));
        foreach ($parts as $part) {
            if (!empty($part)) {
                $q->orWhere('first_name', 'like', "%{$part}%")
                  ->orWhere('last_name', 'like', "%{$part}%");
            }
        }
    });

    if ($request->user()) {
        $restrictedIds = $request->user()->getRestrictedUserIds();
        $usersQuery->whereNotIn('id', $restrictedIds)
                   ->where('id', '!=', $request->user()->id);
    }

    $users = $usersQuery->limit(10)->get();

    // 2. بحث البوستات مع Pagination
    $postsQuery = Post::with(['user', 'images']) // بنحمل الأساسيات بس
        ->where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
              ->orWhere('content', 'like', "%{$query}%");
        });

    if ($request->user()) {
        $restrictedIds = $request->user()->getRestrictedUserIds();
        $postsQuery->whereNotIn('user_id', $restrictedIds);
    }

    // استخدمنا paginate بدل get عشان لو النتايج كتير
    $posts = $postsQuery->latest()->paginate(10);

    return response()->json([
        'status' => 'success',
        'data' => [
            'users' => UserResource::collection($users),
            'posts' => PostResource::collection($posts)->response()->getData(true), // عشان يرجع الـ pagination links
        ]
    ]);
}}