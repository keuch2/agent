<?php

use App\Http\Controllers\Admin\ChannelController;
use App\Http\Controllers\Admin\MailSettingsController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SupportModeController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Controllers\Tenant\WhatsAppChannelController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => to_route('dashboard'))->name('home');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::middleware('can:channels.manage')->prefix('channels/whatsapp')->name('channels.whatsapp.')->controller(WhatsAppChannelController::class)->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
        Route::post('verify', 'verify')->name('verify');
        Route::delete('/', 'destroy')->name('destroy');
    });
});

Route::middleware(['auth', 'superadmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => to_route('admin.tenants.index'))->name('home');
    Route::resource('tenants', TenantController::class)->except(['show', 'destroy']);
    Route::resource('plans', PlanController::class)->only(['index', 'edit', 'update']);

    Route::get('channels', [ChannelController::class, 'index'])->name('channels.index');
    Route::prefix('tenants/{tenant}/whatsapp')->name('channels.whatsapp.')->controller(ChannelController::class)->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
        Route::post('verify', 'verify')->name('verify');
        Route::delete('/', 'destroy')->name('destroy');
    });

    Route::post('tenants/{tenant}/support', [SupportModeController::class, 'store'])->name('support.store');
    Route::delete('support', [SupportModeController::class, 'destroy'])->name('support.destroy');

    Route::get('settings/mail', [MailSettingsController::class, 'edit'])->name('settings.mail.edit');
    Route::put('settings/mail', [MailSettingsController::class, 'update'])->name('settings.mail.update');
    Route::post('settings/mail/test', [MailSettingsController::class, 'test'])->name('settings.mail.test');
});

require __DIR__.'/settings.php';
