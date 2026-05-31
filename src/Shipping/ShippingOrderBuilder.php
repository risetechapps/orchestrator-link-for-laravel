<?php

namespace RiseTechApps\OrchestratorLink\Shipping;

use RiseTechApps\OrchestratorLink\Feature\Service;
use RiseTechApps\OrchestratorLink\Models\Shipment;

class ShippingOrderBuilder
{
    private array $data    = ['options' => []];
    private bool  $persist = false;

    public function __construct(private readonly Service $service) {}

    // ID do serviço escolhido (vem do calculateShipping)
    public function service(int $serviceId): self
    {
        $this->data['service'] = $serviceId;
        return $this;
    }

    // Remetente
    public function sender(ShippingAddress|callable $address): self
    {
        if (is_callable($address)) {
            $address = $address(ShippingAddress::make());
        }

        $this->data['from'] = $address->toArray();
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

    // Produto dentro do pacote
    public function addProduct(ShippingProduct|callable $product): self
    {
        if (is_callable($product)) {
            $product = $product(ShippingProduct::make());
        }

        $this->data['products'][] = $product->toArray();
        return $this;
    }

    // Dimensões do pacote
    public function addVolume(ShippingVolume|callable $volume): self
    {
        if (is_callable($volume)) {
            $volume = $volume(ShippingVolume::make());
        }

        $this->data['volumes'][] = $volume->toArray();
        return $this;
    }

    // Opção individual (ex: ->option('insurance_value', 75.00))
    public function option(string $key, mixed $value): self
    {
        $this->data['options'][$key] = $value;
        return $this;
    }

    // Múltiplas opções de uma vez
    public function options(array $options): self
    {
        $this->data['options'] = array_merge($this->data['options'], $options);
        return $this;
    }

    // Salvar na tabela orchestrator_shipments após addToCart
    public function save(): self
    {
        $this->persist = true;
        return $this;
    }

    // Adiciona ao carrinho do Melhor Envio
    public function addToCart(): array
    {
        $result = $this->service->addToCart($this->data);

        if ($this->persist && $result['success'] && !empty($result['data'])) {
            $cart = $result['data'];

            Shipment::create([
                'me_order_id'     => $cart['id'] ?? null,
                'me_protocol'     => $cart['protocol'] ?? null,
                'service_id'      => $this->data['service'] ?? null,
                'status'          => $cart['status'] ?? 'pending',
                'price'           => $cart['price'] ?? null,
                'from_postal_code'=> $this->data['from']['postal_code'] ?? null,
                'from_data'       => $this->data['from'] ?? null,
                'to_postal_code'  => $this->data['to']['postal_code'] ?? null,
                'to_data'         => $this->data['to'] ?? null,
                'products'        => $this->data['products'] ?? null,
                'volumes'         => $this->data['volumes'] ?? null,
                'options'         => $this->data['options'] ?? null,
            ]);
        }

        return $result;
    }
}
