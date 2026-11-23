<?php

use App\Http\Controllers\Auth\AuthController;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Dashboard\ProfileForm;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;
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

        // Product edit stub route with policy check
        Route::get('/products/{product}/edit', function (Product $product) {
            Gate::authorize('update', $product);

            return response('Edit Product '.$product->id);
        })->name('products.edit');
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
