#!/usr/bin/env bash
set -e

# Em containers Cloud Native Buildpacks (Dokploy / Pack / Heroku CNB), o launcher configura todo o ambiente PHP:
if [ -f "/cnb/lifecycle/launcher" ]; then
    if [ "$#" -eq 0 ]; then
        exec /cnb/lifecycle/launcher php artisan feeds:collect --sync --process
    else
        exec /cnb/lifecycle/launcher php artisan "$@"
    fi
fi

# Fallback para containers Herokuish clássicos
for f in /app/.profile.d/*.sh /workspace/.profile.d/*.sh; do
    if [ -f "$f" ]; then
        # shellcheck source=/dev/null
        . "$f"
    fi
done

if [ -d "/app/.heroku/php/bin" ]; then
    export PATH="/app/.heroku/php/bin:/app/.heroku/php/sbin:$PATH"
fi

if [ -d "/workspace/.heroku/php/bin" ]; then
    export PATH="/workspace/.heroku/php/bin:/workspace/.heroku/php/sbin:$PATH"
fi

if [ -d "/workspace" ]; then
    cd /workspace
elif [ -d "/app" ]; then
    cd /app
fi

if [ "$#" -eq 0 ]; then
    exec php artisan feeds:collect --sync --process
else
    exec php artisan "$@"
fi
