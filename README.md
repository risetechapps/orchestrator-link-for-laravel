# Orchestrator Link for Laravel

Package Laravel para comunicação com a API Orchestrator da RiseTech. Fornece acesso a dados de CPF, CNPJ (com sócios e Simples/MEI), CNAE, CEP, código IBGE, geolocalização, bancos, países, estados, cidades, clima, feriados, calendário, WHOIS, FIPE, câmbio, ações B3, indicadores do Banco Central, PIX copia e cola, NCM (com tributos), validação de e-mail e telefone, e frete white label (cotação, envio, PIX, etiqueta e rastreio).

> **Versão 3.0** acompanha o orchestrator 4.0. O módulo de frete mudou por completo: veja [Atualizando da 2.x](#atualizando-da-2x).

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

# Opcional: outro ambiente (homologação, túnel local...). Padrão: produção.
ORCHESTRATOR_URL=https://orchestrator.app.br/api/v1/services
```

A chave de teste da conta também funciona: no frete, ela cria envios de teste (sandbox), sem cobrança real.

### Publicar config (opcional)

```bash
php artisan vendor:publish --tag=config
```

Isso cria `config/orchestrator-link.php`:

```php
return [
    'token' => env('ORCHESTRATOR_TOKEN', ''),
    'url'   => env('ORCHESTRATOR_URL', 'https://orchestrator.app.br/api/v1/services'),
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

> As rotas usam a chave da **sua** conta no orchestrator. Coloque-as atrás da autenticação da sua app (`Route::middleware('auth')->group(...)`): sem isso, qualquer visitante consulta com a sua cota e, no frete, vê e cria envios da conta.

Isso registra as rotas abaixo no seu app:

| Método | Rota | Serviço |
|---|---|---|
| GET | `/services/cpf/{cpf}/{date?}` | CPF |
| GET | `/services/cnpj/{cnpj}` | CNPJ |
| GET | `/services/zip_code/{zip_code}` | CEP |
| GET | `/services/cnpj/{cnpj}/partners` | Sócios do CNPJ |
| GET | `/services/cnpj/{cnpj}/simples` | Simples Nacional e MEI |
| GET | `/services/cnae/{code}` | CNAE por código |
| GET | `/services/cnae?search=` | Busca CNAE |
| GET | `/services/ibge/cities/{uf}?name=` | Municípios da UF (código IBGE) |
| GET | `/services/ibge/city/{code}` | Município pelo código IBGE |
| GET | `/services/ibge/zip_code/{zip_code}` | Código IBGE do CEP |
| GET | `/services/geo/zip_code/{zip_code}` | Coordenadas do CEP |
| GET | `/services/geo/distance/{from}/{to}` | Distância entre CEPs |
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
| GET | `/services/stocks` | Catálogo da B3 |
| GET | `/services/indicators` | Indicadores econômicos |
| GET | `/services/indicators/{indicator}?from=&to=` | Um indicador / série |
| POST | `/services/pix/decode` | PIX copia e cola |
| GET | `/services/ncm/search` | NCM por descrição |
| GET | `/services/ncm/{code}` | NCM por código |
| GET | `/services/ncm/{code}/taxes?uf=` | Tributos do NCM (IBPT) |
| GET | `/services/email/{email}` | Validação de e-mail |
| GET | `/services/phone/{phone}` | Validação de telefone |
| GET | `/services/shipping/services` | Serviços de entrega |
| POST | `/services/shipping/quotes` | Cotação |
| GET | `/services/shipping/agencies` | Agências de postagem |
| GET/POST | `/services/shipping/senders` | Remetentes salvos |
| PUT/DELETE | `/services/shipping/senders/{id}` | Altera/remove remetente |
| GET/POST | `/services/shipping/shipments` | Lista/cria envios |
| GET | `/services/shipping/shipments/{id}` | Envio |
| POST | `/services/shipping/shipments/{id}/purchase` | Gera o PIX |
| GET | `/services/shipping/shipments/{id}/payment` | Pagamento |
| POST | `/services/shipping/shipments/{id}/payment/simulate` | Simula pagamento (chave de teste) |
| POST | `/services/shipping/shipments/payments` | Um PIX para vários envios |
| GET | `/services/shipping/shipments/payments/{group}` | Situação do PIX de vários |
| POST | `/services/shipping/shipments/{id}/cancel` | Cancela |
| POST | `/services/shipping/shipments/{id}/reverse` | Devolução |
| GET | `/services/shipping/shipments/{id}/tracking` | Rastreio |
| GET | `/services/shipping/shipments/{id}/label` | Etiqueta (PDF) |
| GET | `/services/shipping/shipments/labels?ids=` | Etiquetas de vários (PDF) |
| GET/PUT/DELETE | `/services/shipping/webhook` | Webhook da conta |

---

## Macros de resposta

> **Movidas a partir da v2.0.0.** As macros `jsonSuccess`, `jsonError`, `jsonGone` e `jsonNotValidated` **não são mais registradas por este package** — passaram a ser providas por outro package **risetechapps/risetools**.

---

## Formato de retorno

Todos os métodos do helper `orchestrator()` retornam:

```php
[
    'success' => true|false,
    'data'    => [...],
]
```

`success => false` com `data => []` é o retorno de **qualquer falha** — os métodos **nunca lançam exceção** para não derrubar a request (degradação graciosa). A falha traz também o que o orchestrator respondeu, para mostrar ao usuário:

```php
[
    'success' => false,
    'data'    => [],
    'status'  => 422,                 // HTTP do orchestrator; null sem resposta (conexão, token ausente)
    'message' => 'Dados inválidos.',  // mensagem do orchestrator, quando houver
    'errors'  => ['to.postal_code' => ['O CEP deve ter 8 dígitos.']],  // erros de validação
]
```

Status comuns: 401 chave inválida, 402 serviço fora do plano, 403 plano inativo, 404 não encontrado, 409 conflito (ex.: etiqueta ainda não pronta), 410 fonte de dados fora do ar, 422 validação, 429 limite, 503 serviço não configurado no servidor.

Cai nesse retorno quando:

| Situação | Comportamento | Log |
|---|---|---|
| Timeout / DNS / conexão recusada | `errorResponse` (não vira HTTP 500) | `warning` |
| HTTP 5xx (orchestrator quebrado) | `errorResponse` | `error` |
| HTTP 4xx (401/404/429...) | `errorResponse` | `warning` (com status + corpo) |
| `ORCHESTRATOR_TOKEN` não configurado | `errorResponse` **sem** sair pra rede | `error` |
| Corpo não-JSON / shape inesperado | `success:false`, `data:[]` (não quebra) | — |

Sempre confira `success` antes de usar `data`.

### Resiliência (timeouts / retry)

- `connectTimeout` 5s + `timeout` 15s por tentativa — a chamada roda dentro da request web e não prende o worker.
- **Retry só em GET** (idempotente) e só em falha de rede: 2 tentativas no total. Resposta 4xx/5xx volta na hora. POST/PUT/PATCH/DELETE **não** repetem (evita duplicar envio/PIX); para repetir com segurança, use a chave de idempotência (ver [Frete](#frete-white-label)).

### Cache

Respostas de **GET com sucesso** são cacheadas por tipo de volatilidade. POST e falhas **nunca** são cacheados. Invalidação é por TTL (sem flush manual).

| TTL | Endpoints |
|---|---|
| **30 dias** | bancos, países, estados, cidades, feriados, FIPE (marcas/modelos/anos/preço), NCM, CEP, CNAE, código IBGE, coordenadas e distância |
| **1 dia** | CNPJ, sócios, Simples/MEI, tributos do NCM, telefone, serviços de frete |
| **1 hora** | domínio (WHOIS), indicadores, e-mail, agências de postagem |
| **sem cache** | CPF, clima, câmbio, ações B3, calendário, PIX e as operações de frete (envios, pagamento, etiqueta, rastreio, remetentes, webhook) |

> Em ambiente multi-tenant, o cache é isolado por tenant — cada tenant busca o dado uma vez por TTL. Com os TTLs longos, a redundância é desprezível.

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

## Empresa: sócios, Simples/MEI e CNAE

```php
// Quadro de sócios e administradores (documentos mascarados pela Receita)
$r = orchestrator()->getCnpjPartners('33.000.167/0001-01');
$r['data']['total'];                    // 10
$r['data']['partners'][0]['name'];      // CLARICE COPPETTI
$r['data']['partners'][0]['document'];  // ***995240**
$r['data']['partners'][0]['role'];      // qualificação (Diretor, Sócio-Administrador...)

// Opção pelo Simples Nacional e pelo MEI
$r = orchestrator()->getCnpjSimples('33000167000101');
$r['data']['simples'];  // ['optant' => false, 'since' => null, 'until' => null]
$r['data']['mei'];      // idem

// CNAE (aceita 1921700 ou o formato do cartão CNPJ, 1921-7/00)
$r = orchestrator()->getCnae('1921-7/00');
$r['data']['formatted'];    // 1921-7/00
$r['data']['description'];  // FABRICAÇÃO DE PRODUTOS DO REFINO DE PETRÓLEO

// Busca pela descrição (sem acento) ou pelo começo do código
$r = orchestrator()->searchCnae('padaria');
```

---

## Código IBGE e geolocalização

```php
// Município do CEP com o código IBGE (o que a NF-e pede)
$r = orchestrator()->getIbgeByZipCode('13010-001');
$r['data']['code'];         // 3509502
$r['data']['name'];         // Campinas
$r['data']['state']['uf'];  // SP

// Municípios da UF; com nome, acha um (ignora acento, hífen e apóstrofo)
orchestrator()->getIbgeCities('SP');
orchestrator()->getIbgeCities('SP', "Santa Barbara d'Oeste");

// Município pelo código
orchestrator()->getIbgeCity('3509502');

// Coordenadas do CEP
$r = orchestrator()->getGeoByZipCode('01001-000');
$r['data']['latitude'];   // -23.5496714
$r['data']['longitude'];  // -46.6332637
$r['data']['precision'];  // zip_code | street | city

// Distância em linha reta entre dois CEPs
$r = orchestrator()->getDistance('13010-001', '01001-000');
```

---

## Indicadores econômicos e PIX

```php
// Último valor de Selic, CDI, IPCA, IPCA 12m, IGP-M, INPC e TR (Banco Central)
$r = orchestrator()->getIndicators();
$r['data'][0];  // ['indicator' => 'selic', 'name' => ..., 'unit' => '% a.a.', 'date' => ..., 'value' => 13.75]

// Um indicador; com período (Y-m-d, até 10 anos), a série
orchestrator()->getIndicator('ipca');
$r = orchestrator()->getIndicator('selic', '2025-01-01', '2025-12-31');
$r['data']['values'];  // [['date' => '2025-01-01', 'value' => 12.25], ...]

// PIX copia e cola: confere o CRC e extrai os dados (não diz se foi pago)
$r = orchestrator()->decodePix('00020126580014br.gov.bcb.pix0136...6304ABCD');
$r['data']['valid'];     // true
$r['data']['type'];      // static | dynamic
$r['data']['key'];       // chave PIX
$r['data']['key_type'];  // cpf | cnpj | email | phone | evp
$r['data']['amount'];    // null quando o valor é livre
```

---

## Tributos por NCM (IBPT)

```php
// NCM com 8 dígitos e a UF. Precisa do token do IBPT no servidor (503 sem ele).
$r = orchestrator()->getNcmTaxes('8517.13.00', 'SP');
```

---

## Validação de e-mail e telefone

```php
$r = orchestrator()->validateEmail('contato@gmial.com');
$r['data']['valid'];         // false
$r['data']['mx'];            // o domínio recebe e-mail?
$r['data']['disposable'];    // e-mail descartável
$r['data']['role_account'];  // contato@, vendas@...
$r['data']['suggestion'];    // contato@gmail.com

$r = orchestrator()->validatePhone('(21) 98765-4321');  // sem DDI, lido como brasileiro
$r['data']['valid'];     // true
$r['data']['type'];      // mobile | fixed_line ...
$r['data']['e164'];      // +5521987654321
$r['data']['national'];  // (21) 98765-4321
```

---

## Catálogo da B3

```php
// Filtros: search, sector, type, sort_by, sort_order, limit e page
$r = orchestrator()->getStocks(['search' => 'petr', 'limit' => 10]);
$r['data']['stocks'];
```

---

## Frete (white label)

O cliente fala só com o orchestrator: ids próprios, preço final já com a margem e nenhum dado do provedor. O fluxo é: **cotar → criar o envio → gerar o PIX → (pago) etiqueta emitida → imprimir → rastrear**.

### Serviços e cotação

```php
use RiseTechApps\OrchestratorLink\Shipping\ShippingProduct;
use RiseTechApps\OrchestratorLink\Shipping\ShippingVolume;

orchestrator()->getShippingServices();
// [['id' => 1, 'name' => 'PAC', 'carrier' => 'Correios', 'requires_agency' => false], ...]

$quote = orchestrator()->buildQuote()
    ->from('01001-000')
    ->to('13010-001')
    ->addVolume(fn (ShippingVolume $v) => $v->dimensions(10, 20, 30)->weight(1.5))  // altura, largura, comprimento (cm), kg
    // ou produtos soltos: ->addProduct(fn (ShippingProduct $p) => $p->id('sku-1')->dimensions(20, 10, 30)->weight(0.5)->quantity(2))
    ->insurance(150.00)
    ->services([1, 2])  // opcional: só estes serviços
    ->send();
```

Serviço com `requires_agency` pede a agência de postagem:

```php
orchestrator()->getShippingAgencies(service: 3, postalCode: '01001-000', state: 'SP', city: 'São Paulo');
```

### Criar o envio

```php
use RiseTechApps\OrchestratorLink\Shipping\ShippingAddress;

$result = orchestrator()->buildShipment()
    ->reference('pedido-1050')          // sua referência, para achar o envio depois
    ->service(1)
    ->sender(fn (ShippingAddress $a) => $a
        ->name('Loja Aurora')->phone('(21) 99999-8888')->email('loja@example.com')
        ->companyDocument('14.779.686/0001-95')
        ->street('Rua das Flores', '42', 'Loja 3')->district('Centro')
        ->city('Rio de Janeiro', 'RJ')->postalCode('20040-020'))
    // ou um remetente salvo: ->senderId(1)
    ->recipient(fn (ShippingAddress $a) => $a
        ->name('Maria Souza')->phone('(19) 98888-7777')->email('maria@example.com')
        ->document('529.982.247-25')
        ->street('Av. Dr. Moraes Salles', '100')->district('Centro')
        ->city('Campinas', 'SP')->postalCode('13010-001'))
    ->addVolume(fn (ShippingVolume $v) => $v
        ->dimensions(10, 20, 30)->weight(1.5)
        // Correios: um objeto por volume, cada um com os próprios produtos
        ->addProduct(fn (ShippingProduct $p) => $p->name('Caneca')->quantity(2)->price(30.00)))
    // em outras transportadoras, os produtos podem ir no envio: ->addProduct(...)
    ->insurance(60.00)
    // ->invoice('3525...0019')         // NF-e (44 dígitos): dispensa a declaração de conteúdo
    // ->notifications(sender: true, recipient: false)
    // ->labelLogo($base64Png)          // logo do remetente na etiqueta
    ->idempotencyKey('pedido-1050')     // repetir com a mesma chave não cria outro envio
    ->save()                            // grava a cópia local em orchestrator_shipments
    ->create();

$shipmentId = $result['data']['id'];    // UUID do envio
$result['data']['price'];               // preço final
$result['data']['tracking_url'];        // página pública de rastreio para o destinatário
```

### Pagamento (PIX)

A etiqueta só é comprada depois que o PIX é pago.

```php
$pix = orchestrator()->purchaseShipment($shipmentId);
$pix['data']['pix']['code'];       // copia e cola
$pix['data']['pix']['qr_base64'];  // QR Code
$pix['data']['expires_at'];

orchestrator()->getShipmentPayment($shipmentId);  // confere no gateway

// Vários envios num PIX só (até 50)
$group = orchestrator()->payShipments([$id1, $id2]);
orchestrator()->getShipmentPaymentGroup($group['data']['id']);

// Com a chave de teste: aprova o PIX simulado e segue o fluxo no sandbox
orchestrator()->simulateShipmentPayment($shipmentId);
```

### Etiqueta, rastreio, cancelamento e devolução

```php
// PDF (bytes em data.content)
$label = orchestrator()->getShipmentLabel($shipmentId);
if ($label['success']) {
    return response($label['data']['content'], 200, ['Content-Type' => 'application/pdf']);
}

orchestrator()->getShipmentLabels([$id1, $id2]);  // um PDF com várias

orchestrator()->trackShipment($shipmentId);       // sincroniza status e rastreio
orchestrator()->getShipment($shipmentId);         // envio com o histórico
orchestrator()->listShipments(['status' => 'posted', 'external_reference' => 'pedido-1050', 'per_page' => 20]);
orchestrator()->listShipments(['label_code' => 'ORD-123']);  // pelo código impresso na etiqueta

orchestrator()->cancelShipment($shipmentId, 'Cliente desistiu');  // se pago, o PIX é estornado

// Devolução: endereços do original invertidos
orchestrator()->reverseShipment($shipmentId, [
    'service' => 1,
    'volumes' => [['height' => 10, 'width' => 20, 'length' => 30, 'weight' => 1.5]],
]);
```

Status do envio: `pending`, `awaiting_payment`, `processing`, `purchased`, `label_ready`, `posted`, `delivered`, `undelivered` e `cancelled`.

### Remetentes salvos

```php
orchestrator()->getShippingSenders();
orchestrator()->createShippingSender(ShippingAddress::make()->name('Loja')->/* ... */toArray() + ['label' => 'Loja Centro', 'is_default' => true]);
orchestrator()->updateShippingSender(1, [...]);
orchestrator()->deleteShippingSender(1);
```

### Webhook

O orchestrator avisa cada mudança de status (`shipment.payment_confirmed`, `shipment.purchased`, `shipment.label_ready`, `shipment.posted`, `shipment.delivered`, `shipment.undelivered`, `shipment.cancelled`) numa URL HTTPS pública.

```php
$r = orchestrator()->setShippingWebhook('https://minhaloja.com.br/webhooks/frete');
$secret = $r['data']['secret'];  // mostrado só agora: guarde (ex.: no .env)
```

Na rota que recebe:

```php
use RiseTechApps\OrchestratorLink\Models\Shipment;
use RiseTechApps\OrchestratorLink\Shipping\ShippingWebhook;

Route::post('/webhooks/frete', function (Request $request) {
    if (!ShippingWebhook::verifyRequest($request, config('services.orchestrator.webhook_secret'))) {
        abort(401);
    }

    // X-Orchestrator-Delivery identifica a entrega: use para não processar duas vezes.
    Shipment::syncFromOrchestrator($request->input('data'));

    return response()->noContent();
});
```

Resposta fora de 2xx é reenviada até 8 vezes ao longo de ~1 dia. Avisos com mais de 5 minutos são recusados por padrão (`verify(..., tolerance: 300)`).

### Model Shipment

`->save()` no builder e `Shipment::syncFromOrchestrator($data)` gravam a cópia local em `orchestrator_shipments`, pelo `orchestrator_id`. Um aviso parcial não apaga o que já estava salvo.

```php
use RiseTechApps\OrchestratorLink\Models\Shipment;

$shipment = Shipment::where('orchestrator_id', $shipmentId)->first();
Shipment::where('external_reference', 'pedido-1050')->first();
Shipment::pending()->get();  // também posted() e delivered()

$shipment->isAwaitingPayment();
$shipment->isLabelReady();
$shipment->isPosted();
$shipment->isDelivered();
$shipment->isCanceled();

// Colunas: orchestrator_id, external_reference, service_id, service_name, carrier,
// status, sandbox, tracking_code, tracking_url, from_postal_code, from_data (json),
// to_postal_code, to_data (json), products (json), volumes (json), options (json),
// price, paid_at, posted_at, delivered_at, canceled_at. As colunas me_* e
// label_url são dos envios criados antes da 3.0.
```

### Atualizando da 2.x

A 2.x falava com as rotas do Melhor Envio, que o orchestrator 4.0 removeu: na prática o frete da 2.x já não funciona.

| 2.x | 3.0 |
|---|---|
| `getCarriers()` | `getShippingServices()` |
| `buildCalculate()` / `calculateShipping()` | `buildQuote()` / `quoteShipping()` |
| `buildOrder()->addToCart()` / `addToCart()` | `buildShipment()->create()` / `createShipment()` |
| `checkout()` (saldo da carteira) | `purchaseShipment()` / `payShipments()` (PIX) |
| `generateLabels()` | automático depois do pagamento |
| `printLabels()` (URL) | `getShipmentLabel()` / `getShipmentLabels()` (PDF) |
| `cancelLabel()` | `cancelShipment()` |
| `listOrders()` / `getOrder()` / `searchOrder()` | `listShipments()` / `getShipment()` |
| `trackShipment(array)` | `trackShipment(string $id)` |
| `removeFromCart()` | `cancelShipment()` |

Rode `php artisan migrate`: a migration nova acrescenta as colunas do envio novo em `orchestrator_shipments`.

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
| `getStocks($filters?)` | Catálogo de ativos da B3 |
| `getCnpjPartners($cnpj)` | Sócios do CNPJ (QSA) |
| `getCnpjSimples($cnpj)` | Simples Nacional e MEI |
| `getCnae($code)` | CNAE por código |
| `searchCnae($term)` | Busca CNAE |
| `getIbgeCities($uf, $name?)` | Municípios da UF com código IBGE |
| `getIbgeCity($code)` | Município pelo código IBGE |
| `getIbgeByZipCode($cep)` | Código IBGE do CEP |
| `getGeoByZipCode($cep)` | Coordenadas do CEP |
| `getDistance($from, $to)` | Distância entre CEPs |
| `getIndicators()` | Indicadores econômicos (último valor) |
| `getIndicator($indicator, $from?, $to?)` | Um indicador ou a série |
| `decodePix($code)` | Lê PIX copia e cola |
| `getNcmTaxes($code, $uf)` | Tributos do NCM (IBPT) |
| `validateEmail($email)` | Validação de e-mail |
| `validatePhone($phone)` | Validação de telefone |
| `getShippingServices()` | Serviços de entrega |
| `buildQuote()` / `quoteShipping($data)` | Cotação |
| `getShippingAgencies($service, $postalCode, $state, $city, $district?)` | Agências de postagem |
| `getShippingSenders()` / `createShippingSender($data)` / `updateShippingSender($id, $data)` / `deleteShippingSender($id)` | Remetentes salvos |
| `buildShipment()` / `createShipment($data, $idempotencyKey?)` | Cria envio |
| `listShipments($filters?)` | Lista envios |
| `getShipment($id)` | Envio com histórico |
| `purchaseShipment($id, $idempotencyKey?)` | Gera o PIX |
| `getShipmentPayment($id)` | Pagamento do envio |
| `payShipments($ids, $idempotencyKey?)` / `getShipmentPaymentGroup($group)` | PIX de vários envios |
| `simulateShipmentPayment($id)` | Simula pagamento (chave de teste) |
| `cancelShipment($id, $reason?)` | Cancela (estorna se pago) |
| `reverseShipment($id, $data, $idempotencyKey?)` | Devolução |
| `trackShipment($id)` | Sincroniza rastreio |
| `getShipmentLabel($id)` / `getShipmentLabels($ids)` | Etiqueta em PDF |
| `getShippingWebhook()` / `setShippingWebhook($url)` / `deleteShippingWebhook()` | Webhook da conta |

---

## Requisitos

- PHP >= 8.4
- Laravel >= 12

---

💡 **Desenvolvido por [Rise Tech](https://risetech.com.br)**
