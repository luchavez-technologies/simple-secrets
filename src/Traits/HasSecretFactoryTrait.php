<?php

namespace Luchavez\SimpleSecrets\Traits;

use Luchavez\SimpleSecrets\Database\Factories\SecretFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Trait HasSecretFactoryTrait
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
trait HasSecretFactoryTrait
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return SecretFactory::new();
    }
}
