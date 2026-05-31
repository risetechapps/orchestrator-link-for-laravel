<?php

namespace RiseTechApps\OrchestratorLink\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RiseTechApps\OrchestratorLink\Feature\Service;

class OrchestratorLinkController extends Controller
{
    private Service $service;

    public function __construct()
    {
        $this->service = new Service();
    }

    // ─── CPF / CNPJ / CEP ────────────────────────────────────────────────────

    public function cpf(Request $request, string $cpf, string $date = ''): JsonResponse
    {
        return response()->json($this->service->getCPF($cpf, $date));
    }

    public function cnpj(Request $request, string $cnpj): JsonResponse
    {
        return response()->json($this->service->getCNPJ($cnpj));
    }

    public function zipCode(Request $request, string $zip_code): JsonResponse
    {
        return response()->json($this->service->getZipCode($zip_code));
    }

    // ─── Bancos / Países / Estados / Cidades ─────────────────────────────────

    public function banks(): JsonResponse
    {
        return response()->json($this->service->getBanks());
    }

    public function countries(): JsonResponse
    {
        return response()->json($this->service->getCountries());
    }

    public function countryInfo(Request $request, string $country): JsonResponse
    {
        return response()->json($this->service->getCountryInfo($country));
    }

    public function states(Request $request, string $country): JsonResponse
    {
        return response()->json($this->service->getStates($country));
    }

    public function stateInfo(Request $request, string $country, string $state): JsonResponse
    {
        return response()->json($this->service->getStateInfo($country, $state));
    }

    public function cities(Request $request, string $country, string $state): JsonResponse
    {
        return response()->json($this->service->getCities($country, $state));
    }

    // ─── Feriados / Clima / Domínio / Calendário ─────────────────────────────

    public function holidays(Request $request, string $year, string $state): JsonResponse
    {
        return response()->json($this->service->getHolidays($state, $year));
    }

    public function weather(Request $request, string $city, string $country, string $state = ''): JsonResponse
    {
        return response()->json($this->service->getWeather($city, $country, $state));
    }

    public function domain(Request $request, string $domain): JsonResponse
    {
        return response()->json($this->service->getDomain($domain));
    }

    public function calendar(Request $request): JsonResponse
    {
        return response()->json($this->service->getCalendar(
            $request->input('country'),
            $request->input('state'),
            $request->input('city'),
            $request->input('dateStart'),
            $request->input('dateEnd'),
        ));
    }

    // ─── FIPE ────────────────────────────────────────────────────────────────

    public function fipeBrands(Request $request, string $type): JsonResponse
    {
        return response()->json($this->service->getFipeBrands($type));
    }

    public function fipeModels(Request $request, string $type, string $brand): JsonResponse
    {
        return response()->json($this->service->getFipeModels($type, $brand));
    }

    public function fipeYears(Request $request, string $type, string $brand, string $model): JsonResponse
    {
        return response()->json($this->service->getFipeYears($type, $brand, $model));
    }

    public function fipePrice(Request $request, string $type, string $brand, string $model, string $year): JsonResponse
    {
        return response()->json($this->service->getFipePrice($type, $brand, $model, $year));
    }

    // ─── Câmbio ──────────────────────────────────────────────────────────────

    public function exchange(Request $request, string $from, string $to): JsonResponse
    {
        return response()->json($this->service->getExchange($from, $to));
    }

    // ─── Ações B3 ────────────────────────────────────────────────────────────

    public function stocks(Request $request, string $symbol): JsonResponse
    {
        return response()->json($this->service->getStock($symbol));
    }

    // ─── NCM ─────────────────────────────────────────────────────────────────

    public function ncm(Request $request, string $code): JsonResponse
    {
        return response()->json($this->service->getNcm($code));
    }

    public function ncmSearch(Request $request): JsonResponse
    {
        return response()->json($this->service->searchNcm($request->query('search', '')));
    }

    // ─── Shipping (Melhor Envio) ──────────────────────────────────────────────

    public function shippingCarriers(): JsonResponse
    {
        return response()->json($this->service->getCarriers());
    }

    public function shippingCalculate(Request $request): JsonResponse
    {
        return response()->json($this->service->calculateShipping($request->all()));
    }

    public function shippingAddToCart(Request $request): JsonResponse
    {
        return response()->json($this->service->addToCart($request->all()));
    }

    public function shippingRemoveFromCart(Request $request, string $id): JsonResponse
    {
        return response()->json($this->service->removeFromCart($id));
    }

    public function shippingCheckout(Request $request): JsonResponse
    {
        return response()->json($this->service->checkout($request->input('orders', [])));
    }

    public function shippingGenerateLabels(Request $request): JsonResponse
    {
        return response()->json($this->service->generateLabels($request->input('orders', [])));
    }

    public function shippingPrintLabels(Request $request): JsonResponse
    {
        return response()->json($this->service->printLabels(
            $request->input('orders', []),
            $request->input('mode', 'public')
        ));
    }

    public function shippingCancelLabel(Request $request): JsonResponse
    {
        return response()->json($this->service->cancelLabel(
            $request->input('id'),
            $request->input('description', 'Cancelado via integração')
        ));
    }

    public function shippingListOrders(Request $request): JsonResponse
    {
        return response()->json($this->service->listOrders(
            $request->query('status'),
            (int) $request->query('page', 1)
        ));
    }

    public function shippingGetOrder(Request $request, string $id): JsonResponse
    {
        return response()->json($this->service->getOrder($id));
    }

    public function shippingSearchOrder(Request $request): JsonResponse
    {
        return response()->json($this->service->searchOrder($request->query('q', '')));
    }

    public function shippingTrack(Request $request): JsonResponse
    {
        return response()->json($this->service->trackShipment($request->input('orders', [])));
    }
}
