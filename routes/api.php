<?php

use Luchavez\SimpleSecrets\Http\Controllers\SecretController;
use Illuminate\Support\Facades\Route;

/**
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
Route::apiResource('secrets', SecretController::class)->only('index', 'show', 'destroy');
