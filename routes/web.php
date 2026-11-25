<?php

use App\Http\Controllers\Api\MediaApiController;
use App\Http\Controllers\Api\SiteApiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\StudioController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    $user = Auth::user();

    if ($user === null) {
        return redirect()->route('login');
    }

    if ($user->role === 'admin') {
        return redirect()->route('admin');
    }

    return redirect()->route('studio');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/studio', StudioController::class)
        ->middleware('role:umkm')
        ->name('studio');
});

Route::get('/admin', fn () => response('Panel Pendamping Admin', 200))
    ->middleware(['auth', 'role:admin'])
    ->name('admin');

Route::prefix('api')->middleware(['auth', 'role:umkm'])->group(function (): void {
    Route::get('/site', [SiteApiController::class, 'show'])->name('api.site.show');
    Route::put('/site', [SiteApiController::class, 'upsert'])->name('api.site.upsert');
    Route::post('/site/publish', [SiteApiController::class, 'publish'])->name('api.site.publish');
    Route::delete('/site', [SiteApiController::class, 'destroy'])->name('api.site.destroy');
    Route::post('/media', [MediaApiController::class, 'store'])->name('api.media.store');
    Route::delete('/media', [MediaApiController::class, 'destroy'])->name('api.media.destroy');
});

Route::get('/{slug}', PublicSiteController::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('public.site');
