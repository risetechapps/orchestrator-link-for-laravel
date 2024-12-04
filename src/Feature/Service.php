<?php

namespace RiseTechApps\OrchestratorLink\Feature;

use Illuminate\Http\Request;

class Service extends Connection
{
    public static function getCPF(Request $request): array
    {
        $cpf = $request->cpf;
        $date = $request->date;
        return static::request('/cpf/' . $cpf . DIRECTORY_SEPARATOR . $date);
    }

    public static function getCNPJ(Request $request): array
    {
        $cnpj = $request->cnpj;

        return static::request('/cnpj/' . $cnpj);
    }

    public static function getCEP(Request $request): array
    {
        $cep = $request->cep;
        return static::request('/cep/' . $cep);
    }

    public static function getBanks(Request $request): array
    {
        return static::request('/banks');
    }

    public static function getCountries(Request $request): array
    {
        return static::request('/countries');
    }

    public static function getStates(Request $request): array
    {
        $country = $request->country;

        return static::request('/states/' . $country);
    }

    public static function getCities(Request $request): array
    {
        $country = $request->country;
        $state = $request->state;
        return static::request('/cities/' . $country . DIRECTORY_SEPARATOR . $state);
    }

    public static function getStatesAll(Request $request): array
    {
        return static::request('/all/states');
    }

    public static function getCitiesAll(Request $request): array
    {
        return static::request('/all/cities');
    }

    public static function getHolidays(Request $request): array
    {
        $state = $request->state;
        $year = $request->year;
        return static::request('/holidays/' . $year . DIRECTORY_SEPARATOR . $state);
    }

    public static function getHolidaysAll(Request $request): array
    {
        $year = $request->year;
        return static::request('/holidays/' . $year);
    }

    public static function getWeather(Request $request): array
    {
        $state = $request->state;
        $city = $request->city;
        return static::request('/weather/' . $city . DIRECTORY_SEPARATOR . $state);
    }
}
