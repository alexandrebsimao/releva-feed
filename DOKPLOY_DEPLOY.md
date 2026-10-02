# Guia de Deploy no Dokploy (Heroku Buildpack)

Este guia orienta o deploy do **Releva Feed** no [Dokploy](https://dokploy.com) utilizando o **Build Type: Heroku Buildpack**.

---

## 🏗️ Estrutura de Arquivos para o Deploy

Os seguintes arquivos foram estruturados para viabilizar o deploy via buildpack:

- [Procfile](file:///Users/alexandreaugustobernssimao/Projects/releva-feed/Procfile): Define os processos `web`, `worker`, `scheduler` e a fase `release`.
- [nginx_app.conf](file:///Users/alexandreaugustobernssimao/Projects/releva-feed/nginx_app.conf): Configuração customizada do Nginx para o buildpack Heroku (headers de segurança, cache de assets do Vite, rotas do Livewire e roteamento do Laravel).
- [.buildpacks](file:///Users/alexandreaugustobernssimao/Projects/releva-feed/.buildpacks): Ordem de execução dos buildpacks (`heroku-buildpack-nodejs` para compilar os assets com Vite e `heroku-buildpack-php` para o backend PHP 8.3).
- [composer.json](file:///Users/alexandreaugustobernssimao/Projects/releva-feed/composer.json): Declara explicitamente as extensões PHP necessárias (`ext-pdo_pgsql`, `ext-bcmath`, `ext-intl`, `ext-pcntl`, `ext-zip`).
- [package.json](file:///Users/alexandreaugustobernssimao/Projects/releva-feed/package.json): Inclui o script `heroku-postbuild: "npm run build"`.
- [.env.dokploy.example](file:///Users/alexandreaugustobernssimao/Projects/releva-feed/.env.dokploy.example): Modelo com as variáveis de ambiente recomendadas para o Dokploy.

---

## 🚀 Passo a Passo no Painel do Dokploy

### 1. Criar o Banco de Dados PostgreSQL

1. No Dokploy, acesse a aba **Databases** e clique em **Create Database**.
2. Selecione **PostgreSQL** (versão 16 recomendada).
3. Defina um nome (ex: `releva-postgres`), usuário e senha.
4. Anote os dados de conexão interna gerados pelo Dokploy:
   - Host interno (ex: `releva-postgres` ou string completa)
   - Porta (padrão: `5432`)
   - Nome do banco, usuário e senha.
   *(Se preferir, o Dokploy também fornece a variável `DATABASE_URL` completa).*

---

### 2. Criar a Aplicação Principal (Web)

1. No Dokploy, vá em **Applications** -> **Create Application**.
2. Dê o nome: `releva-feed-web`.
3. Em **Source**, conecte o seu repositório Git/GitHub e escolha a branch `main`.
4. Em **Build Type**, selecione **Heroku Buildpacks**.
5. Em **General / Configuration**:
   - Dokploy e o `heroku-php-nginx` utilizam a variável dinâmica `$PORT`. Configure a porta do container como `5000` (ou a porta exposta pelo Dokploy).
6. Na aba **Environment**:
   - Copie as variáveis do arquivo [.env.dokploy.example](file:///Users/alexandreaugustobernssimao/Projects/releva-feed/.env.dokploy.example).
   - Adicione **`PORT=5000`** nas variáveis de ambiente para travar a porta e impedir que o Dokploy sorteie uma porta aleatória a cada deploy.
   - Gere o `APP_KEY` (se você já tiver gerado um para produção, utilize o mesmo; caso contrário, gere localmente com `php artisan key:generate --show`).
   - Configure as credenciais do PostgreSQL obtidas no Passo 1.
   - Adicione `NPM_CONFIG_PRODUCTION=false` para garantir que o Vite e o Tailwind sejam instalados e compilados no build.
7. Na aba **Domains**:
   - Adicione seu domínio público (ex: `releva.seudominio.com`) com **HTTPS (Let's Encrypt)** ativado.
8. Em **Deployments / Advanced**:
   - Adicione o comando de pós-deploy (Post-deployment command):
     ```bash
     php artisan migrate --force && php artisan db:seed --class=DatabaseSeeder --force && php artisan config:cache && php artisan route:cache && php artisan view:cache
     ```
9. Clique em **Deploy**.

---

### 3. Criar o Worker de Filas (Queue Worker)

O Releva Feed processa a coleta de feeds RSS, artigos e digests em segundo plano via fila do banco de dados.

1. No Dokploy, crie uma nova aplicação: `releva-feed-worker`.
2. Aponte para o **mesmo repositório Git** e mesma branch.
3. Selecione **Build Type**: `Heroku Buildpacks`.
4. Em **Environment**, configure as **mesmas variáveis de ambiente** da aplicação Web.
5. Em **Advanced / Run Command** (ou selecione o processo `worker` do `Procfile`):
   ```bash
   php artisan queue:work --queue=feeds,articles,digests,topics,default --sleep=3 --tries=3 --timeout=180 --no-interaction
   ```
6. **Não** configure domínio público para esta aplicação (ela roda apenas como processo de background).
7. Clique em **Deploy**.

---

### 4. Configurar o Scheduler (Agendamento de Tarefas)

O Laravel possui tarefas agendadas (verificação periódica de feeds a cada 6h, envio de digests diários, limpeza de retenção, etc.).

No Dokploy, você pode escolher **uma** das seguintes alternativas:

#### Alternativa A: Usar a aba Schedules / Cron do Dokploy (Recomendada)
1. Na aplicação `releva-feed-web` (ou `releva-feed-worker`), vá até a aba **Schedules** (ou **Cron Jobs**).
2. Adicione uma tarefa com a expressão:
   ```cron
   * * * * *
   ```
3. Comando a executar:
   ```bash
   php artisan schedule:run
   ```

#### Alternativa B: Criar um container dedicado para o Scheduler
1. Crie uma aplicação `releva-feed-scheduler` apontando para o mesmo repositório e variáveis.
2. Defina o comando de execução (processo `scheduler` do `Procfile`):
   ```bash
   php artisan schedule:work
   ```
3. Não adicione domínio e faça o deploy.

---

### 5. Primeira Coleta de Feeds (Opcional / Inicial)

Após a aplicação e o worker estarem ativos, você pode disparar a primeira coleta de notícias manualmente:

1. No Dokploy, acesse a aplicação `releva-feed-web`.
2. Vá até a aba **Terminal / Console**.
3. Execute:
   ```bash
   php artisan feeds:collect
   ```
4. Acompanhe o processamento na aba **Logs** da aplicação `releva-feed-worker`.
