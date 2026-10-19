<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BoardController;
use App\Http\Controllers\Api\V1\PinController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SaveController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    Route::middleware('throttle:6,1')->group(function (): void {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
    });

    /*
    |--------------------------------------------------------------------------
    | Public read endpoints
    |--------------------------------------------------------------------------
    */
    Route::get('pins', [PinController::class, 'index']);
    Route::get('pins/{pin}', [PinController::class, 'show'])->whereNumber('pin');
    Route::get('search', SearchController::class);

    Route::get('boards/{board}', [BoardController::class, 'show'])->whereNumber('board');
    Route::get('boards/{board}/pins', [BoardController::class, 'pins'])->whereNumber('board');

    Route::get('users/{user:username}', [ProfileController::class, 'show']);
    Route::get('users/{user:username}/pins', [ProfileController::class, 'pins']);
    Route::get('users/{user:username}/boards', [ProfileController::class, 'boards']);

    /*
    |--------------------------------------------------------------------------
    | Authenticated endpoints
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::post('pins', [PinController::class, 'store']);
        Route::match(['put', 'patch'], 'pins/{pin}', [PinController::class, 'update'])->whereNumber('pin');
        Route::delete('pins/{pin}', [PinController::class, 'destroy'])->whereNumber('pin');

        Route::post('pins/{pin}/save', [SaveController::class, 'store'])->whereNumber('pin');
        Route::delete('pins/{pin}/save', [SaveController::class, 'destroy'])->whereNumber('pin');
        Route::get('saves', [SaveController::class, 'index']);

        Route::get('boards', [BoardController::class, 'index']);
        Route::post('boards', [BoardController::class, 'store']);
        Route::match(['put', 'patch'], 'boards/{board}', [BoardController::class, 'update'])->whereNumber('board');
        Route::delete('boards/{board}', [BoardController::class, 'destroy'])->whereNumber('board');

        Route::get('profile', [ProfileController::class, 'me']);
        Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update']);
        Route::post('profile/avatar', [ProfileController::class, 'updateAvatar']);

        Route::get('settings', [SettingsController::class, 'show']);
        Route::match(['put', 'patch'], 'settings', [SettingsController::class, 'update']);
        Route::match(['put', 'patch'], 'settings/password', [SettingsController::class, 'updatePassword']);
    });
});
