<?php

use Illuminate\Support\Facades\Route;
use Luchavez\SimpleSecrets\Http\Controllers\SecretController;

/**
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
Route::post('secrets/{secret}/restore', [SecretController::class, 'restore'])
    ->middleware(config('simple-secrets.middlewares.restore'))
    ->name('secrets.restore');

Route::apiResource('secrets', SecretController::class)->only('index', 'show', 'destroy');
