<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\RecyclableCategoryController;
use App\Http\Controllers\Api\V1\RecycleOrderController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Online Recycle System API - Version 1
| All routes are prefixed with /api/v1
|
*/

Route::prefix('v1')->group(function () {

    // ── Public Routes ──────────────────────────────────────

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        // Social Login (web redirect flow)
        Route::get('/{provider}/redirect', [AuthController::class, 'redirectToProvider'])
            ->where('provider', 'google|microsoft|apple');
        Route::get('/{provider}/callback', [AuthController::class, 'handleProviderCallback'])
            ->where('provider', 'google|microsoft|apple');

        // Social Login (token-based for mobile apps)
        Route::post('/{provider}/token', [AuthController::class, 'socialLoginWithToken'])
            ->where('provider', 'google|microsoft|apple');
    });

    // Public category listing
    Route::get('/categories', [RecyclableCategoryController::class, 'index']);
    Route::get('/categories/{category}', [RecyclableCategoryController::class, 'show']);

    // Public branch listing
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/{branch}', [BranchController::class, 'show']);

    // Price estimate (public)
    Route::post('/orders/estimate', [RecycleOrderController::class, 'estimate']);


    // ── Authenticated Routes ───────────────────────────────

    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
        });

        // Profile
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/profile', [UserController::class, 'updateProfile']); // for multipart

        // ── Customer Routes ────────────────────────────────

        // Orders (customers can create & view their own)
        Route::get('/orders', [RecycleOrderController::class, 'index']);
        Route::post('/orders', [RecycleOrderController::class, 'store']);
        Route::get('/orders/{order}', [RecycleOrderController::class, 'show']);
        Route::post('/orders/{order}/cancel', [RecycleOrderController::class, 'cancel']);

        // Payments (customers can view their own)
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::put('/{notification}/read', [NotificationController::class, 'markAsRead']);
            Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::delete('/{notification}', [NotificationController::class, 'destroy']);
        });


        // ── Staff / Branch Manager Routes ──────────────────

        Route::middleware('role:admin,branch_manager,staff')->group(function () {
            // Order management
            Route::put('/orders/{order}/status', [RecycleOrderController::class, 'updateStatus']);

            // Payment processing
            Route::post('/orders/{order}/payment', [PaymentController::class, 'processPayment']);
            Route::put('/payments/{payment}/status', [PaymentController::class, 'updateStatus']);

            // Inventory
            Route::get('/inventory', [InventoryController::class, 'index']);
            Route::post('/inventory', [InventoryController::class, 'store']);
            Route::get('/inventory/{inventoryRecord}', [InventoryController::class, 'show']);
            Route::get('/inventory/branch/{branchId}/stock', [InventoryController::class, 'branchStock']);
        });


        // ── Branch Manager Routes ──────────────────────────

        Route::middleware('role:admin,branch_manager')->group(function () {
            // Branch staff management
            Route::post('/branches/{branch}/staff', [BranchController::class, 'assignStaff']);
            Route::delete('/branches/{branch}/staff/{userId}', [BranchController::class, 'removeStaff']);
            Route::get('/branches/{branch}/staff', [BranchController::class, 'staff']);

            // Branch dashboard
            Route::get('/dashboard/branch/{branchId}', [DashboardController::class, 'branchDashboard']);
        });


        // ── Admin Only Routes ──────────────────────────────

        Route::middleware('role:admin')->group(function () {
            // User management
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::put('/users/{user}', [UserController::class, 'adminUpdate']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);

            // Branch CRUD
            Route::post('/branches', [BranchController::class, 'store']);
            Route::put('/branches/{branch}', [BranchController::class, 'update']);
            Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);

            // Category CRUD
            Route::post('/categories', [RecyclableCategoryController::class, 'store']);
            Route::put('/categories/{category}', [RecyclableCategoryController::class, 'update']);
            Route::delete('/categories/{category}', [RecyclableCategoryController::class, 'destroy']);

            // Admin Dashboard & Reports
            Route::get('/dashboard/admin', [DashboardController::class, 'adminDashboard']);
            Route::get('/reports/collections', [DashboardController::class, 'collectionReport']);
            Route::get('/reports/orders', [DashboardController::class, 'orderStats']);
            Route::get('/reports/revenue', [DashboardController::class, 'revenueStats']);
        });
    });
});
