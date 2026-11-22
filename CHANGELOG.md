# Histórico de alterações

## 1.1.1 — Security & Quality (candidata a homologação)

- Reutilização apenas de links não expirados; cobrança vencida exige conciliação antes de eventual nova emissão.
- Lock de criação por pedido e segunda verificação do status após retorno da API, evitando reverter pedidos pagos para pendentes.
- Reconciliação via Action Scheduler, com no máximo seis tentativas e backoff, quando a InfinitePay já forneceu `transaction_nsu` e `slug`.
- Validação rigorosa de identificadores, moeda, valores em centavos e idempotência por transação.
- Financeiro com filtros de 7/30/90 dias, indicadores de pendências, sinalização de conciliação manual e limite explícito de 2.000 pedidos por consulta.
- Licença GPLv2 completa, documentação de segurança, contribuição e templates para relatar bugs.
- Regressões mockadas no CI. Requisito mínimo de PHP elevado para 8.2.

**Limitações:** sem consulta de saldo, extrato ou taxa real pela API pública; sem cancelamento automático de link; sem descoberta de transações sem NSU/slug. Validação em WordPress real e pagamento controlado ainda necessária.

## 1.1.0
- Painel administrativo read-only inicial e preparação para o diretório WordPress.org.

## 1.0.2
- Pré-preenchimento de dados do cliente e identificação de produtos.

## 1.0.1
- Melhorias para Checkout Blocks e diagnóstico de configuração.

## 1.0.0
- Integração inicial com checkout hospedado, webhooks e `payment_check`.
