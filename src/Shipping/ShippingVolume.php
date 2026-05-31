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

    public function toArray(): array
    {
        return $this->data;
    }
}
