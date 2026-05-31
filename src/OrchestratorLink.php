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

        // ─── Bancos / Países / Estados / Cidades ─────────────────────────────
        Route::get('/services/banks', [OrchestratorLinkController::class, 'banks']);
        Route::get('/services/countries', [OrchestratorLinkController::class, 'countries']);
        Route::get('/services/country/{country}', [OrchestratorLinkController::class, 'countryInfo']);
        Route::get('/services/states/{country}', [OrchestratorLinkController::class, 'states']);
        Route::get('/services/states/{country}/{state}', [OrchestratorLinkController::class, 'stateInfo']);
        Route::get('/services/cities/{country}/{state}', [OrchestratorLinkController::class, 'cities']);

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

        // ─── NCM ─────────────────────────────────────────────────────────────
        Route::get('/services/ncm/search', [OrchestratorLinkController::class, 'ncmSearch']);
        Route::get('/services/ncm/{code}', [OrchestratorLinkController::class, 'ncm']);

        // ─── Shipping (Melhor Envio) ──────────────────────────────────────────
        Route::prefix('/services/shipping')->group(function () {
            Route::get('carriers', [OrchestratorLinkController::class, 'shippingCarriers']);
            Route::post('calculate', [OrchestratorLinkController::class, 'shippingCalculate']);
            Route::post('cart', [OrchestratorLinkController::class, 'shippingAddToCart']);
            Route::delete('cart/{id}', [OrchestratorLinkController::class, 'shippingRemoveFromCart']);
            Route::post('checkout', [OrchestratorLinkController::class, 'shippingCheckout']);
            Route::post('labels/generate', [OrchestratorLinkController::class, 'shippingGenerateLabels']);
            Route::post('labels/print', [OrchestratorLinkController::class, 'shippingPrintLabels']);
            Route::post('labels/cancel', [OrchestratorLinkController::class, 'shippingCancelLabel']);
            Route::get('orders/search', [OrchestratorLinkController::class, 'shippingSearchOrder']);
            Route::get('orders/{id}', [OrchestratorLinkController::class, 'shippingGetOrder']);
            Route::get('orders', [OrchestratorLinkController::class, 'shippingListOrders']);
            Route::post('track', [OrchestratorLinkController::class, 'shippingTrack']);
        });
    }
}
