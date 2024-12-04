<?php

namespace RiseTechApps\OrchestratorLink\Feature;

use Illuminate\Support\Facades\Http;

abstract class Connection
{
    public static function request($url): array
    {
        $token = config('orchestrator-link.token');
        $host = config('orchestrator-link.host');

        $response = Http::withHeaders([
            'Authorization' => $token,
        ])->get($host . $url);

        if ($response->failed()) {
            return static::errorResponse();
        }

        return static::successResponse($response);
    }

    private static function errorResponse(): array
    {
        return [
            'success' => false,
            'data' => []
        ];
    }

    private static function successResponse(\GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response $response): array
    {
        $_response = $response->json();

        return [
            'success' => $_response['success'],
            'data' => $_response['data'],
        ];
    }
}
