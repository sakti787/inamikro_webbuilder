<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\AdminApiController;
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

Route::get('/admin', AdminController::class)
    ->middleware(['auth', 'role:admin'])
    ->name('admin');

Route::prefix('api/admin')->middleware(['auth', 'role:admin'])->group(function (): void {
    Route::get('/bootstrap', [AdminApiController::class, 'bootstrap'])->name('api.admin.bootstrap');
    Route::get('/logs', [AdminApiController::class, 'logs'])->name('api.admin.logs');
    Route::post('/umkm/{user}/approve', [AdminApiController::class, 'approve'])->name('api.admin.umkm.approve');
    Route::post('/umkm/{user}/revise', [AdminApiController::class, 'revise'])->name('api.admin.umkm.revise');
    Route::patch('/umkm/{user}/note', [AdminApiController::class, 'saveNote'])->name('api.admin.umkm.note');
    Route::patch('/templates/{templateId}', [AdminApiController::class, 'toggleTemplate'])->name('api.admin.templates.toggle');
    Route::patch('/settings/auto-approve', [AdminApiController::class, 'toggleAutoApprove'])->name('api.admin.settings.auto-approve');
    Route::patch('/settings/contact', [AdminApiController::class, 'saveContact'])->name('api.admin.settings.contact');
});

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
