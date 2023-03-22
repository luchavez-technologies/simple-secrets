<?php

namespace Luchavez\SimpleSecrets\Providers;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Class SecretUserProvider
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class SecretUserProvider extends EloquentUserProvider
{
    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (empty($credentials)) {
            return null;
        }

        // Secret query builder
        $query = $this->newModelQuery();

        foreach ($credentials as $code => $value) {
            $query->where('type', $code)->where('value', $value);
        }

        $secret = $query->with('owner')->first();

        return $secret?->owner;
    }
}
