<?php

namespace RiseTechApps\OrchestratorLink;



use Illuminate\Support\Facades\Route;
use RiseTechApps\OrchestratorLink\Http\Controllers\OrchestratorLinkController;

class OrchestratorLink
{
    public static function routes(): void
    {
        Route::get('/services/cpf/{cpf}/{date}', [OrchestratorLinkController::class, 'cpf']);
        Route::get('/services/cnpj/{cnpj}', [OrchestratorLinkController::class, 'cnpj']);
        Route::get('/services/zip_code/{zip_code}', [OrchestratorLinkController::class, 'zipCode']);
        Route::get('/services/banks', [OrchestratorLinkController::class, 'banks']);
        Route::get('/services/countries', [OrchestratorLinkController::class, 'countries']);
        Route::get('/services/states/{country}', [OrchestratorLinkController::class, 'states']);
        Route::get('/services/cities/{country}/{state}', [OrchestratorLinkController::class, 'cities']);
        Route::get('/services/all/states', [OrchestratorLinkController::class, 'statesAll']);
        Route::get('/services/all/cities', [OrchestratorLinkController::class, 'citiesAll']);
        Route::get('/services/holidays/{year}/{state}', [OrchestratorLinkController::class, 'holidays']);
        Route::get('/services/all/holidays/{year}', [OrchestratorLinkController::class, 'holidaysAll']);
        Route::get('/services/weather/{city}/{state}', [OrchestratorLinkController::class, 'weather']);
    }
}
