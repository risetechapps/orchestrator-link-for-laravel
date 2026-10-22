<?php

use RiseTechApps\OrchestratorLink\Tests\TestCase;

uses(TestCase::class)->in('Feature');

const BASE = 'https://orchestrator.test/api/v1/services';

/** Resposta de sucesso no envelope do orchestrator. */
function ok(array $data = [], int $status = 200): \GuzzleHttp\Promise\PromiseInterface
{
    return \Illuminate\Support\Facades\Http::response(['success' => true, 'code' => $status, 'data' => $data], $status);
}
