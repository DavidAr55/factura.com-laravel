<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\CfdiController;
use App\Http\Controllers\Api\V1\ClientsController;
use App\Http\Controllers\Api\V1\PaymentController;

Route::prefix('v1')->group(function () {
    // Health check
    Route::get('health', HealthController::class);

    // Basic CRUD of invoices
    Route::apiResource('cfdi', CfdiController::class)
        ->only(['index','show','store'])
        ->parameters(['cfdi' => 'uuid']);

    // Specific actions
    Route::post('cfdi/{uuid}/cancel', [CfdiController::class, 'cancel'])->name('cfdi.cancel');
    Route::post('cfdi/{uuid}/email',  [CfdiController::class, 'sendEmail'])->name('cfdi.email');

    // Get resources
    Route::get('cfdi-types', [CfdiController::class, 'getCfdiTypes'])->name('cfdi.types');
    Route::get('clients', [ClientsController::class, 'index'])->name('clients');
    Route::get('cfdi-usage', [CfdiController::class, 'cfdiUsage'])->name('cfdi.usage');
    Route::get('payment-methods', [PaymentController::class, 'index'])->name('payment.methods');
});
