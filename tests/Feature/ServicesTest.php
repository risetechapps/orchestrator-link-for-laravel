<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

// Os serviços novos do orchestrator 4.0: cada método chama a rota certa, com
// o método e a entrada no formato que o servidor valida.

dataset('get endpoints', [
    'sócios do CNPJ'        => [fn () => orchestrator()->getCnpjPartners('33.000.167/0001-01'), '/cnpj/33000167000101/partners'],
    'CNPJ alfanumérico'     => [fn () => orchestrator()->getCNPJ('12.abc.345/01de-35'), '/cnpj/12ABC34501DE35'],
    'sócios (alfanumérico)' => [fn () => orchestrator()->getCnpjPartners('12.ABC.345/01DE-35'), '/cnpj/12ABC34501DE35/partners'],
    'Simples (alfanumérico)' => [fn () => orchestrator()->getCnpjSimples('12ABC34501DE35'), '/cnpj/12ABC34501DE35/simples'],
    'Simples/MEI'           => [fn () => orchestrator()->getCnpjSimples('33.000.167/0001-01'), '/cnpj/33000167000101/simples'],
    'CNAE no formato cartão' => [fn () => orchestrator()->getCnae('1921-7/00'), '/cnae/1921700'],
    'busca CNAE'            => [fn () => orchestrator()->searchCnae('padaria pão'), '/cnae?search=padaria+p%C3%A3o'],
    'municípios da UF'      => [fn () => orchestrator()->getIbgeCities('sp'), '/ibge/cities/SP'],
    'município pelo nome'   => [fn () => orchestrator()->getIbgeCities('SP', "Santa Bárbara d'Oeste"), '/ibge/cities/SP?name=Santa+B%C3%A1rbara+d%27Oeste'],
    'município pelo código' => [fn () => orchestrator()->getIbgeCity('3509502'), '/ibge/city/3509502'],
    'IBGE do CEP'           => [fn () => orchestrator()->getIbgeByZipCode('13010-001'), '/ibge/zip_code/13010001'],
    'coordenadas do CEP'    => [fn () => orchestrator()->getGeoByZipCode('01001-000'), '/geo/zip_code/01001000'],
    'distância'             => [fn () => orchestrator()->getDistance('13010-001', '01001-000'), '/geo/distance/13010001/01001000'],
    'indicadores'           => [fn () => orchestrator()->getIndicators(), '/indicators'],
    'um indicador'          => [fn () => orchestrator()->getIndicator('SELIC'), '/indicators/selic'],
    'série do indicador'    => [fn () => orchestrator()->getIndicator('ipca', '2025-01-01', '2025-12-31'), '/indicators/ipca?from=2025-01-01&to=2025-12-31'],
    'tributos do NCM'       => [fn () => orchestrator()->getNcmTaxes('8517.13.00', 'sp'), '/ncm/85171300/taxes?uf=SP'],
    'e-mail'                => [fn () => orchestrator()->validateEmail(' joao+loja@gmail.com '), '/email/joao%2Bloja%40gmail.com'],
    'telefone'              => [fn () => orchestrator()->validatePhone('(21) 98765-4321'), '/phone/21987654321'],
    'telefone com DDI'      => [fn () => orchestrator()->validatePhone('+1 (202) 555-0100'), '/phone/%2B12025550100'],
    'catálogo da B3'        => [fn () => orchestrator()->getStocks(['search' => 'petr', 'type' => null, 'page' => 2]), '/stocks?search=petr&page=2'],
]);

it('chama a rota do orchestrator', function (Closure $call, string $path) {
    Http::fake(['*' => ok(['ok' => true])]);

    $result = $call();

    expect($result)->toBe(['success' => true, 'data' => ['ok' => true]]);
    Http::assertSent(fn (Request $request) => $request->method() === 'GET'
        && $request->url() === BASE . $path
        && $request->header('X-API-KEY') === ['test-key']);
})->with('get endpoints');

it('lê o PIX copia e cola por POST, sem espaços nas pontas', function () {
    Http::fake(['*' => ok(['valid' => true, 'key' => 'fulano@example.com'])]);

    $result = orchestrator()->decodePix("  000201010211...6304ABCD \n");

    expect($result['data']['valid'])->toBeTrue();
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === BASE . '/pix/decode'
        && $request->data() === ['code' => '000201010211...6304ABCD']);
});

it('guarda em cache as consultas de referência e não chama de novo', function () {
    Http::fake(['*' => ok(['code' => '1921700'])]);

    orchestrator()->getCnae('1921700');
    orchestrator()->getCnae('1921-7/00');

    Http::assertSentCount(1);
});

it('não guarda falha em cache', function () {
    Http::fakeSequence()
        ->push(['success' => false, 'code' => 410, 'message' => 'Fonte fora do ar.'], 410)
        ->push(['success' => true, 'data' => ['selic' => 15]]);

    expect(orchestrator()->getIndicators()['success'])->toBeFalse()
        ->and(orchestrator()->getIndicators()['data'])->toBe(['selic' => 15]);
});
