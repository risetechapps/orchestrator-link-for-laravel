<?php

namespace RiseTechApps\OrchestratorLink\Shipping;

use Illuminate\Http\Request;

/**
 * Confere os avisos de frete que o orchestrator manda para a URL cadastrada
 * em setShippingWebhook().
 *
 * Cada aviso vem com:
 *  - X-Orchestrator-Event:     nome do evento (shipment.delivered, ...);
 *  - X-Orchestrator-Delivery:  id único da entrega (use para deduplicar);
 *  - X-Orchestrator-Signature: `t=<unix>,v1=<hex>`, HMAC-SHA256 de "<t>.<corpo>"
 *    com o secret devolvido no cadastro da URL.
 *
 * Corpo: {"id": "...", "event": "shipment.posted", "created_at": "...", "data": {envio}}.
 */
class ShippingWebhook
{
    public const string SIGNATURE_HEADER = 'X-Orchestrator-Signature';
    public const string EVENT_HEADER     = 'X-Orchestrator-Event';
    public const string DELIVERY_HEADER  = 'X-Orchestrator-Delivery';

    /**
     * Confere a assinatura do corpo cru. `$tolerance` (segundos) recusa avisos
     * velhos, para um aviso capturado não ser reenviado depois; 0 desliga.
     */
    public static function verify(string $body, ?string $signature, string $secret, int $tolerance = 300): bool
    {
        if ($secret === '' || $signature === null || $signature === '') {
            return false;
        }

        $parts = [];

        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[$key] = $value;
        }

        $timestamp = $parts['t'] ?? '';
        $hash      = $parts['v1'] ?? '';

        if (!ctype_digit($timestamp) || $hash === '') {
            return false;
        }

        if ($tolerance > 0 && abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $timestamp . '.' . $body, $secret), $hash);
    }

    /** Confere a requisição recebida (corpo cru + header de assinatura). */
    public static function verifyRequest(Request $request, string $secret, int $tolerance = 300): bool
    {
        return static::verify($request->getContent(), $request->header(self::SIGNATURE_HEADER), $secret, $tolerance);
    }
}
