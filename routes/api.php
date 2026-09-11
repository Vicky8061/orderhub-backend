<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ComboController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\ModifierController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

// Public read-only menu access (useful for kiosk / digital boards)
Route::get('/menu-display', [MenuItemController::class, 'index']);
Route::get('/categories-display', [CategoryController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Authenticated Staff Routes (Admin, Cashier, Kitchen)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Menu Reading (Admin, Cashier, Kitchen)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/menu-items', [MenuItemController::class, 'index']);
    Route::get('/menu-items/{menuItem}', [MenuItemController::class, 'show']);
    Route::get('/modifiers', [ModifierController::class, 'index']);
    Route::get('/modifiers/{modifier}', [ModifierController::class, 'show']);
    Route::get('/combos', [ComboController::class, 'index']);
    Route::get('/combos/{combo}', [ComboController::class, 'show']);

    // Order Operations
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::get('/orders/{order}/receipt', [OrderController::class, 'receipt']);

    // Kitchen Display Queue
    Route::get('/kitchen/queue', [KitchenController::class, 'queue']);

    /*
    |--------------------------------------------------------------------------
    | Admin Only Management & Reporting Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        // Void / Cancel Order
        Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

        // Category Management
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Menu Item Management
        Route::post('/menu-items', [MenuItemController::class, 'store']);
        Route::match(['put', 'post'], '/menu-items/{menuItem}', [MenuItemController::class, 'update']);
        Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy']);

        // Modifier Management
        Route::post('/modifiers', [ModifierController::class, 'store']);
        Route::put('/modifiers/{modifier}', [ModifierController::class, 'update']);
        Route::delete('/modifiers/{modifier}', [ModifierController::class, 'destroy']);

        // Combo Management
        Route::post('/combos', [ComboController::class, 'store']);
        Route::put('/combos/{combo}', [ComboController::class, 'update']);
        Route::delete('/combos/{combo}', [ComboController::class, 'destroy']);

        // Reports & Analytics
        Route::get('/reports/sales-summary', [ReportController::class, 'salesSummary']);
        Route::get('/reports/best-sellers', [ReportController::class, 'bestSellers']);
        Route::get('/reports/revenue-by-hour', [ReportController::class, 'revenueByHour']);
        Route::get('/reports/staff-performance', [ReportController::class, 'staffPerformance']);
    });
});
