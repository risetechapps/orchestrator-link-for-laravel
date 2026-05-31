<?php

namespace RiseTechApps\OrchestratorLink\Shipping;

class ShippingAddress
{
    private array $data = ['country_id' => 'BR'];

    public static function make(): self
    {
        return new self();
    }

    public function name(string $name): self
    {
        $this->data['name'] = $name;
        return $this;
    }

    public function phone(string $phone): self
    {
        $this->data['phone'] = preg_replace('/\D/', '', $phone);
        return $this;
    }

    public function email(string $email): self
    {
        $this->data['email'] = $email;
        return $this;
    }

    public function document(string $document): self
    {
        $this->data['document'] = preg_replace('/\D/', '', $document);
        return $this;
    }

    public function companyDocument(string $document): self
    {
        $this->data['company_document'] = preg_replace('/\D/', '', $document);
        return $this;
    }

    public function street(string $address, string $number, string $complement = ''): self
    {
        $this->data['address'] = $address;
        $this->data['number']  = $number;

        if ($complement) {
            $this->data['complement'] = $complement;
        }

        return $this;
    }

    public function district(string $district): self
    {
        $this->data['district'] = $district;
        return $this;
    }

    public function city(string $city, string $stateAbbr): self
    {
        $this->data['city']       = $city;
        $this->data['state_abbr'] = strtoupper($stateAbbr);
        return $this;
    }

    public function postalCode(string $postalCode): self
    {
        $this->data['postal_code'] = preg_replace('/\D/', '', $postalCode);
        return $this;
    }

    public function country(string $countryId): self
    {
        $this->data['country_id'] = strtoupper($countryId);
        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
