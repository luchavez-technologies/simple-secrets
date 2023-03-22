<?php

namespace Luchavez\SimpleSecrets\Providers;

use Luchavez\SimpleSecrets\Console\Commands\CheckSecretsExpirationCommand;
use Luchavez\SimpleSecrets\Console\Commands\InstallSimpleSecretsCommand;
use Luchavez\SimpleSecrets\Http\Middleware\EnsureUserHasActiveSecretsMiddleware;
use Luchavez\SimpleSecrets\Http\Middleware\EnsureUserHasActiveSecretsOrMiddleware;
use Luchavez\SimpleSecrets\Http\Middleware\RequireSecretsMiddleware;
use Luchavez\SimpleSecrets\Http\Middleware\RequireSecretsOrMiddleware;
use Luchavez\SimpleSecrets\Models\Secret;
use Luchavez\SimpleSecrets\Observers\SecretObserver;
use Luchavez\SimpleSecrets\Repositories\SecretRepository;
use Luchavez\SimpleSecrets\Services\SimpleSecrets;
use Luchavez\StarterKit\Abstracts\BaseStarterKitServiceProvider as ServiceProvider;
use Luchavez\StarterKit\Interfaces\ProviderConsoleKernelInterface;
use Luchavez\StarterKit\Interfaces\ProviderDynamicRelationshipsInterface;
use Luchavez\StarterKit\Interfaces\ProviderHttpKernelInterface;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

/**
 * Class SimpleSecretsServiceProvider
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class SimpleSecretsServiceProvider extends ServiceProvider implements ProviderHttpKernelInterface, ProviderConsoleKernelInterface, ProviderDynamicRelationshipsInterface
{
    /**
     * @var array
     */
    protected array $commands = [
        InstallSimpleSecretsCommand::class,
        CheckSecretsExpirationCommand::class,
    ];

    /**
     * @var string|null
     */
    protected string|null $route_prefix = null;

    /**
     * @var bool
     */
    protected bool $prefix_route_with_file_name = false;

    /**
     * @var bool
     */
    protected bool $prefix_route_with_directory = false;

    /**
     * Polymorphism Morph Map
     *
     * @link    https://laravel.com/docs/8.x/eloquent-relationships#custom-polymorphic-types
     *
     * @example [ 'user' => User::class ]
     *
     * @var array
     */
    protected array $morph_map = [];

    /**
     * Laravel Observer Map
     *
     * @link    https://laravel.com/docs/8.x/eloquent#observers
     *
     * @example [ UserObserver::class => User::class ]
     *
     * @var array
     */
    protected array $observer_map = [
        SecretObserver::class => Secret::class,
    ];

    /**
     * Laravel Policy Map
     *
     * @link    https://laravel.com/docs/8.x/authorization#registering-policies
     *
     * @example [ UserPolicy::class => User::class ]
     *
     * @var array
     */
    protected array $policy_map = [];

    /**
     * Laravel Repository Map
     *
     * @example [ UserRepository::class => User::class ]
     *
     * @var array
     */
    protected array $repository_map = [
        SecretRepository::class => Secret::class
    ];

    /**
     * Publishable Environment Variables
     *
     * @example [ 'HELLO_WORLD' => true ]
     *
     * @var array
     */
    protected array $env_vars = [];

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();

        $this->registerAuthenticationGuards();

        // Set default validation rules for passwords
        simpleSecrets()->setValidationRules('password', [
            Password::min(12)->letters()->mixedCase()->numbers()->uncompromised()
        ]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the service the package provides.
        $this->app->singleton(
            'simple-secrets',
            fn () => new SimpleSecrets(starter_kit: $this->app->make('starter-kit'))
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['simple-secrets'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../../config/simple-secrets.php' => config_path('simple-secrets.php'),
        ], 'simple-secrets.config');

        // Registering package commands.
        $this->commands($this->commands);
    }

    /**
     * @param  Router  $router
     * @return void
     */
    public function registerToHttpKernel(Router $router): void
    {
        if (simpleSecrets()->isHasSecretsTraitUsed()) {
            $router->aliasMiddleware('secrets_require', RequireSecretsMiddleware::class);
            $router->aliasMiddleware('secrets_require_or', RequireSecretsOrMiddleware::class);
            $router->aliasMiddleware('secrets_active', EnsureUserHasActiveSecretsMiddleware::class);
            $router->aliasMiddleware('secrets_active_or', EnsureUserHasActiveSecretsOrMiddleware::class);

            $global_middleware = simpleSecrets()->getGlobalMiddleware();

            collect($router->getMiddlewareGroups())
                ->each(fn ($v, $k) => $router->pushMiddlewareToGroup($k, $global_middleware));
        }
    }

    /**
     * @param  Schedule  $schedule
     * @return void
     */
    public function registerToConsoleKernel(Schedule $schedule): void
    {
        $schedule
            ->command('secrets:check')
            ->name('Check the expiration of user secrets.')
            ->onOneServer()
            ->daily();
    }

    /**
     * @return void
     *
     * @link   https://laravel.com/docs/8.x/eloquent-relationships#dynamic-relationships
     */
    public function registerDynamicRelationships(): void
    {
        if (simpleSecrets()->isHasSecretsTraitUsed()) {
            $user_model = starterKit()->getUserModel();

            $user_model::resolveRelationUsing('secrets', function (Model $user) {
                return $user->hasMany(Secret::class, Secret::getOwnerIdColumn())->latest('id');
            });

            simpleSecrets()->getTypes()->each(function (Collection $type) use ($user_model) {
                $user_model::resolveRelationUsing($type->get('relationship_name'), function (Model $user) use ($type) {
                    return $user->secrets()->scopes(['type' => $type->get('key')]);
                });
            });
        }
    }

    public function registerAuthenticationGuards()
    {
        if ($this->app->configurationIsCached()) {
            return;
        }

        $types = simpleSecrets()->getTypes()
            ->where('hashed', false)
            ->where('unique_for_all', true);

        if ($types->count()) {
            $provider_name = 'secrets';
            $provider_driver = $provider_name.'-driver';

            // Add new Auth Driver
            Auth::provider($provider_driver, function (Application $app, array $config) {
                return new SecretUserProvider($app['hash'], $config['model']);
            });

            // Fetch copy of Auth Providers
            $providers = config('auth.providers');

            // Add new Auth Provider
            $providers[$provider_name] = [
                'driver' => $provider_driver,
                'model' => Secret::class,
            ];

            // Set new Auth Providers
            config(['auth.providers' => $providers]);

            // Fetch copy of Auth Guards
            $guards = config('auth.guards');

            // Add new Auth Guards
            $types->each(function (Collection $type, string $key) use ($provider_name, &$guards) {
                $accessor_name = $type->get('accessor_name');
                $code = $type->get('code');

                $guards[$accessor_name] = [
                    'driver' => 'token',
                    'provider' => $provider_name,
                    'input_key' => $accessor_name,
                    'storage_key' => $code,
                ];
            });

            // Set new Auth Guards
            config(['auth.guards' => $guards]);
        }
    }
}
