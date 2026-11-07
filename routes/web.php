<?php

use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Tenant\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route('dashboard'))->name('home');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
});

Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => to_route('admin.tenants.index'))->name('home');
    Route::resource('tenants', TenantController::class)->except(['show', 'destroy']);
    Route::resource('plans', PlanController::class)->only(['index', 'edit', 'update']);
});

require __DIR__.'/settings.php';
