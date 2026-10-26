<?php

namespace RiseTechApps\OrchestratorLink\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

abstract class Connection
{
    private const string DEFAULT_HOST = 'https://orchestrator.app.br/api/v1/services';

    /**
     * TTLs de cache em segundos, usados pelos métodos do Service para declarar
     * a volatilidade de cada endpoint. Só GET é cacheado (ver request()).
     */
    public const int TTL_STATIC = 2592000; // 30 dias — dados de referência (bancos, CEP, FIPE, ...)
    public const int TTL_DAY    = 86400;   // 1 dia  — muda devagar (CNPJ, transportadoras)
    public const int TTL_HOUR   = 3600;    // 1 hora — muda com frequência (domínio)

    /**
     * Erros da conta que integra o orchestrator (chave inválida, plano, cota),
     * não do usuário final da app: o detalhe vai só para o log, e a resposta
     * vira um 503 genérico.
     */
    public const array ACCOUNT_ERROR_STATUSES = [401, 402, 403, 429];

    public const string UNAVAILABLE_MESSAGE = 'This service is temporarily unavailable. Please try again later.';

    /**
     * @param int   $cacheTtl Segundos de cache. 0 = sem cache. Só tem efeito em GET.
     * @param array $headers  Headers extras (ex.: Idempotency-Key).
     */
    public static function request($url, $method = 'GET', array $data = [], int $cacheTtl = 0, array $headers = []): array
    {
        $method = strtoupper((string) $method);

        // Cache apenas em GET com TTL positivo, e apenas de respostas de SUCESSO.
        // Nunca cacheia POST/PUT/PATCH/DELETE (efeito colateral) nem falhas (um
        // timeout transitório envenenaria o cache com {success:false}).
        if ($method === 'GET' && $cacheTtl > 0) {
            // O host entra na chave: trocar ORCHESTRATOR_URL não reaproveita
            // respostas de outro ambiente.
            $key = 'orchestrator:' . md5(static::host() . $url);

            if (is_array($cached = Cache::get($key))) {
                return $cached;
            }

            $result = static::send($method, $url, $data, $headers);

            if ($result['success'] ?? false) {
                Cache::put($key, $result, $cacheTtl);
            }

            return $result;
        }

        return static::send($method, $url, $data, $headers);
    }

    /**
     * GET de um arquivo (PDF da etiqueta). Sucesso devolve o conteúdo em
     * `data.content`, com `content_type` e `filename`; falha, o mesmo
     * {success:false} das demais chamadas. Nunca é cacheado.
     */
    public static function download(string $url): array
    {
        $http = static::client(['Accept' => 'application/pdf, application/json']);

        if ($http === null) {
            return static::errorResponse();
        }

        try {
            $response = static::retryOnConnectionError($http)->get(static::host() . $url);
        } catch (ConnectionException $e) {
            static::logConnectionError('GET', $url, $e);

            return static::errorResponse();
        }

        if ($response->failed()) {
            static::logHttpError('GET', $url, $response);

            return static::errorResponse($response);
        }

        $filename = null;

        if (preg_match('/filename="?([^";]+)"?/i', (string) $response->header('Content-Disposition'), $m)) {
            $filename = $m[1];
        }

        return [
            'success' => true,
            'data'    => [
                'content'      => $response->body(),
                'content_type' => $response->header('Content-Type') ?: 'application/octet-stream',
                'filename'     => $filename,
            ],
        ];
    }

    private static function send(string $method, string $url, array $data, array $headers = []): array
    {
        $http = static::client($headers);

        if ($http === null) {
            return static::errorResponse();
        }

        // Retry apenas em GET (idempotente) — reexecuta blips de rede sem risco.
        // POST/PUT/PATCH/DELETE podem ter efeito colateral (criar envio, gerar
        // PIX); reexecutar duplicaria a operação. O retry só reage a
        // ConnectionException (transporte), não a status HTTP 4xx/5xx.
        if ($method === 'GET') {
            $http = static::retryOnConnectionError($http);
        }

        $endpoint = static::host() . $url;

        try {
            $response = match ($method) {
                'POST'   => $http->post($endpoint, $data),
                'PUT'    => $http->put($endpoint, $data),
                'PATCH'  => $http->patch($endpoint, $data),
                'DELETE' => $http->delete($endpoint, $data),
                default  => $http->get($endpoint),
            };
        } catch (ConnectionException $e) {
            // Timeout, DNS ou conexão recusada (orchestrator fora do ar/lento).
            // Http lança ConnectionException nesses casos — sem este catch,
            // viraria um HTTP 500 na request do usuário. Degrada graciosamente.
            static::logConnectionError($method, $url, $e);

            return static::errorResponse();
        }

        if ($response->failed()) {
            static::logHttpError($method, $url, $response);

            return static::errorResponse($response);
        }

        return static::successResponse($response);
    }

