<?php

namespace RiseTechApps\OrchestratorLink\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use RiseTechApps\OrchestratorLink\Feature\Service;

class OrchestratorLinkController extends Controller
{
    private readonly Service $service;

    public function __construct()
    {
        $this->service = new Service();
    }

    // ─── CPF / CNPJ / CEP ────────────────────────────────────────────────────

    public function cpf(Request $request, string $cpf, string $date = ''): JsonResponse
    {
        return $this->respond($this->service->getCPF($cpf, $date));
    }

    public function cnpj(Request $request, string $cnpj): JsonResponse
    {
        return $this->respond($this->service->getCNPJ($cnpj));
    }

    public function zipCode(Request $request, string $zip_code): JsonResponse
    {
        return $this->respond($this->service->getZipCode($zip_code));
    }

    // ─── Empresa: sócios, Simples/MEI e CNAE ─────────────────────────────────

    public function cnpjPartners(Request $request, string $cnpj): JsonResponse
    {
        return $this->respond($this->service->getCnpjPartners($cnpj));
    }

    public function cnpjSimples(Request $request, string $cnpj): JsonResponse
    {
        return $this->respond($this->service->getCnpjSimples($cnpj));
    }

    public function cnae(Request $request, string $code): JsonResponse
    {
        return $this->respond($this->service->getCnae($code));
    }

    public function cnaeSearch(Request $request): JsonResponse
    {
        return $this->respond($this->service->searchCnae((string) $request->query('search', '')));
    }

    // ─── Bancos / Países / Estados / Cidades ─────────────────────────────────

    public function banks(): JsonResponse
    {
        return $this->respond($this->service->getBanks());
    }

    public function countries(): JsonResponse
    {
        return $this->respond($this->service->getCountries());
    }

    public function countryInfo(Request $request, string $country): JsonResponse
    {
        return $this->respond($this->service->getCountryInfo($country));
    }

    public function states(Request $request, string $country): JsonResponse
    {
        return $this->respond($this->service->getStates($country));
    }

    public function stateInfo(Request $request, string $country, string $state): JsonResponse
    {
        return $this->respond($this->service->getStateInfo($country, $state));
    }

    public function cities(Request $request, string $country, string $state): JsonResponse
    {
        return $this->respond($this->service->getCities($country, $state));
    }

    // ─── Código IBGE / Geolocalização ────────────────────────────────────────

    public function ibgeCities(Request $request, string $uf): JsonResponse
    {
        return $this->respond($this->service->getIbgeCities($uf, $request->query('name')));
    }

    public function ibgeCity(Request $request, string $code): JsonResponse
    {
        return $this->respond($this->service->getIbgeCity($code));
    }

    public function ibgeZipCode(Request $request, string $zip_code): JsonResponse
    {
        return $this->respond($this->service->getIbgeByZipCode($zip_code));
    }

    public function geoZipCode(Request $request, string $zip_code): JsonResponse
    {
        return $this->respond($this->service->getGeoByZipCode($zip_code));
    }

    public function geoDistance(Request $request, string $from, string $to): JsonResponse
    {
        return $this->respond($this->service->getDistance($from, $to));
    }

    // ─── Feriados / Clima / Domínio / Calendário ─────────────────────────────

    public function holidays(Request $request, string $year, string $state): JsonResponse
    {
        return $this->respond($this->service->getHolidays($state, $year));
    }

    public function weather(Request $request, string $city, string $country, string $state = ''): JsonResponse
    {
        return $this->respond($this->service->getWeather($city, $country, $state));
    }

    public function domain(Request $request, string $domain): JsonResponse
    {
        return $this->respond($this->service->getDomain($domain));
    }

