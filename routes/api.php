<?php
use App\Http\Controllers\GlucoseController;
use App\Http\Controllers\SelectedMedicationController;
use App\Http\Controllers\ChatbotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    BlockController,
    PostController,
    CommentController,
    LikeController,
    FriendshipController,
    ChatController,
    CombinedLogController,
    ConversationController,
    SearchController,
    ReminderController,
    UserController
};
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/check-email', [AuthController::class, 'checkEmail']);

// // Google Socialite Auth
// Route::get('/auth/google', [\App\Http\Controllers\GoogleAuthController::class, 'redirectToGoogle'])->name('google.login');
// Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleAuthController::class, 'handleGoogleCallback'])->name('google.callback');

// المشاهدة فقط (Posts & Comments)
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);
Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
Route::get('/users/{user}/posts', [PostController::class, 'userPosts']);// Route عشان تجيب بوستات يوزر معين عن طريق الـ ID بتاعه


// Search (Public & Protected)
Route::get('/search', [SearchController::class, 'index']);


/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    
    // --- User & Authentication ---
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/user/profile/{id}', [UserController::class, 'showProfile']);
    Route::post('/chatbot/ask', [App\Http\Controllers\ChatbotController::class, 'askChatbot']);// Route عشان تسأل الشات بوت (ممكن تحطه في مكان تاني لو حابب)
    Route::get('/chatbot/history', [ChatbotController::class, 'getChatHistory']);
    // Preferred standardized endpoint for retrieving the authenticated user
    Route::get('/me', [AuthController::class, 'me']);
    Route::delete('/delete-me', [AuthController::class, 'deleteUser']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);

    // --- Community: Posts ---
    Route::apiResource('posts', PostController::class);
    Route::get('/my-posts', [PostController::class, 'myPosts']);
    Route::get('/users/{user}/posts', [PostController::class, 'userPosts']);// Route عشان تجيب بوستات يوزر معين عن طريق الـ ID بتاعه



    // --- Community: Comments ---
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']); // ✅ مكانها الصح
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // --- Community: Likes (Polymorphic) ---
    Route::post('/posts/{post}/like', [LikeController::class, 'togglePost']);
    Route::post('/comments/{comment}/like', [LikeController::class, 'toggleComment']);
    Route::get('/posts/{post}/likes', [LikeController::class, 'getPostLikes']);
    Route::get('/comments/{comment}/likes', [LikeController::class, 'getCommentLikes']);

    // --- Community: Friendships ---
    Route::post('/friends/{id}/request', [FriendshipController::class, 'sendRequest']);
    Route::post('/friends/{id}/accept', [FriendshipController::class, 'acceptRequest']);
    Route::delete('/friends/{id}/cancel', [FriendshipController::class, 'cancelRequest']);
    Route::delete('/friends/{id}', [FriendshipController::class, 'removeFriend']);
    Route::post('/friends/{id}/block', [BlockController::class, 'block']);
    Route::delete('/friends/{id}/unblock', [BlockController::class, 'unblock']);
    Route::get('/friends/blocks', [BlockController::class, 'index']);

    // --- Health Tracking (Resources) ---

    Route::get('/logs/user', [CombinedLogController::class, 'show']);
    Route::get('/logs/user/{log_id}', [CombinedLogController::class, 'getLogById']);
    Route::get('/logs/sync', [CombinedLogController::class, 'sync']);


    Route::apiResource('logs', CombinedLogController::class);
    //  Route::put('logs', CombinedLogController::class, 'update');
    Route::post('/logs/android/', [CombinedLogController::class, 'storeWithAndroidId']);
    Route::apiResource('selected-medications' , SelectedMedicationController::class);



    Route::get('/glucose/history', [GlucoseController::class, 'getGlucoseHistory']);
    // Route::get('/logs/user', [CombinedLogController::class, 'show']);
    Route::get('/logs/{date}', [CombinedLogController::class, 'show']);

    // --- Real-time Chat (Reverb) ---
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'index']);
        Route::get('/{conversation_id}', [ConversationController::class, 'show']);
    });

    // --- Chatbot ---
    Route::post('/chatbot/ask', [ChatbotController::class, 'askChatbot']);

    Route::prefix('messages')->group(function () {
        Route::get('/chat/{receiver_id}', [ChatController::class, 'index']);
        Route::post('/', [ChatController::class, 'store']);
        Route::put('/{id}', [ChatController::class, 'updateMessage']);
        Route::delete('/{id}', [ChatController::class, 'deleteMessage']);
    });

    Route::post('/conversations/{conversation_id}/mark-as-read', [ChatController::class, 'markAsRead']);

    // --- Reminders ---
    Route::apiResource('reminders', ReminderController::class, ['only' => ['index', 'store', 'destroy']]);
    Route::put('/reminders/{reminder}/status', [ReminderController::class, 'updateStatus']);

    // --- Notifications ---
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);

    Route::post('/user/save-device-token', function (Request $request) {
        $request->validate([
            'device_token' => 'required|string',
        ]);

        auth('sanctum')->user()->update([
            'device_token' => $request->device_token
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device token updated successfully.'
        ]);
    });
    // ... الـ routes القديمة


//     Route::get('/test-fcm', function() {
//     // 1. جيب يوزر عندك يكون مسجل device_token فعلي
//     $user = \App\Models\User::whereNotNull('device_token')->first();
    
//     if (!$user) {
//         return response()->json(['error' => 'No user found with a device token'], 404);
//     }

//     // 2. نقرأ ملف الـ JSON
//     $filePath = storage_path('app/firebase_credentials.json');
//     $credentials = json_decode(file_get_contents($filePath), true);
//     $projectId = $credentials['project_id'];

//     // 3. نولد الـ Token (هنستعين بنفس الـ Logic اللي جوه الـ Controller)
//     // لتسهيل التست هنا، هفترض إنك هتعمل الـ JWT سريعا أو تنده على الميثود لو عاملها في Helper
//     // لكن الأسهل خد السطور دي عشان تشوف النتيجة علطول:
    
//     $privateKey = $credentials['private_key'];
//     $clientEmail = $credentials['client_email'];
//     $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
//     $now = time();
//     $payload = json_encode([
//         'iss' => $clientEmail,
//         'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
//         'aud' => 'https://oauth2.googleapis.com/token',
//         'exp' => $now + 3600,
//         'iat' => $now
//     ]);
//     $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
//     $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
//     $signature = '';
//     openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
//     $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
//     $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

//     $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
//         'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
//         'assertion' => $jwt,
//     ]);
    
//     $accessToken = $tokenResponse->json()['access_token'] ?? null;

//     // 4. نبعت لـ Firebase ونرجع الـ Response الأصلي بتاع جوجل للـ Postman
//     $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
//     $fcmResponse = Http::withToken($accessToken)->post($url, [
//         'message' => [
//             'token' => $user->device_token,
//             'notification' => [
//                 'title' => 'Test Notification',
//                 'body' => 'Hello from Laravel Postman Test!',
//             ],
//         ]
//     ]);

//     // هنرجع رد جوجل بالملّي للبوست مان
//     return response()->json([
//         'google_status' => $fcmResponse->status(),
//         'google_response' => $fcmResponse->json()
//     ]);
// });
  
});
/*
|--------------------------------------------------------------------------
| Broadcasting Routes (Reverb)
|--------------------------------------------------------------------------




*/
Broadcast::routes(['middleware' => ['auth:sanctum']]);