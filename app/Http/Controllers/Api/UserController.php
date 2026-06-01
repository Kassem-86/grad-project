<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{public function showProfile(Request $request, $id)
{
    // 1️⃣ جلب بيانات اليوزر صاحب البروفايل (أو يرجع 404 لو مش موجود)
    $profileUser = \App\Models\User::findOrFail($id);

    // 2️⃣ حساب عداد الأصدقاء مباشرة من الدالة الذكية بتاعتك في الموديل
    $friendsCount = $profileUser->friends()->count(); 
    $postsCount = $profileUser->posts()->count(); // لو عايز تعدد البوستات كمان يوزر اليوزر في البروفايل

    $currentUser = auth('sanctum')->user();
    $relationStatus = 'add_friend'; // الحالة الافتراضية لو مفيش أي علاقة

    // 3️⃣ تشيك العلاقات المنطقي (حسب ترتيب الأولويات)
    if ($currentUser) {
        $currentUserId = (int)$currentUser->id;
        $profileUserId = (int)$id;

        if ($currentUserId === $profileUserId) {
            // أ) البروفايل ده بتاعي أنا شخصياً
            $relationStatus = 'me';
        } else {
            // ب) أول وأهم تشيك: هل أنا عملت بلوك لليوزر ده؟
            $isBlocked = \DB::table('blocks') // تأكد من اسم جدول البلوك (غالباً blocks)
                ->where('user_id', $currentUserId)
                ->where('blocked_id', $profileUserId)
                ->exists();

            if ($isBlocked) {
                $relationStatus = 'blocked';
            } else {
                // جـ) لو مفيش بلوك، نشيك على طلبات الصداقة بناءً على هيكل جدولك
                $friendship = \DB::table('friendships')
                    ->where(function($q) use ($currentUserId, $profileUserId) {
                        $q->where('user_id', $currentUserId)->where('friend_id', $profileUserId);
                    })->orWhere(function($q) use ($currentUserId, $profileUserId) {
                        $q->where('user_id', $profileUserId)->where('friend_id', $currentUserId);
                    })->first();

                if ($friendship) {
                    if ($friendship->status === 'accepted') {
                        $relationStatus = 'friends';
                    } elseif ($friendship->status === 'pending') {
                        // بنشوف مين الراسل ومين المستقبل
                        if ((int)$friendship->user_id === $currentUserId) {
                            $relationStatus = 'pending_sent'; // أنا اللي بعت ومستني الرد
                        } else {
                            $relationStatus = 'pending_received'; // هو اللي بعتلي وأنا أوافق أو أرفض
                        }
                    }
                }
            }
        }
    }

    // 4️⃣ الـ Response الصافي والنظيف المتطابق مع الـ UI للأندرويد
    return response()->json([
        'success' => true,
        'data' => [
            'id'               => $profileUser->id,
            'full_name'        => trim($profileUser->first_name . ' ' . $profileUser->last_name),
            'profile_picture'  => $profileUser->profile_picture ?? 'https://inquisitorial-elba-undistractedly.ngrok-free.dev/storage/profiles/default.png', 
            'diabetes_type'    => $profileUser->diabetes_type, // "MODY" أو "Gestational" إلخ
            'friends_count'    => $friendsCount,
            'relation_status'  => $relationStatus, // 🚀 الحقل السحري (me, blocked, friends, pending_sent, pending_received, add_friend)
            'posts_count'      => $postsCount,
        ]
    ], 200);
}
}