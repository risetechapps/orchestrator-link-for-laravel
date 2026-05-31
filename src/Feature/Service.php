<?php

namespace RiseTechApps\OrchestratorLink\Feature;

use Illuminate\Support\Str;
use RiseTechApps\OrchestratorLink\Shipping\ShippingCalculateBuilder;
use RiseTechApps\OrchestratorLink\Shipping\ShippingOrderBuilder;

class Service extends Connection
{
    // ─── CPF / CNPJ / CEP ────────────────────────────────────────────────────

    public function getCPF(string $cpf, string $date = ''): array
    {
        $cpf  = preg_replace('/[^0-9]/', '', $cpf);
        $path = '/cpf/' . $cpf . ($date ? '/' . $date : '');
        return static::request($path);
    }

    public function getCNPJ(string $cnpj): array
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return static::request('/cnpj/' . $cnpj);
    }

    public function getZipCode(string $zip_code): array
    {
        $zip_code = preg_replace('/[^0-9]/', '', $zip_code);
        return static::request('/zip_code/' . $zip_code);
    }

    // ─── Bancos / Países / Estados / Cidades ─────────────────────────────────

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
        return static::request('/states/' . $country . '/' . $state);
    }

    public function getCities(string $country, string $state): array
    {
        return static::request('/cities/' . $country . '/' . $state);
    }

    // ─── Feriados / Clima / Domínio / Calendário ──────────────────────────────

    public function getHolidays(string $state, string $year): array
    {
        return static::request('/holidays/' . $year . '/' . $state);
    }

    public function getWeather(string $city, string $country, string $state = ''): array
    {
        $path = '/weather/' . $city . '/' . $country . ($state ? '/' . $state : '');
        return static::request($path);
    }

    public function getDomain(string $domain): array
    {
        $domain = Str::replace(['https://', 'http://'], '', $domain);
        return static::request('/domain/' . $domain);
    }

    public function getCalendar(string $country, string $state, string $city, string $start, string $end): array
    {
        return static::request('/calendar', 'POST', [
            'dateStart' => $start,
            'dateEnd'   => $end,
            'country'   => $country,
            'state'     => $state,
            'city'      => $city,
        ]);
    }

    // ─── FIPE ────────────────────────────────────────────────────────────────

    public function getFipeBrands(string $type): array
    {
        return static::request('/fipe/' . $type . '/brands');
    }

    public function getFipeModels(string $type, string $brand): array
    {
        return static::request('/fipe/' . $type . '/' . $brand . '/models');
    }

    public function getFipeYears(string $type, string $brand, string $model): array
    {
        return static::request('/fipe/' . $type . '/' . $brand . '/' . $model . '/years');
    }

    public function getFipePrice(string $type, string $brand, string $model, string $year): array
    {
        return static::request('/fipe/' . $type . '/' . $brand . '/' . $model . '/' . $year);
    }

    // ─── Câmbio ──────────────────────────────────────────────────────────────

    public function getExchange(string $from, string $to): array
    {
        return static::request('/exchange/' . strtoupper($from) . '/' . strtoupper($to));
    }

    // ─── Ações B3 ────────────────────────────────────────────────────────────

    public function getStock(string $symbol): array
    {
        return static::request('/stocks/' . strtoupper($symbol));
    }

    // ─── NCM ─────────────────────────────────────────────────────────────────

    public function getNcm(string $code): array
    {
        $code = preg_replace('/[^0-9]/', '', $code);
        return static::request('/ncm/' . $code);
    }

    public function searchNcm(string $term): array
    {
        return static::request('/ncm?search=' . urlencode($term));
    }

    // ─── Shipping (Melhor Envio) ──────────────────────────────────────────────

    public function getCarriers(): array
    {
        return static::request('/shipping/carriers');
    }

    public function calculateShipping(array $data): array
    {
        return static::request('/shipping/calculate', 'POST', $data);
    }

    public function addToCart(array $data): array
    {
        return static::request('/shipping/cart', 'POST', $data);
    }

    public function removeFromCart(string $id): array
    {
        return static::request('/shipping/cart/' . $id, 'DELETE');
    }

    public function checkout(string|array $orderIds): array
    {
        $orders = is_string($orderIds) ? [$orderIds] : $orderIds;
        return static::request('/shipping/checkout', 'POST', ['orders' => $orders]);
    }

    public function generateLabels(string|array $orderIds): array
    {
        $orders = is_string($orderIds) ? [$orderIds] : $orderIds;
        return static::request('/shipping/labels/generate', 'POST', ['orders' => $orders]);
    }

    public function printLabels(string|array $orderIds, string $mode = 'public'): array
    {
        $orders = is_string($orderIds) ? [$orderIds] : $orderIds;
        return static::request('/shipping/labels/print', 'POST', ['orders' => $orders, 'mode' => $mode]);
    }

    public function cancelLabel(string $id, string $description = 'Cancelado via integração'): array
    {
        return static::request('/shipping/labels/cancel', 'POST', ['id' => $id, 'description' => $description]);
    }

    public function listOrders(?string $status = null, int $page = 1): array
    {
        $query = http_build_query(array_filter(['status' => $status, 'page' => $page > 1 ? $page : null]));
        return static::request('/shipping/orders' . ($query ? '?' . $query : ''));
    }

    public function getOrder(string $id): array
    {
        return static::request('/shipping/orders/' . $id);
    }

    public function searchOrder(string $query): array
    {
        return static::request('/shipping/orders/search?q=' . urlencode($query));
    }

    public function trackShipment(array $orderIds): array
    {
        return static::request('/shipping/track', 'POST', ['orders' => $orderIds]);
    }

    // ─── Builders ────────────────────────────────────────────────────────────

    public function buildCalculate(): ShippingCalculateBuilder
    {
        return new ShippingCalculateBuilder($this);
    }

    public function buildOrder(): ShippingOrderBuilder
    {
        return new ShippingOrderBuilder($this);
    }
}
