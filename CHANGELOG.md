# Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.
O formato é baseado em [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), e este projeto segue o [Versionamento Semântico](https://semver.org/lang/pt-BR/) (SemVer).

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
