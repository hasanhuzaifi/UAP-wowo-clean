<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContainerController;

/*
|--------------------------------------------------------------------------
| API Routes - WowoClean System
|--------------------------------------------------------------------------
*/

Route::prefix('containers')->group(function () {
    // CRUD Operations
    Route::get('/', [ContainerController::class, 'index']);
    Route::post('/', [ContainerController::class, 'store']);
    Route::get('/{id}', [ContainerController::class, 'show']);
    Route::patch('/{id}', [ContainerController::class, 'update']);
    Route::delete('/{id}', [ContainerController::class, 'destroy']);
    
    // Search & Filter
    Route::get('/search/filter', [ContainerController::class, 'search']);
    
    // Nested Resource - Tracking Logs
    Route::get('/{id}/logs', [ContainerController::class, 'getLogs']);
});