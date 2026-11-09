<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// public
Route::post('/login', [AuthController::class, 'login']);

// needs a logged-in, active user
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});