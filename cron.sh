#!/usr/bin/env bash
set -e

# Carrega todas as variáveis de ambiente e caminhos dos buildpacks do Heroku
for f in /app/.profile.d/*.sh; do
    if [ -f "$f" ]; then
        # shellcheck source=/dev/null
        . "$f"
    fi
done

# Garante o binário do PHP no PATH do container
if [ -d "/app/.heroku/php/bin" ]; then
    export PATH="/app/.heroku/php/bin:/app/.heroku/php/sbin:$PATH"
fi

cd /app

if [ "$#" -eq 0 ]; then
    exec php /app/artisan feeds:collect --sync --process
else
    exec php /app/artisan "$@"
fi
