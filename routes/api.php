<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\AdminController;

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

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Public survey routes (accessible via invitation token)
    Route::get('/surveys/{survey}', [SurveyController::class, 'show']);
    Route::post('/surveys/{survey}/start', [SurveyController::class, 'startResponse']);
    Route::post('/surveys/{survey}/answer', [SurveyController::class, 'submitAnswer']);
    Route::post('/surveys/{survey}/complete', [SurveyController::class, 'completeResponse']);
    Route::get('/surveys/{survey}/progress', [SurveyController::class, 'getProgress']);
});

// Protected routes (authentication required)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Authentication routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Authenticated survey routes
    Route::get('/my-surveys', [SurveyController::class, 'mySurveys']);
    Route::get('/my-responses', [SurveyController::class, 'myResponses']);
});

// Admin-only routes (authentication + admin role required)
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);

    // Alumni management (Alumni Bank)
    Route::get('/alumni', [AdminController::class, 'getAlumni']);
    Route::get('/alumni/stats', [AdminController::class, 'getAlumniStats']);
    Route::get('/alumni/{id}', [AdminController::class, 'getAlumniProfile']);
    Route::get('/alumni/export', [AdminController::class, 'exportAlumni']);

    // Survey management
    Route::get('/surveys', [AdminController::class, 'getSurveys']);
    Route::get('/surveys/{survey}/responses', [AdminController::class, 'getSurveyResponses']);
    Route::get('/surveys/{survey}/export', [AdminController::class, 'exportSurveyResponses']);

    // Batch management
    Route::get('/batches', [AdminController::class, 'getBatches']);
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0'
    ]);
});
