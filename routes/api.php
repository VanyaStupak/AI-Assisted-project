<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PromoCodeController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::post('/promo/claim', [PromoCodeController::class, 'claim']);
    Route::get('/promo/history', [PromoCodeController::class, 'history']);
    Route::patch('/promo/{claim}/revoke', [PromoCodeController::class, 'revoke']);
});
