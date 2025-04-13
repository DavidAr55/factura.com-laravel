<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CfdiController;

Route::prefix('v1')->group(function () {
    Route::get('/status', [CfdiController::class, 'status']);
});