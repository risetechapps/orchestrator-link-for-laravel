<?php

namespace RiseTechApps\OrchestratorLink\Shipping;

use RiseTechApps\OrchestratorLink\Feature\Service;
use RiseTechApps\OrchestratorLink\Models\Shipment;

class ShipmentBuilder
{
    private array $data = [];
    private bool $persist = false;
    private ?string $idempotencyKey = null;

    public function __construct(private readonly Service $service) {}

    // Sua referência (ex.: id do pedido na loja), para achar o envio depois
    public function reference(string $externalReference): self
    {
        $this->data['external_reference'] = $externalReference;
        return $this;
    }

    // Id do serviço escolhido (vem da cotação)
    public function service(int $serviceId): self
    {
        $this->data['service'] = $serviceId;
        return $this;
    }

    // Agência de postagem, nos serviços que exigem (getShippingAgencies)
    public function agency(int $agencyId): self
    {
        $this->data['agency'] = $agencyId;
        return $this;
    }

    // Remetente
    public function sender(ShippingAddress|callable $address): self
    {
        if (is_callable($address)) {
            $address = $address(ShippingAddress::make());
        }

        $this->data['from'] = $address->toArray();
        unset($this->data['sender_id']);
        return $this;
    }

    // Remetente salvo (createShippingSender), no lugar do sender()
    public function senderId(int $senderId): self
    {
        $this->data['sender_id'] = $senderId;
        unset($this->data['from']);
        return $this;
    }

    // Destinatário
    public function recipient(ShippingAddress|callable $address): self
    {
        if (is_callable($address)) {
            $address = $address(ShippingAddress::make());
        }

        $this->data['to'] = $address->toArray();
        return $this;
    }

    // Produto do envio (declaração de conteúdo): nome, quantidade e valor unitário
    public function addProduct(ShippingProduct|callable $product): self
    {
        if (is_callable($product)) {
            $product = $product(ShippingProduct::make());
        }

        $this->data['products'][] = $product->toDeclaration();
        return $this;
    }

    // Volume (pacote). Nos Correios, cada volume leva os próprios produtos.
    public function addVolume(ShippingVolume|callable $volume): self
    {
        if (is_callable($volume)) {
            $volume = $volume(ShippingVolume::make());
        }

        $this->data['volumes'][] = $volume->toArray();
        return $this;
    }

    // Valor declarado para o seguro
    public function insurance(float $value): self
    {
        return $this->option('insurance_value', $value);
    }

    // Chave da NF-e (44 dígitos): envio comercial, dispensa a declaração de conteúdo
    public function invoice(string $key): self
    {
        return $this->option('invoice', ['key' => preg_replace('/\D/', '', $key)]);
    }

    // Opção individual: insurance_value, receipt, own_hand, non_commercial, invoice
    public function option(string $key, mixed $value): self
    {
        $this->data['options'][$key] = $value;
        return $this;
    }

    public function options(array $options): self
    {
        $this->data['options'] = array_merge($this->data['options'] ?? [], $options);
        return $this;
    }

    // Avisos por e-mail a cada etapa (padrão no orchestrator: os dois ligados)
    public function notifications(bool $sender = true, bool $recipient = true): self
    {
        $this->data['notifications'] = ['sender' => $sender, 'recipient' => $recipient];
        return $this;
    }

    // Logo do remetente na etiqueta: PNG, JPEG ou WebP em base64 (ou data URI), até 512 KB
    public function labelLogo(string $base64): self
    {
        $this->data['label_logo'] = $base64;
        return $this;
    }

    // Repetir create() com a mesma chave devolve o mesmo envio em vez de criar outro
    public function idempotencyKey(string $key): self
    {
        $this->idempotencyKey = $key;
        return $this;
    }

    // Salvar na tabela orchestrator_shipments depois de criado
    public function save(): self
    {
        $this->persist = true;
        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    // Cria o envio no orchestrator
    public function create(): array
    {
        $result = $this->service->createShipment($this->data, $this->idempotencyKey);

        if ($this->persist && $result['success'] && !empty($result['data']['id'])) {
            Shipment::syncFromOrchestrator($result['data'], [
                'products' => $this->data['products'] ?? null,
                'volumes'  => $this->data['volumes'] ?? null,
                'options'  => $this->data['options'] ?? null,
            ]);
        }

        return $result;
    }
}
