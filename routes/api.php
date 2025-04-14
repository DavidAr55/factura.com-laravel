<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\CfdiController;

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
});
