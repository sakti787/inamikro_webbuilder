<?php

use App\Http\Controllers\Auth\AuthController;
use App\Livewire\Banners\BannerManager;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Dashboard\ProfileForm;
use App\Livewire\Products\ProductForm;
use App\Livewire\Products\ProductList;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // UMKM Owner Routes
    Route::middleware('umkm.owner')->prefix('dashboard')->group(function () {
        Route::get('/', DashboardIndex::class)->name('dashboard');
        Route::get('/profile', ProfileForm::class)->name('dashboard.profile');

        Route::get('/products', ProductList::class)->name('dashboard.products');
        Route::get('/products/create', ProductForm::class)->name('dashboard.products.create');
        Route::get('/products/{product}/edit', ProductForm::class)->name('dashboard.products.edit');
        Route::get('/banners', BannerManager::class)->name('dashboard.banners');
    });

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return response('Admin Dashboard');
        })->name('dashboard');

        Route::get('/users', function () {
            return response('Admin Users');
        })->name('users');
    });
});
