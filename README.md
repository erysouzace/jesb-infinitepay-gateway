# JESB InfinitePay Gateway

Plugin independente para WooCommerce e InfinitePay: checkout hospedado Pix/cartão, confirmação por `payment_check`, suporte a checkout clássico/Blocks e HPOS, painel operacional de pedidos recentes.

**Estado:** release candidate para testes e revisão. Não interpretar como aprovação do WordPress.org nem como auditoria de segurança completa.

## Desenvolvimento

- Código do plugin: raiz deste repositório.
- Documentação pública: `readme.txt`.
- Workflow de CI: `.github/workflows/ci.yml`.
- Assets WordPress.org: `.wordpress-org/` (não distribuídos no ZIP).
- Releases: tags `v1.1.0`, `v1.1.1`, etc.
- Antes de publicação: revisar Plugin Check, testes reais, compatibilidade e licença.

## Configuração

Ative WooCommerce e Checkout Integrado InfinitePay. Em WooCommerce > Configurações > Pagamentos, habilite JESB InfinitePay e informe a InfiniteTag. Para links de pagamento de uso único/expiração, defina `JESB_INFINITEPAY_API_KEY` no servidor, nunca no repositório.

## Privacidade

O checkout transfere dados do comprador à InfinitePay para processar o pedido. Consulte a seção *External service* em `readme.txt`. Não coloque dados reais, tokens ou logs sensíveis em issues.

## Publicação

1. Criar repositório GitHub público com o slug `jesb-infinitepay-gateway`.
2. Publicar o código e testar o fluxo de CI.
3. Enviar pacote de submissão ao WordPress.org e aguardar análise.
4. Após aprovação e criação do SVN, configurar segredos GitHub `SVN_USERNAME` e `SVN_PASSWORD` (preferencialmente senha de aplicação WordPress.org), e só então habilitar o workflow de deploy.
5. Publicar tags/versionamentos revisados por release aprovada.

Este projeto não é afiliado oficialmente à InfinitePay. Licença GPL-2.0-or-later.
