<?php

namespace RiseTechApps\OrchestratorLink\Feature;

use Illuminate\Http\Request;

class Service extends Connection
{
    public function getCPF(string $cpf, string $date): array
    {
        return static::request('/cpf/' . $cpf . DIRECTORY_SEPARATOR . $date);
    }

    public function getCNPJ(string $cnpj): array
    {
        return static::request('/cnpj/' . $cnpj);
    }

    public function getZipCode(string $zip_code): array
    {
        return static::request('/zip_code/' . $zip_code);
    }

    public function getBanks(): array
    {
        return static::request('/banks');
    }

    public function getCountries(): array
    {
        return static::request('/countries');
    }

    public function getCountryInfo(string $country): array
    {
        return static::request('/country/' . $country);
    }

    public function getStates(string $country): array
    {
        return static::request('/states/' . $country);
    }

    public function getStateInfo(string $country, string $state): array
    {
        return static::request('/states/' . $country . DIRECTORY_SEPARATOR . $state);
    }

    public function getCities(string $country, string $state): array
    {
        return static::request('/cities/' . $country . DIRECTORY_SEPARATOR . $state);
    }

    public function getHolidays(string $state, string $year): array
    {
        return static::request('/holidays/' . $year . DIRECTORY_SEPARATOR . $state);
    }

    public function getWeather(string $state, string $city): array
    {
        return static::request('/weather/' . $city . DIRECTORY_SEPARATOR . $state);
    }

    public function getDomain(string $domain): array
    {
        return static::request('/domain/' . $domain);
    }
}
