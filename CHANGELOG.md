# Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.
O formato é baseado em [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), e este projeto segue o [Versionamento Semântico](https://semver.org/lang/pt-BR/) (SemVer).

## [3.0.0] - 2026-10-13

Acompanha o orchestrator 4.0.

### ⚠️ Mudanças incompatíveis
- **Frete white label no lugar do Melhor Envio.** O orchestrator 4.0 removeu as rotas `carriers`, `calculate`, `cart`, `checkout`, `labels/*`, `orders/*` e `track`, então o frete da 2.x já não funciona. Saíram `getCarriers`, `calculateShipping`, `addToCart`, `removeFromCart`, `checkout`, `generateLabels`, `printLabels`, `cancelLabel`, `listOrders`, `getOrder`, `searchOrder`, `buildCalculate` e `buildOrder` (com `ShippingCalculateBuilder` e `ShippingOrderBuilder`). `trackShipment` passou a receber o id de um envio. A tabela de equivalência está no README, em "Atualizando da 2.x".
- As rotas proxy de frete mudaram junto (`OrchestratorLink::routes()`).
- PHP 8.4 (já era o mínimo do `composer.json`; o README dizia 8.3).

### Adicionado
- **Frete white label:** serviços (`getShippingServices`), cotação (`buildQuote`, `quoteShipping`), agências de postagem, remetentes salvos, criação do envio (`buildShipment`, `createShipment`), PIX por envio e para vários envios, pagamento simulado com a chave de teste, cancelamento com estorno, devolução, rastreio, lista com filtros (status, referência, código da etiqueta) e a etiqueta em PDF (`getShipmentLabel`, `getShipmentLabels`).
- `ShipmentBuilder`: referência externa, agência, remetente salvo (`senderId`), produtos por volume (Correios), seguro, NF-e, avisos por e-mail, logo na etiqueta e chave de idempotência (`Idempotency-Key`), que evita envio duplicado ao repetir a chamada.
- **Webhook de frete:** `setShippingWebhook`, `getShippingWebhook`, `deleteShippingWebhook` e `ShippingWebhook::verify()` / `verifyRequest()`, que conferem a assinatura `X-Orchestrator-Signature` (HMAC-SHA256, com tolerância de 5 minutos contra reenvio).
- `Shipment::syncFromOrchestrator()` grava ou atualiza a cópia local a partir da resposta ou do aviso do webhook, sem apagar o que um aviso parcial não traz. Migration nova com `orchestrator_id`, `external_reference`, `carrier`, `tracking_url`, `sandbox` e `paid_at`; o model ganhou `isAwaitingPayment`, `isLabelReady` e `isUndelivered`.
- **Serviços novos do orchestrator:** sócios do CNPJ (`getCnpjPartners`), Simples/MEI (`getCnpjSimples`), CNAE (`getCnae`, `searchCnae`), código IBGE (`getIbgeCities`, `getIbgeCity`, `getIbgeByZipCode`), geolocalização e distância (`getGeoByZipCode`, `getDistance`), indicadores do Banco Central (`getIndicators`, `getIndicator`), PIX copia e cola (`decodePix`), tributos por NCM (`getNcmTaxes`), validação de e-mail e de telefone (`validateEmail`, `validatePhone`) e o catálogo da B3 (`getStocks`). Todos têm rota proxy.
- `ORCHESTRATOR_URL` (`orchestrator-link.url`) para apontar para outro ambiente. O host entra na chave do cache.
- **Falha com detalhes:** o retorno de erro ganhou `status` (HTTP), `message` (do orchestrator) e `errors` (validação), sem perder `success` e `data`.
- Testes (Pest + Testbench) para todas as rotas, os builders, o webhook, a cópia local e as rotas proxy.

### Corrigido
- **GET com 404/422 virava exceção (HTTP 500 na app).** O `retry` repetia também as respostas 4xx/5xx e, na última tentativa, lançava `RequestException`, que não era capturada. Agora só falha de conexão é repetida, e a resposta de erro volta na hora como `{success: false}`.
- Datas do orchestrator com fuso (`-03:00`) eram gravadas no model sem conversão, com o horário deslocado. Agora são convertidas para o fuso da app.
- O autoload de `helpers.php` tinha uma barra inicial (`/src/helpers.php`).
- `.gitignore` (faltava: `vendor` e `composer.lock` iam para o repositório).

## [2.0.0] - 2026-07-19
### Added
- **Cache de respostas GET** por volatilidade de endpoint (TTL_STATIC 30d, TTL_DAY 1d, TTL_HOUR 1h). Só GET de sucesso é cacheado; POST e falhas nunca. Corta a latência e a dependência do orchestrator nas leituras de referência (bancos, CEP, FIPE, países/estados/cidades, NCM, CNPJ, transportadoras, domínio).
- **Timeouts e retry** em `Connection`: `connectTimeout(5)` + `timeout(15)` por tentativa; retry de 2 tentativas apenas em GET (idempotente). POST/PUT/PATCH/DELETE não repetem.
- **Log por tipo de falha**: conexão (warning), HTTP 5xx (error), HTTP 4xx (warning com status+corpo), token ausente (error) — antes todas as falhas eram indistinguíveis.

### Fixed
- **Erro de conexão não vira mais HTTP 500**: `ConnectionException` (timeout/DNS/conexão recusada) é capturada e retorna `{success:false, data:[]}` (degradação graciosa). Antes, com o orchestrator fora do ar, a request do usuário quebrava com 500.
- **Timeout reduzido de 300s para 15s/tentativa**: um orchestrator lento não prende mais o worker por até 5 minutos.
- **`successResponse()` à prova de corpo inesperado**: `json()` nulo ou shape diferente não lança mais `TypeError`/undefined key (fallback `?? false` / `?? []`).
- **PATCH e DELETE passam a enviar o corpo (`$data`)** — antes era descartado silenciosamente no PATCH/DELETE.
- **Token ausente faz short-circuit**: com `ORCHESTRATOR_TOKEN` vazio, retorna `errorResponse` sem sair para a rede (não gasta round-trip) e loga a misconfiguração.

### Changed
- Controller passa a estender `Illuminate\Routing\Controller` em vez de `App\Http\Controllers\Controller` — remove o acoplamento ao namespace da app (evita fatal em skeletons sem essa classe base).

### Removed
- **Macros de resposta JSON** (`jsonSuccess`, `jsonError`, `jsonGone`, `jsonNotValidated`) removidas do ServiceProvider — passam a ser providas por outro package, evitando poluição/colisão de namespace. ⚠️ **Potencialmente breaking** se algo dependia dessas macros vindas *deste* package; garanta que o package que as fornece esteja registrado.

## [1.2.0] - 2026-05-31
### Added
- Módulo Shipping completo via Melhor Envio: cálculo de frete, carrinho, checkout, geração/impressão/cancelamento de etiquetas, rastreio e listagem de pedidos.
- Builders fluentes `buildCalculate()` e `buildOrder()` com suporte a `ShippingProduct`, `ShippingVolume` e `ShippingAddress`.
- Model `Shipment` com scopes (`pending`, `posted`, `delivered`) e helpers de status (`isPending`, `isPosted`, `isDelivered`, `isCanceled`).
- Migration `orchestrator_shipments` com auto-load via ServiceProvider e publicação por tag `orchestrator-link-migrations`.
- Consulta FIPE para carros, motos e caminhões: marcas, modelos, anos e preço.
- Cotação de câmbio entre qualquer par de moedas (`getExchange`).
- Cotação de ações e índices da B3 (`getStock`).
- Consulta e busca de NCM (`getNcm`, `searchNcm`).
- Consulta de países, info de país, estados, info de estado e cidades (`getCountries`, `getCountryInfo`, `getStates`, `getStateInfo`, `getCities`).
- Parâmetro `country` adicionado ao método `getWeather` (suporte internacional).
- Consulta WHOIS de domínio (`getDomain`), com normalização automática de URL.
- Rotas proxy para todos os novos endpoints via `OrchestratorLink::routes()`.
- Macros de resposta JSON no `ResponseFactory`: `jsonSuccess`, `jsonError`, `jsonGone`, `jsonNotValidated`.
### Fixed
- Rota do CPF corrigida para aceitar data como parâmetro opcional (`{date?}`).
- Rota `stateInfo` corrigida de `/services/state/` para `/services/states/`.

## [1.1.0] - 2026-02-05
### Added
- Adicionar metodo calendar, que captura feriados e clima no invertalo de tempo.
- 
## [1.0.0] - 2025-12-10
### Added
- Lançamento inicial (Primeira versão estável).
