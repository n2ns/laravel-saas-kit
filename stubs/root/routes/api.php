<?php

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\BlogPostController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OttController;
use App\Http\Controllers\Api\ProductConfigController;
use App\Http\Controllers\Api\ProductUsageController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

/*
|--------------------------------------------------------------------------
| Product Site API Routes
|--------------------------------------------------------------------------
|
*/

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function () {
    // ========== Public Authentication Routes ==========
    Route::prefix('auth')->group(function () {
        Route::post('/google', [ApiAuthController::class, 'google'])
            ->middleware('throttle:api-auth')
            ->name('api.auth.google');
        // Refresh is usually public because Access Token might be expired
        Route::post('/refresh', [ApiAuthController::class, 'refresh'])
            ->middleware('throttle:api-refresh')
            ->name('api.auth.refresh');
    });

    Route::get('/products/{product}/config', [ProductConfigController::class, 'show'])
        ->name('api.products.config');

    // ========== Protected Routes (JWT Required) ==========
    Route::middleware(['auth:api', CheckToken::using('api'), 'auth.epoch', 'auth.session'])->group(function () {
        // License status by product
        Route::get('/license/{product}', [LicenseController::class, 'show']);

        // Auth management
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [ApiAuthController::class, 'logout'])->name('api.auth.logout');
            Route::get('/me', [ApiAuthController::class, 'me'])->name('api.auth.me');
            Route::get('/sessions', [ApiAuthController::class, 'sessions'])->name('api.auth.sessions');
            Route::post('/sessions/revoke', [ApiAuthController::class, 'revokeSessions'])->name('api.auth.sessions.revoke_many');
            Route::delete('/sessions/{session}', [ApiAuthController::class, 'revokeSession'])->name('api.auth.sessions.revoke');

            // Generate One-Time Token for web login
            Route::post('/ott', [OttController::class, 'store']);
        });

        // Subscriptions & Commerce
        Route::get('/subscriptions', [SubscriptionController::class, 'index']);
        Route::get('/orders', [OrderController::class, 'index']);

        // Product usage events
        Route::prefix('product-usage/{client}')->group(function () {
            Route::post('/track', [ProductUsageController::class, 'track'])
                ->middleware('throttle:product-usage-track');
            Route::get('/daily', [ProductUsageController::class, 'daily'])
                ->middleware('throttle:product-usage-read');
            Route::get('/summary', [ProductUsageController::class, 'summary'])
                ->middleware('throttle:product-usage-read');
            Route::get('/events', [ProductUsageController::class, 'eventTypes'])
                ->middleware('throttle:product-usage-read');
        });

    });

    // ========== Bot API for Blog Management ==========
    Route::middleware(['api_key'])->prefix('blog')->group(function () {
        Route::get('/', [BlogPostController::class, 'index']);
        Route::post('/', [BlogPostController::class, 'store']);
        Route::get('/{blogPost:slug}', [BlogPostController::class, 'show']);
        Route::put('/{blogPost:slug}', [BlogPostController::class, 'update']);
        Route::delete('/{blogPost:slug}', [BlogPostController::class, 'destroy']);
    });

    if (filter_var(env('SAAS_KIT_LEGACY_MCP_ROUTES', false), FILTER_VALIDATE_BOOLEAN)) {
        // ========== Legacy MCP Management API ==========
        Route::middleware(['api_key'])->prefix('mcp')->group(function () {
            Route::get('/capabilities', [App\Http\Controllers\Api\Mcp\BlogPostController::class, 'capabilities']);
            Route::get('/products/{contentScope}', [App\Http\Controllers\Api\Mcp\BlogPostController::class, 'productContext']);
            Route::get('/posts', [App\Http\Controllers\Api\Mcp\BlogPostController::class, 'index']);
            Route::post('/posts', [App\Http\Controllers\Api\Mcp\BlogPostController::class, 'store']);
            Route::get('/posts/{id}', [App\Http\Controllers\Api\Mcp\BlogPostController::class, 'show']);
            Route::put('/posts/{id}', [App\Http\Controllers\Api\Mcp\BlogPostController::class, 'update']);
            Route::patch('/posts/{id}', [App\Http\Controllers\Api\Mcp\BlogPostController::class, 'update']);
            Route::post('/posts/{id}/publish', [App\Http\Controllers\Api\Mcp\BlogPostController::class, 'publish']);
        });
    }

});
