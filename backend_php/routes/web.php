<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\CustomerController;

/*
|--------------------------------------------------------------------------
| Web Routes (Admin Web Panel)
|--------------------------------------------------------------------------
*/

// Redirect หน้าแรกไปหน้าแอดมิน
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

Route::prefix('admin')->group(function () {
    // ล็อกอิน / ออกจากระบบ สำหรับแอดมิน (ใส่ throttle ป้องกันเดารหัสผ่าน)
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1')->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // เมนูหลังบ้าน (ต้องมีสิทธิ์ role == admin เท่านั้น)
    Route::middleware('admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // จัดการสินค้า (CRUD + Restore)
        Route::post('/products/{id}/restore', [ProductController::class, 'restore'])->name('admin.products.restore');
        Route::resource('products', ProductController::class, ['as' => 'admin']);

        // จัดการคำสั่งซื้อและตรวจสลิป
        Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders.index');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->name('admin.orders.show');
        Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.status');
        Route::get('/payments/{id}/slip', [OrderController::class, 'viewSlip'])->name('admin.payments.slip');
        Route::post('/payments/{id}/approve', [OrderController::class, 'approvePayment'])->name('admin.payments.approve');
        Route::post('/payments/{id}/reject', [OrderController::class, 'rejectPayment'])->name('admin.payments.reject');

        // จัดการข้อมูลลูกค้า
        Route::get('/customers', [CustomerController::class, 'index'])->name('admin.customers.index');
    });
});
