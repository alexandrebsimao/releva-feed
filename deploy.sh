#!/usr/bin/env bash

set -Eeuo pipefail

readonly REPO_ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly COMPOSE_FILE="${COMPOSE_FILE:-docker-compose-production.yml}"
readonly ENV_FILE="${ENV_FILE:-.env.production}"
readonly DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
readonly PROXY_NETWORK="${PROXY_NETWORK:-proxy}"
readonly HEALTH_TIMEOUT="${DEPLOY_HEALTH_TIMEOUT:-180}"

cd "$REPO_ROOT"

compose=(docker compose --env-file "$ENV_FILE" -f "$COMPOSE_FILE")

log() {
    printf '[deploy] %s\n' "$*"
}

fail() {
    printf '[deploy] ERRO: %s\n' "$*" >&2
    exit 1
}

on_error() {
    local status=$?

    printf '[deploy] Falha no deploy (código %s).\n' "$status" >&2
    "${compose[@]}" ps 2>/dev/null || true

    exit "$status"
}

trap on_error ERR

usage() {
    cat <<'HELP'
Uso: ./deploy.sh [opções]

Opções:
  --collect       Dispara a coleta de feeds após o deploy.
  --no-cache      Recria as imagens sem usar o cache do Docker.
  --cached        Permite usar o cache do Docker durante o build.
  -h, --help      Mostra esta ajuda.

Variáveis opcionais:
  DEPLOY_BRANCH          Branch a atualizar (padrão: main)
  DEPLOY_HEALTH_TIMEOUT  Tempo máximo de espera por saúde, em segundos (180)
  PROXY_NETWORK          Rede externa do Traefik (proxy)
  DEPLOY_NO_CACHE        1 para build sem cache, 0 para usar cache (1)
HELP
}

collect_feeds=0
no_cache="${DEPLOY_NO_CACHE:-1}"

while (($# > 0)); do
    case "$1" in
        --collect)
            collect_feeds=1
            ;;
        --no-cache)
            no_cache=1
            ;;
        --cached)
            no_cache=0
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            usage >&2
            fail "Opção desconhecida: $1"
            ;;
    esac
    shift
done

command -v git >/dev/null 2>&1 || fail 'git não está instalado.'
command -v docker >/dev/null 2>&1 || fail 'Docker não está instalado.'
docker compose version >/dev/null 2>&1 || fail 'Docker Compose v2 não está disponível.'

[[ -f "$COMPOSE_FILE" ]] || fail "Arquivo Compose não encontrado: $COMPOSE_FILE"
[[ -f "$ENV_FILE" ]] || fail "Arquivo de ambiente não encontrado: $ENV_FILE"

if [[ -n "$(git status --porcelain)" ]]; then
    fail 'A cópia de produção possui alterações locais. Faça commit, stash ou descarte-as antes do deploy.'
fi

log "Atualizando a branch $DEPLOY_BRANCH..."
git pull --ff-only origin "$DEPLOY_BRANCH"

[[ -f "$COMPOSE_FILE" ]] || fail "O pull não trouxe o arquivo Compose: $COMPOSE_FILE"
[[ -f "$ENV_FILE" ]] || fail "O arquivo de ambiente não existe após o pull: $ENV_FILE"

grep -Eq '^APP_KEY=[^[:space:]]' "$ENV_FILE" || fail "APP_KEY não configurada em $ENV_FILE"
grep -Eq '^APP_HOST=[^[:space:]]' "$ENV_FILE" || fail "APP_HOST não configurada em $ENV_FILE"
grep -Eq '^DB_PASSWORD=[^[:space:]]' "$ENV_FILE" || fail "DB_PASSWORD não configurada em $ENV_FILE"

log 'Validando a configuração do Docker Compose...'
"${compose[@]}" config --quiet

if ! docker network inspect "$PROXY_NETWORK" >/dev/null 2>&1; then
    log "Criando a rede externa $PROXY_NETWORK..."
    docker network create "$PROXY_NETWORK" >/dev/null
fi

build_args=(build --pull)
if [[ "$no_cache" == '1' ]]; then
    build_args+=(--no-cache)
fi

log 'Construindo as imagens de produção e os assets...'
"${compose[@]}" "${build_args[@]}"

wait_for_health() {
    local service="$1"
    local elapsed=0
    local container=''
    local health=''

    log "Aguardando o serviço $service ficar saudável..."

    while ((elapsed < HEALTH_TIMEOUT)); do
        container=$("${compose[@]}" ps -q "$service" 2>/dev/null || true)

        if [[ -n "$container" ]]; then
            health=$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}no-healthcheck{{end}}' "$container" 2>/dev/null || true)

            case "$health" in
                healthy|no-healthcheck)
                    return 0
                    ;;
                unhealthy)
                    fail "O serviço $service ficou unhealthy."
                    ;;
            esac
        fi

        sleep 2
        elapsed=$((elapsed + 2))
    done

    fail "Tempo esgotado aguardando o serviço $service."
}

log 'Iniciando o PostgreSQL...'
"${compose[@]}" up -d postgres
wait_for_health postgres

log 'Aplicando migrations...'
"${compose[@]}" run --rm app php artisan migrate --force

log 'Atualizando os catálogos de fontes e assuntos...'
"${compose[@]}" run --rm app php artisan db:seed --class=DatabaseSeeder --force

log 'Subindo aplicação, web, worker e scheduler...'
"${compose[@]}" up -d --force-recreate app web queue scheduler
wait_for_health app

log 'Gerando o cache de configuração...'
"${compose[@]}" exec -T app php artisan config:cache

log 'Validando que app e web usam o mesmo manifest de assets...'
app_manifest=$("${compose[@]}" exec -T app sha256sum public/build/manifest.json | awk '{print $1}')
web_manifest=$("${compose[@]}" exec -T web sha256sum /var/www/html/public/build/manifest.json | awk '{print $1}')
[[ -n "$app_manifest" && "$app_manifest" == "$web_manifest" ]] || fail 'Os manifests de assets entre app e web são diferentes.'

log 'Reiniciando processos persistentes para carregar a configuração atual...'
"${compose[@]}" restart app queue scheduler

if ((collect_feeds == 1)); then
    log 'Disparando a coleta de feeds...'
    "${compose[@]}" exec -T app php artisan feeds:collect
fi

log 'Deploy concluído.'
"${compose[@]}" ps