    /** Cliente HTTP com a chave e os timeouts; null quando a chave não está configurada. */
    private static function client(array $headers = []): ?PendingRequest
    {
        $token = config('orchestrator-link.token');

        if (empty($token)) {
            logger()->error('[orchestrator-link] ORCHESTRATOR_TOKEN Not configured—Request aborted.');

            return null;
        }

        // Timeouts curtos: esta chamada roda DENTRO da request web, então não
        // pode prender o worker esperando o orchestrator. connectTimeout limita
        // o handshake (DNS/conexão); timeout limita a resposta inteira.
        return Http::connectTimeout(5)
            ->timeout(15)
            ->withHeaders(array_merge(
                ['Accept' => 'application/json'],
                array_filter($headers, fn ($value) => $value !== null && $value !== ''),
                ['X-API-KEY' => $token],
            ));
    }

    /**
     * Repete só falha de transporte. Uma resposta 4xx/5xx volta na hora: repetir
     * um 404 ou 422 não muda nada, e sem `throw: false` o Http lançaria
     * RequestException na última tentativa (virava 500 na app).
     */
    private static function retryOnConnectionError(PendingRequest $http): PendingRequest
    {
        return $http->retry(2, 200, fn (\Throwable $e) => $e instanceof ConnectionException, throw: false);
    }

    protected static function host(): string
    {
        return rtrim((string) (config('orchestrator-link.url') ?: self::DEFAULT_HOST), '/');
    }

    private static function logConnectionError(string $method, string $url, ConnectionException $e): void
    {
        logger()->warning('[orchestrator-link] Falha de conexão com o orchestrator', [
            'method' => $method,
            'url'    => $url,
            'error'  => $e->getMessage(),
        ]);
    }

    private static function logHttpError(string $method, string $url, Response $response): void
    {
        // Distingue os tipos de falha HTTP:
        //  - 5xx → orchestrator quebrado (error, digno de alerta);
        //  - 401/402/403 e cota esgotada (REQUEST_LIMIT_REACHED) → chave, plano
        //    ou cota da conta (error: precisa de ação, e o usuário final só vê
        //    a mensagem genérica);
        //  - demais 4xx → validação (422), não encontrado (404), limite por
        //    minuto (429 RATE_LIMITED)... (warning).
        // `error_code` é o identificador estável dos erros da conta
        // (PLAN_EXPIRED, INVALID_API_KEY...); o texto de `message` pode mudar.
        $status    = $response->status();
        $errorCode = $response->json('error_code');
        $errorCode = is_string($errorCode) ? $errorCode : null;

        $isAccountProblem = in_array($status, [401, 402, 403], true) || $errorCode === 'REQUEST_LIMIT_REACHED';

        logger()->log(
            $status >= 500 || $isAccountProblem ? 'error' : 'warning',
            '[orchestrator-link] Resposta de erro do orchestrator',
            [
                'method'     => $method,
                'url'        => $url,
                'status'     => $status,
                'error_code' => $errorCode,
                'body'       => substr((string) $response->body(), 0, 500),
            ]
        );
    }

    /**
     * Falha. Quando o orchestrator respondeu, vêm junto o status HTTP, a
     * mensagem dele e os erros de validação (422), para a app mostrar ao
     * usuário; sem resposta (conexão, chave ausente), `status` é null e a
     * mensagem é a genérica. Erros da conta (ACCOUNT_ERROR_STATUSES) viram
     * 503 com a mensagem genérica — o original fica no log. No 429,
     * `retry_after` diz em quantos segundos tentar de novo.
     */
    private static function errorResponse(?Response $response = null): array
    {
        $body = $response?->json();
        $body = is_array($body) ? $body : [];

        $retryAfter = static::retryAfter($response, $body);

        if ($response === null || in_array($response->status(), self::ACCOUNT_ERROR_STATUSES, true)) {
            return [
                'success'     => false,
                'data'        => [],
                'status'      => $response === null ? null : 503,
                'message'     => self::UNAVAILABLE_MESSAGE,
                'errors'      => [],
                'retry_after' => $retryAfter,
            ];
        }

        $errors = $body['errors'] ?? $body['data'] ?? [];

        return [
            'success'     => false,
            'data'        => [],
            'status'      => $response->status(),
            'message'     => static::message($body),
            'errors'      => is_array($errors) ? $errors : [],
            'retry_after' => $retryAfter,
        ];
    }

    private static function successResponse(Response $response): array
    {
        // json() vira null se o corpo não for JSON válido; sem o ?? [] o acesso
        // a offset em null lançaria TypeError. As chaves também podem faltar num
        // corpo com shape inesperado — daí o fallback por chave.
        $_response = $response->json();
        $_response = is_array($_response) ? $_response : [];

        // `status` repassa o 201 das criações; `message` traz avisos como o do
        // secret do webhook, que só aparece uma vez.
        return [
            'success' => $_response['success'] ?? false,
            'data'    => $_response['data'] ?? [],
            'status'  => $response->status(),
            'message' => static::message($_response),
        ];
    }

    private static function message(array $body): ?string
    {
        return is_string($body['message'] ?? null) && $body['message'] !== '' ? $body['message'] : null;
    }

    private static function retryAfter(?Response $response, array $body): ?int
    {
        $value = $response?->header('Retry-After') ?: ($body['retry_after'] ?? null);

        return is_numeric($value) ? (int) $value : null;
    }
}
