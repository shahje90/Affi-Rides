<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminKycController;
use App\Http\Controllers\AdminMonitorController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminRideController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminWalletController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PassengerController;
use App\Http\Controllers\PassengerRideController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\RiderRideController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'show'])->name('home');

Route::get('/language/{locale}', [HomeController::class, 'switchLanguage'])->name('language.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:12,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('register.store');
});

Route::middleware('guest')->prefix('rider')->name('rider.')->group(function () {
    Route::get('/register', [RiderController::class, 'showRegistration'])->name('register');
    Route::post('/register', [RiderController::class, 'register'])->middleware('throttle:3,1')->name('register.store');
});

Route::middleware('auth')->post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::middleware('auth')->get('/dashboard', [DashboardController::class, 'redirectUser'])->name('dashboard');

Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/pending-items', [AdminController::class, 'pendingItems'])->middleware('throttle:120,1')->name('pending-items');
    Route::post('/wallet/{walletTransaction}/approve', [AdminWalletController::class, 'approve'])->middleware('throttle:30,1')->name('wallet.approve');
    Route::post('/wallet/{walletTransaction}/reject', [AdminWalletController::class, 'reject'])->middleware('throttle:30,1')->name('wallet.reject');
    Route::post('/wallet/adjustments', [AdminWalletController::class, 'adjust'])->middleware('throttle:20,1')->name('wallet.adjustments.store');
    Route::get('/kyc', [AdminKycController::class, 'index'])->name('kyc.index');
    Route::get('/kyc/{profile}', [AdminKycController::class, 'show'])->name('kyc.show');
    Route::get('/kyc/{profile}/documents/{document}', [AdminKycController::class, 'document'])->name('kyc.document');
    Route::post('/kyc/{profile}/review', [AdminKycController::class, 'review'])->name('kyc.review');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
    Route::get('/monitor', [AdminMonitorController::class, 'index'])->name('monitor');
    Route::get('/monitor/snapshot', [AdminMonitorController::class, 'snapshot'])->middleware('throttle:120,1')->name('monitor.snapshot');
    Route::get('/rides', [AdminRideController::class, 'index'])->name('rides.index');
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [AdminReportController::class, 'export'])->name('reports.export');
});

Route::middleware(['auth', 'role:rider'])->prefix('rider')->name('rider.')->group(function () {
    Route::get('/dashboard', [RiderController::class, 'dashboard'])->name('dashboard');
    Route::get('/kyc-status', [RiderController::class, 'kycStatus'])->name('kyc-status');
    Route::post('/online', [RiderController::class, 'toggleOnline'])->name('online.toggle');
    Route::get('/active-state', [RiderController::class, 'activeState'])->middleware('throttle:120,1')->name('active-state');
    Route::post('/location', [RiderController::class, 'updateLocation'])->middleware('throttle:60,1')->name('location');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::post('/wallet/recharge', [WalletController::class, 'storeRecharge'])->middleware('throttle:6,1')->name('wallet.recharge');
    Route::get('/ride-requests', [RiderRideController::class, 'requests'])->middleware('throttle:120,1')->name('ride-requests');
    Route::post('/rides/{ride}/accept', [RiderRideController::class, 'accept'])->middleware('throttle:20,1')->name('rides.accept');
    Route::get('/rides/{ride}', [RiderRideController::class, 'show'])->name('rides.show');
    Route::post('/rides/{ride}/status', [RiderRideController::class, 'transition'])->middleware('throttle:20,1')->name('rides.status');
});

Route::middleware(['auth', 'role:passenger'])->prefix('passenger')->name('passenger.')->group(function () {
    Route::get('/home', [PassengerController::class, 'home'])->name('home');
    Route::get('/rides', [PassengerController::class, 'history'])->name('rides.index');
    Route::post('/fare', [PassengerController::class, 'estimate'])->middleware('throttle:30,1')->name('fare');
    Route::post('/rides', [PassengerController::class, 'book'])->middleware('throttle:10,1')->name('rides.store');
    Route::get('/rides/{ride}', [PassengerRideController::class, 'show'])->name('rides.show');
    Route::get('/rides/{ride}/status', [PassengerRideController::class, 'status'])->middleware('throttle:120,1')->name('rides.status');
    Route::post('/rides/{ride}/cancel', [PassengerRideController::class, 'cancel'])->middleware('throttle:10,1')->name('rides.cancel');
});
