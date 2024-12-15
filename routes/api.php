<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('log.activity')
        ->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('log.activity')
        ->name('auth.login');

    Route::middleware(['auth:api', 'log.activity'])->post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::middleware(['log.activity'])->post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');

    Route::middleware(['auth:api', 'log.activity'])->post('/otp-request', [AuthController::class, 'sendOtp'])->name('auth.otp.send');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('log.activity')
        ->name('auth.otp.verify');

    Route::middleware(['log.activity'])->post('/otp-request/forgot-password', [AuthController::class, 'otpRequestForgotPassword'])->name('auth.otp.request.forgot-password');
    Route::middleware(['log.activity'])->post('/otp-verify/forgot-password', [AuthController::class, 'otpVerifyForgotPassword'])->name('auth.otp.verify.forgot-password');
});

// users
use App\Http\Controllers\UserController;

Route::middleware(['auth:api', 'permission:users.manage'])->group(function () {
    Route::resource('users', UserController::class)->except(['create', 'edit']);
});
Route::post('/users-search', [UserController::class, 'searchByName']);


// roles
use App\Http\Controllers\RoleController;

Route::middleware(['auth:api', 'permission:role-list'])->get('roles', [RoleController::class, 'index']);
Route::middleware(['auth:api', 'permission:role-create'])->post('roles', [RoleController::class, 'store']);
Route::middleware(['auth:api', 'permission:role-edit'])->put('roles/{id}', [RoleController::class, 'update']);
Route::middleware(['auth:api', 'permission:role-delete'])->delete('roles/{id}', [RoleController::class, 'destroy']);
Route::middleware(['auth:api', 'permission:role-add-permissions'])->put('roles/{id}/permissions', [RoleController::class, 'assignPermissions']);
Route::post('/roles/{id}/add-permissions', [RoleController::class, 'addPermissionToRole']);
Route::middleware(['auth:api'])->get('roles/{id}', [RoleController::class, 'show']);
Route::middleware(['auth:api'])->get('permissions', [RoleController::class, 'showPermissions']);
// log-activity
use App\Http\Controllers\LogActivityController;

Route::middleware(['auth:api', 'permission:log-activity'])->group(function () {
    Route::post('/log-activity/search', [LogActivityController::class, 'search']);
    Route::get('/log-activity', [LogActivityController::class, 'index'])->name('log.activity.index');
    Route::get('/logs/user/{userId}', [LogActivityController::class, 'getLogsByUserId']);
    Route::get('/log-detail/{logId}', [LogActivityController::class, 'getLogDetail'])->name('log.detail');
});

// categories
use App\Http\Controllers\KategoriController;

Route::middleware(['auth:api', 'permission:kategori-list'])->get('/categories', [KategoriController::class, 'index']);
Route::middleware(['auth:api', 'permission:kategori-show'])->get('/categories/{id}', [KategoriController::class, 'show']);
Route::middleware(['auth:api', 'permission:kategori-create'])->post('/categories', [KategoriController::class, 'store']);
Route::middleware(['auth:api', 'permission:kategori-edit'])->put('/categories/{id}', [KategoriController::class, 'update']);
Route::middleware(['auth:api', 'permission:kategori-delete'])->delete('/categories/{id}', [KategoriController::class, 'destroy']);

// Promo
use App\Http\Controllers\PromoController;

Route::middleware(['auth:api', 'permission:promo-list'])->get('/promo', [PromoController::class, 'index']);
Route::middleware(['auth:api', 'permission:promo-show'])->get('/promo/{id}', [PromoController::class, 'show']);
Route::middleware(['auth:api', 'permission:promo-create'])->post('/promo', [PromoController::class, 'store']);
Route::middleware(['auth:api', 'permission:promo-edit'])->put('/promo/{id}', [PromoController::class, 'update']);
Route::middleware(['auth:api', 'permission:promo-delete'])->delete('/promo/{id}', [PromoController::class, 'destroy']);


// Product
use App\Http\Controllers\ProductController;

Route::middleware(['auth:api', 'permission:produk-list'])->get('/products', [ProductController::class, 'index']);
Route::middleware(['auth:api', 'permission:produk-show'])->get('/products/{id}', [ProductController::class, 'show']);
Route::middleware(['auth:api', 'permission:produk-create'])->post('/products', [ProductController::class, 'store']);
Route::middleware(['auth:api', 'permission:produk-edit'])->put('/products/{id}', [ProductController::class, 'update']);
Route::middleware(['auth:api', 'permission:produk-delete'])->delete('/products/{id}', [ProductController::class, 'destroy']);
Route::middleware(['auth:api'])->get('/products-all', [ProductController::class, 'productsAll']);
Route::middleware(['auth:api'])->post('/search-products', [ProductController::class, 'searchProduct']);

