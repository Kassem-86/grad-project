<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    BlockController,
    PostController,
    CommentController,
    LikeController,
    FriendshipController
    
};
use App\Http\Controllers\{
    GlucoseController,
    MealController,
    MedicationController,
    MedicationLogController

};

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/check-email', [AuthController::class, 'checkEmail']);

// المشاهدة فقط (Posts & Comments)
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);
Route::get('/posts/{post}/comments', [CommentController::class, 'index']);

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
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- Community: Posts ---
    Route::apiResource('posts', PostController::class);

    // --- Community: Comments ---
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']); // ✅ مكانها الصح
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // --- Community: Likes (Polymorphic) ---
    Route::post('/posts/{post}/like', [LikeController::class, 'togglePost']);
    Route::post('/comments/{comment}/like', [LikeController::class, 'toggleComment']);

    // --- Community: Friendships ---
    Route::post('/friends/{id}/request', [FriendshipController::class, 'sendRequest']);
    Route::post('/friends/{id}/accept', [FriendshipController::class, 'acceptRequest']);
    Route::delete('/friends/{id}', [FriendshipController::class, 'removeFriend']);
      Route::post('/friends/{id}/block', [BlockController::class, 'block']);
    Route::delete('/friends/{id}/unblock', [BlockController::class, 'unblock']);

    // --- Health Tracking (Resources) ---
    Route::apiResource('glucose', GlucoseController::class);
    Route::apiResource('meals', MealController::class);
    Route::apiResource('medications', MedicationController::class);
    Route::apiResource('medication-logs', MedicationLogController::class)->only(['store', 'index', 'update', 'destroy']);

    // ... الـ routes القديمة
  
});