<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\GlucoseController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\MedicationLogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public posts routes (read-only)
Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Posts routes (create, update, delete protected)
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);

    // Comments routes
    Route::get('/posts/{post}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // Likes routes (toggle for posts and comments)
    Route::post('/posts/{post}/toggle-like', [LikeController::class, 'togglePost']);
    Route::post('/comments/{comment}/toggle-like', [LikeController::class, 'toggleComment']);

    // Glucose routes
    Route::apiResource('glucose', GlucoseController::class);

    // Meal routes
    Route::apiResource('meals', MealController::class);

    // Medication routes
    Route::apiResource('medications', controller: MedicationController::class);

    // Medication logs routes (track medication status)
    Route::apiResource('medication-logs', MedicationLogController::class)->only(['store', 'index', 'update', 'destroy']);
});

