<div align="center">

# JESB InfinitePay Gateway

**Gateway gratuito e independente para WooCommerce com Pix e cartão via checkout hospedado InfinitePay.**

[![CI](https://github.com/erysouzace/jesb-infinitepay-gateway/actions/workflows/ci.yml/badge.svg)](https://github.com/erysouzace/jesb-infinitepay-gateway/actions/workflows/ci.yml)
![Release](https://img.shields.io/badge/release-v1.1.3--candidate-orange)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)
![WooCommerce](https://img.shields.io/badge/WooCommerce-HPOS%20%2B%20Blocks-96588a)

[Releases](https://github.com/erysouzace/jesb-infinitepay-gateway/releases) · [Instalação](#instalação) · [Segurança](SECURITY.md) · [Contribuições](CONTRIBUTING.md)

</div>

> **Aviso:** a v1.1.3 é uma candidata de homologação. Os testes de pagamento e locks realizados no ambiente de testes não garantem compatibilidade com todas as versões do WooCommerce ou ausência de falhas. Avalie o ambiente da loja antes de utilizar em produção.

## Recursos

| Área | Funcionalidade |
| --- | --- |
| Pagamento | Pix e cartão no checkout hospedado InfinitePay |
| WooCommerce | Checkout clássico, Blocks e integração declarada com HPOS |
| Confirmação | `payment_check` antes de chamar `payment_complete()` |
| Segurança | Validação de valor, identificadores, URL e controle de concorrência |
| Recuperação | Tentativas limitadas via Action Scheduler quando há NSU, transaction_nsu e slug |
| Pedidos | Digitais baixáveis concluídos automaticamente; físicos e mistos processando |
| Financeiro | Relatório de 7, 30 e 90 dias; limite explícito de até 2.000 pedidos da loja por consulta |
| Privacidade | Logs sanitizados e processamento sem dados de cartão na loja |

## Instalação

1. Faça **backup** e teste primeiro em um ambiente de homologação, utilizando HTTPS.
2. Verifique que seu servidor usa **PHP 8.2 ou mais recente**, então utilize WooCommerce com moeda **BRL** e conta InfinitePay com Checkout Integrado.
3. Instale o **ZIP do plugin** em **Plugins → Adicionar novo → Enviar plugin**. Não use o ZIP completo do repositório como plugin.
4. Acesse **WooCommerce → Configurações → Pagamentos → JESB InfinitePay Gateway** e informe sua InfiniteTag.
5. Configure a API Key opcional **somente no servidor** para habilitar validade e uso único:

```php
// wp-config.php, antes de carregar wp-settings.php:
define( 'JESB_INFINITEPAY_API_KEY', getenv( 'JESB_INFINITEPAY_API_KEY' ) );
```

6. Confirme a URL REST pública, retorne ao checkout e teste o fluxo completo com pagamento controlado.

## Fluxo financeiro e limitações

1. WooCommerce cria o pedido e o plugin gera o link pela InfinitePay.
2. O cliente paga na página segura da InfinitePay.
3. O plugin recebe webhook ou dados de retorno; **não aprova o pedido apenas por receber a notificação**.
4. O plugin verifica na API oficial a transação e o valor do pedido.
5. Com identificadores válidos, falhas transitórias podem ser reconsultadas pelo **Action Scheduler** em até seis tentativas.
6. Sem identificadores, expirado ou com divergência, o pedido fica para **conciliação manual**, sem criar cobranças concorrentes.

**Importante:** links sem API Key não recebem expiração automática. O plugin não cancela cobranças nem consulta extrato e taxas reais da InfinitePay. O dashboard exibe informações de pedidos WooCommerce, não o saldo bancário.

## Painel

No WordPress: **WooCommerce → JESB InfinitePay**. Visão read-only de indicadores, pendências e transações com acesso `manage_woocommerce`. Consultas extensas são limitadas e identificadas como parciais.

## Desenvolvimento e testes

```bash
php tests/check-metadata.php
php tests/payment-flow.php
php tests/plugin-check-regressions.php
find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l
```

Os testes mockados verificam identificadores, pagamentos divergentes, idempotência, pedidos mistos, links expirados, concorrência e limites de reconciliação. **Ainda é necessária homologação com WordPress, WooCommerce e a InfinitePay em ambiente real.**

## Documentação

- [Guia de publicação](PUBLICACAO.md)
- [Segurança e LGPD](SECURITY.md)
- [Como contribuir](CONTRIBUTING.md)
- [Código de conduta](CODE_OF_CONDUCT.md)
- [Readme do diretório WordPress.org](readme.txt)

## Licença e marcas

**GPL-2.0-or-later.** O arquivo `LICENSE` reproduz o texto integral da GPLv2. O projeto é desenvolvido pela JESB DIGITAL, **sem afiliação, certificação ou endosso oficial da InfinitePay ou WooCommerce**.
