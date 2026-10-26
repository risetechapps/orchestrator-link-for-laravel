<?php

namespace RiseTechApps\OrchestratorLink;

use Illuminate\Support\Facades\Route;
use RiseTechApps\OrchestratorLink\Http\Controllers\OrchestratorLinkController;

class OrchestratorLink
{
    public static function routes(): void
    {
        // ─── CPF / CNPJ / CEP ────────────────────────────────────────────────
        Route::get('/services/cpf/{cpf}/{date?}', [OrchestratorLinkController::class, 'cpf']);
        Route::get('/services/cnpj/{cnpj}', [OrchestratorLinkController::class, 'cnpj']);
        Route::get('/services/zip_code/{zip_code}', [OrchestratorLinkController::class, 'zipCode']);

        // ─── Empresa: sócios, Simples/MEI e CNAE ─────────────────────────────
        Route::get('/services/cnpj/{cnpj}/partners', [OrchestratorLinkController::class, 'cnpjPartners']);
        Route::get('/services/cnpj/{cnpj}/simples', [OrchestratorLinkController::class, 'cnpjSimples']);
        Route::get('/services/cnae', [OrchestratorLinkController::class, 'cnaeSearch']);
        Route::get('/services/cnae/{code}', [OrchestratorLinkController::class, 'cnae'])
            ->where('code', '[0-9./-]{7,10}');

        // ─── Bancos / Países / Estados / Cidades ─────────────────────────────
        Route::get('/services/banks', [OrchestratorLinkController::class, 'banks']);
        Route::get('/services/countries', [OrchestratorLinkController::class, 'countries']);
        Route::get('/services/country/{country}', [OrchestratorLinkController::class, 'countryInfo']);
        Route::get('/services/states/{country}', [OrchestratorLinkController::class, 'states']);
        Route::get('/services/states/{country}/{state}', [OrchestratorLinkController::class, 'stateInfo']);
        Route::get('/services/cities/{country}/{state}', [OrchestratorLinkController::class, 'cities']);

        // ─── Código IBGE / Geolocalização ────────────────────────────────────
        Route::get('/services/ibge/cities/{uf}', [OrchestratorLinkController::class, 'ibgeCities']);
        Route::get('/services/ibge/city/{code}', [OrchestratorLinkController::class, 'ibgeCity']);
        Route::get('/services/ibge/zip_code/{zip_code}', [OrchestratorLinkController::class, 'ibgeZipCode']);
        Route::get('/services/geo/zip_code/{zip_code}', [OrchestratorLinkController::class, 'geoZipCode']);
        Route::get('/services/geo/distance/{from}/{to}', [OrchestratorLinkController::class, 'geoDistance']);

        // ─── Feriados / Clima / Domínio / Calendário ─────────────────────────
        Route::get('/services/holidays/{year}/{state}', [OrchestratorLinkController::class, 'holidays']);
        Route::get('/services/weather/{city}/{country}/{state?}', [OrchestratorLinkController::class, 'weather']);
        Route::get('/services/domain/{domain}', [OrchestratorLinkController::class, 'domain']);
        Route::post('/services/calendar', [OrchestratorLinkController::class, 'calendar']);

        // ─── FIPE ────────────────────────────────────────────────────────────
        Route::get('/services/fipe/{type}/brands', [OrchestratorLinkController::class, 'fipeBrands'])
            ->where('type', 'cars|motorcycles|trucks');
        Route::get('/services/fipe/{type}/{brand}/models', [OrchestratorLinkController::class, 'fipeModels'])
            ->where('type', 'cars|motorcycles|trucks');
        Route::get('/services/fipe/{type}/{brand}/{model}/years', [OrchestratorLinkController::class, 'fipeYears'])
            ->where('type', 'cars|motorcycles|trucks');
        Route::get('/services/fipe/{type}/{brand}/{model}/{year}', [OrchestratorLinkController::class, 'fipePrice'])
            ->where('type', 'cars|motorcycles|trucks');

        // ─── Câmbio ──────────────────────────────────────────────────────────
        Route::get('/services/exchange/{from}/{to}', [OrchestratorLinkController::class, 'exchange']);

        // ─── Ações B3 ────────────────────────────────────────────────────────
        Route::get('/services/stocks/{symbol}', [OrchestratorLinkController::class, 'stocks']);
        Route::get('/services/stocks', [OrchestratorLinkController::class, 'stocksCatalog']);

        // ─── Indicadores / PIX ───────────────────────────────────────────────
        Route::get('/services/indicators', [OrchestratorLinkController::class, 'indicators']);
        Route::get('/services/indicators/{indicator}', [OrchestratorLinkController::class, 'indicator']);
        Route::post('/services/pix/decode', [OrchestratorLinkController::class, 'pixDecode']);

        // ─── NCM ─────────────────────────────────────────────────────────────
        Route::get('/services/ncm', [OrchestratorLinkController::class, 'ncmSearch']);
        // Caminho antigo da busca, mantido por compatibilidade.
        Route::get('/services/ncm/search', [OrchestratorLinkController::class, 'ncmSearch']);
        Route::get('/services/ncm/{code}/taxes', [OrchestratorLinkController::class, 'ncmTaxes']);
        Route::get('/services/ncm/{code}', [OrchestratorLinkController::class, 'ncm']);

        // ─── Validação: e-mail e telefone ────────────────────────────────────
        Route::get('/services/email/{email}', [OrchestratorLinkController::class, 'email'])
            ->where('email', '[^/]+');
        Route::get('/services/phone/{phone}', [OrchestratorLinkController::class, 'phone']);

        // ─── Frete (white label) ─────────────────────────────────────────────
        Route::prefix('/services/shipping')->group(function () {
            Route::get('services', [OrchestratorLinkController::class, 'shippingServices']);
            Route::post('quotes', [OrchestratorLinkController::class, 'shippingQuote']);
            Route::get('agencies', [OrchestratorLinkController::class, 'shippingAgencies']);

            Route::get('senders', [OrchestratorLinkController::class, 'shippingSenders']);
            Route::post('senders', [OrchestratorLinkController::class, 'shippingCreateSender']);
            Route::put('senders/{sender}', [OrchestratorLinkController::class, 'shippingUpdateSender'])->whereNumber('sender');
            Route::delete('senders/{sender}', [OrchestratorLinkController::class, 'shippingDeleteSender'])->whereNumber('sender');

            Route::get('shipments', [OrchestratorLinkController::class, 'shippingListShipments']);
            Route::post('shipments', [OrchestratorLinkController::class, 'shippingCreateShipment']);
            // Antes de shipments/{shipment}: "labels" e "payments" não são ids.
            Route::get('shipments/labels', [OrchestratorLinkController::class, 'shippingLabels']);
            Route::post('shipments/payments', [OrchestratorLinkController::class, 'shippingPayMany']);
            Route::get('shipments/payments/{group}', [OrchestratorLinkController::class, 'shippingPaymentGroup']);

            Route::get('shipments/{shipment}', [OrchestratorLinkController::class, 'shippingShowShipment']);
            Route::post('shipments/{shipment}/payment/simulate', [OrchestratorLinkController::class, 'shippingSimulatePayment']);
            Route::post('shipments/{shipment}/reverse', [OrchestratorLinkController::class, 'shippingReverse']);
            Route::post('shipments/{shipment}/purchase', [OrchestratorLinkController::class, 'shippingPurchase']);
            Route::get('shipments/{shipment}/payment', [OrchestratorLinkController::class, 'shippingPayment']);
            Route::post('shipments/{shipment}/cancel', [OrchestratorLinkController::class, 'shippingCancel']);
            Route::get('shipments/{shipment}/label', [OrchestratorLinkController::class, 'shippingLabel']);
            Route::get('shipments/{shipment}/tracking', [OrchestratorLinkController::class, 'shippingTracking']);

            Route::get('webhook', [OrchestratorLinkController::class, 'shippingWebhook']);
            Route::put('webhook', [OrchestratorLinkController::class, 'shippingUpdateWebhook']);
            Route::delete('webhook', [OrchestratorLinkController::class, 'shippingDeleteWebhook']);
        });
    }
}
