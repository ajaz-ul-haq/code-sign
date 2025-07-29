<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CodeSignController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/code-sign', [CodeSignController::class, 'handleCodeSign']);
});

Route::get('/auth/info', [AuthController::class, 'info']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::fallback(\App\Http\Controllers\Controller::invalidApiEndPoint(...));
