<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\MunicipalityAdminDashboardController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\TouristSpotController;
use App\Http\Controllers\MunicipalityController;
use App\Http\Controllers\ReviewController;

// Auth Routes
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'storeRegister'])->name('register.store');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Super Admin Routes
    Route::middleware(['superadmin'])->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/spots/{touristSpot}/approve', [SuperAdminDashboardController::class, 'approveSpot'])->name('spots.approve');
        Route::post('/spots/{touristSpot}/reject', [SuperAdminDashboardController::class, 'rejectSpot'])->name('spots.reject');
        Route::get('/tourist-spots', [SuperAdminDashboardController::class, 'touristSpots'])->name('tourist-spots');
        Route::get('/admins', [SuperAdminDashboardController::class, 'admins'])->name('admins');
        Route::get('/admins/create', [SuperAdminDashboardController::class, 'createAdmin'])->name('admins.create');
        Route::post('/admins', [SuperAdminDashboardController::class, 'storeAdmin'])->name('admins.store');
        Route::get('/reports', [SuperAdminDashboardController::class, 'reports'])->name('reports');
        Route::get('/admins/{admin}/edit', [SuperAdminDashboardController::class, 'editAdmin'])->name('admins.edit');
        Route::put('/admins/{admin}', [SuperAdminDashboardController::class, 'updateAdmin'])->name('admins.update');
        Route::patch('/admins/{admin}/status', [SuperAdminDashboardController::class, 'toggleAdminStatus'])->name('admins.toggle-status');
        Route::get('/admins/{admin}/password', [SuperAdminDashboardController::class, 'getAdminPassword'])->name('admins.password');
    });

    // Municipality Admin Routes
    Route::middleware(['municipalityadmin'])->prefix('municipality-admin')->name('municipality-admin.')->group(function () {
        Route::get('/dashboard', [MunicipalityAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/tourist-spots', [MunicipalityAdminDashboardController::class, 'touristSpots'])->name('tourist-spots');
        Route::get('/reports', [MunicipalityAdminDashboardController::class, 'reports'])->name('reports');
        Route::get('/reviews', [MunicipalityAdminDashboardController::class, 'reviews'])->name('reviews');
    });

    // Shared Admin Profile Routes
    Route::middleware(['admin'])->group(function () {
        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
    });

    // Tourist Spots
    Route::resource('tourist_spots', TouristSpotController::class);
    Route::get(
        '/tourist_spots/{touristSpot}/reviews-json',
        [TouristSpotController::class, 'reviewsJson']
    )->name('tourist_spots.reviews_json');

    // Municipalities
    Route::resource('municipalities', MunicipalityController::class);

    // Reviews
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/status', [ReviewController::class, 'updateStatus'])->name('reviews.updateStatus');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});

// Redirect root to dashboard
Route::redirect('/', '/dashboard');
