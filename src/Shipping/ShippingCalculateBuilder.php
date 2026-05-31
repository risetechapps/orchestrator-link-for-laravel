<?php

namespace RiseTechApps\OrchestratorLink\Shipping;

use RiseTechApps\OrchestratorLink\Feature\Service;

class ShippingCalculateBuilder
{
    private array $data = [];

    public function __construct(private readonly Service $service) {}

    // CEP de origem (string simples ou ShippingAddress completo)
    public function from(string|ShippingAddress $origin): self
    {
        $this->data['from'] = is_string($origin)
            ? ['postal_code' => preg_replace('/\D/', '', $origin)]
            : $origin->toArray();

        return $this;
    }

    // CEP de destino
    public function to(string|ShippingAddress $destination): self
    {
        $this->data['to'] = is_string($destination)
            ? ['postal_code' => preg_replace('/\D/', '', $destination)]
            : $destination->toArray();

        return $this;
    }

    // Adicionar produto ao cálculo
    public function addProduct(ShippingProduct|callable $product): self
    {
        if (is_callable($product)) {
            $product = $product(ShippingProduct::make());
        }

        $this->data['products'][] = $product->toArray();
        return $this;
    }

    // Adicionar volume ao cálculo (alternativo a products)
    public function addVolume(ShippingVolume|callable $volume): self
    {
        if (is_callable($volume)) {
            $volume = $volume(ShippingVolume::make());
        }

        $this->data['volumes'][] = $volume->toArray();
        return $this;
    }

    // Filtrar por IDs de serviço (ex: '1,2,3')
    public function services(string $serviceIds): self
    {
        $this->data['services'] = $serviceIds;
        return $this;
    }

    // Opções extras
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

    // Envia e retorna o resultado
    public function send(): array
    {
        return $this->service->calculateShipping($this->data);
    }
}
