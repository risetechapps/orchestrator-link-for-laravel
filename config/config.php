<?php

/*
 * You can place your custom package configuration in here.
 */
return [

    'token' => env('ORCHESTRATOR_TOKEN', ''),
    'host' => env('ORCHESTRATOR_HOST', 'localhost'),

    'drivers' => [
        'cpf' => [
            'hub_desenvolvedor_cpf' => env('HUB_DESENVOLVEDOR_CPF', ''),
        ],
        'holidays' => [
            'holidays' => env('HOLIDAYS_TOKEN', '')
        ],
        'weather' => [
            'hg_brasil' => env('HG_BRASIL_Weather', '')
        ]
    ]
];
