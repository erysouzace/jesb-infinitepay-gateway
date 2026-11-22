# Relatório de validação — v1.1.1 (22/11/2026)

## O que foi executado neste ambiente

- `php -l` em todos os arquivos PHP: sucesso no PHP 8.4.24.
- `php tests/check-metadata.php`: metadados principais e stable tag sincronizados na versão 1.1.1.
- `php tests/payment-flow.php`: simulações sem chamadas externas com cenários de NSU desconhecido, identificadores malformados, concorrência de criação, expiração, reutilização de link ativo, alteração de valor, orçamento de retries, pedidos digitais/mistos, pagamento não aprovado, valor divergente, confirmação válida, callbacks duplicados, conflitos e webhook falsificado.
- Integridade do ZIP com `zipfile.testzip()`, verificando estrutura do diretório instalável e conteúdo da versão GitHub.

## O que NÃO foi possível validar aqui

- Instalação real do plugin em WordPress/WooCommerce com base de dados e HPOS; checkout clássico e Blocks no navegador.
- Pagamentos reais Pix/cartão, webhook e redirecionamento hospedado com a conta InfinitePay do lojista.
- Plugin Check oficial, PHPCS completo, testes end-to-end sob múltiplas versões de WordPress, WooCommerce, PHP e extensões de cache.
- Carga alta, casos de falha de infraestrutura, análise de segurança independente, certificação PCI DSS ou conformidade legal integral com a LGPD.

## Resultado e recomendação

**Candidato a homologação, NÃO certificação de produção.** Antes da distribuição como release estável, reproduzir a matriz de testes em uma loja de homologação com transações controladas e corrigir quaisquer divergências. Em especial: link vencido, retorno tardio, duas tentativas simultâneas, falha após pagamento, reembolso, ausência de identificadores e pedidos com frete/descontos.
