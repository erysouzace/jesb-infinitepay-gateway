# Homologação da v1.1.2

Origem: relatório Plugin Check da homologação em 22/11/2026.

## Correções do relatório

- `WordPress.WP.I18n.MissingTranslatorsComment` (3): incluídos comentários para os tradutores.
- `WordPress.WP.I18n.UnorderedPlaceholdersText` (1): `%1$s` e `%2$s`.
- `missing_readme_header_tested` (1): `Tested up to: 7.1`, conforme versão principal observada no ambiente informado. Esse campo **não equivale a homologação integral**.
- `load_plugin_textdomainFound` (1): removido carregamento manual; tradução distribuída no WordPress.org é carregada automaticamente.
- `NonceVerification.Recommended` administrativo (2): nonce com verificação, sem mutações no filtro.
- `NonceVerification.Recommended` retorno externo (8): supressão documentada de falso positivo; o provedor externo não tem nonce WordPress. O código continua verificando a chave do pedido e, em seguida, consulta a API de pagamento. **Não remova essa verificação ou exija nonce nesse retorno.**

## Ainda obrigatório

1. Instalar a v1.1.2 em homologação com PHP 8.2+, WooCommerce e WordPress.
2. Executar novamente Plugin Check com todas as categorias e anexar o JSON.
3. Executar testes reais Pix/cartão, checkout clássico e Blocks, HPOS, duplicidades, expiração e falhas de conectividade.
4. Revisar acessibilidade, retenção de dados, privacidade e possíveis vulnerabilidades com uma auditoria independente.
5. Confirmar a versão WordPress testada e ajustar `Tested up to` antes da submissão oficial se necessário.

Não há alegação de segurança absoluta ou homologação do processamento financeiro baseada apenas em lint e testes mockados.
