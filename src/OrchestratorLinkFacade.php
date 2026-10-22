<?php

namespace RiseTechApps\OrchestratorLink;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RiseTechApps\OrchestratorLink\Skeleton\SkeletonClass
 * @method routes
 */
class OrchestratorLinkFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'orchestratorLink';
    }
}
