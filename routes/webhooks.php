<?php

use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use App\Http\Middleware\VerifyMetaSignature;
use Illuminate\Support\Facades\Route;

/*
 * Public endpoints called by Meta: no session, no CSRF, no tenant context.
 */
Route::get('whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('whatsapp', [WhatsAppWebhookController::class, 'receive'])
    ->middleware(VerifyMetaSignature::class)
    ->name('webhooks.whatsapp.receive');
