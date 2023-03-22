<?php

namespace Luchavez\SimpleSecrets\Http\Middleware;

use Luchavez\SimpleSecrets\Exceptions\NoActiveSecretException;
use Illuminate\Foundation\Auth\User;

/**
 * Class EnsureUserHasActiveSecretsOrMiddleware
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class EnsureUserHasActiveSecretsOrMiddleware extends EnsureUserHasActiveSecretsMiddleware
{
    /**
     * @param User $user
     * @param array $accessor_names
     * @return void
     * @throws NoActiveSecretException
     */
    public function accessUserSecrets(User $user, array $accessor_names): void
    {
        // Start with $success as false
        $success = false;

        foreach ($accessor_names as $accessor_name) {
            try {
                $user->$accessor_name;

                // Set $success to true if NoActiveSecretException is not triggered
                $success = true;

                // Stop the loop since only one success is needed
                break;
            } catch (NoActiveSecretException) {
                //
            }
        }

        if (! $success) {
            $types = simpleSecrets()->getTypes()->whereIn('accessor_name', $accessor_names)->keys();

            throw new NoActiveSecretException(...$types);
        }
    }
}
