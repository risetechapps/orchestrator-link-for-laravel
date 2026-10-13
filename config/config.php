<?php

return [

    // Chave de API da conta no orchestrator (enviada no header X-API-KEY).
    // A chave de teste (sandbox) também serve: no frete, cria envios de teste
    // sem cobrança real.
    'token' => env('ORCHESTRATOR_TOKEN', ''),

    // Endereço base dos serviços. Troque para apontar para outro ambiente
    // (homologação, um túnel local etc.).
    'url' => env('ORCHESTRATOR_URL', 'https://orchestrator.app.br/api/v1/services'),
];
