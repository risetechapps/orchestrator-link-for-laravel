# Orchestrator Link for Laravel

Package Laravel para comunicação com a API Orchestrator da RiseTech. Fornece acesso a dados de CPF, CNPJ, CEP, bancos, países, estados, cidades, clima, feriados, calendário, WHOIS, FIPE, câmbio, ações B3, NCM e envio de encomendas via Melhor Envio.

---

## Instalação

```bash
composer require risetechapps/orchestrator-link-for-laravel
```

---

## Configuração

### Token de acesso

Adicione ao seu `.env`:

```env
ORCHESTRATOR_TOKEN=seu_token_aqui
```

### Publicar config (opcional)

```bash
php artisan vendor:publish --tag=config
```

Isso cria `config/orchestrator-link.php`:

```php
return [
    'token' => env('ORCHESTRATOR_TOKEN', ''),
];
```

### Migration (Shipping)

A migration é carregada automaticamente. Para rodar:

```bash
php artisan migrate
```

Para publicar e personalizar:

```bash
php artisan vendor:publish --tag=orchestrator-link-migrations
```

---

## Rotas proxy (opcional)

Para expor os endpoints como proxy na sua aplicação:

```php
// routes/web.php ou routes/api.php
\RiseTechApps\OrchestratorLink\OrchestratorLink::routes();
```

Isso registra as rotas abaixo no seu app:

| Método | Rota | Serviço |
|---|---|---|
| GET | `/services/cpf/{cpf}/{date?}` | CPF |
| GET | `/services/cnpj/{cnpj}` | CNPJ |
| GET | `/services/zip_code/{zip_code}` | CEP |
| GET | `/services/banks` | Bancos |
| GET | `/services/countries` | Países |
| GET | `/services/country/{country}` | Info de país |
| GET | `/services/states/{country}` | Estados |
| GET | `/services/states/{country}/{state}` | Info de estado |
| GET | `/services/cities/{country}/{state}` | Cidades |
| GET | `/services/holidays/{year}/{state}` | Feriados |
| GET | `/services/weather/{city}/{country}/{state?}` | Clima |
| GET | `/services/domain/{domain}` | WHOIS |
| POST | `/services/calendar` | Calendário |
| GET | `/services/fipe/{type}/brands` | Marcas FIPE |
| GET | `/services/fipe/{type}/{brand}/models` | Modelos FIPE |
| GET | `/services/fipe/{type}/{brand}/{model}/years` | Anos FIPE |
| GET | `/services/fipe/{type}/{brand}/{model}/{year}` | Preço FIPE |
| GET | `/services/exchange/{from}/{to}` | Câmbio |
| GET | `/services/stocks/{symbol}` | Ações B3 |
| GET | `/services/ncm/search` | NCM por descrição |
| GET | `/services/ncm/{code}` | NCM por código |
| GET | `/services/shipping/carriers` | Transportadoras |
| POST | `/services/shipping/calculate` | Calcular frete |
| POST | `/services/shipping/cart` | Adicionar ao carrinho |
| DELETE | `/services/shipping/cart/{id}` | Remover do carrinho |
| POST | `/services/shipping/checkout` | Checkout |
| POST | `/services/shipping/labels/generate` | Gerar etiquetas |
| POST | `/services/shipping/labels/print` | Imprimir etiquetas |
| POST | `/services/shipping/labels/cancel` | Cancelar etiqueta |
| GET | `/services/shipping/orders` | Listar pedidos |
| GET | `/services/shipping/orders/search` | Pesquisar pedido |
| GET | `/services/shipping/orders/{id}` | Detalhes do pedido |
| POST | `/services/shipping/track` | Rastrear envio |

---

## Macros de resposta (opcional)

O package registra macros no `ResponseFactory` para padronizar respostas JSON:

```php
// 200 OK — sucesso com dados
return response()->jsonSuccess($data);
// {"success": true, "data": [...]}

// 412 Precondition Failed — erro com mensagem
return response()->jsonError('Mensagem de erro');
// {"success": false, "message": "Mensagem de erro"}

// 410 Gone — recurso removido
return response()->jsonGone('Recurso não disponível');
// {"success": false, "message": "Recurso não disponível"}

// 422 Unprocessable Entity — falha de validação
return response()->jsonNotValidated('Dados inválidos');
// {"success": false, "message": "Dados inválidos"}
```

