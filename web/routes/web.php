<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\MunicipalityAdminDashboardController;
use App\Http\Controllers\TouristSpotController;
use App\Http\Controllers\MunicipalityController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\MunicipalityStaffController;
use App\Http\Controllers\LandingPageController;

// Public tourism landing page
Route::get('/', [LandingPageController::class, 'index'])->name('home');

// Auth Routes
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'storeRegister'])->name('register.store');

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Protected Routes
Route::middleware(['auth', 'prevent.cache'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Super Admin Routes
    Route::middleware(['superadmin'])->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('/spots/{touristSpot}/approve', [SuperAdminDashboardController::class, 'approveSpot'])->name('spots.approve');
        Route::post('/spots/{touristSpot}/reject', [SuperAdminDashboardController::class, 'rejectSpot'])->name('spots.reject');
        Route::post('/spots/{touristSpot}/request-revision', [SuperAdminDashboardController::class, 'requestSpotRevision'])->name('spots.request-revision');
        Route::get('/tourist-spots', [SuperAdminDashboardController::class, 'touristSpots'])->name('tourist-spots');
        Route::get('/admins', [SuperAdminDashboardController::class, 'admins'])->name('admins');
        Route::get('/admins/create', [SuperAdminDashboardController::class, 'createAdmin'])->name('admins.create');
        Route::post('/admins', [SuperAdminDashboardController::class, 'storeAdmin'])->name('admins.store');
        Route::get('/reports', [SuperAdminDashboardController::class, 'reports'])->name('reports');
        Route::get('/admins/{admin}', [SuperAdminDashboardController::class, 'showAdmin'])->name('admins.show');
        Route::get('/admins/{admin}/edit', [SuperAdminDashboardController::class, 'editAdmin'])->name('admins.edit');
        Route::put('/admins/{admin}', [SuperAdminDashboardController::class, 'updateAdmin'])->name('admins.update');
        Route::patch('/admins/{admin}/status', [SuperAdminDashboardController::class, 'toggleAdminStatus'])->name('admins.toggle-status');
        Route::get('/admins/{admin}/password', [SuperAdminDashboardController::class, 'getAdminPassword'])->name('admins.password');
    });

    // Municipality Admin Routes
    Route::middleware(['municipalityadmin'])->prefix('municipality-admin')->name('municipality-admin.')->group(function () {
        Route::get('/dashboard', [MunicipalityAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/notifications/{notification}/open', [MunicipalityAdminDashboardController::class, 'openNotification'])->name('notifications.open');
        Route::post('/notifications/read-all', [MunicipalityAdminDashboardController::class, 'markNotificationsRead'])->name('notifications.read-all');
        Route::get('/tourist-spots', [MunicipalityAdminDashboardController::class, 'touristSpots'])->name('tourist-spots');
        Route::get('/reports', [MunicipalityAdminDashboardController::class, 'reports'])->name('reports');
        Route::get('/reviews', [MunicipalityAdminDashboardController::class, 'reviews'])->name('reviews');
        Route::get('/staff', [MunicipalityStaffController::class, 'index'])->name('staff');
        Route::get('/staff/create', [MunicipalityStaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [MunicipalityStaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{staff}/edit', [MunicipalityStaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [MunicipalityStaffController::class, 'update'])->name('staff.update');
        Route::patch('/staff/{staff}/status', [MunicipalityStaffController::class, 'toggleStatus'])->name('staff.toggle-status');
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
