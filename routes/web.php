<?php

use App\Http\Controllers\Api\MediaApiController;
use App\Http\Controllers\Api\SiteApiController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): RedirectResponse {
    return Auth::check()
        ? redirect()->route('studio')
        : redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/studio', function (): Response {
        return response('INAweb Studio');
    })->name('studio');
});

Route::prefix('api')->middleware('auth')->group(function (): void {
    Route::get('/site', [SiteApiController::class, 'show'])->name('api.site.show');
    Route::put('/site', [SiteApiController::class, 'upsert'])->name('api.site.upsert');
    Route::post('/site/publish', [SiteApiController::class, 'publish'])->name('api.site.publish');
    Route::delete('/site', [SiteApiController::class, 'destroy'])->name('api.site.destroy');
    Route::post('/media', [MediaApiController::class, 'store'])->name('api.media.store');
    Route::delete('/media', [MediaApiController::class, 'destroy'])->name('api.media.destroy');
});