Route::middleware(['auth:api'])->get('/products-view', [ProductController::class, 'productsView']);

// Cart
use App\Http\Controllers\CartController;

Route::middleware(['auth:api', 'permission:cart-list'])->get('/cart', [CartController::class, 'index']);
Route::middleware(['auth:api', 'permission:cart-add', 'log.activity'])
    ->post('/cart/add', [CartController::class, 'add'])
    ->name('cart.add');
    Route::get('/cart/count', [CartController::class, 'countItems']);

Route::middleware(['auth:api', 'permission:cart-edit'])->put('/cart/update/{item_id}', [CartController::class, 'update']);
Route::middleware(['auth:api'])->delete('/cart/remove/{item_id}', [CartController::class, 'remove']);
// Route::middleware(['auth:api'])->get('/cart/klaim-voucher', [CartController::class, 'index']);
// Payment
use App\Http\Controllers\PaymentController;

Route::middleware(['auth:api'])->post('/payment/{orderId}', [PaymentController::class, 'createTransaction']);
Route::middleware(['auth:api'])->post('/payment/notification', [PaymentController::class, 'notificationHandler']);
Route::middleware(['auth:api'])->get('/pay/{orderId}', [PaymentController::class, 'payView'])->name('pay');


// Order
use App\Http\Controllers\OrderController;

// Route::middleware(['auth:api'])->group(function () {
    Route::middleware(['auth:api', 'permission:order-create'])->post('/orders', [OrderController::class, 'create']);        // Membuat order baru
    Route::middleware(['auth:api', 'permission:order-list'])->get('/orders', [OrderController::class, 'index']);         // Mengambil daftar order
    Route::middleware(['auth:api', 'permission:order-show'])->get('/orders/{id}', [OrderController::class, 'show']);     // Mengambil detail order
    Route::middleware(['auth:api', 'permission:order-edit'])->put('/orders/{id}', [OrderController::class, 'update']);   // Mengupdate status order
    Route::middleware(['auth:api', 'permission:order-delete'])->delete('/orders/{id}', [OrderController::class, 'destroy']);// Menghapus order
// });

Route::post('/order-now', [OrderController::class, 'orderNow']);
Route::middleware(['auth:api'])->post('/orders-search', [OrderController::class, 'searchOrder']);         // Mengambil daftar order
// Route::middleware(['auth:api'])->get('/orders/user', [OrderController::class, 'getOrdersByUser']);
Route::middleware(['auth:api', 'permission:order-list'])->get('/orders-user', [OrderController::class, 'indexx']);  

// Transaction
use App\Http\Controllers\TransactionController;

// Route::middleware(['auth:api'])->group(function () {
    Route::middleware(['auth:api', 'permission:transaksi-list'])->get('/transactions', [TransactionController::class, 'index']);           // Daftar transaksi
    // Rute yang baru
Route::middleware(['auth:api', 'permission:transaksi-show'])->get('/transactions/{transaksi_id}', [TransactionController::class, 'showByTransaction']);

    Route::middleware(['auth:api', 'permission:transaksi-edit'])->put('/transactions/{id}', [TransactionController::class, 'update']);     // Update status transaksi
// });

// Review
use App\Http\Controllers\ReviewController;

Route::middleware('auth:api')->group(function () {
    Route::middleware(['auth:api', 'permission:review-create'])->post('/reviews', [ReviewController::class, 'store']);
    Route::middleware(['auth:api', 'permission:review-list'])->get('/reviews', [ReviewController::class, 'index']);
    Route::middleware(['auth:api', 'permission:review-show'])->get('/reviews/{produk_id}', [ReviewController::class, 'show']);
    Route::middleware(['auth:api', 'permission:review-edit'])->put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::middleware(['auth:api', 'permission:review-delete'])->delete('/reviews/{id}', [ReviewController::class, 'destroy']);
    Route::middleware(['auth:api'])->get('/products/{produk_id}/average-rating', [ReviewController::class, 'getAverageRating']);
    Route::get('review/order', [ReviewController::class, 'getReviewsByOrderId']);
    Route::get('/review/{order_id}', [ReviewController::class, 'showReviewByOrderId']);

});

