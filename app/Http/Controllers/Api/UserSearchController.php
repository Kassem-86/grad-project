<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1|max:255',
        ]);

        $user = $request->user();
        $restrictedUserIds = $user->getRestrictedUserIds();

        $query = $request->input('query');

        $users = User::where(function ($q) use ($query) {
            $q->where('first_name', 'like', "%{$query}%")
              ->orWhere('last_name', 'like', "%{$query}%");
        })
        ->whereNotIn('id', $restrictedUserIds)
        ->where('id', '!=', $user->id)
        ->limit(20)
        ->get(['id', 'first_name', 'last_name']);

        return response()->json($users);
    }}