<?php

namespace RiseTechApps\OrchestratorLink\Shipping;

use Illuminate\Http\Request;

/**
 * Confere os avisos de frete que o orchestrator manda para a URL cadastrada
 * em setShippingWebhook().
 *
 * Os avisos saem pelo mesmo sistema da tela Webhooks do painel do orchestrator
 * e são assinados com o segredo de webhook da conta (um só para todo webhook,
 * em Webhooks no painel; também vem em `secret` no setShippingWebhook()).
 *
 * Cada aviso vem com:
 *  - X-Webhook-Event:     nome do evento (shipment.delivered, ...);
 *  - X-Webhook-Id:        id único do aviso, igual ao `id` do corpo (use para deduplicar);
 *  - X-Webhook-Timestamp: unix, em segundos;
 *  - X-Webhook-Signature: `sha256=<hex>`, HMAC-SHA256 de "<timestamp>.<corpo>"
 *    com o segredo da conta.
 *
 * Corpo: {"id": "...", "event": "shipment.posted", "created_at": "...", "data": {envio}}.
 */
class ShippingWebhook
{
    public const string SIGNATURE_HEADER = 'X-Webhook-Signature';
    public const string TIMESTAMP_HEADER = 'X-Webhook-Timestamp';
    public const string EVENT_HEADER     = 'X-Webhook-Event';
    public const string DELIVERY_HEADER  = 'X-Webhook-Id';

    /**
     * Confere a assinatura do corpo cru. `$tolerance` (segundos) recusa avisos
     * velhos, para um aviso capturado não ser reenviado depois; 0 desliga.
     */
    public static function verify(string $body, ?string $signature, ?string $timestamp, string $secret, int $tolerance = 300): bool
    {
        if ($secret === '' || $signature === null || $timestamp === null || !ctype_digit($timestamp)) {
            return false;
        }

        if (!str_starts_with($signature, 'sha256=') || strlen($signature) === 7) {
            return false;
        }

        if ($tolerance > 0 && abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        return hash_equals('sha256=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret), $signature);
    }

    /** Confere a requisição recebida (corpo cru + headers de assinatura e de horário). */
    public static function verifyRequest(Request $request, string $secret, int $tolerance = 300): bool
    {
        return static::verify(
            $request->getContent(),
            $request->header(self::SIGNATURE_HEADER),
            $request->header(self::TIMESTAMP_HEADER),
            $secret,
            $tolerance,
        );
    }

    /** Nome do evento (header, ou o `event` do corpo). */
    public static function event(Request $request): ?string
    {
        $event = $request->header(self::EVENT_HEADER) ?: $request->input('event');

        return is_string($event) && $event !== '' ? $event : null;
    }

    /** Id do aviso, para deduplicar (header, ou o `id` do corpo). */
    public static function deliveryId(Request $request): ?string
    {
        $id = $request->header(self::DELIVERY_HEADER) ?: $request->input('id');

        return is_string($id) && $id !== '' ? $id : null;
    }
}
