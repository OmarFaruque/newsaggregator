<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\UserPreferenceController;


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


// Registration Route
Route::post('register', [AuthController::class, 'register']);

// Login Route
Route::post('login', [AuthController::class, 'login']);

// Logout Route (Authenticated Users Only)
Route::middleware('auth:sanctum')->post('logout', [AuthController::class, 'logout']);

// Password Reset Routes
Route::post('password/email', [PasswordResetController::class, 'sendResetLinkEmail']);
Route::post('password/reset', [PasswordResetController::class, 'reset'])->name('password.reset');

// Articles 
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{id}', [ArticleController::class, 'show'])->where('id', '.*');


// Preferences
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/preferences', [UserPreferenceController::class, 'store']);
    Route::get('/user/preferences', [UserPreferenceController::class, 'show']);
    Route::get('/user/personalized-feed', [UserPreferenceController::class, 'personalizedFeed']);
    Route::get('/articles/fetch', [ArticleController::class, 'fetchArticles']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
