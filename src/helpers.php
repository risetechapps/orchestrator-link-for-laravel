<?php

if (!function_exists('orchestrator')) {
    function orchestrator(): \RiseTechApps\OrchestratorLink\Feature\Service
    {
        return (new \RiseTechApps\OrchestratorLink\Feature\Service());
    }
}
