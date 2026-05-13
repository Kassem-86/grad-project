<?php
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
    ConversationController,
    SearchController
};
use App\Http\Controllers\{
    GlucoseController,
    MealController,
    RecordMedicationController,
    SelectedMedicationController
};

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/check-email', [AuthController::class, 'checkEmail']);

// Google Socialite Auth
Route::get('/auth/google', [\App\Http\Controllers\GoogleAuthController::class, 'redirectToGoogle'])->name('google.login');
Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleAuthController::class, 'handleGoogleCallback'])->name('google.callback');

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
    // Preferred standardized endpoint for retrieving the authenticated user
    Route::get('/me', [AuthController::class, 'me']);
    Route::delete('/delete-me', [AuthController::class, 'deleteUser']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Community: Posts ---
    Route::apiResource('posts', PostController::class);
    Route::get('/my-posts', [PostController::class, 'myPosts']);


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
    Route::delete('/friends/{id}', [FriendshipController::class, 'removeFriend']);
      Route::post('/friends/{id}/block', [BlockController::class, 'block']);
    Route::delete('/friends/{id}/unblock', [BlockController::class, 'unblock']);

    // --- Health Tracking (Resources) ---
    Route::apiResource('glucose', GlucoseController::class);
    Route::apiResource('meals', MealController::class);
    Route::apiResource('record-medications', RecordMedicationController::class);
    Route::apiResource('selected-medications', SelectedMedicationController::class, [
        'only' => ['index', 'show', 'update', 'destroy']
    ]);

    // --- Real-time Chat (Reverb) ---
    Route::prefix('conversations')->group(function () {
        Route::get('/', [ConversationController::class, 'index']);
        Route::get('/{conversation_id}', [ConversationController::class, 'show']);
    });

    Route::prefix('messages')->group(function () {
        Route::post('/', [ChatController::class, 'store']);
        Route::put('/{id}', [ChatController::class, 'updateMessage']);
        Route::delete('/{id}', [ChatController::class, 'deleteMessage']);
    });

    Route::post('/conversations/{conversation_id}/mark-as-read', [ChatController::class, 'markAsRead']);

    // ... الـ routes القديمة
  
});

/*
|--------------------------------------------------------------------------
| Broadcasting Routes (Reverb)
|--------------------------------------------------------------------------
*/
Broadcast::routes(['middleware' => ['auth:sanctum']]);