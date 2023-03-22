<?php

namespace Luchavez\SimpleSecrets\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class SimpleSecrets
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 *
 * @see \Luchavez\SimpleSecrets\Services\SimpleSecrets
 */
class SimpleSecrets extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'simple-secrets';
    }
}
