<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiUserController;
use App\Http\Controllers\Api\ApiScriptController;
use App\Http\Controllers\Api\ApiSiteController;
use App\Http\Controllers\Api\ApiPersonaController;
use App\Http\Controllers\Api\SyncController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [ApiUserController::class, 'show']);

    Route::apiResource('scripts', ApiScriptController::class);
    Route::apiResource('sites', ApiSiteController::class);
    Route::apiResource('personas', ApiPersonaController::class);

    Route::post('/sync', [SyncController::class, 'sync'])
        ->middleware('subscribed');
});
