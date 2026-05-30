<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GatewayContainerController;

/*
|--------------------------------------------------------------------------
| API Routes v1 - WowoClean UAP System
|--------------------------------------------------------------------------
| Versioning: /api/v1
| Authentication: JWT (Tymon\JwtAuth)
| Authorization: Role-based (admin / user)
*/

Route::prefix('v1')->group(function () {
    
    // ========== Authentication Routes (Public) ==========
    // Login endpoint - tidak perlu auth
    Route::post('login', [AuthController::class, 'login']);

    // Protected routes - semua endpoint di bawah ini memerlukan JWT token
    Route::middleware('auth:api')->group(function () {
        
        // Profile dan logout endpoint
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);

        // ========== API Gateway Routes (dengan versioning) ==========
        // Gateway prefix untuk semua operasi container
        Route::prefix('gateway')->group(function () {
            
            // GET /api/v1/gateway/containers - semua user bisa akses (admin dan user)
            Route::get('containers', [GatewayContainerController::class, 'index']);
            
            // GET /api/v1/gateway/containers/{id}/logs - semua user bisa akses tracking logs
            Route::get('containers/{id}/logs', [GatewayContainerController::class, 'getLogs']);

            // ========== ADMIN ONLY ROUTES ==========
            // POST, PATCH, DELETE - hanya admin yang bisa akses
            Route::middleware('role:admin')->group(function () {
                
                // POST /api/v1/gateway/containers - create container (admin only)
                Route::post('containers', [GatewayContainerController::class, 'store']);
                
                // PATCH /api/v1/gateway/containers/{id} - update container (admin only)
                Route::patch('containers/{id}', [GatewayContainerController::class, 'update']);
                
                // DELETE /api/v1/gateway/containers/{id} - delete container (admin only)
                Route::delete('containers/{id}', [GatewayContainerController::class, 'destroy']);
            });
        });
    });
});
