<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login']);
    Route::post('/forgot-password', [App\Http\Controllers\API\AuthController::class, 'forgotPassword']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [App\Http\Controllers\API\AuthController::class, 'logout']);
        Route::get('/user', [App\Http\Controllers\API\AuthController::class, 'user']);
        
        // More API endpoints will be added here in future phases
    });
});