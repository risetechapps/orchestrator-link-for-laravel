<?php

namespace RiseTechApps\OrchestratorLink\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RiseTechApps\OrchestratorLink\Feature\Service;

class OrchestratorLinkController extends Controller
{
    public function cpf(Request $request): JsonResponse
    {
        return response()->json(Service::getCPF($request));
    }

    public function cnpj(Request $request): JsonResponse
    {
        return response()->json(Service::getCNPJ($request));
    }

    public function zipCode(Request $request): JsonResponse
    {
        return response()->json(Service::getZipCode($request));
    }

    public function banks(Request $request): JsonResponse
    {
        return response()->json(Service::getBanks($request));
    }

    public function countries(Request $request): JsonResponse
    {
        return response()->json(Service::getCountries($request));
    }

    public function countryInfo(Request $request): JsonResponse
    {
        return response()->json(Service::getCountryInfo($request));
    }

    public function states(Request $request): JsonResponse
    {
        return response()->json(Service::getStates($request));
    }

    public function stateInfo(Request $request): JsonResponse
    {
        return response()->json(Service::getStateInfo($request->country, $request->state));
    }

    public function cities(Request $request): JsonResponse
    {
        return response()->json(Service::getCities($request));
    }


    public function statesAll(Request $request): JsonResponse
    {
        return response()->json(Service::getStatesAll($request));
    }

    public function citiesAll(Request $request): JsonResponse
    {
        return response()->json(Service::getCitiesAll($request));
    }

    public function holidays(Request $request)
    {
        return response()->json(Service::getHolidays($request));
    }

    public function holidaysAll(Request $request)
    {
        return response()->json(Service::getHolidaysAll($request));
    }

    public function weather(Request $request)
    {
        return response()->json(Service::getWeather($request));
    }

    public function domain(Request $request)
    {
        return response()->json(Service::getDomain($request));
    }
}
