<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RiseTechApps\OrchestratorLink\Models\Shipment;
use RiseTechApps\OrchestratorLink\Shipping\ShippingAddress;
use RiseTechApps\OrchestratorLink\Shipping\ShippingProduct;
use RiseTechApps\OrchestratorLink\Shipping\ShippingVolume;

// Frete white label: rotas e corpo de cada chamada, builders e a cópia local.

uses(RefreshDatabase::class);

const SHIPMENT_ID = '9b1e7c2a-4f5d-4e8a-9c3b-1a2b3c4d5e6f';

function address(string $name, string $postalCode): ShippingAddress
{
    return ShippingAddress::make()
        ->name($name)->phone('(11) 98765-4321')->email('contato@example.com')
        ->document('123.456.789-09')
        ->street('Praça da Sé', '100', 'Sala 2')->district('Sé')
        ->city('São Paulo', 'sp')->postalCode($postalCode);
}

/** Envio como o orchestrator devolve (ShipmentResource). */
function shipmentPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'id'                 => SHIPMENT_ID,
        'external_reference' => 'pedido-42',
        'sandbox'            => true,
        'tracking_url'       => 'https://orchestrator.test/rastreio/tok',
        'status'             => 'pending',
        'carrier'            => 'Correios',
        'service'            => ['id' => 1, 'name' => 'PAC'],
        'tracking_code'      => null,
        'volumes'            => [['number' => 1, 'status' => 'pending', 'label_code' => null, 'tracking_code' => null]],
        'addresses'          => ['from' => ['postal_code' => '01001000', 'name' => 'Loja'], 'to' => ['postal_code' => '13010001', 'name' => 'Maria']],
        'price'              => 23.9,
        'currency'           => 'BRL',
        'paid_at'            => null,
        'posted_at'          => null,
        'delivered_at'       => null,
        'cancelled_at'       => null,
    ], $overrides);
}

dataset('shipping calls', [
    'serviços'          => [fn () => orchestrator()->getShippingServices(), 'GET', '/shipping/services', []],
    'agências'          => [fn () => orchestrator()->getShippingAgencies(3, '01001-000', 'sp', 'São Paulo'), 'GET', '/shipping/agencies?service=3&postal_code=01001000&state=SP&city=S%C3%A3o+Paulo', []],
    'remetentes'        => [fn () => orchestrator()->getShippingSenders(), 'GET', '/shipping/senders', []],
    'novo remetente'    => [fn () => orchestrator()->createShippingSender(['label' => 'Loja']), 'POST', '/shipping/senders', ['label' => 'Loja']],
    'troca remetente'   => [fn () => orchestrator()->updateShippingSender(7, ['label' => 'CD']), 'PUT', '/shipping/senders/7', ['label' => 'CD']],
    'apaga remetente'   => [fn () => orchestrator()->deleteShippingSender(7), 'DELETE', '/shipping/senders/7', []],
    'lista envios'      => [fn () => orchestrator()->listShipments(['status' => 'posted', 'label_code' => null, 'per_page' => 50]), 'GET', '/shipping/shipments?status=posted&per_page=50', []],
    'envio'             => [fn () => orchestrator()->getShipment(SHIPMENT_ID), 'GET', '/shipping/shipments/' . SHIPMENT_ID, []],
    'gera PIX'          => [fn () => orchestrator()->purchaseShipment(SHIPMENT_ID), 'POST', '/shipping/shipments/' . SHIPMENT_ID . '/purchase', []],
    'pagamento'         => [fn () => orchestrator()->getShipmentPayment(SHIPMENT_ID), 'GET', '/shipping/shipments/' . SHIPMENT_ID . '/payment', []],
    'PIX de vários'     => [fn () => orchestrator()->payShipments(['a' => SHIPMENT_ID]), 'POST', '/shipping/shipments/payments', ['shipment_ids' => [SHIPMENT_ID]]],
    'PIX de vários (situação)' => [fn () => orchestrator()->getShipmentPaymentGroup('g-1'), 'GET', '/shipping/shipments/payments/g-1', []],
    'simula pagamento'  => [fn () => orchestrator()->simulateShipmentPayment(SHIPMENT_ID), 'POST', '/shipping/shipments/' . SHIPMENT_ID . '/payment/simulate', []],
    'devolução'         => [fn () => orchestrator()->reverseShipment(SHIPMENT_ID, ['service' => 1]), 'POST', '/shipping/shipments/' . SHIPMENT_ID . '/reverse', ['service' => 1]],
    'cancela'           => [fn () => orchestrator()->cancelShipment(SHIPMENT_ID, 'Desistiu'), 'POST', '/shipping/shipments/' . SHIPMENT_ID . '/cancel', ['reason' => 'Desistiu']],
    'cancela sem motivo' => [fn () => orchestrator()->cancelShipment(SHIPMENT_ID), 'POST', '/shipping/shipments/' . SHIPMENT_ID . '/cancel', []],
    'rastreio'          => [fn () => orchestrator()->trackShipment(SHIPMENT_ID), 'GET', '/shipping/shipments/' . SHIPMENT_ID . '/tracking', []],
    'etiquetas'         => [fn () => orchestrator()->getShipmentLabels(['a', 'b']), 'GET', '/shipping/shipments/labels?ids=a%2Cb', []],
    'webhook'           => [fn () => orchestrator()->getShippingWebhook(), 'GET', '/shipping/webhook', []],
    'cadastra webhook'  => [fn () => orchestrator()->setShippingWebhook('https://loja.test/hook'), 'PUT', '/shipping/webhook', ['url' => 'https://loja.test/hook']],
    'remove webhook'    => [fn () => orchestrator()->deleteShippingWebhook(), 'DELETE', '/shipping/webhook', []],
]);

