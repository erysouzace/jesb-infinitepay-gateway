# Publicação do JESB InfinitePay Gateway no WordPress.org

> Guia de manutenção do repositório existente `erysouzace/jesb-infinitepay-gateway`. Não inicialize outro repositório Git, não recrie `main` e não regrave tags já publicadas.

## 1. Pré-requisitos e critérios de aceitação

- WordPress >= 6.4, PHP >= 8.2, WooCommerce ativo, moeda BRL e HTTPS.
- Homologar Checkout clássico e Checkout Blocks, Pix e cartão, pedidos digitais, físicos e mistos, retorno e webhook; verificar pedido, notas e log após o pagamento.
- Executar os testes automatizados em PHP 8.2, 8.3 e 8.4 (matriz do CI) e conferir o status dos workflows do GitHub.
- Executar **Plugin Check** no plugin efetivamente empacotado; investigar erros e avisos. Os quatro avisos de consultas SQL diretas relativas a `GET_LOCK` e `RELEASE_LOCK` são conhecidos, mas sua justificativa não garante aceitação pela equipe de revisão.
- Validar o `readme.txt` no validador oficial: https://wordpress.org/plugins/developers/readme-validator/ . Confirmar que o valor `Tested up to` corresponde a versão realmente testada e que `Contributors` usa conta existente no WordPress.org.
- Revisar LICENSE (GPLv2 ou posterior), `readme.txt`, código-fonte, dependências, marca e declaração de serviço externo. Incluir termos e privacidade da InfinitePay, sem afirmar afiliação oficial.
- Gerar o ZIP **instalável** com a pasta raiz `jesb-infinitepay-gateway/`, sem `tests/`, `.github/`, `.git/`, segredos ou dados de clientes. Verificar o ZIP final, não somente a cópia no Git.
- Fazer backup dos arquivos e banco do WordPress antes de instalar qualquer atualização.

## 2. Fluxo do repositório GitHub

O projeto já existe: https://github.com/erysouzace/jesb-infinitepay-gateway

Use branches/PRs para alterações críticas; revise `git diff` e execute os testes. Não armazene chaves de API, `.env`, credenciais nem dados de compradores no GitHub.

Para conferir o repositório local no Windows/PowerShell:

```powershell
cd C:\Projetos\jesb-infinitepay-gateway
git status
git pull --ff-only origin main
php tests/check-metadata.php
php tests/payment-flow.php
php tests/plugin-check-regressions.php
```

Uma alteração documental na branch `main` **não modifica** as tags existentes. Caso os documentos alterados sejam incluídos em um ZIP distribuído, prepare um novo commit, atualize o número de versão em **todos** os metadados aplicáveis, execute os testes e crie uma tag nova; não substitua silenciosamente os arquivos anexados à release `v1.1.3`.

## 3. Submissão inicial ao diretório WordPress.org

1. Confirme que possui conta em https://wordpress.org/ e que o contribuinte indicado no `readme.txt` é uma conta válida do WordPress.org.
2. Envie o ZIP instalável completo no portal https://wordpress.org/plugins/developers/add/ .
3. Aguarde o processo de revisão; o envio **não publica automaticamente** o plugin e não garante aprovação ou o slug solicitado.
4. Se a equipe solicitar correções, aplique-as no código, reexecute os testes e envie o pacote atualizado pelo fluxo orientado pelos revisores.
5. Apenas após a aprovação, use o slug e o repositório SVN realmente atribuídos pelo WordPress.org.

Antes da aprovação, **não execute** o workflow de deploy ao SVN e não configure seu uso como atualização automática de lojas.

## 4. Releases rastreáveis

- Sincronize `Version:` no PHP, `JESB_IPG_VERSION`, `Stable tag:` e a seção do changelog no `readme.txt` antes de uma nova release.
- Revise a lista exata de arquivos do pacote, execute o Plugin Check nele e confirme o GitHub Actions em verde.
- Crie uma tag imutável para o commit testado; confira o SHA e o hash SHA-256 do ZIP para rastrear os arquivos distribuídos.
- Anexe à release o ZIP instalável correspondente **à mesma revisão de código e documentos**.
- Mantenha o histórico de releases anteriores.

## 5. Publicação e atualizações no SVN (somente após aprovação)

O projeto contém `.github/workflows/deploy-wordpress-org.yml`, acionado **manualmente** pela tag e com confirmação `PUBLICAR` no ambiente `wordpress-org`.

Antes do primeiro uso:

1. Confirme que o slug aprovado é `jesb-infinitepay-gateway`. Se for diferente, atualize e teste o valor `SLUG` no workflow.
2. Configure `Settings > Environments > wordpress-org` no GitHub, com as proteções/revisores apropriados.
3. Configure `SVN_USERNAME` e `SVN_PASSWORD` como **secrets** acessíveis ao workflow, nunca nos arquivos versionados.
4. Revise a versão das ações do workflow, o conteúdo do pacote gerado e os assets da página do plugin.
5. Crie uma tag nova após a validação, selecione essa tag no disparo manual e confirme `PUBLICAR` somente após conferir o destino.
6. Verifique a publicação no SVN e o download gerado pelo WordPress.org.

Assets de diretório (ícone, banner e screenshots) devem ser imagens reais e licenciadas adequadamente; os recursos do WordPress.org são publicados separadamente em `assets/` do SVN quando o projeto for aprovado.

Documentação: https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/ ; https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/ ; https://developer.wordpress.org/plugins/wordpress-org/common-issues/ .

## 6. Política operacional

O plugin é independente da InfinitePay. O serviço externo recebe os dados mínimos de itens e contato/endereço necessários à operação; não recebe dados de cartão dentro do WordPress. Consulte o [readme.txt](readme.txt) e [SECURITY.md](SECURITY.md). Não publique relatórios que contenham identificadores reais, endereços, dados de clientes ou segredos.

A aprovação do CI ou do Plugin Check **não** assegura ausência de falhas nem aprovação pelo diretório. Testes de concorrência MySQL e pagamentos reais realizados na homologação são evidências para as condições testadas, não uma garantia universal.
