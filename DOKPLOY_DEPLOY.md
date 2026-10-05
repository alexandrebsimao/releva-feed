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
1. Na aplicação `releva-feed-web`, vá até a aba **Schedules** (ou **Cron Jobs**).
2. Adicione uma tarefa com a expressão:
   ```cron
   */30 * * * *
   ```
   *(ou `0 * * * *` a cada hora)*
3. Comando a executar:
   > **Nota Importante:** Como o Dokploy executa o cron via `docker exec bash -c` em sessão não-interativa, a variável `$PATH` padrão não inclui o PHP instalado pelo buildpack do Heroku. Utilize o caminho absoluto do PHP ou o script `/app/cron.sh`:
   ```bash
   /app/.heroku/php/bin/php /app/artisan feeds:collect --sync --process
   ```
   *(Ou alternativamente: `bash /app/cron.sh`)*

#### Alternativa B: Criar um container dedicado para o Scheduler
1. Crie uma aplicação `releva-feed-scheduler` apontando para o mesmo repositório e variáveis.
2. Defina o comando de execução (processo `scheduler` do `Procfile`):
   ```bash
   php artisan schedule:work
   ```
3. Não adicione domínio e faça o deploy.

---

### 5. Primeira Coleta de Feeds (Opcional / Inicial)

Após a aplicação e o worker estarem ativos, você pode disparar a primeira coleta de notícias diretamente no terminal do Dokploy:

1. No Dokploy, acesse a aplicação `releva-feed-web`.
2. Vá até a aba **Terminal / Console**.
3. Para coletar e ver o resultado imediatamente na tela (modo síncrono):
   ```bash
   php artisan feeds:collect --sync
   ```
4. Para coletar via fila (assíncrono):
   ```bash
   php artisan feeds:collect
   ```

---

## 🔍 Diagnóstico: Notícias Não Estão Atualizando?

Se a página inicial não estiver exibindo notícias ou a atualização não acontecer, execute o comando de diagnóstico no terminal da aplicação `releva-feed-web`:

```bash
php artisan feeds:status
```

O comando verificará automaticamente:
- Se as fontes e feeds estão populados no banco (`feeds` e `sources`).
- Se existem jobs parados na tabela `jobs` do PostgreSQL (sintoma de worker parado).
- Se existem jobs com falha na tabela `failed_jobs`.
- Se os artigos estão como `pending`, `processing`, `failed` ou `processed`.
- Se a chave `GROQ_API_KEY` está preenchida nas variáveis de ambiente.

### Principais Causas e Soluções:

1. **O Worker de Filas (`releva-feed-worker`) não foi criado ou não está rodando**:
   - No Dokploy, apenas a aplicação Web é iniciada por padrão.
   - O Laravel salva os jobs na tabela `jobs` do PostgreSQL, mas **ninguém consome** se o container do worker não estiver ativo.
   - **Solução**: Crie a aplicação `releva-feed-worker` com o comando:
     ```bash
     php artisan queue:work --queue=feeds,articles,digests,topics,default --sleep=3 --tries=3 --timeout=180 --no-interaction
     ```

2. **O Agendador (Cron) não está ativo no Dokploy**:
   - Por padrão, a coleta automática roda a cada 6 horas (`routes/console.php`).
   - Se o cron do Dokploy não estiver chamando `php artisan schedule:run` a cada minuto, o agendamento nunca dispara.
   - **Solução**: Na aplicação `releva-feed-web`, acesse **Schedules / Cron Jobs** e crie a regra:
     - Expressão: `* * * * *`
     - Comando: `php artisan schedule:run`

3. **Banco de Dados não foi semeado (Tabela de Feeds Vazia)**:
   - Se o deploy foi feito sem executar o seeder, `feeds` estará vazio e nada será coletado.
   - **Solução**: No terminal da aplicação Web, execute:
     ```bash
     php artisan db:seed --class=DatabaseSeeder --force
     ```

4. **Chave `GROQ_API_KEY` ausente ou inválida**:
   - Os artigos são importados pelo feed, mas precisam ser classificados e sumarizados por IA para gerarem notícias e clusters na home.
   - Se `GROQ_API_KEY` estiver vazia ou com erro, os artigos ficam com status `failed`.
   - **Solução**: Preencha `GROQ_API_KEY` na aba **Environment** da aplicação Web e do Worker.

