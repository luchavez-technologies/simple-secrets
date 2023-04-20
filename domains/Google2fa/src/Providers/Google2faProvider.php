<?php

namespace Luchavez\SimpleSecrets\Domains\Google2fa\Providers;

use Luchavez\StarterKit\Abstracts\BaseStarterKitServiceProvider as ServiceProvider;

/**
 * Class Google2faProvider
 *
 * @author James Carlo Luchavez <jamescarloluchavez@gmail.com>
 */
class Google2faProvider extends ServiceProvider
{
    protected array $commands = [];

    protected string|null $route_prefix = null;

    protected bool $prefix_route_with_file_name = false;

    protected bool $prefix_route_with_directory = false;

    /**
     * Polymorphism Morph Map
     *
     * @link    https://laravel.com/docs/8.x/eloquent-relationships#custom-polymorphic-types
     *
     * @example [ 'user' => User::class ]
     */
    protected array $morph_map = [];

    /**
     * Laravel Observer Map
     *
     * @link    https://laravel.com/docs/8.x/eloquent#observers
     *
     * @example [ UserObserver::class => User::class ]
     */
    protected array $observer_map = [];

    /**
     * Laravel Policy Map
     *
     * @link    https://laravel.com/docs/8.x/authorization#registering-policies
     *
     * @example [ UserPolicy::class => User::class ]
     */
    protected array $policy_map = [];

    /**
     * Laravel Repository Map
     *
     * @example [ UserRepository::class => User::class ]
     */
    protected array $repository_map = [];

    /**
     * Publishable Environment Variables
     *
     * @example [ 'HELLO_WORLD' => true ]
     */
    protected array $env_vars = [];

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        parent::register();
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Console-specific booting.
     */
    protected function bootForConsole(): void
    {
        // Registering package commands.
        $this->commands($this->commands);
    }
}
