<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Block; // الموديل     اللي كوبايلوت عمله
use Illuminate\Http\Request;    

class BlockController extends Controller
{
    public function block($id)
    {
        $user = auth('sanctum')->user();

        // سجل البلوك في الجدول اللي كوبايلوت كريته
        Block::updateOrCreate([
            'user_id' => $user->id,
            'blocked_id' => $id
        ]);

        return response()->json(['message' => 'User blocked successfully']);
    }

   public function unblock($id)
{
    $user = auth('sanctum')->user();

    // هنمسح السطر اللي فيه الـ user_id بتاعي والـ blocked_id بتاع الشخص التاني
    $deleted = \App\Models\Block::where('user_id', $user->id)
        ->where('blocked_id', $id)
        ->delete();

    if ($deleted) {
        return response()->json(['message' => 'User unblocked successfully']);
    }

    return response()->json(['message' => 'Block record not found'], 404);
}

public function index(Request $request)
{
    $user = auth('sanctum')->user();

    // 🚀 هنجيب الداتا من جدول الـ Block علطول، ونعمل Eager Load للشخص المتبلك
    $perPage = $request->query('per_page', 20);
    
    $blocks = \App\Models\Block::where('user_id', $user->id)
        ->with('blockedUser') // العلاقة النظيفة اللي في الموديل عندك
        ->latest()
        ->paginate($perPage);

    // بنحول الداتا لـ Resource عشان نبعت بيانات اليوزر المتبلك صافية لأندرويد
    $blockedUsersData = $blocks->map(function ($block) {
        return $block->blockedUser ? new UserResource($block->blockedUser) : null;
    })->filter();

    return response()->json([
        'success' => true,
        'blocked_users' => array_values($blockedUsersData->toArray()),
        'pagination' => [
            'current_page' => $blocks->currentPage(),
            'last_page'    => $blocks->lastPage(),
            'per_page'     => $blocks->perPage(),
            'total'        => $blocks->total(),
        ]
    ], 200);
}
}




