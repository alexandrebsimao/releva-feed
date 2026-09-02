# Releva Feed

Fundação de uma aplicação de curadoria pessoal de notícias. O projeto usa Laravel 13, PHP 8.3, PostgreSQL 16, Livewire 4, Tailwind CSS 4 e Vite.

## Pré-requisitos

- Docker Desktop com o daemon em execução;
- Docker Compose v2 (o comando usado é `docker compose`).

PHP, Composer, Node e PostgreSQL não precisam estar instalados na máquina: são executados pelos serviços Docker.

## Desenvolvimento

Na primeira execução, copie o ambiente de exemplo e ajuste os valores locais se necessário:

```bash
cp .env.example .env
docker compose up --build
```

A aplicação estará disponível em <http://localhost>. O Mailpit fica em <http://localhost:8025> e o Vite em `http://localhost:5173`.

Se a porta 80 já estiver ocupada na máquina, inicie o Nginx em outra porta, por exemplo: `APP_PORT=8080 docker compose up -d`.

Comandos úteis:

```bash
# Executar em segundo plano
docker compose up -d --build

# Acompanhar logs
docker compose logs -f app queue scheduler frontend

# Executar Artisan
docker compose exec app php artisan about
docker compose exec app php artisan migrate:status

# Rodar testes
docker compose run --rm app php artisan test

# Parar os serviços (mantém os dados do PostgreSQL)
docker compose down

# Recriar também o banco local
docker compose down -v
```

O container `app` espera o PostgreSQL ficar saudável e aplica as migrations antes de iniciar o PHP-FPM. `queue` executa o worker da fila baseada em banco, `scheduler` mantém o planejador do Laravel ativo e `frontend` instala as dependências NPM e inicia o Vite com HMR.

## Produção

Configure as variáveis de produção antes de iniciar os containers:

