<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'health');
Route::fallback(fn() => abort(404));
