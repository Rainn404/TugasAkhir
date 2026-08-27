<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - ARSY TENDA
|--------------------------------------------------------------------------
*/

// Public Guest Routes
Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('/perlengkapan', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/perlengkapan/{slug}', [CatalogController::class, 'show'])->name('catalog.show');

// Auth Routes (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
    Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');
});

// Midtrans Payment Webhook Callback (CSRF exempted in bootstrap/app.php)
Route::post('/payment/webhook', [PaymentController::class, 'handleNotification'])->name('payment.webhook');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Shopping Cart & Pemesanan
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update/{key}', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove/{key}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/update-date', [CartController::class, 'updateDate'])->name('cart.update-date');

    // Reservation / Stepper Checkout
    Route::get('/checkout', [ReservationController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [ReservationController::class, 'store'])->name('checkout.store');

    // Survey Location Request (Bisa diakses siapapun yang login)
    Route::get('/survey/request', [SurveyController::class, 'create'])->name('survey.create');
    Route::post('/survey/request', [SurveyController::class, 'store'])->name('survey.store');

    // Customer Dashboard Portal
    Route::middleware('role:customer,admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/dashboard/events', [DashboardController::class, 'events'])->name('dashboard.events');
        Route::get('/dashboard/orders', [DashboardController::class, 'orders'])->name('dashboard.orders');
        Route::get('/dashboard/surveys', [DashboardController::class, 'surveys'])->name('dashboard.surveys');
        Route::get('/dashboard/profile', [DashboardController::class, 'profile'])->name('dashboard.profile');
        Route::post('/dashboard/profile', [DashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
    });

    // Shared Order Detail (Customer Owner & Admin)
    Route::get('/dashboard/order/{order_number}', [DashboardController::class, 'showOrder'])->name('dashboard.order');

    // Admin & Pekerja Features
    Route::middleware('role:admin,pekerja')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

        // Operasional
        Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
        Route::get('/orders/{order}', [AdminController::class, 'showOrder'])->name('orders.show');
        Route::post('/orders/{order}/status', [AdminController::class, 'updateOrderStatus'])->name('orders.update-status');

        Route::get('/events', [AdminController::class, 'events'])->name('events');

        Route::get('/surveys', [AdminController::class, 'surveys'])->name('surveys');
        Route::post('/surveys/{survey}/status', [SurveyController::class, 'updateStatus'])->name('surveys.update-status');
        Route::post('/surveys/{survey}/assign-worker', [SurveyController::class, 'assignWorker'])->name('surveys.assign-worker');

        // Inventaris & Variasi
        Route::get('/inventories', [AdminController::class, 'inventories'])->name('inventories');
        Route::post('/inventories', [AdminController::class, 'storeInventory'])->name('inventories.store');
        Route::post('/inventories/{inventory}/update', [AdminController::class, 'updateInventory'])->name('inventories.update');
        Route::post('/inventories/{inventory}/delete', [AdminController::class, 'deleteInventory'])->name('inventories.delete');

        Route::get('/inventory-variants', [AdminController::class, 'inventoryVariants'])->name('inventory-variants');
        Route::post('/inventory-variants', [AdminController::class, 'storeVariant'])->name('inventory-variants.store');
        Route::post('/inventory-variants/{variant}/delete', [AdminController::class, 'deleteVariant'])->name('inventory-variants.delete');

        // Transaksi & Pelanggan
        Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
        Route::get('/customers', [AdminController::class, 'customers'])->name('customers');

        // Manajemen Pekerja / Tim Lapangan
        Route::get('/workers', [AdminController::class, 'workers'])->name('workers');
        Route::post('/workers', [AdminController::class, 'storeWorker'])->name('workers.store');
        Route::post('/workers/{worker}/update', [AdminController::class, 'updateWorker'])->name('workers.update');
        Route::post('/workers/{worker}/delete', [AdminController::class, 'deleteWorker'])->name('workers.delete');

        // Laporan
        Route::get('/reports/orders', [ReportController::class, 'orders'])->name('reports.orders');
        Route::get('/reports/inventories', [ReportController::class, 'inventories'])->name('reports.inventories');
        Route::get('/reports/payments', [ReportController::class, 'payments'])->name('reports.payments');
    });
});
