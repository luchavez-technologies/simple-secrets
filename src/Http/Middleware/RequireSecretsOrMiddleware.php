<?php

namespace Luchavez\SimpleSecrets\Http\Middleware;

use Exception;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Luchavez\SimpleSecrets\Exceptions\InvalidSecretException;
use Luchavez\SimpleSecrets\Exceptions\NoActiveSecretException;
use Luchavez\SimpleSecrets\Models\Secret;

/**
 * Class RequireSecretsOrMiddleware
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class RequireSecretsOrMiddleware extends RequireSecretsMiddleware
{
    /**
     * Decides if AND or OR
     *
     * @var bool
     */
    public bool $is_and = false;

    /**
     * @param  Request  $request
     * @param  Collection<Collection>  $types
     * @return void
     */
    public function validateSecretsFromRequest(Request $request, Collection $types): void
    {
        $rules = [];

        $accessor_names = $types->pluck('accessor_name');

        foreach ($types as $type) {
            $accessor_name = $type->get('accessor_name');
            $rules[$accessor_name] = 'required_without_all:'.$accessor_names->diff($accessor_name)->join(',');
        }

        $request->validate($rules);
    }

    /**
     * @param  Request  $request
     * @param  User  $user
     * @param  Collection<Collection>  $types
     * @return void
     *
     * @throws NoActiveSecretException
     * @throws InvalidSecretException
     * @throws Exception
     */
    public function verifyUserSecrets(Request $request, User $user, Collection $types): void
    {
        $result = null;
        $last_type = null;

        foreach ($types as $type) {
            $accessor_name = $type->get('accessor_name');
            $key = $type->get('key');

            // Get secrets from database
            if ($secret = simpleSecrets()->getSecrets(user: $user, accessor_name: $accessor_name)) {
                $input = $request->get($accessor_name);

                // If we can't get a secret from request, we can just skip.
                if (! $input) {
                    continue;
                }

                // Save current type for error display purposes
                $last_type = $key;

                $result = simpleSecrets()->checkSecret(input: $input, secret: $secret, return_as_model: true);

                // Since at least one secret must be correct, we can stop the loop.
                if ($result instanceof Secret) {
                    $result->use(); // Decrement usage left of Secret
                    break;
                }
            } else {
                throw new NoActiveSecretException($key);
            }
        }

        if (! is_null($result) && $last_type) {
            throw new InvalidSecretException($last_type);
        }
    }
}
