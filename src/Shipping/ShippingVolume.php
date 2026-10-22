<?php

namespace RiseTechApps\OrchestratorLink\Shipping;

class ShippingVolume
{
    private array $data = [];

    public static function make(): self
    {
        return new self();
    }

    // Dimensões em cm: altura, largura, comprimento
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

    // Produto dentro deste volume. Nos Correios (um volume por objeto), cada
    // volume precisa dos próprios produtos para a declaração de conteúdo.
    public function addProduct(ShippingProduct|callable $product): self
    {
        if (is_callable($product)) {
            $product = $product(ShippingProduct::make());
        }

        $this->data['products'][] = $product->toDeclaration();
        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
