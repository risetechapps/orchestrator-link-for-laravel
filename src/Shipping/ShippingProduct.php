<?php

namespace RiseTechApps\OrchestratorLink\Shipping;

class ShippingProduct
{
    private array $data = ['quantity' => 1];

    public static function make(): self
    {
        return new self();
    }

    // Para a cotação (identifica o produto internamente)
    public function id(string $id): self
    {
        $this->data['id'] = $id;
        return $this;
    }

    // Para o envio (declaração de conteúdo)
    public function name(string $name): self
    {
        $this->data['name'] = $name;
        return $this;
    }

    // Dimensões em cm: altura, largura, comprimento (a mesma ordem do volume)
    public function dimensions(float $height, float $width, float $length): self
    {
        $this->data['height'] = $height;
        $this->data['width']  = $width;
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

    // Valor para seguro (cotação)
    public function insureFor(float $value): self
    {
        $this->data['insurance_value'] = $value;
        return $this;
    }

    // Valor unitário (envio)
    public function price(float $value): self
    {
        $this->data['unitary_value'] = $value;
        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    // Só o que a declaração de conteúdo do envio pede: nome, quantidade e valor unitário
    public function toDeclaration(): array
    {
        return array_intersect_key($this->data, array_flip(['name', 'quantity', 'unitary_value']));
    }
}
