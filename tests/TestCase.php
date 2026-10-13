<?php

namespace RiseTechApps\OrchestratorLink\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use RiseTechApps\OrchestratorLink\OrchestratorLinkServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [OrchestratorLinkServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('orchestrator-link.token', 'test-key');
        $app['config']->set('orchestrator-link.url', 'https://orchestrator.test/api/v1/services');
    }
}
