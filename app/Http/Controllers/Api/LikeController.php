<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LikeResource;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Models\Notification as CustomNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LikeController extends Controller
{
    /**
     * Toggle like on a post.
     */
    public function togglePost(Request $request, Post $post): JsonResponse
    {
        $like = Like::where('user_id', $request->user()->id)
            ->where('likeable_id', $post->id)
            ->where('likeable_type', Post::class)
            ->first();

        if ($like) {
            // Unlike
            $like->delete();
            return response()->json(['message' => 'Post unliked successfully', 'liked' => false]);
        } else {
            // Like
            $like = $post->likes()->create([
                'user_id' => $request->user()->id,
            ]);
            $like->load('user');

            // 🚀 إرسال الإشعار المحلّي ولـ Firebase أوتوماتيك
            $this->togglePostNotification($request, $post);

            return response()->json([
                'message' => 'Post liked successfully',
                'liked' => true,
                'like' => new LikeResource($like),
            ], 201);
        }
    }

    /**
     * Toggle like on a comment.
     */
    public function toggleComment(Request $request, Comment $comment): JsonResponse
    {
        $like = Like::where('user_id', $request->user()->id)
            ->where('likeable_id', $comment->id)
            ->where('likeable_type', Comment::class)
            ->first();

        if ($like) {
            // Unlike
            $like->delete();
            return response()->json(['message' => 'Comment unliked successfully', 'liked' => false]);
        } else {
            // Like
            $like = $comment->likes()->create([
                'user_id' => $request->user()->id,
            ]);
            $like->load('user');

            // 🚀 إرسال إشعار لايك الكومنت
            $this->toggleCommentNotification($request, $comment);

            return response()->json([
                'message' => 'Comment liked successfully',
                'liked' => true,
                'like' => new LikeResource($like),
            ], 201);
        }
    }

    /**
     * Get all users who liked a post.
     */
    public function getPostLikes(Post $post): JsonResponse
    {
        $users = $post->likes()->with('user')->get()->pluck('user');

        return response()->json([
            'post_id' => $post->id,
            'total_likes' => $users->count(),
            'users' => $users
        ]);
    }

    /**
     * Get all users who liked a comment.
     */
    public function getCommentLikes(Comment $comment): JsonResponse
    {
        $users = $comment->likes()->with('user')->get()->pluck('user');

        return response()->json([
            'comment_id' => $comment->id,
            'total_likes' => $users->count(),
            'users' => $users
        ]);
    }

    /**
     * Trigger notification for post owner
     */
    public function togglePostNotification(Request $request, Post $post): void
    {
        if ((int) $post->user_id !== (int) $request->user()->id) {
            $title = 'Post Liked';
            $message = $request->user()->first_name . ' liked your post.';
            $type = 'community';

            CustomNotification::create([
                'user_id' => $post->user_id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'reference_id' => $post->id,
            ]);

            // جلب بيانات اليوزر المستهدف للتحقق من الـ Token
            $targetUser = User::find($post->user_id);
            if ($targetUser && $targetUser->device_token) {
                $this->sendFcmNotification($targetUser->device_token, $title, $message, [
                    'type' => $type,
                    'reference_id' => (string) $post->id
                ]);
            }
        }
    }

    /**
     * Trigger notification for comment owner
     */
    public function toggleCommentNotification(Request $request, Comment $comment): void
    {
        if ((int) $comment->user_id !== (int) $request->user()->id) {
            $title = 'Comment Liked';
            $message = $request->user()->first_name . ' liked your comment.';
            $type = 'community';

            CustomNotification::create([
                'user_id' => $comment->user_id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'reference_id' => $comment->id,
            ]);

            $targetUser = User::find($comment->user_id);
            if ($targetUser && $targetUser->device_token) {
                $this->sendFcmNotification($targetUser->device_token, $title, $message, [
                    'type' => $type,
                    'reference_id' => (string) $comment->id
                ]);
            }
        }
    }

    /**
     * Trigger notification for post owner when commented
     */
    public function togglePostNotificationComment(Request $request, Post $post): void
    {
        $currentUser = auth('sanctum')->user() ?? $request->user();

        if ($currentUser && (int) $post->user_id !== (int) $currentUser->id) {
            $title = 'New Comment';
            $message = $currentUser->first_name . ' commented on your post.';
            $type = 'community';

            CustomNotification::create([
                'user_id' => $post->user_id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'reference_id' => $post->id,
            ]);

            $targetUser = User::find($post->user_id);
            if ($targetUser && $targetUser->device_token) {
                $this->sendFcmNotification($targetUser->device_token, $title, $message, [
                    'type' => $type,
                    'reference_id' => (string) $post->id
                ]);
            }
        }
    }

    /**
     * 🔥 العقل المدبر: توليد الـ OAuth2 Token وإرسال الإشعار لـ Firebase v1
     */
    protected function sendFcmNotification($deviceToken, $title, $body, $data = [])
    {
        try {
            $filePath = storage_path('app/firebase_credentials.json');

            if (!file_exists($filePath)) {
                Log::error("FCM Error: Credentials file not found at " . $filePath);
                return;
            }

            $credentials = json_decode(file_get_contents($filePath), true);
            $projectId = $credentials['project_id'];

            // توليد الـ Access Token يدويًا عبر الـ JWT لعدم إجبار السيستم على مكاتب خارجية
            $accessToken = $this->generateGoogleAccessToken($credentials);

            if (!$accessToken) {
                Log::error("FCM Error: Could not generate Google Access Token.");
                return;
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $payload = [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data // الداتا المخفية اللي بيقرأها الأندرويد لفتح الشاشات
                ]
            ];

            $response = Http::withToken($accessToken)->post($url, $payload);

            if (!$response->successful()) {
                Log::error("FCM Server Response Failed: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('FCM Core System Failure: ' . $e->getMessage());
        }
    }

    /**
     * helper function لتوليد الـ JWT الـ الخص بجوجل واستبداله بـ Access Token
     */
    private function generateGoogleAccessToken($credentials)
    {
        $privateKey = $credentials['private_key'];
        $clientEmail = $credentials['client_email'];

        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        
        $now = time();
        $payload = json_encode([
            'iss' => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = '';
        openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        // طلب الـ Access Token من جوجل
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        return $response->json()['access_token'] ?? null;
    }
}