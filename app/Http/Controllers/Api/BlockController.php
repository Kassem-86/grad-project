<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block; // الموديل     اللي كوبايلوت عمله
use Illuminate\Http\Request;    

class BlockController extends Controller
{
    public function block($id)
    {
        $user = auth()->user();

        // سجل البلوك في الجدول اللي كوبايلوت كريته
        Block::updateOrCreate([
            'user_id' => $user->id,
            'blocked_id' => $id
        ]);

        return response()->json(['message' => 'User blocked successfully']);
    }

   public function unblock($id)
{
    $user = auth()->user();

    // هنمسح السطر اللي فيه الـ user_id بتاعي والـ blocked_id بتاع الشخص التاني
    $deleted = \App\Models\Block::where('user_id', $user->id)
        ->where('blocked_id', $id)
        ->delete();

    if ($deleted) {
        return response()->json(['message' => 'User unblocked successfully']);
    }

    return response()->json(['message' => 'Block record not found'], 404);
}
}




