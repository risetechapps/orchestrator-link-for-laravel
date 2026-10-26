# Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.
O formato é baseado em [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), e este projeto segue o [Versionamento Semântico](https://semver.org/lang/pt-BR/) (SemVer).

## [Não lançado]

## [1.0.3] - 2026-10-26

### Corrigido

- Rotas proxy: o 404 do orchestrator (dado não encontrado) responde 410 (Gone) com a mesma mensagem (ex.: `Domain not found.`, `Envio não encontrado.`). O 404 ficava igual ao de rota inexistente no front. O retorno de `orchestrator()->...` continua com `status` 404.

## [1.0.2] - 2026-10-26

### Adicionado

- O retorno de sucesso traz `status` (HTTP do orchestrator) e `message`. A mensagem carrega avisos como o do `secret` do webhook, que só aparece uma vez.
- Falhas trazem `retry_after` (segundos) no 429, lido do header `Retry-After` ou do corpo.
- Erros da conta no orchestrator (401, 402, 403, 429) não expõem a mensagem original ao usuário final (ex.: "Seu plano expirou"): voltam com `status` 503 e a mensagem genérica `Connection::UNAVAILABLE_MESSAGE`, em inglês. O status, o `error_code` e o corpo originais ficam no log, como `error` em 401/402/403 e na cota esgotada (`REQUEST_LIMIT_REACHED`). Sem resposta do orchestrator, a mesma mensagem genérica no lugar de `null`.
- Rota proxy `GET /services/ncm?search=`, o caminho do OpenAPI. `/services/ncm/search` continua respondendo.

### Corrigido

- As rotas proxy respondem 201 nas criações (envio, remetente, PIX, devolução), como o orchestrator, em vez de 200, e repassam o header `Retry-After` no 429.
- Valores de texto que vão no caminho (cidade, estado, país, domínio, marca/modelo/ano FIPE, símbolo, indicador, id do envio) são codificados com `rawurlencode`. Uma `/`, `?` ou `#` no valor mudava a rota chamada.
- README: país do calendário em ISO3 (`BRA`), campos do clima e do WHOIS conforme a API, e telefone de outros países com `+DDI`.

## [1.0.1] - 2026-10-22

### Corrigido

- Frete: `ShippingAddress::document()` e `companyDocument()` mantêm as letras do CNPJ alfanumérico. Antes tiravam tudo o que não fosse dígito (`12.ABC.345/01DE-35` virava `123450135`), e a criação do envio voltava 422.

## [1.0.0] - 2026-10-22

Primeira versão pública. As versões anteriores foram de teste.

- Cliente da API Orchestrator pelo helper `orchestrator()`: CPF, CNPJ (inclusive alfanumérico), sócios, Simples/MEI, CNAE, CEP, código IBGE, geolocalização e distância, bancos, países, estados, cidades, feriados, clima, calendário, WHOIS, FIPE, câmbio, ações e catálogo da B3, indicadores do Banco Central, leitura de PIX copia e cola, NCM com tributos do IBPT e validação de e-mail e telefone.
- Frete white label: serviços, cotação (`buildQuote`), agências de postagem, remetentes salvos, criação do envio (`buildShipment`) com chave de idempotência, PIX por envio e para vários envios, pagamento simulado com a chave de teste, cancelamento com estorno, devolução, rastreio, lista com filtros e etiqueta em PDF.
- `ShippingWebhook::verify()` / `verifyRequest()` conferem a assinatura dos avisos de frete.
- Model `Shipment` com a cópia local dos envios (`syncFromOrchestrator`) e a migration `orchestrator_shipments`.
- Rotas proxy opcionais (`OrchestratorLink::routes()`), que repassam o status HTTP do orchestrator (502 quando ele não responde).
- Falhas nunca lançam exceção: voltam como `success: false` com `status`, `message` e `errors` do orchestrator. GET repete só falha de conexão; consultas de referência ficam em cache.
