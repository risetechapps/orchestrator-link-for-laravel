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

    public function states(Request $request): JsonResponse
    {
        return response()->json(Service::getStates($request));
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


        $data = Service::getWeather($city, $state);

        return response()->json([
            'success' => !is_null($data),
            'data' => $data
        ]);
    }
}
