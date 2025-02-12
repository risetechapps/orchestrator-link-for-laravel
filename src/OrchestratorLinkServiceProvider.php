<?php

namespace RiseTechApps\OrchestratorLink;

use Illuminate\Support\ServiceProvider;

class OrchestratorLinkServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('orchestrator-link.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'orchestrator-link');
        // Register the main class to use with the facade
        $this->app->singleton('orchestratorLink', function () {
            return new OrchestratorLink;
        });
    }
}