it('chama a rota de frete', function (Closure $call, string $method, string $path, array $body) {
    Http::fake(['*' => ok(['ok' => true])]);

    $call();

    Http::assertSent(fn (Request $request) => $request->method() === $method
        && $request->url() === BASE . $path
        // Num GET, data() são os parâmetros da query (já conferidos na URL).
        && ($method === 'GET' || $request->data() === $body));
})->with('shipping calls');

it('cotação pelo builder: CEPs, volumes sem produtos e serviços', function () {
    Http::fake(['*' => ok([['service' => ['id' => 1], 'price' => 23.9]])]);

    orchestrator()->buildQuote()
        ->from('01001-000')
        ->to(address('Maria', '13010-001'))
        ->addVolume(fn (ShippingVolume $v) => $v->dimensions(10, 20, 30)->weight(1.5)->addProduct(fn ($p) => $p->name('Caneca')->price(30)))
        ->services([1, 2])
        ->insurance(80)
        ->send();

    Http::assertSent(fn (Request $request) => $request->url() === BASE . '/shipping/quotes' && $request->data() === [
        'from'     => ['postal_code' => '01001000'],
        'to'       => ['postal_code' => '13010001'],
        'volumes'  => [['height' => 10.0, 'width' => 20.0, 'length' => 30.0, 'weight' => 1.5]],
        'services' => '1,2',
        'options'  => ['insurance_value' => 80.0],
    ]);
});

it('envio pelo builder: endereços, produtos por volume, NF-e e chave de idempotência', function () {
    Http::fake(['*' => ok(shipmentPayload(), 201)]);

    orchestrator()->buildShipment()
        ->reference('pedido-42')
        ->service(1)
        ->agency(55)
        ->sender(address('Loja', '01001-000'))
        ->recipient(fn (ShippingAddress $a) => $a->name('Maria')->postalCode('13010-001'))
        ->addProduct(ShippingProduct::make()->name('Caneca')->quantity(2)->price(30)->weight(0.4))
        ->addVolume(ShippingVolume::make()->dimensions(10, 20, 30)->weight(1)->addProduct(fn ($p) => $p->name('Caneca')->quantity(2)->price(30)))
        ->invoice('3525 0101 2345 6700 0108 5500 1000 0000 0110 0000 0019')
        ->notifications(recipient: false)
        ->idempotencyKey('pedido-42')
        ->create();

    Http::assertSent(function (Request $request) {
        $data = $request->data();

        return $request->url() === BASE . '/shipping/shipments'
            && $request->header('Idempotency-Key') === ['pedido-42']
            && $data['external_reference'] === 'pedido-42'
            && $data['service'] === 1
            && $data['agency'] === 55
            && $data['from']['state_abbr'] === 'SP'
            && $data['from']['document'] === '12345678909'
            && $data['to'] === ['country_id' => 'BR', 'name' => 'Maria', 'postal_code' => '13010001']
            && $data['products'] === [['quantity' => 2, 'name' => 'Caneca', 'unitary_value' => 30.0]]
            && $data['volumes'][0]['products'] === [['quantity' => 2, 'name' => 'Caneca', 'unitary_value' => 30.0]]
            && $data['options'] === ['invoice' => ['key' => '35250101234567000108550010000000011000000019']]
            && $data['notifications'] === ['sender' => true, 'recipient' => false];
    });
});

