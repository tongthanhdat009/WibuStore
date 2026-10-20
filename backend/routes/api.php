<?php

use App\Http\Controllers\Api\V1\Admin\AdminCatalogController;
use App\Http\Controllers\Api\V1\Admin\AdminCmsController;
use App\Http\Controllers\Api\V1\Admin\AdminOperationsController;
use App\Http\Controllers\Api\V1\Admin\RbacController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\Public\CatalogController;
use App\Http\Controllers\Api\V1\Public\ReviewController;
use Illuminate\Support\Facades\Route;

// Fallback for unauthenticated redirects (always JSON 401, never HTML).
Route::get('/login', fn () => response()->json(['error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Chua dang nhap']], 401))->name('login');

Route::prefix('v1')->group(function () {
    // Public catalog
    Route::get('/health', [CatalogController::class, 'health']);
    Route::get('/products', [CatalogController::class, 'index']);
    Route::get('/products/{slug}', [CatalogController::class, 'show']);
    Route::get('/products/{slug}/reviews', [ReviewController::class, 'index']);
    Route::get('/products/{slug}/reviews/eligibility', [ReviewController::class, 'eligibility'])->middleware('auth:sanctum');
    Route::post('/products/{slug}/reviews', [ReviewController::class, 'store'])->middleware(['auth:sanctum', 'throttle:5,1']);
    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::get('/anime-series', [CatalogController::class, 'series']);
    Route::get('/manufacturers', [CatalogController::class, 'manufacturers']);
    Route::get('/preorders', [CatalogController::class, 'preorders']);
    Route::get('/preorders/{id}', [CatalogController::class, 'preorderShow']);
    Route::get('/pages/home', [CatalogController::class, 'home']);
    Route::get('/collections', [CatalogController::class, 'collections']);
    Route::get('/collections/{slug}', [CatalogController::class, 'collection']);

    // Auth (Sanctum stateful session)
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    // Cart & checkout (guest via X-Guest-Token, member via session)
    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::patch('/cart/items/{id}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
    Route::post('/checkout/preview', [CartController::class, 'preview']);
    Route::post('/orders', [CartController::class, 'checkout'])->middleware('throttle:20,1');
    Route::get('/orders/{publicId}', [CartController::class, 'showOrder']);
    Route::get('/orders/{publicId}/invoice', [CartController::class, 'invoice']);
    Route::post('/orders/{publicId}/cancel', [CartController::class, 'cancel']);
    Route::post('/orders/lookup', [CartController::class, 'lookup'])->middleware('throttle:10,1');

    // Webhooks (no CSRF/session; provider signature verified at gateway level in prod)
    Route::post('/webhooks/payments/{provider}', [AdminOperationsController::class, 'webhook']);

    // Customer account
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/me', [AuthController::class, 'updateMe']);
        Route::get('/me/orders', [CartController::class, 'myOrders']);
        Route::get('/me/addresses', [AuthController::class, 'addresses']);
        Route::post('/me/addresses', [AuthController::class, 'storeAddress']);
        Route::get('/wishlist', [AuthController::class, 'wishlist']);
        Route::post('/wishlist', [AuthController::class, 'storeWishlist']);
        Route::delete('/wishlist/{product}', [AuthController::class, 'destroyWishlist']);
    });

    // Admin (auth + per-permission)
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        Route::get('/dashboard/overview', [AdminOperationsController::class, 'overview'])->middleware('perm:reports.read');
        Route::get('/dashboard/tasks', [AdminOperationsController::class, 'tasks'])->middleware('perm:reports.read');
        Route::get('/products', [AdminCatalogController::class, 'products'])->middleware('perm:products.read');
        Route::post('/products', [AdminCatalogController::class, 'storeProduct'])->middleware('perm:products.write');
        Route::get('/products/{id}', [AdminCatalogController::class, 'showProduct'])->middleware('perm:products.read');
        Route::patch('/products/{id}', [AdminCatalogController::class, 'updateProduct'])->middleware('perm:products.write');
        Route::post('/products/{id}/publish', [AdminCatalogController::class, 'publish'])->middleware('perm:products.publish');
        Route::post('/products/{id}/archive', [AdminCatalogController::class, 'archive'])->middleware('perm:products.publish');
        Route::post('/products/{id}/variants', [AdminCatalogController::class, 'storeVariant'])->middleware('perm:products.write');
        Route::patch('/variants/{id}', [AdminCatalogController::class, 'updateVariant'])->middleware('perm:products.write');
        Route::match(['get', 'post'], '/categories', [AdminCatalogController::class, 'categories'])->middleware('perm:products.write');
        Route::match(['get', 'post'], '/anime-series', [AdminCatalogController::class, 'series'])->middleware('perm:products.write');
        Route::match(['get', 'post'], '/manufacturers', [AdminCatalogController::class, 'manufacturers'])->middleware('perm:products.write');
        Route::get('/reviews', [AdminCatalogController::class, 'reviews'])->middleware('perm:products.write');
        Route::patch('/reviews/{id}', [AdminCatalogController::class, 'moderateReview'])->middleware('perm:products.publish');

        Route::get('/orders', [AdminOperationsController::class, 'orders'])->middleware('perm:orders.transition');
        Route::get('/orders/{id}', [AdminOperationsController::class, 'showOrder'])->middleware('perm:orders.transition');
        Route::get('/orders/{id}/invoice', [AdminOperationsController::class, 'invoice'])->middleware('perm:orders.transition');
        Route::post('/orders/{id}/transition', [AdminOperationsController::class, 'transition'])->middleware('perm:orders.transition');
        Route::get('/inventory', [AdminOperationsController::class, 'inventory'])->middleware('perm:inventory.adjust');
        Route::post('/inventory/adjustments', [AdminOperationsController::class, 'adjust'])->middleware('perm:inventory.adjust');
        Route::get('/inventory/movements', [AdminOperationsController::class, 'movements'])->middleware('perm:inventory.adjust');
        Route::match(['get', 'post'], '/preorder-campaigns', [AdminOperationsController::class, 'campaigns'])->middleware('perm:preorders.manage');
        Route::get('/preorder-campaigns/{id}', [AdminOperationsController::class, 'showCampaign'])->middleware('perm:preorders.manage');
        Route::post('/preorder-campaigns/{id}/open', [AdminOperationsController::class, 'openCampaign'])->middleware('perm:preorders.manage');
        Route::post('/preorder-campaigns/{id}/close', [AdminOperationsController::class, 'closeCampaign'])->middleware('perm:preorders.manage');
        Route::get('/preorder-reservations', [AdminOperationsController::class, 'reservations'])->middleware('perm:preorders.manage');
        Route::post('/preorder-reservations/{id}/balance-due', [AdminOperationsController::class, 'balanceDue'])->middleware('perm:preorders.manage');
        Route::post('/payments/{id}/confirm', [AdminOperationsController::class, 'confirmPayment'])->middleware('perm:payments.confirm');
        Route::post('/payments/{id}/reject', [AdminOperationsController::class, 'rejectPayment'])->middleware('perm:payments.confirm');
        Route::get('/customers', [AdminOperationsController::class, 'customers'])->middleware('perm:orders.transition');
        Route::get('/customers/{id}', [AdminOperationsController::class, 'showCustomer'])->middleware('perm:orders.transition');
        Route::get('/reports/sales', [AdminOperationsController::class, 'salesReport'])->middleware('perm:reports.read');

        Route::match(['get', 'post'], '/media', [AdminCmsController::class, 'media'])->middleware('perm:products.write');
        Route::post('/media/{id}/complete', [AdminCmsController::class, 'completeMedia'])->middleware('perm:products.write');
        Route::delete('/media/{id}', [AdminCmsController::class, 'deleteMedia'])->middleware('perm:products.write');
        Route::match(['get', 'post'], '/pages', [AdminCmsController::class, 'pages'])->middleware('perm:cms.publish');
        Route::get('/pages/{slug}/revisions', [AdminCmsController::class, 'revisions'])->middleware('perm:cms.publish');
        Route::post('/pages/{slug}/revisions', [AdminCmsController::class, 'storeRevision'])->middleware('perm:cms.publish');
        Route::post('/pages/{slug}/revisions/{id}/publish', [AdminCmsController::class, 'publishRevision'])->middleware('perm:cms.publish');
        Route::match(['get', 'post'], '/coupons', [AdminCmsController::class, 'coupons'])->middleware('perm:products.write');
        Route::get('/activity-logs', [AdminCmsController::class, 'logs'])->middleware('perm:users.manage');
        Route::get('/users', [AdminCmsController::class, 'users'])->middleware('perm:users.manage');
        Route::get('/roles', [AdminCmsController::class, 'roles'])->middleware('perm:users.manage');
        Route::put('/users/{id}/roles', [AdminCmsController::class, 'updateRoles'])->middleware('perm:users.manage');

        Route::get('/permissions', [RbacController::class, 'permissions'])->middleware('perm:users.manage');
        Route::get('/rbac/roles', [RbacController::class, 'roles'])->middleware('perm:users.manage');
        Route::post('/rbac/roles', [RbacController::class, 'storeRole'])->middleware('perm:users.manage');
        Route::patch('/rbac/roles/{id}', [RbacController::class, 'updateRole'])->middleware('perm:users.manage');
        Route::delete('/rbac/roles/{id}', [RbacController::class, 'destroyRole'])->middleware('perm:users.manage');
        Route::get('/staff', [RbacController::class, 'staff'])->middleware('perm:users.manage');
        Route::post('/staff', [RbacController::class, 'storeStaff'])->middleware('perm:users.manage');
        Route::patch('/staff/{id}', [RbacController::class, 'updateStaff'])->middleware('perm:users.manage');
        Route::delete('/staff/{id}', [RbacController::class, 'destroyStaff'])->middleware('perm:users.manage');
        Route::match(['get', 'patch'], '/settings', [AdminCmsController::class, 'settings'])->middleware('perm:settings.manage');
    });
});
