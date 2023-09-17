<?php

namespace Luchavez\SimpleSecrets\Casts;

use Exception;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Luchavez\SimpleSecrets\Exceptions\NoActiveSecretException;
use Luchavez\SimpleSecrets\Exceptions\SecretAlreadyExistsException;

/**
 * Class AsSecretCast
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class AsSecretCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array|string|null
     *
     * @throws NoActiveSecretException
     */
    public function get($model, string $key, $value, array $attributes): array|string|null
    {
        return simpleSecrets()->getSecrets(user: $model, accessor_name: $key, value_only: true);
    }

    /**
     * Prepare the given value for storage.
     *
     * When setting new secret, it should be unique compared to previous x secrets.
     *
     * @param  Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array|string|null
     *
     * @throws SecretAlreadyExistsException
     * @throws Exception
     */
    public function set($model, string $key, $value, array $attributes): array|string|null
    {
        // Validate first the secret
        simpleSecrets()->validateSecret(accessor_name: $key, value: $value);

        // Check if the secret is unique
        if (! simpleSecrets()->isSecretUnique(accessor_name: $key, value: $value, user: $model)) {
            throw new SecretAlreadyExistsException($key);
        }

        return [$key => simpleSecrets()->addNewSecret(user: $model, accessor_name: $key, value: $value)];
    }
}
