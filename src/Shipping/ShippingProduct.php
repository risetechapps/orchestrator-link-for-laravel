<?php

namespace RiseTechApps\OrchestratorLink\Shipping;

class ShippingProduct
{
    private array $data = ['quantity' => 1];

    public static function make(): self
    {
        return new self();
    }

    // Para calculateShipping (identifica o produto internamente)
    public function id(string $id): self
    {
        $this->data['id'] = $id;
        return $this;
    }

    // Para addToCart
    public function name(string $name): self
    {
        $this->data['name'] = $name;
        return $this;
    }

    // Dimensões em cm: largura, altura, comprimento
    public function dimensions(float $width, float $height, float $length): self
    {
        $this->data['width']  = $width;
        $this->data['height'] = $height;
        $this->data['length'] = $length;
        return $this;
    }

    public function weight(float $kg): self
    {
        $this->data['weight'] = $kg;
        return $this;
    }

    public function quantity(int $qty): self
    {
        $this->data['quantity'] = $qty;
        return $this;
    }

    // Valor para seguro (calculateShipping)
    public function insureFor(float $value): self
    {
        $this->data['insurance_value'] = $value;
        return $this;
    }

    // Valor unitário (addToCart)
    public function price(float $value): self
    {
        $this->data['unitary_value'] = $value;
        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