---

## Formato de retorno

Todos os métodos do helper `orchestrator()` retornam:

```php
[
    'success' => true|false,
    'data'    => [...],
]
```

---

## CPF

```php
// Com data de nascimento (recomendado para validação)
$result = orchestrator()->getCPF('98765432100', '1990-05-15');

// Sem data
$result = orchestrator()->getCPF('98765432100');

// CPF com pontuação também é aceito (remove automaticamente)
$result = orchestrator()->getCPF('987.654.321-00');

// Resposta
$result['data']['name'];  // Nome
$result['data']['cpf'];   // CPF formatado
```

---

## CNPJ

```php
$result = orchestrator()->getCNPJ('14779686000195');

// CNPJ com pontuação também é aceito
$result = orchestrator()->getCNPJ('14.779.686/0001-95');

// Resposta
$result['data']['name'];          // Razão social
$result['data']['trade_name'];    // Nome fantasia
$result['data']['status'];        // Ativa / Inapta
$result['data']['address'];       // Endereço completo
$result['data']['partners'];      // Sócios
$result['data']['phones'];        // Telefones
```

---

## CEP

```php
$result = orchestrator()->getZipCode('01310100');

// CEP com hífen também é aceito
$result = orchestrator()->getZipCode('01310-100');

// Resposta
$result['data']['zip_code'];  // CEP formatado
$result['data']['address'];   // Logradouro
$result['data']['district'];  // Bairro
$result['data']['city'];      // Cidade
$result['data']['state'];     // Estado (sigla)
$result['data']['country'];   // País
```

---

## Bancos

```php
$result = orchestrator()->getBanks();

foreach ($result['data'] as $bank) {
    $bank['code']; // Código (ex: 001)
    $bank['name']; // Nome (ex: Banco do Brasil S.A.)
}
```

---

## Países / Estados / Cidades

```php
// Lista todos os países
$result = orchestrator()->getCountries();

// Info de um país (aceita nome, ISO2 ou ISO3)
$result = orchestrator()->getCountryInfo('BR');
$result = orchestrator()->getCountryInfo('BRA');
$result = orchestrator()->getCountryInfo('Brazil');

// Estados de um país
$result = orchestrator()->getStates('BR');

// Info de um estado específico
$result = orchestrator()->getStateInfo('BR', 'SP');

// Cidades de um estado
$result = orchestrator()->getCities('BR', 'SP');
```

---

## Feriados

```php
// Feriados de um estado em um ano
$result = orchestrator()->getHolidays('SP', '2026');

foreach ($result['data'] as $holiday) {
    $holiday['date'];  // 2026-01-01
    $holiday['name'];  // Confraternização Universal
    $holiday['type'];  // national / state
}
```

---

## Clima

```php
// Por cidade e país
$result = orchestrator()->getWeather('London', 'GB');

// Por cidade, país e estado (recomendado para cidades brasileiras)
$result = orchestrator()->getWeather('São Paulo', 'BR', 'SP');

foreach ($result['data'] as $forecast) {
    $forecast['date'];              // 2026-05-31
    $forecast['max'];               // Temperatura máxima
    $forecast['min'];               // Temperatura mínima
    $forecast['condition'];         // Descrição (ex: Parcialmente nublado)
    $forecast['rain_probability'];  // % de chuva
}
```

---

## Domínio (WHOIS)

```php
// Com ou sem protocolo — ambos são aceitos
$result = orchestrator()->getDomain('risetech.com.br');
$result = orchestrator()->getDomain('https://risetech.com.br');

$result['data']['registered'];    // true/false
$result['data']['created_at'];    // Data de criação
$result['data']['expires_at'];    // Data de expiração
$result['data']['nameservers'];   // Nameservers
$result['data']['owner'];         // Proprietário
```

---

## Calendário

Combina feriados + previsão do tempo por período.

```php
$result = orchestrator()->getCalendar(
    country: 'BR',
    state:   'SP',
    city:    'São Paulo',
    start:   '2026-06-01',
    end:     '2026-06-07'
);

foreach ($result['data'] as $day) {
    $day['date'];     // 2026-06-01
    $day['weather'];  // Dados do clima
    $day['holidays']; // Feriados do dia (ou null)
}
```

