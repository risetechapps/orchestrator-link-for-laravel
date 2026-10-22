<?php

namespace RiseTechApps\OrchestratorLink\Shipping;

use RiseTechApps\OrchestratorLink\Feature\Service;

class ShippingQuoteBuilder
{
    private array $data = [];

    public function __construct(private readonly Service $service) {}

    // CEP de origem
    public function from(string|ShippingAddress $origin): self
    {
        $this->data['from'] = ['postal_code' => $this->postalCode($origin)];
        return $this;
    }

    // CEP de destino
    public function to(string|ShippingAddress $destination): self
    {
        $this->data['to'] = ['postal_code' => $this->postalCode($destination)];
        return $this;
    }

    // Produto: precisa de id, dimensões, peso e quantidade
    public function addProduct(ShippingProduct|callable $product): self
    {
        if (is_callable($product)) {
            $product = $product(ShippingProduct::make());
        }

        $this->data['products'][] = $product->toArray();
        return $this;
    }

    // Volume já embalado (alternativo a products)
    public function addVolume(ShippingVolume|callable $volume): self
    {
        if (is_callable($volume)) {
            $volume = $volume(ShippingVolume::make());
        }

        // Os produtos do volume só valem na criação do envio.
        $this->data['volumes'][] = array_diff_key($volume->toArray(), ['products' => true]);
        return $this;
    }

    // Filtrar por ids de serviço (ex.: [1, 2] ou '1,2')
    public function services(array|string $serviceIds): self
    {
        $this->data['services'] = is_array($serviceIds) ? implode(',', $serviceIds) : $serviceIds;
        return $this;
    }

    // Valor declarado para o seguro
    public function insurance(float $value): self
    {
        return $this->option('insurance_value', $value);
    }

    // Opções extras: insurance_value, receipt, own_hand
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

    public function toArray(): array
    {
        return $this->data;
    }

    // Envia e retorna a cotação
    public function send(): array
    {
        return $this->service->quoteShipping($this->data);
    }

    private function postalCode(string|ShippingAddress $value): string
    {
        $postalCode = is_string($value) ? $value : ($value->toArray()['postal_code'] ?? '');

        return preg_replace('/\D/', '', $postalCode);
    }
}
