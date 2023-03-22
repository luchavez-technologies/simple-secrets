<?php

/**
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */

use Luchavez\SimpleSecrets\Services\SimpleSecrets;

if (! function_exists('simpleSecrets')) {
    /**
     * @return SimpleSecrets
     */
    function simpleSecrets(): SimpleSecrets
    {
        return resolve('simple-secrets');
    }
}

if (! function_exists('simple_secrets')) {
    /**
     * @return SimpleSecrets
     */
    function simple_secrets(): SimpleSecrets
    {
        return simpleSecrets();
    }
}
