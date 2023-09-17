<?php

use Illuminate\Support\Facades\Route;
use Luchavez\SimpleSecrets\Http\Controllers\SecretController;

/**
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
Route::apiResource('secrets', SecretController::class)->only('index', 'show', 'destroy');
