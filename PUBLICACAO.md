# Publicação do JESB InfinitePay Gateway

## 1 — GitHub (repositório de desenvolvimento)

Crie no GitHub, dentro do perfil `erysouzace`, um repositório público **vazio** chamado `jesb-infinitepay-gateway`, sem adicionar README/LICENSE automaticamente. Em um computador com Git instalado:

```bash
# Dentro da pasta jesb-infinitepay-gateway extraída do ZIP GitHub
git init -b main
git add .
git commit -m "chore: preparar release 1.1.0 candidata ao WordPress.org"
git remote add origin https://github.com/erysouzace/jesb-infinitepay-gateway.git
git push -u origin main
```

Não adicione API Keys, credenciais de clientes, dados pessoais nem arquivos `.env`. Habilite branch protection e revisão de pull requests para atualizações críticas.

## 2 — Homologação

- Executar Plugin Check em instalação WordPress com WooCommerce e revisar todos os erros/avisos;
- Rodar o validador oficial de readme do WordPress.org;
- Testar PHP 8.1–8.4, WooCommerce e WordPress suportados;
- Testar checkout clássico, Blocks, HPOS, Pix, cartão, falhas de API e webhooks falsificados;
- Confirmar política de privacidade e uso de marca InfinitePay;
- Revisar eventuais exigências da licença GPL em arquivos herdados de terceiros;
- Capturar screenshots **reais** e adicioná-las a `.wordpress-org/` antes do primeiro deploy;
- Verificar o identificador de contribuinte WordPress.org e substituir o valor em `readme.txt` se necessário.

## 3 — Submissão WordPress.org

Envie o ZIP candidato apenas depois da homologação para https://wordpress.org/plugins/developers/add/ . A submissão não publica o plugin automaticamente. A equipe fará revisão manual, podendo solicitar modificações. Não distribua o ZIP como versão estável sem essa etapa.

## 4 — Release e atualizações (após aprovação)

O repositório WordPress.org SVN terá o slug aprovado. O workflow `Deploy to WordPress.org (after approval)` é **manual**, exige aprovação do ambiente `wordpress-org` e só funciona em uma tag `vX.Y.Z`, com os segredos GitHub Actions `SVN_USERNAME` e `SVN_PASSWORD`.

Crie e envie uma tag somente depois de passar testes, revisão e atualizar os metadados de versão:

```bash
git tag -a v1.1.0 -m "Release 1.1.0"
git push origin v1.1.0
```

No GitHub, configure **Settings > Environments > New environment** com nome `wordpress-org`; exija revisão antes do deployment; configure `SVN_USERNAME` e `SVN_PASSWORD` em Actions Secrets; abra Actions e dispare o deploy selecionando a tag e digitando PUBLICAR. A infraestrutura WordPress.org disponibilizará a atualização depois da publicação SVN.

**Importante:** esta ação não cria a conta WordPress.org nem o repositório SVN, e não substitui a aprovação manual.
