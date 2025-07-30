<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\IncomingRequestController;


Route::get('/agent/download/{hash}', [IncomingRequestController::class, 'downloadAgent']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/code-sign/request', [IncomingRequestController::class, 'initializeCodeSigning']);
    Route::post('/agent/upload',[IncomingRequestController::class, 'saveCompiledAgents']);
    Route::get('/auth/info', [AuthController::class, 'info']);
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::fallback(\App\Http\Controllers\Controller::invalidApiEndPoint(...));
