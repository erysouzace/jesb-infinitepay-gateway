# Política de segurança — JESB InfinitePay Gateway

## Comunicação responsável

Não publique exploits funcionais, chaves, identificadores de pagamentos reais, comprovantes ou dados de clientes em Issues públicas. Abra uma **GitHub Security Advisory privada** em **Security → Advisories → Report a vulnerability**, se o recurso estiver disponível no repositório. Se não estiver, procure o contato oficial disponibilizado no site https://jesbdigital.com.br para combinar um canal privado.

Informe, sem dados pessoais reais: versão do plugin, versões PHP/WordPress/WooCommerce, impacto, passos mínimos de reprodução e sugestões de mitigação. Não existe garantia de prazo de resposta ou programa de recompensa.

## Modelo de segurança

- O checkout de cartão é hospedado na InfinitePay; o plugin não recebe nem armazena PAN/CVV.
- O webhook não é assinado: o plugin **não confia** no payload para marcar pedidos como pagos. Valida `order_nsu`, `transaction_nsu`, `slug` e consulta a API `payment_check`.
- O valor em centavos é comparado ao valor do pedido e à referência persistida; divergências não aprovam o pedido.
- Há controle de concorrência na criação e confirmação de cobranças.
- Falhas transitórias recebem tentativas limitadas **somente se houver identificadores válidos**. Sem eles a conciliação exige ação humana.
- Links expirados são bloqueados para nova emissão automática, porque não existe endpoint documentado de cancelamento.
- Logs opcionais não incluem credenciais, URLs de pagamentos, nome, e-mail, telefone ou dados de cartão.
- A chave de API opcional deve ficar fora do Git, em `wp-config.php` ou no ambiente protegido do servidor.

## Riscos residuais

Um link criado sem API Key não expira e pode permitir múltiplos pagamentos conforme configuração do serviço. Para lojas comerciais, habilite a chave com links `single_payment` e expiração. Mesmo assim, não existe garantia de cancelamento ou consulta de todos os pagamentos pendentes sem identificadores recebidos. É obrigação operacional do lojista conferir transações divergentes no painel InfinitePay antes de criar nova cobrança ou entregar mercadorias.

## Privacidade e LGPD

A loja é responsável por informar sua política de privacidade, o repasse de dados de contato/endereço para a InfinitePay, as bases legais, o prazo de retenção e o atendimento a solicitações de titulares. Os identificadores e estados de transação permanecem no WooCommerce segundo sua retenção; não há telemetria enviada à JESB DIGITAL. Este documento não constitui certificação LGPD, PCI DSS nem parecer jurídico.