---

## FIPE (Tabela de Veículos)

Tipos disponíveis: `cars`, `motorcycles`, `trucks`

```php
// 1. Listar marcas
$brands = orchestrator()->getFipeBrands('cars');
// [['code' => '059', 'name' => 'VOLKSWAGEN'], ...]

$brands = orchestrator()->getFipeBrands('motorcycles');
$brands = orchestrator()->getFipeBrands('trucks');

// 2. Listar modelos de uma marca
$models = orchestrator()->getFipeModels('cars', '059');
// [['code' => '5585', 'name' => 'AMAROK CD2.0...'], ...]

// 3. Anos disponíveis para um modelo (OBRIGATÓRIO antes do preço)
$years = orchestrator()->getFipeYears('cars', '059', '5585');
// [['code' => '2022-3', 'name' => '2022 Diesel'], ...]

// 4. Preço FIPE (use o code retornado em getFipeYears)
$price = orchestrator()->getFipePrice('cars', '059', '5585', '2022-3');

$price['data']['fipe_code'];        // Código FIPE
$price['data']['brand'];            // Marca
$price['data']['model'];            // Modelo
$price['data']['year'];             // Ano
$price['data']['fuel'];             // Combustível
$price['data']['price'];            // Preço (ex: R$ 180.000,00)
$price['data']['reference_month'];  // Mês de referência
```

> **Sufixo do ano:** `-1` Gasolina · `-2` Álcool · `-3` Diesel

---

## Câmbio

```php
// Cotação de qualquer par de moedas (maiúsculas ou minúsculas — normalizado automaticamente)
$result = orchestrator()->getExchange('USD', 'BRL');
$result = orchestrator()->getExchange('EUR', 'BRL');
$result = orchestrator()->getExchange('BTC', 'BRL');
$result = orchestrator()->getExchange('GBP', 'USD');

$result['data']['from'];        // USD
$result['data']['to'];          // BRL
$result['data']['bid'];         // Compra (ex: 5.45)
$result['data']['ask'];         // Venda (ex: 5.46)
$result['data']['high'];        // Máxima do dia
$result['data']['low'];         // Mínima do dia
$result['data']['variation'];   // Variação
$result['data']['pct_change'];  // Variação em %
$result['data']['updated_at'];  // Última atualização
```

---

## Ações B3

```php
// Ações individuais (símbolo normalizado automaticamente para maiúsculas)
$result = orchestrator()->getStock('PETR4');
$result = orchestrator()->getStock('VALE3');
$result = orchestrator()->getStock('petr4'); // também funciona

// Índices
$result = orchestrator()->getStock('IBOV');

$result['data']['symbol'];      // PETR4
$result['data']['name'];        // Petróleo Brasileiro S.A.
$result['data']['price'];       // Preço atual
$result['data']['change'];      // Variação em R$
$result['data']['change_pct'];  // Variação em %
$result['data']['open'];        // Abertura
$result['data']['day_high'];    // Máxima do dia
$result['data']['day_low'];     // Mínima do dia
$result['data']['volume'];      // Volume negociado
$result['data']['market_cap'];  // Capitalização de mercado
```

---

## NCM (Nomenclatura Comum do Mercosul)

```php
// Busca por código (8 dígitos — pontuação removida automaticamente)
$result = orchestrator()->getNcm('84713019');
$result = orchestrator()->getNcm('8471.30.19'); // também funciona

// Busca por descrição (termo de texto livre)
$result = orchestrator()->searchNcm('notebook');
$result = orchestrator()->searchNcm('computador portátil');

$result['data']['code'];         // 84713019
$result['data']['description'];  // Descrição completa
$result['data']['start_date'];   // Vigência início
$result['data']['end_date'];     // Vigência fim (null = vigente)
$result['data']['act_type'];     // Tipo do ato
$result['data']['act_number'];   // Número do ato
$result['data']['act_year'];     // Ano do ato
```

---

## Shipping — Melhor Envio

### Transportadoras disponíveis

```php
$result = orchestrator()->getCarriers();

foreach ($result['data'] as $carrier) {
    $carrier['id'];           // ID do serviço (use em buildOrder()->service())
    $carrier['name'];         // Nome (ex: Correios PAC)
    $carrier['company'];      // Transportadora
}
```

