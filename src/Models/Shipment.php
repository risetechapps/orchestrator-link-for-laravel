<?php

namespace RiseTechApps\OrchestratorLink\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Cópia local de um envio do orchestrator. Status no vocabulário dele:
 * pending, awaiting_payment, processing, purchased, label_ready, posted,
 * delivered, undelivered e cancelled.
 *
 * As colunas me_* são dos envios criados antes da 3.0 (Melhor Envio direto).
 */
class Shipment extends Model
{
    protected $table = 'orchestrator_shipments';

    protected $fillable = [
        'orchestrator_id',
        'external_reference',
        'me_order_id',
        'me_protocol',
        'service_id',
        'service_name',
        'carrier',
        'status',
        'sandbox',
        'tracking_code',
        'tracking_url',
        'from_postal_code',
        'from_data',
        'to_postal_code',
        'to_data',
        'products',
        'volumes',
        'options',
        'price',
        'label_url',
        'paid_at',
        'posted_at',
        'delivered_at',
        'canceled_at',
    ];

    protected $casts = [
        'from_data'    => 'array',
        'to_data'      => 'array',
        'products'     => 'array',
        'volumes'      => 'array',
        'options'      => 'array',
        'sandbox'      => 'boolean',
        'price'        => 'decimal:2',
        'paid_at'      => 'datetime',
        'posted_at'    => 'datetime',
        'delivered_at' => 'datetime',
        'canceled_at'  => 'datetime',
    ];

    /**
     * Cria ou atualiza a cópia local a partir de um envio do orchestrator
     * (resposta de createShipment/getShipment/trackShipment ou o `data` do
     * webhook). `$extra` completa o que a resposta não traz (products,
     * volumes e options como foram enviados); valores nulos são ignorados.
     */
    public static function syncFromOrchestrator(array $data, array $extra = []): self
    {
        $from = $data['addresses']['from'] ?? null;
        $to   = $data['addresses']['to'] ?? null;

        $attributes = [
            'external_reference' => $data['external_reference'] ?? null,
            'service_id'         => $data['service']['id'] ?? null,
            'service_name'       => $data['service']['name'] ?? null,
            'carrier'            => $data['carrier'] ?? null,
            'status'             => $data['status'] ?? null,
            'sandbox'            => (bool) ($data['sandbox'] ?? false),
            'tracking_code'      => $data['tracking_code'] ?? null,
            'tracking_url'       => $data['tracking_url'] ?? null,
            'from_postal_code'   => $from['postal_code'] ?? null,
            'from_data'          => $from,
            'to_postal_code'     => $to['postal_code'] ?? null,
            'to_data'            => $to,
            'price'              => $data['price'] ?? null,
            'paid_at'            => static::date($data['paid_at'] ?? null),
            'posted_at'          => static::date($data['posted_at'] ?? null),
            'delivered_at'       => static::date($data['delivered_at'] ?? null),
            'canceled_at'        => static::date($data['cancelled_at'] ?? null),
        ];

        // Aviso parcial (ex.: sem endereços) não apaga o que já estava salvo.
        $attributes = array_filter($attributes, fn ($value) => $value !== null);
        $extra      = array_filter($extra, fn ($value) => $value !== null);

        return static::updateOrCreate(
            ['orchestrator_id' => $data['id']],
            array_merge($attributes, $extra),
        );
    }

    /**
     * Data ISO 8601 do orchestrator (com fuso) no fuso da app. Sem converter,
     * o cast gravaria o horário de -03:00 como se fosse o fuso da app.
     */
    private static function date(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value)->setTimezone(config('app.timezone')) : null;
    }

    public function isPending(): bool         { return $this->status === 'pending'; }
    public function isAwaitingPayment(): bool { return $this->status === 'awaiting_payment'; }
    public function isLabelReady(): bool      { return $this->status === 'label_ready'; }
    public function isPosted(): bool          { return $this->status === 'posted'; }
    public function isDelivered(): bool       { return $this->status === 'delivered'; }
    public function isUndelivered(): bool     { return $this->status === 'undelivered'; }
    // `canceled` é a grafia dos envios antigos (Melhor Envio).
    public function isCanceled(): bool        { return in_array($this->status, ['cancelled', 'canceled'], true); }

    public function scopePending($query)   { return $query->where('status', 'pending'); }
    public function scopePosted($query)    { return $query->where('status', 'posted'); }
    public function scopeDelivered($query) { return $query->where('status', 'delivered'); }
}
