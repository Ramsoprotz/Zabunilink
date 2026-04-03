<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TenderApplicationController;
use App\Http\Controllers\Api\TenderController;
use App\Http\Controllers\Api\TendereeController;
use App\Models\Category;
use App\Models\Location;
use App\Models\Tender;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/payments/callback', [PaymentController::class, 'callback']);

// Public browsing
Route::get('/tenders', [TenderController::class, 'index']);
Route::get('/tenders/{id}', [TenderController::class, 'show']);
Route::get('/plans', [SubscriptionController::class, 'plans']);
Route::get('/categories', function () {
    return response()->json(['data' => Category::where('is_active', true)->get()]);
});
Route::get('/locations', function () {
    return response()->json(['data' => Location::where('is_active', true)->get()]);
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{tenderId}', [FavoriteController::class, 'destroy']);

    // Subscriptions
    Route::get('/my-subscription', [SubscriptionController::class, 'mySubscription']);
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('/subscription/change-plan', [SubscriptionController::class, 'changePlan']);
    Route::post('/subscription/preview-change', [SubscriptionController::class, 'previewChange']);
    Route::post('/subscription/cancel-scheduled', [SubscriptionController::class, 'cancelScheduledChange']);
    Route::post('/subscription/start-trial', [SubscriptionController::class, 'startTrial']);

    // Payments
    Route::get('/payments/{reference}/status', [PaymentController::class, 'checkStatus']);

    // Tender Applications
    Route::get('/my-applications', [TenderApplicationController::class, 'index']);
    Route::post('/applications', [TenderApplicationController::class, 'store']);
    Route::get('/applications/{id}', [TenderApplicationController::class, 'show']);
    Route::post('/applications/{id}/documents', [TenderApplicationController::class, 'uploadDocuments']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notification-preferences', [NotificationController::class, 'preferences']);
    Route::put('/notification-preferences', [NotificationController::class, 'updatePreferences']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markRead']);

});

// Tenderee routes (Business plan)
Route::middleware(['auth:sanctum', 'business'])->prefix('tenderee')->group(function () {
    Route::get('/tenders', [TendereeController::class, 'index']);
    Route::post('/tenders', [TendereeController::class, 'store']);
    Route::get('/tenders/{id}', [TendereeController::class, 'show']);
    Route::put('/tenders/{id}', [TendereeController::class, 'update']);
    Route::delete('/tenders/{id}', [TendereeController::class, 'destroy']);
    Route::post('/tenders/{id}/documents', [TendereeController::class, 'uploadDocuments']);
    Route::delete('/tenders/{id}/documents', [TendereeController::class, 'deleteDocument']);
    Route::get('/tenders/{tenderId}/applications', [TendereeController::class, 'applications']);
    Route::put('/tenders/{tenderId}/applications/{appId}', [TendereeController::class, 'updateApplication']);
    Route::get('/stats', [TendereeController::class, 'stats']);
});

// Public: system branding
Route::get('/branding', function () {
    $logo = \App\Models\Setting::get('system_logo');
    $favicon = \App\Models\Setting::get('system_favicon');
    return response()->json([
        'data' => [
            'logo_url' => $logo ? asset(\Illuminate\Support\Facades\Storage::url($logo)) : null,
            'favicon_url' => $favicon ? asset(\Illuminate\Support\Facades\Storage::url($favicon)) : null,
        ],
    ]);
});

// Public: increment tender view count
Route::post('/tenders/{id}/view', function ($id) {
    Tender::where('id', $id)->increment('views_count');
    return response()->json(['ok' => true]);
});
