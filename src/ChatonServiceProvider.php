<?php

namespace Chaton\SDK;

use Illuminate\Support\ServiceProvider;
use Chaton\SDK\Contracts\LicenseInterface;
use Chaton\SDK\Middleware\EnsureLicenseValid;
use Chaton\SDK\Middleware\EnsureSaasEnabled;

class LicenseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/chaton-license.php',
            'chaton-license'
        );

        // Register singletons
        $this->app->singleton(LicenseClient::class);
        $this->app->singleton(LicenseCache::class);
        $this->app->singleton(SignatureVerifier::class);

        $this->app->singleton(LicenseInterface::class, function ($app) {
            return new LicenseManager(
                $app->make(LicenseClient::class),
                $app->make(LicenseCache::class),
                $app->make(SignatureVerifier::class)
            );
        });

        // Alias
        $this->app->alias(LicenseInterface::class, 'chaton.license');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/chaton-license.php' => config_path('chaton-license.php'),
            ], 'chaton-license-config');
        }

        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('license.valid', EnsureLicenseValid::class);
        $router->aliasMiddleware('license.saas', EnsureSaasEnabled::class);
    }
}
