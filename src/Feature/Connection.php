<?php

namespace RiseTechApps\OrchestratorLink\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

abstract class Connection
{
    private const string Host = "https://orchestrator.app.br/api/v1/services";
//    private const Host = "https://orchestrator.risetech.dev.br/api/v1/services";

    /**
     * TTLs de cache em segundos, usados pelos métodos do Service para declarar
     * a volatilidade de cada endpoint. Só GET é cacheado (ver request()).
     */
    public const int TTL_STATIC = 2592000; // 30 dias — dados de referência (bancos, CEP, FIPE, ...)
    public const int TTL_DAY    = 86400;   // 1 dia  — muda devagar (CNPJ, transportadoras)
    public const int TTL_HOUR   = 3600;    // 1 hora — muda com frequência (domínio)

    /**
     * @param int $cacheTtl Segundos de cache. 0 = sem cache. Só tem efeito em GET.
     */
    public static function request($url, $method = 'GET', array $data = [], int $cacheTtl = 0): array
    {
        $method = strtoupper((string) $method);

        // Cache apenas em GET com TTL positivo, e apenas de respostas de SUCESSO.
        // Nunca cacheia POST/PUT/PATCH/DELETE (efeito colateral) nem falhas (um
        // timeout transitório envenenaria o cache com {success:false}).
        if ($method === 'GET' && $cacheTtl > 0) {
            $key = 'orchestrator:' . md5($url);

            if (is_array($cached = Cache::get($key))) {
                return $cached;
            }

            $result = static::send($method, $url, $data);

            if ($result['success'] ?? false) {
                Cache::put($key, $result, $cacheTtl);
            }

            return $result;
        }

        return static::send($method, $url, $data);
    }

    private static function send(string $method, string $url, array $data): array
    {
        $token = config('orchestrator-link.token');

        if (empty($token)) {
            logger()->error('[orchestrator-link] ORCHESTRATOR_TOKEN Not configured—Request aborted.', [
                'method' => $method,
                'url'    => $url,
            ]);
            return static::errorResponse();
        }

        // Timeouts curtos: esta chamada roda DENTRO da request web, então não
        // pode prender o worker esperando o orchestrator. connectTimeout limita
        // o handshake (DNS/conexão); timeout limita a resposta inteira.
        $http = Http::connectTimeout(5)
            ->timeout(15)
            ->withHeaders([
                'X-API-KEY' => $token,
                'Accept'    => 'application/json',
            ]);

        // Retry apenas em GET (idempotente) — reexecuta blips de rede sem risco.
        // POST/PUT/PATCH/DELETE podem ter efeito colateral (checkout, gerar
        // etiqueta); reexecutar duplicaria a operação. O retry só reage a
        // ConnectionException (transporte), não a status HTTP 4xx/5xx.
        if ($method === 'GET') {
            $http = $http->retry(2, 200);
        }

        try {
            $response = match ($method) {
                'POST' => $http->post(Connection::Host . $url, $data),
                'PUT' => $http->put(Connection::Host . $url, $data),
                'PATCH' => $http->patch(Connection::Host . $url, $data),
                'DELETE' => $http->delete(Connection::Host . $url, $data),
                default => $http->get(Connection::Host . $url),
            };
        } catch (ConnectionException $e) {
            // Timeout, DNS ou conexão recusada (orchestrator fora do ar/lento).
            // Http lança ConnectionException nesses casos — sem este catch,
            // viraria um HTTP 500 na request do usuário. Degrada graciosamente.
            logger()->warning('[orchestrator-link] Falha de conexão com o orchestrator', [
                'method' => $method,
                'url'    => $url,
                'error'  => $e->getMessage(),
            ]);

            return static::errorResponse();
        }

        if ($response->failed()) {
            // Distingue os tipos de falha HTTP (antes, todos viravam um
            // {success:false} mudo, indistinguível de "resultado vazio"):
            //  - 5xx → orchestrator quebrado (error, digno de alerta);
            //  - 4xx → auth (401), rate limit (429), não encontrado (404)... (warning).
            $status = $response->status();

            logger()->log(
                $status >= 500 ? 'error' : 'warning',
                '[orchestrator-link] Resposta de erro do orchestrator',
                [
                    'method' => $method,
                    'url'    => $url,
                    'status' => $status,
                    'body'   => substr((string) $response->body(), 0, 500),
                ]
            );

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
        // json() vira null se o corpo não for JSON válido; sem o ?? [] o acesso
        // a offset em null lançaria TypeError. As chaves também podem faltar num
        // corpo com shape inesperado — daí o fallback por chave.
        $_response = $response->json() ?? [];

        return [
            'success' => $_response['success'] ?? false,
            'data'    => $_response['data'] ?? [],
        ];
    }
}
