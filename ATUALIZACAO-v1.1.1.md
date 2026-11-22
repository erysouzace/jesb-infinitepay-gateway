# Atualização v1.1.0 → v1.1.1

## Antes de atualizar

1. Verifique que a hospedagem usa **PHP 8.2+** e que WordPress/WooCommerce estão atualizados. O requisito mínimo foi elevado.
2. Faça backup do banco WooCommerce, WordPress e arquivos. Preserve credenciais no servidor.
3. Não substitua um checkout recebendo pagamentos sem janela de manutenção. Confira no painel InfinitePay os pedidos pendentes e links já criados.
4. Use uma loja de homologação primeiro. Não confunda aprovação do pagamento com dinheiro disponível no saldo bancário.

## Atualização do plugin

1. WordPress → Plugins → Adicionar novo → Enviar plugin.
2. Envie `JESB-InfinitePay-Gateway-v1.1.1-wordpress.zip` e confirme **Substituir versão atual**.
3. WooCommerce → Configurações → Pagamentos → JESB InfinitePay Gateway: confira InfiniteTag, BRL, HTTPS e as URLs.
4. WooCommerce → JESB InfinitePay: teste períodos de 7, 30 e 90 dias.
5. WooCommerce → Status → Ações agendadas: consulte ações no grupo `jesb-infinitepay` quando houver falhas transitórias de confirmação.
6. Faça compras reais de baixo valor em ambiente controlado: Pix e cartão, retorno sem webhook, webhook sem retorno, callbacks repetidos, pedido físico e digital, concorrência e erro de API.
7. Revise logs opcionais; nenhum pedido deve ser marcado como pago sem resposta `paid: true` da API e valor íntegro.

## Como distribuir no GitHub

1. Extraia `JESB-InfinitePay-Gateway-GitHub-v1.1.1.zip` em uma pasta **separada** e copie seus arquivos para o clone Git existente. Preserve `.git`.
2. Faça `git status` e revise alterações; execute os testes.
3. Crie commit na branch `main` (ou PR com revisão).
4. Somente após os testes, crie tag `v1.1.1` **no novo commit**, nunca na `v1.1.0`.
5. GitHub → Releases → Draft a new release → tag `v1.1.1` → anexe o ZIP WordPress → marque **Pre-release** enquanto não houver homologação real.
6. Não execute deploy para WordPress.org antes de aprovação do diretório.

## Limitações conscientes

Quando um link expira, o plugin **não cria outro automaticamente**: o serviço não oferece cancelamento e a cobrança anterior pode exigir conferência. Quando o webhook e a página de retorno não fornecem os IDs necessários, a API não permite descobrir uma transação apenas pelo pedido. O administrador deve conciliar no painel InfinitePay.

A v1.1.1 não inclui um ambiente de teste completo do WordPress nem certificação LGPD/PCI; os testes mockados validam a lógica sob condições controladas.
