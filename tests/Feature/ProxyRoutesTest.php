<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RiseTechApps\OrchestratorLink\OrchestratorLink;

// Rotas proxy (OrchestratorLink::routes()): repassam ao orchestrator e
// devolvem a resposta, com o status HTTP dela. A etiqueta volta como PDF.

beforeEach(function () {
    OrchestratorLink::routes();
});

it('repassa as consultas novas', function (string $uri, string $path) {
    Http::fake(['*' => ok(['ok' => true])]);

    $this->getJson($uri)->assertOk()->assertExactJson(['success' => true, 'data' => ['ok' => true]]);

    Http::assertSent(fn (Request $request) => $request->url() === BASE . $path);
})->with([
    'CNAE com barra'   => ['/services/cnae/1921-7/00', '/cnae/1921700'],
    'e-mail com @'     => ['/services/email/joao@gmail.com', '/email/joao%40gmail.com'],
    'tributos do NCM'  => ['/services/ncm/85171300/taxes?uf=sp', '/ncm/85171300/taxes?uf=SP'],
    'busca de NCM'     => ['/services/ncm/search?search=celular', '/ncm?search=celular'],
    'município'        => ['/services/ibge/cities/SP?name=Campinas', '/ibge/cities/SP?name=Campinas'],
    'série'            => ['/services/indicators/selic?from=2025-01-01', '/indicators/selic?from=2025-01-01'],
    'sócios'           => ['/services/cnpj/33000167000101/partners', '/cnpj/33000167000101/partners'],
    'envios'           => ['/services/shipping/shipments?status=posted&ignored=1', '/shipping/shipments?status=posted'],
]);

it('repassa a chave de idempotência na criação do envio', function () {
    Http::fake(['*' => ok(['id' => SHIPMENT_ID_PROXY], 201)]);

    $this->postJson('/services/shipping/shipments', ['service' => 1], ['Idempotency-Key' => 'k-1'])->assertOk();

    Http::assertSent(fn (Request $request) => $request->header('Idempotency-Key') === ['k-1'] && $request->data() === ['service' => 1]);
});

it('a etiqueta volta como PDF', function () {
    Http::fake(['*' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="etiqueta-1.pdf"'])]);

    $response = $this->get('/services/shipping/shipments/' . SHIPMENT_ID_PROXY . '/label');

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Content-Disposition', 'inline; filename="etiqueta-1.pdf"');
    expect($response->getContent())->toBe('%PDF-1.4');
});

it('etiqueta indisponível volta como o JSON de erro', function () {
    Http::fake(['*' => Http::response(['success' => false, 'message' => 'Envio não encontrado.'], 404)]);

    $this->get('/services/shipping/shipments/x/label')->assertNotFound()->assertJson(['success' => false, 'status' => 404, 'message' => 'Envio não encontrado.']);
});

it('repassa o status HTTP do erro do orchestrator', function () {
    Http::fake(['*' => Http::response(['success' => false, 'message' => 'Dados inválidos.', 'errors' => ['from' => ['O campo from é obrigatório.']]], 422)]);

    $this->postJson('/services/shipping/senders', ['label' => 'Loja'])
        ->assertStatus(422)
        ->assertJson(['success' => false, 'status' => 422, 'errors' => ['from' => ['O campo from é obrigatório.']]]);
});

it('responde 502 quando o orchestrator não responde', function () {
    Http::fake(['*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout')]);

    $this->getJson('/services/banks')->assertStatus(502)->assertJson(['success' => false, 'status' => null]);
});

const SHIPMENT_ID_PROXY = '0c3f8a1e-2b4d-4c6e-8f0a-1b2c3d4e5f60';