    public function calendar(Request $request): JsonResponse
    {
        return $this->respond($this->service->getCalendar(
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
        return $this->respond($this->service->getFipeBrands($type));
    }

    public function fipeModels(Request $request, string $type, string $brand): JsonResponse
    {
        return $this->respond($this->service->getFipeModels($type, $brand));
    }

    public function fipeYears(Request $request, string $type, string $brand, string $model): JsonResponse
    {
        return $this->respond($this->service->getFipeYears($type, $brand, $model));
    }

    public function fipePrice(Request $request, string $type, string $brand, string $model, string $year): JsonResponse
    {
        return $this->respond($this->service->getFipePrice($type, $brand, $model, $year));
    }

    // ─── Câmbio ──────────────────────────────────────────────────────────────

    public function exchange(Request $request, string $from, string $to): JsonResponse
    {
        return $this->respond($this->service->getExchange($from, $to));
    }

    // ─── Ações B3 ────────────────────────────────────────────────────────────

    public function stocks(Request $request, string $symbol): JsonResponse
    {
        return $this->respond($this->service->getStock($symbol));
    }

    public function stocksCatalog(Request $request): JsonResponse
    {
        return $this->respond($this->service->getStocks(
            $request->only(['search', 'sector', 'type', 'sort_by', 'sort_order', 'limit', 'page'])
        ));
    }

    // ─── Indicadores / PIX ───────────────────────────────────────────────────

    public function indicators(): JsonResponse
    {
        return $this->respond($this->service->getIndicators());
    }

    public function indicator(Request $request, string $indicator): JsonResponse
    {
        return $this->respond($this->service->getIndicator($indicator, $request->query('from'), $request->query('to')));
    }

    public function pixDecode(Request $request): JsonResponse
    {
        return $this->respond($this->service->decodePix((string) $request->input('code', '')));
    }

    // ─── NCM ─────────────────────────────────────────────────────────────────

    public function ncm(Request $request, string $code): JsonResponse
    {
        return $this->respond($this->service->getNcm($code));
    }

    public function ncmSearch(Request $request): JsonResponse
    {
        return $this->respond($this->service->searchNcm($request->query('search', '')));
    }

    public function ncmTaxes(Request $request, string $code): JsonResponse
    {
        return $this->respond($this->service->getNcmTaxes($code, (string) $request->query('uf', '')));
    }

    // ─── Validação: e-mail e telefone ────────────────────────────────────────

    public function email(Request $request, string $email): JsonResponse
    {
        return $this->respond($this->service->validateEmail($email));
    }

    public function phone(Request $request, string $phone): JsonResponse
    {
        return $this->respond($this->service->validatePhone($phone));
    }

    // ─── Frete (white label) ─────────────────────────────────────────────────

    public function shippingServices(): JsonResponse
    {
        return $this->respond($this->service->getShippingServices());
    }

    public function shippingQuote(Request $request): JsonResponse
    {
        return $this->respond($this->service->quoteShipping($request->all()));
    }

    public function shippingAgencies(Request $request): JsonResponse
    {
        return $this->respond($this->service->getShippingAgencies(
            (int) $request->query('service'),
            (string) $request->query('postal_code', ''),
            (string) $request->query('state', ''),
            (string) $request->query('city', ''),
            $request->query('district'),
        ));
    }

    public function shippingSenders(): JsonResponse
    {
        return $this->respond($this->service->getShippingSenders());
    }

    public function shippingCreateSender(Request $request): JsonResponse
    {
        return $this->respond($this->service->createShippingSender($request->all()));
    }

    public function shippingUpdateSender(Request $request, int $sender): JsonResponse
    {
        return $this->respond($this->service->updateShippingSender($sender, $request->all()));
    }

    public function shippingDeleteSender(int $sender): JsonResponse
    {
        return $this->respond($this->service->deleteShippingSender($sender));
    }

    public function shippingListShipments(Request $request): JsonResponse
    {
        return $this->respond($this->service->listShipments(
            $request->only(['status', 'external_reference', 'label_code', 'per_page', 'page'])
        ));
    }

    public function shippingCreateShipment(Request $request): JsonResponse
    {
        return $this->respond($this->service->createShipment($request->all(), $request->header('Idempotency-Key')));
    }

    public function shippingShowShipment(string $shipment): JsonResponse
    {
        return $this->respond($this->service->getShipment($shipment));
    }

    public function shippingPurchase(Request $request, string $shipment): JsonResponse
    {
        return $this->respond($this->service->purchaseShipment($shipment, $request->header('Idempotency-Key')));
    }

    public function shippingPayment(string $shipment): JsonResponse
    {
        return $this->respond($this->service->getShipmentPayment($shipment));
    }

    public function shippingPayMany(Request $request): JsonResponse
    {
        return $this->respond($this->service->payShipments(
            (array) $request->input('shipment_ids', []),
            $request->header('Idempotency-Key')
        ));
    }

    public function shippingPaymentGroup(string $group): JsonResponse
    {
        return $this->respond($this->service->getShipmentPaymentGroup($group));
    }

    public function shippingSimulatePayment(string $shipment): JsonResponse
    {
        return $this->respond($this->service->simulateShipmentPayment($shipment));
    }

    public function shippingReverse(Request $request, string $shipment): JsonResponse
    {
        return $this->respond($this->service->reverseShipment($shipment, $request->all(), $request->header('Idempotency-Key')));
    }

    public function shippingCancel(Request $request, string $shipment): JsonResponse
    {
        return $this->respond($this->service->cancelShipment($shipment, $request->input('reason')));
    }

    public function shippingTracking(string $shipment): JsonResponse
    {
        return $this->respond($this->service->trackShipment($shipment));
    }

    public function shippingLabel(string $shipment): Response
    {
        return $this->pdf($this->service->getShipmentLabel($shipment));
    }

    public function shippingLabels(Request $request): Response
    {
        $ids = array_filter(explode(',', (string) $request->query('ids', '')));

        return $this->pdf($this->service->getShipmentLabels($ids));
    }

    public function shippingWebhook(): JsonResponse
    {
        return $this->respond($this->service->getShippingWebhook());
    }

    public function shippingUpdateWebhook(Request $request): JsonResponse
    {
        return $this->respond($this->service->setShippingWebhook((string) $request->input('url', '')));
    }

    public function shippingDeleteWebhook(): JsonResponse
    {
        return $this->respond($this->service->deleteShippingWebhook());
    }

    /** PDF do orchestrator repassado como arquivo; falha, como o JSON das demais rotas. */
    private function pdf(array $result): Response
    {
        if (!$result['success']) {
            return $this->respond($result);
        }

        $filename = $result['data']['filename'] ?? 'etiqueta.pdf';

        return response($result['data']['content'], 200, [
            'Content-Type'        => $result['data']['content_type'],
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control'       => 'private, no-store',
        ]);
    }

    /**
     * Resposta do orchestrator com o status HTTP dele: 422 de validação, 404,
     * 409... chegam ao front como vieram. Sem resposta (orchestrator fora do
     * ar ou chave não configurada), 502.
     */
    private function respond(array $result): JsonResponse
    {
        return response()->json($result, $result['success'] ? 200 : ($result['status'] ?? 502));
    }
}
