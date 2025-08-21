<?php

use Illuminate\Support\Facades\Route;

// Public webhook endpoints (no authentication required)
Route::post('/webhooks/leads/{slug}', [App\Http\Controllers\Api\WebhookController::class, 'handleLeadWebhook'])
    ->name('webhooks.leads');

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
