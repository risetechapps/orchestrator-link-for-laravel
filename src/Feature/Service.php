<?php

namespace RiseTechApps\OrchestratorLink\Feature;

use Illuminate\Support\Str;
use RiseTechApps\OrchestratorLink\Shipping\ShipmentBuilder;
use RiseTechApps\OrchestratorLink\Shipping\ShippingQuoteBuilder;

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
        return static::request('/cnpj/' . static::cnpj($cnpj), cacheTtl: self::TTL_DAY);
    }

    public function getZipCode(string $zip_code): array
    {
        $zip_code = preg_replace('/[^0-9]/', '', $zip_code);
        return static::request('/zip_code/' . $zip_code, cacheTtl: self::TTL_STATIC);
    }

    // ─── Empresa: sócios, Simples/MEI e CNAE ─────────────────────────────────

    /** Quadro de sócios e administradores (QSA) do CNPJ. */
    public function getCnpjPartners(string $cnpj): array
    {
        return static::request('/cnpj/' . static::cnpj($cnpj) . '/partners', cacheTtl: self::TTL_DAY);
    }

    /** Opção pelo Simples Nacional e pelo MEI, com as datas de opção e de exclusão. */
    public function getCnpjSimples(string $cnpj): array
    {
        return static::request('/cnpj/' . static::cnpj($cnpj) . '/simples', cacheTtl: self::TTL_DAY);
    }

    /** Subclasse CNAE: aceita `1921700` ou o formato do cartão CNPJ (`1921-7/00`). */
    public function getCnae(string $code): array
    {
        $code = preg_replace('/[^0-9]/', '', $code);
        return static::request('/cnae/' . $code, cacheTtl: self::TTL_STATIC);
    }

    /** Busca CNAE pela descrição (sem acento) ou pelo começo do código. */
    public function searchCnae(string $term): array
    {
        return static::request('/cnae?' . http_build_query(['search' => $term]), cacheTtl: self::TTL_STATIC);
    }

    // ─── Bancos / Países / Estados / Cidades ─────────────────────────────────

    public function getBanks(): array
    {
        return static::request('/banks', cacheTtl: self::TTL_STATIC);
    }

    public function getCountries(): array
    {
        return static::request('/countries', cacheTtl: self::TTL_STATIC);
    }

    public function getCountryInfo(string $country): array
    {
        return static::request('/country/' . $country, cacheTtl: self::TTL_STATIC);
    }

    public function getStates(string $country): array
    {
        return static::request('/states/' . $country, cacheTtl: self::TTL_STATIC);
    }

    public function getStateInfo(string $country, string $state): array
    {
        return static::request('/states/' . $country . '/' . $state, cacheTtl: self::TTL_STATIC);
    }

    public function getCities(string $country, string $state): array
    {
        return static::request('/cities/' . $country . '/' . $state, cacheTtl: self::TTL_STATIC);
    }

    // ─── Localização: código IBGE e geolocalização ───────────────────────────

    /** Municípios da UF com o código IBGE; com `$name`, só os que batem com o nome. */
    public function getIbgeCities(string $uf, ?string $name = null): array
    {
        $query = $name ? '?' . http_build_query(['name' => $name]) : '';
        return static::request('/ibge/cities/' . strtoupper($uf) . $query, cacheTtl: self::TTL_STATIC);
    }

    /** Município pelo código IBGE (7 dígitos). */
    public function getIbgeCity(string $code): array
    {
        $code = preg_replace('/[^0-9]/', '', $code);
        return static::request('/ibge/city/' . $code, cacheTtl: self::TTL_STATIC);
    }

    /** Município do CEP com o código IBGE (o que a NF-e pede). */
    public function getIbgeByZipCode(string $zip_code): array
    {
        $zip_code = preg_replace('/[^0-9]/', '', $zip_code);
        return static::request('/ibge/zip_code/' . $zip_code, cacheTtl: self::TTL_STATIC);
    }

    /** Latitude e longitude do CEP, com a precisão (`zip_code`, `street` ou `city`). */
    public function getGeoByZipCode(string $zip_code): array
    {
        $zip_code = preg_replace('/[^0-9]/', '', $zip_code);
        return static::request('/geo/zip_code/' . $zip_code, cacheTtl: self::TTL_STATIC);
    }

    /** Distância em linha reta entre dois CEPs. */
    public function getDistance(string $from, string $to): array
    {
        $from = preg_replace('/[^0-9]/', '', $from);
        $to   = preg_replace('/[^0-9]/', '', $to);
        return static::request('/geo/distance/' . $from . '/' . $to, cacheTtl: self::TTL_STATIC);
    }

    // ─── Feriados / Clima / Domínio / Calendário ──────────────────────────────

    public function getHolidays(string $state, string $year): array
    {
        return static::request('/holidays/' . $year . '/' . $state, cacheTtl: self::TTL_STATIC);
    }

    public function getWeather(string $city, string $country, string $state = ''): array
    {
        $path = '/weather/' . $city . '/' . $country . ($state ? '/' . $state : '');
        return static::request($path);
    }

    public function getDomain(string $domain): array
    {
        $domain = Str::replace(['https://', 'http://'], '', $domain);
        return static::request('/domain/' . $domain, cacheTtl: self::TTL_HOUR);
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
        return static::request('/fipe/' . $type . '/brands', cacheTtl: self::TTL_STATIC);
    }

    public function getFipeModels(string $type, string $brand): array
    {
        return static::request('/fipe/' . $type . '/' . $brand . '/models', cacheTtl: self::TTL_STATIC);
    }

    public function getFipeYears(string $type, string $brand, string $model): array
    {
        return static::request('/fipe/' . $type . '/' . $brand . '/' . $model . '/years', cacheTtl: self::TTL_STATIC);
    }

    public function getFipePrice(string $type, string $brand, string $model, string $year): array
    {
        return static::request('/fipe/' . $type . '/' . $brand . '/' . $model . '/' . $year, cacheTtl: self::TTL_STATIC);
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

    /**
     * Catálogo de ativos da B3, paginado. Filtros: search, sector, type,
     * sort_by, sort_order, limit e page.
     */
    public function getStocks(array $filters = []): array
    {
        $query = http_build_query(array_filter($filters, fn ($value) => $value !== null && $value !== ''));
        return static::request('/stocks' . ($query ? '?' . $query : ''));
    }

    // ─── Indicadores / PIX ───────────────────────────────────────────────────

    /** Último valor de cada indicador (Selic, CDI, IPCA, IPCA 12m, IGP-M, INPC e TR). */
    public function getIndicators(): array
    {
        return static::request('/indicators', cacheTtl: self::TTL_HOUR);
    }

    /**
     * Um indicador: o último valor ou, com `$from` (Y-m-d), a série até `$to`
     * (padrão hoje), de no máximo 10 anos.
     */
    public function getIndicator(string $indicator, ?string $from = null, ?string $to = null): array
    {
        $query = http_build_query(array_filter(['from' => $from, 'to' => $to]));
        return static::request('/indicators/' . strtolower($indicator) . ($query ? '?' . $query : ''), cacheTtl: self::TTL_HOUR);
    }

    /** Lê um PIX copia e cola: confere o CRC e devolve chave, recebedor, valor, txid etc. */
    public function decodePix(string $code): array
    {
        return static::request('/pix/decode', 'POST', ['code' => trim($code)]);
    }

    // ─── NCM ─────────────────────────────────────────────────────────────────

    public function getNcm(string $code): array
    {
        $code = preg_replace('/[^0-9]/', '', $code);
        return static::request('/ncm/' . $code, cacheTtl: self::TTL_STATIC);
    }

    public function searchNcm(string $term): array
    {
        return static::request('/ncm?search=' . urlencode($term), cacheTtl: self::TTL_STATIC);
    }

    /** Tributos aproximados do NCM (8 dígitos) na UF, pela tabela do IBPT. */
    public function getNcmTaxes(string $code, string $uf): array
    {
        $code = preg_replace('/[^0-9]/', '', $code);
        return static::request('/ncm/' . $code . '/taxes?' . http_build_query(['uf' => strtoupper($uf)]), cacheTtl: self::TTL_DAY);
    }

    // ─── Validação: e-mail e telefone ────────────────────────────────────────

    /** Sintaxe, MX, provedor gratuito, descartável, conta de função e sugestão de correção. */
    public function validateEmail(string $email): array
    {
        return static::request('/email/' . rawurlencode(trim($email)), cacheTtl: self::TTL_HOUR);
    }

    /** Valida o telefone (sem DDI, lido como brasileiro): tipo, formatos, UF e cidades do DDD. */
    public function validatePhone(string $phone): array
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return static::request('/phone/' . rawurlencode($phone), cacheTtl: self::TTL_DAY);
    }

    // ─── Frete: catálogo e cotação ───────────────────────────────────────────

    /** Serviços de entrega disponíveis (transportadora e modalidade). */
    public function getShippingServices(): array
    {
        return static::request('/shipping/services', cacheTtl: self::TTL_DAY);
    }

    /** Cotação com o preço final de cada serviço. Prefira o builder `buildQuote()`. */
    public function quoteShipping(array $data): array
    {
        return static::request('/shipping/quotes', 'POST', $data);
    }

    /** Agências de postagem perto do remetente, para os serviços que exigem uma. */
    public function getShippingAgencies(int $service, string $postalCode, string $state, string $city, ?string $district = null): array
    {
        $query = http_build_query(array_filter([
            'service'     => $service,
            'postal_code' => preg_replace('/[^0-9]/', '', $postalCode),
            'state'       => strtoupper($state),
            'city'        => $city,
            'district'    => $district,
        ]));

        return static::request('/shipping/agencies?' . $query, cacheTtl: self::TTL_HOUR);
    }

    // ─── Frete: remetentes salvos ────────────────────────────────────────────

    public function getShippingSenders(): array
    {
        return static::request('/shipping/senders');
    }

    /**
     * Endereço do remetente em `from` (campos de ShippingAddress) mais `label`,
     * `is_default` e `logo`. O endereço também pode vir solto, junto do
     * `label`: ele é agrupado em `from`, como o orchestrator espera.
     */
    public function createShippingSender(array $data): array
    {
        return static::request('/shipping/senders', 'POST', static::senderPayload($data));
    }

    public function updateShippingSender(int $id, array $data): array
    {
        return static::request('/shipping/senders/' . $id, 'PUT', static::senderPayload($data));
    }

    public function deleteShippingSender(int $id): array
    {
        return static::request('/shipping/senders/' . $id, 'DELETE');
    }

    // ─── Frete: envios ───────────────────────────────────────────────────────

    /** Envios da conta. Filtros: status, external_reference, label_code, per_page e page. */
    public function listShipments(array $filters = []): array
    {
        $query = http_build_query(array_filter($filters, fn ($value) => $value !== null && $value !== ''));
        return static::request('/shipping/shipments' . ($query ? '?' . $query : ''));
    }

    /**
     * Cria o envio. Prefira o builder `buildShipment()`. Com `$idempotencyKey`,
     * repetir a chamada com a mesma chave devolve o mesmo envio em vez de criar outro.
     */
    public function createShipment(array $data, ?string $idempotencyKey = null): array
    {
        return static::request('/shipping/shipments', 'POST', $data, headers: ['Idempotency-Key' => $idempotencyKey]);
    }

    /** Envio com o histórico de eventos. */
    public function getShipment(string $id): array
    {
        return static::request('/shipping/shipments/' . $id);
    }

    /** Gera (ou devolve, se ainda válido) o PIX do envio. A etiqueta é comprada quando o PIX for pago. */
    public function purchaseShipment(string $id, ?string $idempotencyKey = null): array
    {
        return static::request('/shipping/shipments/' . $id . '/purchase', 'POST', headers: ['Idempotency-Key' => $idempotencyKey]);
    }

    /** Último pagamento do envio, conferido no gateway. */
    public function getShipmentPayment(string $id): array
    {
        return static::request('/shipping/shipments/' . $id . '/payment');
    }

    /** Um PIX para vários envios (até 50). */
    public function payShipments(array $shipmentIds, ?string $idempotencyKey = null): array
    {
        return static::request('/shipping/shipments/payments', 'POST', [
            'shipment_ids' => array_values($shipmentIds),
        ], headers: ['Idempotency-Key' => $idempotencyKey]);
    }

    /** Situação do PIX de vários envios (o `id` devolvido por payShipments). */
    public function getShipmentPaymentGroup(string $groupId): array
    {
        return static::request('/shipping/shipments/payments/' . $groupId);
    }

    /** Só com a chave de teste: aprova o PIX simulado e segue o fluxo (etiqueta no sandbox). */
    public function simulateShipmentPayment(string $id): array
    {
        return static::request('/shipping/shipments/' . $id . '/payment/simulate', 'POST');
    }

    /**
     * Devolução: novo envio com os endereços do original invertidos. `$data`
     * leva service, volumes, products e options, como na criação.
     */
    public function reverseShipment(string $id, array $data, ?string $idempotencyKey = null): array
    {
        return static::request('/shipping/shipments/' . $id . '/reverse', 'POST', $data, headers: ['Idempotency-Key' => $idempotencyKey]);
    }

    /** Cancela o envio; se já pago, o PIX é estornado. */
    public function cancelShipment(string $id, ?string $reason = null): array
    {
        return static::request('/shipping/shipments/' . $id . '/cancel', 'POST', array_filter(['reason' => $reason]));
    }

    /** Sincroniza status e rastreio com a transportadora e devolve o envio atualizado. */
    public function trackShipment(string $id): array
    {
        return static::request('/shipping/shipments/' . $id . '/tracking');
    }

    /** PDF da etiqueta: `data.content` (bytes), `data.content_type` e `data.filename`. */
    public function getShipmentLabel(string $id): array
    {
        return static::download('/shipping/shipments/' . $id . '/label');
    }

    /** Um PDF com as etiquetas de vários envios (até 50). */
    public function getShipmentLabels(array $shipmentIds): array
    {
        return static::download('/shipping/shipments/labels?' . http_build_query(['ids' => implode(',', $shipmentIds)]));
    }

    // ─── Frete: webhook da conta ─────────────────────────────────────────────

    public function getShippingWebhook(): array
    {
        return static::request('/shipping/webhook');
    }

    /**
     * Cadastra ou troca a URL (HTTPS pública). A resposta traz o `secret` de
     * assinatura uma única vez: guarde-o para ShippingWebhook::verify().
     */
    public function setShippingWebhook(string $url): array
    {
        return static::request('/shipping/webhook', 'PUT', ['url' => $url]);
    }

    public function deleteShippingWebhook(): array
    {
        return static::request('/shipping/webhook', 'DELETE');
    }

    // ─── Normalização ────────────────────────────────────────────────────────

    /**
     * CNPJ como o orchestrator valida: 14 caracteres, letras maiúsculas e
     * números. Desde 2026 a Receita emite CNPJ com letras nas 12 primeiras
     * posições; tirar tudo que não é dígito mutilava esses.
     */
    protected static function cnpj(string $cnpj): string
    {
        return preg_replace('/[^0-9A-Z]/', '', strtoupper($cnpj));
    }

    /** Remetente com o endereço em `from`; o que já vem assim passa como está. */
    protected static function senderPayload(array $data): array
    {
        if (array_key_exists('from', $data)) {
            return $data;
        }

        $meta = array_intersect_key($data, array_flip(['label', 'is_default', 'logo']));
        $address = array_diff_key($data, $meta);

        return $address === [] ? $meta : ['from' => $address] + $meta;
    }

    // ─── Builders ────────────────────────────────────────────────────────────

    public function buildQuote(): ShippingQuoteBuilder
    {
        return new ShippingQuoteBuilder($this);
    }

    public function buildShipment(): ShipmentBuilder
    {
        return new ShipmentBuilder($this);
    }
}
