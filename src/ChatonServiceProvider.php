<?php

namespace Chaton\SDK;

use Chaton\SDK\Contracts\LicenseInterface;
use Chaton\SDK\Contracts\PluginUpdaterInterface;
use Chaton\SDK\Middleware\EnsureLicenseValid;
use Chaton\SDK\Middleware\EnsureSaasEnabled;
use Chaton\SDK\PluginClient;
use Chaton\SDK\PluginUpdateChecker;
use Illuminate\Support\ServiceProvider;

class ChatonServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/chaton-license.php',
            'chaton-license'
        );

        // Register singletons
        $this->app->singleton(LicenseClient::class);
        $this->app->singleton(LicenseCache::class);
        $this->app->singleton(SignatureVerifier::class);
        $this->app->singleton(PluginClient::class);

        $this->app->singleton(LicenseInterface::class, function ($app) {
            return new LicenseManager(
                $app->make(LicenseClient::class),
                $app->make(LicenseCache::class),
                $app->make(SignatureVerifier::class)
            );
        });

        $this->app->singleton(PluginUpdaterInterface::class, function ($app) {
            return new PluginUpdateChecker(
                $app->make(PluginClient::class),
                $app->make(SignatureVerifier::class),
                $app->make(LicenseInterface::class)
            );
        });

        // Aliases
        $this->app->alias(LicenseInterface::class, 'chaton.license');
        $this->app->alias(PluginUpdaterInterface::class, 'chaton.plugin_updater');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/chaton-license.php' => config_path('chaton-license.php'),
            ], 'chaton-license-config');
        }

        // Register middleware
        $router = $this->app['router'];
        $router->aliasMiddleware('license.valid', EnsureLicenseValid::class);
        $router->aliasMiddleware('license.saas', EnsureSaasEnabled::class);
    }
}
