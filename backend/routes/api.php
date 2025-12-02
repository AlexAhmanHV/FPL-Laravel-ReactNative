<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\PasswordResetController;

// ──────────────────────────
// Public auth endpoints
// ──────────────────────────

// Registration & login are public, but rate-limited to avoid brute-force attacks
Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:6,1'); // 6 requests per minute

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1'); // 10 login attempts per minute

// Password reset flows are also public but strongly rate-limited
Route::post('/auth/forgot-password', [PasswordResetController::class, 'forgot'])
    ->middleware('throttle:3,1'); // 3 reset emails per minute

Route::post('/auth/reset-password', [PasswordResetController::class, 'reset'])
    ->middleware('throttle:6,1'); // 6 reset attempts per minute

// Email verification link (clicked from email)
// Must be signed to prevent tampering, and lightly throttled
Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// ──────────────────────────
// Authenticated routes (need Sanctum token)
// ──────────────────────────

Route::middleware('auth:sanctum')->group(function () {
    // Basic auth actions
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me',           [AuthController::class, 'me']);

    // High-level status for onboarding / app state
    Route::get('/me/summary',   [UserController::class, 'summary']);

    // Resend verification email (user must be logged in)
    Route::post('/auth/email/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1'); // prevent spam

    // Routes that require verified email as well
    Route::middleware('verified')->group(function () {
        // Link FPL ID
        Route::post('/me/link-fpl', [UserController::class, 'linkFpl']);

        // Team sync + read
        Route::post('/me/sync-team', [TeamController::class, 'syncMyTeam']);
        Route::get('/my-team',       [TeamController::class, 'myTeam']);
    });
});

// ──────────────────────────
// Public data
// ──────────────────────────

// Players list (read-only public info is fine to expose)
Route::get('/players', [PlayerController::class, 'index']);

// ──────────────────────────
// Dev-only route
// ──────────────────────────

// FPL bootstrap sync - only available in local/dev environment
if (app()->environment('local')) {
    Route::get('/dev/sync-bootstrap', [SyncController::class, 'syncBootstrap']);
}
