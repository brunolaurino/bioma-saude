#!/usr/bin/env bash
# Constrói as imagens e sobe o ambiente. Uso:  bash scripts/subir.sh
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f .env ] || { echo "Falta o arquivo .env. Copie .env.example para .env e preencha DOMAIN e ACME_EMAIL." >&2; exit 1; }
[ -s secrets/app_key ] || bash scripts/gerar-segredos.sh

docker compose build
docker compose up -d
echo "Aguardando o app ficar pronto..."
for _ in $(seq 1 60); do
  if docker compose exec -T app php artisan bioma:auditoria >/dev/null 2>&1; then
    . ./.env
    echo "No ar: https://${DOMAIN}"
    docker compose exec -T app php artisan bioma:modulos
    exit 0
  fi
  sleep 3
done
echo "O app não respondeu. Veja: docker compose logs app" >&2
exit 1