---

### Calcular Frete (Builder)

```php
use RiseTechApps\OrchestratorLink\Shipping\ShippingProduct;
use RiseTechApps\OrchestratorLink\Shipping\ShippingVolume;
use RiseTechApps\OrchestratorLink\Shipping\ShippingAddress;

// Simples — só CEP de origem/destino
$result = orchestrator()->buildCalculate()
    ->from('01310100')
    ->to('20040020')
    ->addProduct(
        ShippingProduct::make()
            ->id('prod-1')           // ID interno (referência sua)
            ->dimensions(20, 15, 30) // largura, altura, comprimento (cm)
            ->weight(1.5)            // kg
            ->quantity(1)
            ->insureFor(150.00)      // valor declarado para seguro (R$)
    )
    ->send();

// Com endereço completo no origem/destino
$result = orchestrator()->buildCalculate()
    ->from(
        ShippingAddress::make()
            ->postalCode('01310100')
            ->city('São Paulo', 'SP')
    )
    ->to(
        ShippingAddress::make()
            ->postalCode('20040020')
            ->city('Rio de Janeiro', 'RJ')
    )
    ->addProduct(
        ShippingProduct::make()
            ->id('p1')
            ->dimensions(20, 15, 30)
            ->weight(1.5)
            ->quantity(2)
            ->insureFor(300.00)
    )
    ->send();

// Filtrar transportadoras específicas (ex: só Correios PAC=1 e SEDEX=2)
$result = orchestrator()->buildCalculate()
    ->from('01310100')
    ->to('20040020')
    ->addProduct(
        ShippingProduct::make()->id('p1')->dimensions(20, 15, 30)->weight(1.5)->quantity(1)->insureFor(100)
    )
    ->services('1,2')
    ->send();

// Múltiplos produtos
$result = orchestrator()->buildCalculate()
    ->from('01310100')
    ->to('20040020')
    ->addProduct(ShippingProduct::make()->id('p1')->dimensions(20, 15, 30)->weight(1.5)->quantity(1)->insureFor(100))
    ->addProduct(ShippingProduct::make()->id('p2')->dimensions(10, 10, 15)->weight(0.5)->quantity(3)->insureFor(50))
    ->send();

// Com volumes (alternativo a products para cálculo de frete)
$result = orchestrator()->buildCalculate()
    ->from('01310100')
    ->to('20040020')
    ->addVolume(
        ShippingVolume::make()
            ->dimensions(15, 20, 30) // altura, largura, comprimento (cm)
            ->weight(1.5)
    )
    ->send();

// Com opções extras
$result = orchestrator()->buildCalculate()
    ->from('01310100')
    ->to('20040020')
    ->addProduct(ShippingProduct::make()->id('p1')->dimensions(20, 15, 30)->weight(1.5)->quantity(1)->insureFor(100))
    ->option('receipt', false)
    ->option('own_hand', false)
    ->send();

// Múltiplas opções de uma vez
$result = orchestrator()->buildCalculate()
    ->from('01310100')
    ->to('20040020')
    ->addProduct(ShippingProduct::make()->id('p1')->dimensions(20, 15, 30)->weight(1.5)->quantity(1)->insureFor(100))
    ->options(['receipt' => false, 'own_hand' => false])
    ->send();

// Resultado
$serviceId = $result['data'][0]['id'];
$price     = $result['data'][0]['price'];
$deadline  = $result['data'][0]['custom_delivery_time']; // dias úteis
$carrier   = $result['data'][0]['company']['name'];
```

---

### Adicionar ao Carrinho (Builder)

