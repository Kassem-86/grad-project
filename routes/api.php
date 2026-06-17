<?php
use App\Http\Controllers\Api\ReportController;
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
Route::post('/forgot-password/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPassword']);

Route::get('/test-email', function () {
    try {
        \Illuminate\Support\Facades\Mail::raw('Testing Email!', function ($message) {
            $message->to('test@example.com')->subject('Testing Mailtrap');
        });
        return response()->json(['message' => 'Email sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

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
Route::post('/password/verify', [AuthController::class, 'verifyCurrentPassword']);
Route::post('/password/update', [AuthController::class, 'updateNewPassword']);
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
    Route::put('/comments/{comment}', [CommentController::class, 'update']); 
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
    Route::get('/friends', [FriendshipController::class, 'getFriends']); 
    Route::get('/friends/profile', [FriendshipController::class, 'getFriendsprofile']);
    // الـ Route الجديد لجلب قائمة الأصحاب


    // --- Conversations / Chat Search ---


    // --- Health Tracking (Resources) ---

    Route::get('/logs/user', [CombinedLogController::class, 'show']);
    Route::get('/logs/user/{log_id}', [CombinedLogController::class, 'getLogById']);
    Route::get('/logs/sync', [CombinedLogController::class, 'sync']);


    Route::apiResource('logs', CombinedLogController::class);
    //  Route::put('logs', CombinedLogController::class, 'update');
    Route::post('/logs/android/', [CombinedLogController::class, 'storeWithAndroidId']);
    Route::apiResource('selected-medications' , SelectedMedicationController::class);
Route::post('/sync/medication', [SelectedMedicationController::class, 'sync']);
    

    



    Route::get('/glucose/history', [GlucoseController::class, 'getGlucoseHistory']);
    // Route::get('/logs/user', [CombinedLogController::class, 'show']);
    Route::get('/logs/{date}', [CombinedLogController::class, 'show']);

    // --- Real-time Chat (Reverb) ---
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'index']);
                    Route::get('/search', [ConversationController::class, 'searchChatFriends']);    // الـ Route الجديد للبحث عن شات معين باسم الصاحب

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
    Route::put('/reminders/{reminder}/status', [ReminderController::class, 'updateStatus']);// Route عشان تتعديل حالة التذكير
    Route::post('/sync/reminders', [ReminderController::class, 'sync']);


    // --- Notifications ---
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
    Route::post('/glucose-analysis', [ReportController::class, 'getGlucoseReportGraph']);
    Route::get('/reports/glucose/pdf', [ReportController::class, 'exportGlucosePdf']);

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
   
    
  
});

/*
|--------------------------------------------------------------------------
| Broadcasting Routes (Reverb)
|--------------------------------------------------------------------------




*/                                                                
Broadcast::routes(['middleware' => ['auth:sanctum']]);