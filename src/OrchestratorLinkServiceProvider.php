<?php

namespace RiseTechApps\OrchestratorLink;

use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\ServiceProvider;

class OrchestratorLinkServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('orchestrator-link.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'orchestrator-link-migrations');
        }
    }

    /**
     * Register the application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'orchestrator-link');
        // Register the main class to use with the facade
        $this->app->singleton(OrchestratorLink::class, fn() => new OrchestratorLink);
    }
}