```php
use RiseTechApps\OrchestratorLink\Shipping\ShippingAddress;
use RiseTechApps\OrchestratorLink\Shipping\ShippingProduct;
use RiseTechApps\OrchestratorLink\Shipping\ShippingVolume;

$cart = orchestrator()->buildOrder()
    ->service(1)   // ID do serviço (vem do calculateShipping / getCarriers)
    ->sender(
        ShippingAddress::make()
            ->name('Loja Example')
            ->phone('11999990000')
            ->email('loja@example.com.br')
            ->document('05596752088')              // CPF (sem pontuação — normalizado automaticamente)
            ->companyDocument('14779686000195')    // CNPJ da empresa (opcional)
            ->street('Avenida Paulista', '1000', 'Sala 10')
            ->district('Bela Vista')
            ->city('São Paulo', 'SP')
            ->postalCode('01310100')
            ->country('BR')                        // padrão BR, pode omitir
    )
    ->recipient(
        ShippingAddress::make()
            ->name('João Silva')
            ->phone('21988880000')
            ->email('joao@email.com')
            ->document('05596752088')
            ->street('Rua das Flores', '42', 'Apto 3')
            ->district('Centro')
            ->city('Rio de Janeiro', 'RJ')
            ->postalCode('20040020')
    )
    ->addProduct(
        ShippingProduct::make()
            ->name('Camiseta')
            ->quantity(2)
            ->price(75.00)   // valor unitário (R$)
    )
    ->addVolume(
        ShippingVolume::make()
            ->dimensions(15, 30, 40) // altura, largura, comprimento (cm)
            ->weight(1.5)
    )
    ->option('insurance_value', 150.00)
    ->option('receipt', false)
    ->option('own_hand', false)
    ->save()       // persiste em orchestrator_shipments (opcional)
    ->addToCart();

$orderId = $cart['data']['id']; // UUID do pedido — guarde para os próximos passos

// Sem persistência local
$cart = orchestrator()->buildOrder()
    ->service(1)
    ->sender(ShippingAddress::make()->name('Loja')->postalCode('01310100')->city('São Paulo', 'SP')->street('Av. Paulista', '1000')->district('Bela Vista')->phone('11999990000')->email('loja@example.com')->document('05596752088'))
    ->recipient(ShippingAddress::make()->name('Cliente')->postalCode('20040020')->city('Rio de Janeiro', 'RJ')->street('Rua das Flores', '42')->district('Centro')->phone('21988880000')->email('cliente@example.com')->document('05596752088'))
    ->addProduct(ShippingProduct::make()->name('Produto')->quantity(1)->price(100.00))
    ->addVolume(ShippingVolume::make()->dimensions(10, 15, 20)->weight(1.0))
    ->addToCart(); // sem ->save()

// Múltiplos produtos e volumes
$cart = orchestrator()->buildOrder()
    ->service(1)
    ->sender(/* ... */)
    ->recipient(/* ... */)
    ->addProduct(ShippingProduct::make()->name('Camiseta P')->quantity(1)->price(75.00))
    ->addProduct(ShippingProduct::make()->name('Camiseta M')->quantity(2)->price(75.00))
    ->addVolume(ShippingVolume::make()->dimensions(15, 30, 40)->weight(1.5))
    ->addVolume(ShippingVolume::make()->dimensions(10, 20, 30)->weight(0.8))
    ->options(['insurance_value' => 225.00, 'receipt' => false, 'own_hand' => false])
    ->save()
    ->addToCart();
```

---

### Remover do Carrinho

```php
$result = orchestrator()->removeFromCart('uuid-do-pedido');
```

---

### Checkout (Pagar com saldo da carteira ME)

```php
// Pedido único
$result = orchestrator()->checkout('uuid-do-pedido');

// Múltiplos pedidos de uma vez
$result = orchestrator()->checkout(['uuid-1', 'uuid-2', 'uuid-3']);
```

> Requer saldo na carteira do Melhor Envio. No sandbox há R$10.000 de saldo virtual.

---

### Gerar Etiqueta

```php
// Pedido único
$result = orchestrator()->generateLabels('uuid-do-pedido');

// Múltiplos
$result = orchestrator()->generateLabels(['uuid-1', 'uuid-2']);
```

---

### Imprimir / Obter URL do PDF

```php
// Link público (acessível sem login — padrão)
$result = orchestrator()->printLabels('uuid-do-pedido');
$result = orchestrator()->printLabels('uuid-do-pedido', 'public');
$pdfUrl = $result['data']['url'];

// Link privado (só com autenticação ME)
$result = orchestrator()->printLabels('uuid-do-pedido', 'private');

// Múltiplos pedidos
$result = orchestrator()->printLabels(['uuid-1', 'uuid-2'], 'public');
```

---

### Cancelar Etiqueta

```php
// Com descrição personalizada
$result = orchestrator()->cancelLabel(
    id:          'uuid-do-pedido',
    description: 'Cliente cancelou o pedido'
);

// Descrição padrão: "Cancelado via integração"
$result = orchestrator()->cancelLabel('uuid-do-pedido');
```

