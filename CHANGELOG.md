# Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.
O formato é baseado em [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), e este projeto segue o [Versionamento Semântico](https://semver.org/lang/pt-BR/) (SemVer).

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
