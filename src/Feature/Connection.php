<?php

namespace RiseTechApps\OrchestratorLink\Feature;

use Illuminate\Support\Facades\Http;

abstract class Connection
{
    private const Host = "https://orchestrator.app.br/api/v1/services";

    public static function request($url): array
    {
        $token = config('orchestrator-link.token');

        $response = Http::withHeaders([
            'X-API-KEY' => $token,
        ])->get(Connection::Host. $url);

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