> Etiquetas já postadas na transportadora não podem ser canceladas.

---

### Listar Pedidos

```php
// Todos os pedidos (página 1)
$result = orchestrator()->listOrders();

// Filtrar por status
$result = orchestrator()->listOrders('pending');       // Aguardando pagamento
$result = orchestrator()->listOrders('released');      // Pagos / liberados
$result = orchestrator()->listOrders('posted');        // Postados
$result = orchestrator()->listOrders('delivered');     // Entregues
$result = orchestrator()->listOrders('canceled');      // Cancelados
$result = orchestrator()->listOrders('Not Delivered'); // Não entregues

// Com paginação
$result = orchestrator()->listOrders('posted', page: 2);
$result = orchestrator()->listOrders(page: 3); // sem filtro, página 3
```

**Status disponíveis:** `pending` · `released` · `posted` · `delivered` · `canceled` · `Not Delivered`

---

### Detalhes de um Pedido

```php
$result = orchestrator()->getOrder('uuid-do-pedido');

$result['data']['status'];    // Status atual
$result['data']['tracking'];  // Código de rastreio
$result['data']['price'];     // Valor pago
```

---

### Pesquisar Pedido

```php
// Por código de rastreio
$result = orchestrator()->searchOrder('ME23002OWZ7BR');

// Por protocolo
$result = orchestrator()->searchOrder('ORD-20220397305');

// Por CPF/CNPJ do destinatário
$result = orchestrator()->searchOrder('05596752088');

// Por UUID do pedido
$result = orchestrator()->searchOrder('uuid-do-pedido');
```

---

### Rastrear Envio

```php
// Pedido único
$result = orchestrator()->trackShipment('uuid-do-pedido');

// Múltiplos pedidos
$result = orchestrator()->trackShipment(['uuid-1', 'uuid-2']);

$result['data']['uuid-do-pedido']['status'];        // posted / delivered
$result['data']['uuid-do-pedido']['tracking'];      // ME23002OWZ7BR
$result['data']['uuid-do-pedido']['posted_at'];     // Data de postagem
$result['data']['uuid-do-pedido']['delivered_at'];  // Data de entrega
```

---

### Model Shipment

Ao usar `->save()` no builder, o envio é persistido na tabela `orchestrator_shipments`.

```php
use RiseTechApps\OrchestratorLink\Models\Shipment;

// Listar todos os envios
Shipment::all();

// Scopes por status
Shipment::pending()->get();
Shipment::posted()->get();
Shipment::delivered()->get();

// Buscar por campo
Shipment::where('me_order_id', 'uuid')->first();
Shipment::where('tracking_code', 'ME23002OWZ7BR')->first();
Shipment::where('to_postal_code', '20040020')->get();

// Verificar status via método
$shipment = Shipment::find(1);
$shipment->isPending();    // bool
$shipment->isPosted();     // bool
$shipment->isDelivered();  // bool
$shipment->isCanceled();   // bool

// Atualizar após rastreamento
$shipment->update([
    'status'        => 'delivered',
    'tracking_code' => 'ME23002OWZ7BR',
    'delivered_at'  => now(),
]);

// Atualizar URL da etiqueta após gerar
$shipment->update(['label_url' => $pdfUrl]);

// Colunas disponíveis
// me_order_id, me_protocol, service_id, service_name, status, tracking_code,
// from_postal_code, from_data (json), to_postal_code, to_data (json),
// products (json), volumes (json), options (json), price, label_url,
// posted_at, delivered_at, canceled_at, created_at, updated_at
```

---

### Fluxo Completo de Envio

