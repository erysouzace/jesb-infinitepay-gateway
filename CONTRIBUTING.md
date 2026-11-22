# Contribuindo para o JESB InfinitePay Gateway

Obrigado por colaborar! Use Issues para bugs não sensíveis e pull requests pequenos. Reporte vulnerabilidades apenas pelo canal privado descrito em [SECURITY.md](SECURITY.md).

1. Crie uma branch a partir de `main` (ex.: `fix/retry-webhook`).
2. Não use credenciais ou dados reais em testes. Não inclua `.env`, logs ou dumps.
3. Execute `php tests/check-metadata.php` e `php tests/payment-flow.php` e valide sintaxe de todos os arquivos PHP.
4. Teste no WordPress + WooCommerce em uma loja de homologação com HPOS e checkout clássico/blocos.
5. Abra o pull request informando impacto, riscos, rollback e testes realizados.

Toda alteração de fluxo financeiro precisa preservar a validação server-side do `payment_check` e a idempotência. Novos recursos não devem criar aprovações apenas com base em webhook/retorno. O projeto usa GPL-2.0-or-later. Evite introduzir dependências incompatíveis.
