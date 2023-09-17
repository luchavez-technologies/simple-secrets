<?php

namespace Luchavez\SimpleSecrets\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Luchavez\SimpleSecrets\Exceptions\InvalidSecretException;
use Luchavez\SimpleSecrets\Exceptions\NoActiveSecretException;
use Luchavez\SimpleSecrets\Models\Secret;
use Throwable;

/**
 * Class RequireSecretsMiddleware
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class RequireSecretsMiddleware
{
    /**
     * Decides if AND or OR
     *
     * @var bool
     */
    public bool $is_and = true;

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string  ...$types
     * @return mixed
     *
     * @throws AuthenticationException
     * @throws InvalidSecretException
     * @throws NoActiveSecretException
     */
    public function handle(Request $request, Closure $next, string ...$types): mixed
    {
        if (! ($user = $request->user())) {
            throw new AuthenticationException();
        }

        $secret_types = collect($types)
            ->map(fn ($item) => explode($this->is_and ? '&' : '|', $item))
            ->flatten()
            ->map(function ($item) use ($request) {
                // If the type still contains | or &, we should call the appropriate counterpart middleware
                $symbol = $this->is_and ? '|' : '&';
                if (Str::contains($item, $symbol)) {
                    $items = explode($symbol, $item);
                    $middleware = $symbol == '&' ? self::class : RequireSecretsOrMiddleware::class;

                    try {
                        app($middleware)->handle($request, fn ($request) => next($request), ...$items);
                    } catch (Throwable $throwable) {
                        if ($this->is_and) {
                            throw $throwable;
                        }
                    }

                    return null; // will be filtered after
                }

                return simpleSecrets()->getTypeByKey($item);
            })
            ->filter();

        $this->validateSecretsFromRequest($request, $secret_types);

        $this->verifyUserSecrets($request, $user, $secret_types);

        return $next($request);
    }

    /**
     * @param  Request  $request
     * @param  Collection<Collection>  $types
     * @return void
     */
    public function validateSecretsFromRequest(Request $request, Collection $types): void
    {
        $rules = [];

        foreach ($types as $type) {
            $rules[$type->get('accessor_name')] = 'required';
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
        $key = null;

        foreach ($types as $type) {
            $accessor_name = $type->get('accessor_name');
            $key = $type->get('key');

            // Get secrets from database
            if ($secret = simpleSecrets()->getSecrets(user: $user, accessor_name: $accessor_name)) {
                $input = $request->get($accessor_name);

                $result = simpleSecrets()->checkSecret(input: $input, secret: $secret, return_as_model: true);

                // Decrement usage left of Secret
                if ($result instanceof Secret) {
                    $this->decrementSecretUsageLeft(secret: $secret);
                } else {
                    break; // If a secret is incorrect, we can stop the loop
                }
            } else {
                throw new NoActiveSecretException($key);
            }
        }

        if (is_null($result) && $key) {
            throw new InvalidSecretException($key);
        }
    }

    /**
     * @param  Secret  $secret
     * @return void
     */
    protected function decrementSecretUsageLeft(Secret $secret): void
    {
        $key = 'decrement-usage-left-'.$secret->id;

        RateLimiter::attempt(
            key: $key,
            maxAttempts: 1,
            callback: fn () => $secret->use(),
        );

        app()->terminating(function () use ($key) {
            RateLimiter::clear($key);
        });
    }
}