```php
use RiseTechApps\OrchestratorLink\Shipping\ShippingAddress;
use RiseTechApps\OrchestratorLink\Shipping\ShippingProduct;
use RiseTechApps\OrchestratorLink\Shipping\ShippingVolume;

// 1. Calcular fretes disponíveis
$calc = orchestrator()->buildCalculate()
    ->from('01310100')
    ->to('20040020')
    ->addProduct(
        ShippingProduct::make()->id('p1')->dimensions(20, 15, 30)->weight(1.5)->quantity(1)->insureFor(75)
    )
    ->send();

$serviceId = $calc['data'][0]['id'];  // serviço mais barato

// 2. Adicionar ao carrinho
$cart = orchestrator()->buildOrder()
    ->service($serviceId)
    ->sender(
        ShippingAddress::make()
            ->name('Loja Example')->phone('11999990000')->email('loja@example.com.br')
            ->document('14779686000195')->street('Av. Paulista', '1000')
            ->district('Bela Vista')->city('São Paulo', 'SP')->postalCode('01310100')
    )
    ->recipient(
        ShippingAddress::make()
            ->name('João Silva')->phone('21988880000')->email('joao@email.com')
            ->document('05596752088')->street('Rua das Flores', '42', 'Apto 3')
            ->district('Centro')->city('Rio de Janeiro', 'RJ')->postalCode('20040020')
    )
    ->addProduct(ShippingProduct::make()->name('Camiseta')->quantity(1)->price(75.00))
    ->addVolume(ShippingVolume::make()->dimensions(15, 30, 40)->weight(1.5))
    ->option('insurance_value', 75.00)
    ->save()
    ->addToCart();

$orderId = $cart['data']['id'];

// 3. Pagar (checkout com saldo da carteira ME)
orchestrator()->checkout($orderId);

// 4. Gerar etiqueta
orchestrator()->generateLabels($orderId);

// 5. Obter URL do PDF
$print  = orchestrator()->printLabels($orderId, 'public');
$pdfUrl = $print['data']['url'];

// 6. Rastrear
$track  = orchestrator()->trackShipment($orderId);
$status = $track['data'][$orderId]['status'];
```

---

## Referência de Métodos

| Método | Descrição |
|---|---|
| `getCPF($cpf, $date?)` | Consulta CPF |
| `getCNPJ($cnpj)` | Consulta CNPJ |
| `getZipCode($cep)` | Consulta CEP |
| `getBanks()` | Lista bancos |
| `getCountries()` | Lista países |
| `getCountryInfo($country)` | Info de um país (nome, ISO2 ou ISO3) |
| `getStates($country)` | Estados de um país |
| `getStateInfo($country, $state)` | Info de um estado |
| `getCities($country, $state)` | Cidades de um estado |
| `getHolidays($state, $year)` | Feriados estaduais e nacionais |
| `getWeather($city, $country, $state?)` | Previsão do tempo |
| `getDomain($domain)` | WHOIS de domínio |
| `getCalendar($country, $state, $city, $start, $end)` | Calendário (feriados + clima) |
| `getFipeBrands($type)` | Marcas FIPE (`cars`/`motorcycles`/`trucks`) |
| `getFipeModels($type, $brand)` | Modelos FIPE de uma marca |
| `getFipeYears($type, $brand, $model)` | Anos disponíveis para um modelo |
| `getFipePrice($type, $brand, $model, $year)` | Preço FIPE |
| `getExchange($from, $to)` | Cotação de câmbio |
| `getStock($symbol)` | Cotação de ação B3 ou índice |
| `getNcm($code)` | NCM por código (8 dígitos) |
| `searchNcm($term)` | NCM por descrição |
| `getCarriers()` | Transportadoras disponíveis no Melhor Envio |
| `buildCalculate()` | Builder fluente para cálculo de frete |
| `buildOrder()` | Builder fluente para criação de pedido |
| `addToCart($data)` | Adicionar ao carrinho (raw, sem builder) |
| `removeFromCart($id)` | Remover do carrinho |
| `checkout($orderIds)` | Pagar fretes (string ou array) |
| `generateLabels($orderIds)` | Gerar etiquetas (string ou array) |
| `printLabels($orderIds, $mode?)` | URL do PDF da etiqueta (`public`/`private`) |
| `cancelLabel($id, $description?)` | Cancelar etiqueta |
| `listOrders($status?, $page?)` | Listar pedidos com filtro e paginação |
| `getOrder($id)` | Detalhes de um pedido |
| `searchOrder($query)` | Pesquisar pedido por rastreio, protocolo, CPF/CNPJ ou ID |
| `trackShipment($orderIds)` | Rastrear envio (string ou array) |

---

## Requisitos

- PHP >= 8.3
- Laravel >= 12

---

💡 **Desenvolvido por [Rise Tech](https://risetech.com.br)**
