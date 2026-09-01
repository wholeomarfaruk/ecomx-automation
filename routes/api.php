<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/courier/{courier}', [\App\Http\Controllers\Api\CourierWebhookController::class, 'handle'])
    ->name('webhooks.courier');