use App\Http\Controllers\NotificationController;

Route::middleware('auth:api')->group(function () {
    Route::middleware(['auth:api', 'permission:notifications-list'])->get('/notifications', [NotificationController::class, 'index']);
    Route::middleware(['auth:api', 'permission:notifications-markasred'])->put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::middleware(['auth:api', 'permission:notifications-create'])->post('/notifications', [NotificationController::class, 'store']);
    Route::middleware(['auth:api'])->get('/notif-all', [NotificationController::class, 'notifAll']);

});

use App\Http\Controllers\SalesReportController;

Route::middleware(['auth:api', 'permission:report-filter'])->post('/report', [SalesReportController::class, 'generateReport']);
Route::middleware(['auth:api', 'permission:report-list'])->get('/report', [SalesReportController::class, 'getAllReports']);
Route::middleware(['auth:api'])->get('/report-offline', [SalesReportController::class, 'generateOfflineReport']);


Route::middleware('auth:api')->group(function () {
    Route::middleware(['auth:api', 'permission:profile-edit'])->put('/profile', [UserController::class, 'updateProfile']);
    Route::middleware(['auth:api', 'permission:profile-show'])->get('/profile', [UserController::class, 'getProfile']);
});

use App\Http\Controllers\AddressController;

Route::middleware('auth:api')->group(function () {
    Route::middleware(['auth:api', 'permission:address-show'])->get('/address', [AddressController::class, 'getAddress']);
    Route::middleware(['auth:api', 'permission:address-edit'])->put('/address', [AddressController::class, 'updateAddress']);
});

use App\Http\Controllers\KeranjangOfflineController;

Route::group(['middleware' => 'auth:api'], function () {
    // Menambahkan produk ke keranjang offline
    Route::post('/keranjang-offline/add', [KeranjangOfflineController::class, 'addToKeranjangOffline']);

    Route::get('/keranjang-offline/count', [KeranjangOfflineController::class, 'countKeranjangOfflineItems']);

    // Checkout dan simpan nota belanja
    Route::post('/keranjang-offline/checkout', [KeranjangOfflineController::class, 'checkout']);
    Route::get('/keranjang-offline', [KeranjangOfflineController::class, 'getAllKeranjangOffline']);
    // Mencetak nota belanja
    Route::post('/post-nota', [KeranjangOfflineController::class, 'checkoutMultipleKeranjang']);
    Route::get('/nota', [KeranjangOfflineController::class, 'itemNota']);
    Route::get('/nota-belanja/print/{notaId}', [KeranjangOfflineController::class, 'printNota']);
    Route::delete('/keranjang-offline/{id}', [KeranjangOfflineController::class, 'removeItem']);

    Route::get('item-nota/{id}', [KeranjangOfflineController::class, 'itemNotaById']);
    Route::get('nota-belanja/{nota_belanja_id}', [KeranjangOfflineController::class, 'getItemsByNotaId']);
});
Route::get('test-auth', [AuthController::class, 'testAuth']);

use App\Http\Controllers\TariffController;
Route::post('/tariff-check', [TariffController::class, 'getTariff']);

use App\Http\Controllers\CheckOngkirController;
Route::get('provinces', [CheckOngkirController::class, 'province'])->name('provinces');
Route::get('cities', [CheckOngkirController::class, 'city'])->name('cities');
Route::post('check-ongkir', [CheckOngkirController::class, 'checkOngkir'])->name('check-ongkir');

Route::middleware('auth:api')->get('/addresses', [AddressController::class, 'getAllAddresses']);
Route::middleware('auth:api')->get('/addresses/{userId}', [AddressController::class, 'getAddressByUserId']);


// Route::middleware('auth:api')->group(function () {
//     // Role Kasir hanya bisa mengakses resource users kecuali create dan edit
//     Route::middleware('role:kasir')->resource('users', UserController::class)->except(['create', 'edit']);

//     // Route untuk Role management
//     Route::middleware('role:kasir')->group(function () {
//         Route::get('roles', [RoleController::class, 'index']);
//         Route::get('roles/{id}', [RoleController::class, 'show']);
//         Route::post('roles', [RoleController::class, 'store']);
//         Route::put('roles/{id}', [RoleController::class, 'update']);
//         Route::delete('roles/{id}', [RoleController::class, 'destroy']);
//     });
// });