- `APP_ENV=production`;
- `APP_DEBUG=false`;
- `APP_URL` com o domínio público;
- `APP_KEY` gerada uma única vez e armazenada como segredo;
- credenciais do PostgreSQL;
- `QUEUE_CONNECTION=database`;
- `AI_PROVIDER=groq`, `GROQ_API_KEY`, `AI_CLASSIFIER_MODEL` e `AI_SUMMARIZER_MODEL`;
- SMTP de produção (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` e `MAIL_FROM_ADDRESS`).

Não execute `key:generate` a cada deploy: trocar a chave invalida sessões e dados criptografados existentes.

Suba a aplicação, o worker e o scheduler usando a configuração de produção:

```bash
docker compose up -d app queue scheduler
```

Se as migrations não forem executadas pelo entrypoint do ambiente, aplique-as manualmente:

```bash
docker compose exec app php artisan migrate --force
```

Cadastre ou atualize o catálogo inicial de fontes e feeds. O seeder é idempotente:

```bash
docker compose exec app php artisan db:seed --class=SourceCatalogSeeder --force
```

Para importar imediatamente os artigos, dispare a coleta:

```bash
docker compose exec app php artisan feeds:collect
```

Esse comando apenas coloca jobs na fila. O serviço `queue` coleta os feeds e depois processa os artigos. Acompanhe a execução com:

```bash
docker compose logs -f queue
```

Consulte jobs que falharam com:

```bash
docker compose exec app php artisan queue:failed
```

O serviço `scheduler` dispara novas coletas automaticamente a cada seis horas. Em uma infraestrutura sem o container `scheduler`, configure um cron para executar `php artisan schedule:run` a cada minuto. Não mantenha dois schedulers ou workers concorrentes para a mesma finalidade sem necessidade.

## Autenticação

O login está disponível em `/login` e o dashboard privado em `/dashboard`. Não há rota nem tela de cadastro público nesta versão. Para criar o primeiro usuário local, use o Tinker dentro do container:

```bash
docker compose exec app php artisan tinker
```

```php
App\Models\User::create([
    'name' => 'Seu nome',
    'email' => 'voce@example.com',
    'password' => 'troque-esta-senha',
]);
```

No primeiro provisionamento de produção, gere o `APP_KEY` com `docker compose exec app php artisan key:generate` e armazene-o como segredo. Não reutilize as credenciais padrão do PostgreSQL.

## Escopo desta fundação

Já estão preparados:

- homepage pública e layout público;
- login por sessão, logout, proteção de rotas, limitação básica de tentativas e layout autenticado;
- dashboard privado com um componente Livewire demonstrativo;
- PostgreSQL, cache, sessões e fila usando tabelas do banco;
- coleta RSS/Atom pelo comando `feeds:collect`, agendado a cada seis horas;
- Docker para PHP-FPM, Nginx, PostgreSQL, worker, scheduler, Vite e Mailpit.

Classificação de assuntos, agrupamento de acontecimentos, IA e e-mail diário ficam deliberadamente para as próximas etapas.

## Sources e feeds

Depois de entrar, acesse `/sources` para listar as fontes cadastradas, criar uma fonte ou editar uma existente. A edição de cada fonte também lista seus feeds e permite adicionar, editar ou remover cada um deles.

Feeds aceitam somente URLs válidas com os esquemas `http` ou `https`. A listagem de fontes mostra prioridade, quantidade de feeds, status e a última coleta bem sucedida.

## Importação RSS

O comando abaixo despacha os feeds ativos de fontes ativas para a fila `feeds`. Cada feed é processado por um job individual, interpreta RSS 2.0/Atom e importa apenas artigos novos:

```bash
docker compose exec app php artisan feeds:collect
```

O comando também é disparado automaticamente pelo scheduler a cada seis horas, sem sobreposição. `DispatchFeedFetchesJob` encontra os feeds elegíveis e `FetchFeedJob` processa um feed por vez, com três tentativas e backoff. Falhas atualizam `last_fetched_at`, `failure_count` e `last_error` no feed; sucessos atualizam `last_success_at` e zeram o contador de falhas. A deduplicação ocorre por GUID, URL canônica/URL e hash do conteúdo, sem alterar artigos já importados.

Os artigos importados podem ser visualizados em `/articles`. Os testes usam XML local em `tests/Fixtures` e não fazem requisições à internet.

Os indicadores de saúde ficam disponíveis na listagem de fontes e na edição de cada fonte: última tentativa, último sucesso, falhas acumuladas e erro mais recente por feed.

## Retenção de artigos

Artigos com `published_at` anterior a 15 dias são removidos automaticamente todos os dias às 04:15 pelo scheduler. Clusters que ficarem sem artigos também são removidos, assim como seus vínculos em pivots. O cache da homepage é invalidado quando há remoções.

O período pode ser alterado pela variável `ARTICLE_RETENTION_DAYS` no ambiente. Para executar a limpeza manualmente:

```bash
docker compose exec app php artisan articles:prune
```

## Topics e aliases

A área autenticada `/topics` organiza assuntos por status: aprovados, sugeridos e ignorados. É possível criar, editar, aprovar e ignorar assuntos, além de cadastrar aliases como `IA`, `AI` e `Artificial Intelligence`.

Aliases são normalizados sem acentos, em minúsculas e com espaços consistentes. O vínculo entre artigos e assuntos fica na pivot `article_topic`, que preserva `confidence` e `detected_by` para a futura classificação automática, sem executar IA nesta etapa.

## Preferências individuais

Em `/my-topics`, cada usuário pode acompanhar ou excluir assuntos, remover uma preferência, pesquisar assuntos disponíveis e definir sua prioridade (baixa, normal ou alta). Uma notícia só entra no radar quando possui um assunto acompanhado; se também possuir qualquer assunto excluído pelo mesmo usuário, a exclusão tem precedência. Essa regra fica centralizada em `UserTopicPreferenceService` e também é usada pelo dashboard.

## Decisões de versão

Laravel 13 é a linha atual no momento desta fundação e requer PHP 8.3–8.5. O Livewire 4 é usado por ser a linha atual compatível com Laravel moderno; o Tailwind 4 é integrado pelo plugin oficial do Vite. O container usa PHP 8.3 por ser o primeiro PHP suportado pelo Laravel 13, PostgreSQL 16 e Node 22 LTS.