it('remetente salvo substitui o endereço do remetente, e vice-versa', function () {
    $builder = orchestrator()->buildShipment()->sender(address('Loja', '01001000'))->senderId(7);
    expect($builder->toArray())->toBe(['sender_id' => 7]);

    $builder->sender(address('Loja', '01001000'));
    expect($builder->toArray())->toHaveKey('from')->not->toHaveKey('sender_id');
});

it('save() grava a cópia local com o id do orchestrator', function () {
    Http::fake(['*' => ok(shipmentPayload(), 201)]);

    orchestrator()->buildShipment()->service(1)
        ->addVolume(fn ($v) => $v->dimensions(10, 20, 30)->weight(1))
        ->insurance(50)
        ->save()
        ->create();

    $shipment = Shipment::sole();

    expect($shipment->orchestrator_id)->toBe(SHIPMENT_ID)
        ->and($shipment->external_reference)->toBe('pedido-42')
        ->and($shipment->service_id)->toBe(1)
        ->and($shipment->service_name)->toBe('PAC')
        ->and($shipment->carrier)->toBe('Correios')
        ->and($shipment->sandbox)->toBeTrue()
        ->and($shipment->price)->toBe('23.90')
        ->and($shipment->to_postal_code)->toBe('13010001')
        ->and($shipment->to_data['name'])->toBe('Maria')
        ->and($shipment->volumes)->toBe([['height' => 10, 'width' => 20, 'length' => 30, 'weight' => 1]])
        ->and($shipment->options)->toBe(['insurance_value' => 50])
        ->and($shipment->isPending())->toBeTrue();
});

it('sem save() ou com falha, nada é gravado', function () {
    Http::fake(['*' => Http::sequence()
        ->push(['success' => true, 'data' => shipmentPayload()], 201)
        ->push(['success' => false, 'message' => 'Dados inválidos.'], 422)]);

    orchestrator()->buildShipment()->service(1)->create();
    orchestrator()->buildShipment()->service(1)->save()->create();

    expect(Shipment::count())->toBe(0);
});

it('o aviso do webhook atualiza o envio sem apagar o que ele não traz', function () {
    Shipment::syncFromOrchestrator(shipmentPayload(), ['products' => [['name' => 'Caneca']]]);

    Shipment::syncFromOrchestrator([
        'id' => SHIPMENT_ID, 'status' => 'cancelled', 'cancelled_at' => '2026-10-13T10:00:00-03:00',
        'tracking_code' => 'AA123456789BR',
    ]);

    $shipment = Shipment::sole();

    expect($shipment->isCancelled())->toBeTrue()
        ->and($shipment->cancelled_at->toIso8601String())->toBe('2026-10-13T13:00:00+00:00')
        ->and($shipment->tracking_code)->toBe('AA123456789BR')
        ->and($shipment->to_postal_code)->toBe('13010001')
        ->and($shipment->products)->toBe([['name' => 'Caneca']]);
});

it('produto e volume recebem as dimensões na mesma ordem: altura, largura, comprimento', function () {
    $keys = ['height' => 10.0, 'width' => 20.0, 'length' => 30.0];

    expect(array_intersect_key(ShippingProduct::make()->dimensions(10, 20, 30)->toArray(), $keys))->toBe($keys)
        ->and(array_intersect_key(ShippingVolume::make()->dimensions(10, 20, 30)->toArray(), $keys))->toBe($keys);
});

it('agrupa em `from` o endereço do remetente mandado solto', function () {
    Http::fake(['*' => ok(['id' => 1])]);
    $from = address('Loja', '01001-000')->toArray();

    orchestrator()->createShippingSender($from + ['label' => 'Loja Centro', 'is_default' => true]);
    orchestrator()->updateShippingSender(7, ['from' => $from, 'label' => 'CD']);

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->data() === ['from' => $from, 'label' => 'Loja Centro', 'is_default' => true]);
    Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
        && $request->data() === ['from' => $from, 'label' => 'CD']);
});

it('diz se o envio está comprando ou já comprou a etiqueta', function () {
    expect((new Shipment(['status' => 'processing']))->isProcessing())->toBeTrue()
        ->and((new Shipment(['status' => 'purchased']))->isPurchased())->toBeTrue()
        ->and((new Shipment(['status' => 'pending']))->isProcessing())->toBeFalse();
});
