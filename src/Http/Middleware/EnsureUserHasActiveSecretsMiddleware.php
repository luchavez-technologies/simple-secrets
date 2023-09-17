<?php

namespace Luchavez\SimpleSecrets\Http\Middleware;

use Closure;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Luchavez\SimpleSecrets\Traits\HasSecretsTrait;

/**
 * Class EnsureUserHasActiveSecretsMiddleware
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class EnsureUserHasActiveSecretsMiddleware
{
    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string  ...$types
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string ...$types): mixed
    {
        $accessor_names = [];

        foreach ($types as $type) {
            $accessor_names[] = simpleSecrets()->getTypeByKey($type)->get('accessor_name');
        }

        $user = null; // Initialize $user with null

        // Get user by using all guards
        foreach (config('auth.guards') as $guard => $value) {
            if ($user = $request->user($guard)) {
                break;
            }
        }

        // if a user is found, check for active secret
        if ($user && class_uses_trait($user, HasSecretsTrait::class)) {
            $throw_exception = ! $request->is(simpleSecrets()->getGlobalMiddlewareExceptRoutes());

            simpleSecrets()->toggleThrowNoActiveSecretException($throw_exception);

            // Try accessing the secret so it can either get from cache or trigger simpleSecrets()->getSecrets(...)
            $this->accessUserSecrets(user: $user, accessor_names: $accessor_names);

            // Invoke purge stale passwords
            simpleSecrets()->purgeUserStaleSecrets(user: $user);

            simpleSecrets()->toggleThrowNoActiveSecretException(false);
        }

        return $next($request);
    }

    /**
     * @param  User  $user
     * @param  array  $accessor_names
     * @return void
     */
    public function accessUserSecrets(User $user, array $accessor_names): void
    {
        foreach ($accessor_names as $accessor_name) {
            $user->$accessor_name;
        }
    }
}
