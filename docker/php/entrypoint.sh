#!/bin/sh
# Lê os segredos montados pelo Docker em /run/secrets e entrega ao Laravel como variáveis.
# Assim nenhuma senha fica em compose.yaml, em .env ou no histórico do Git.
set -eu

read_secret() { [ -f "/run/secrets/$1" ] && cat "/run/secrets/$1" || { echo "segredo $1 ausente" >&2; exit 1; }; }

export APP_KEY="$(read_secret app_key)"
export DB_PASSWORD="$(read_secret db_password)"
export REDIS_PASSWORD="$(read_secret redis_password)"
# Chave mestra que cifra as chaves de cada paciente (formato "1:base64") e chave do índice cego.
# Em produção a KEK deve vir do KMS do provedor; este arquivo é o adaptador local.
export BIOMA_KEK="$(read_secret bioma_kek)"
export BIOMA_INDEX_KEY="$(read_secret bioma_index_key)"
# Senha do SMTP é opcional: crie secrets/mail_password e adicione o segredo no compose se usar.
[ -f /run/secrets/mail_password ] && export MAIL_PASSWORD="$(cat /run/secrets/mail_password)"

php artisan config:cache >/dev/null
php artisan route:cache >/dev/null
php artisan view:cache >/dev/null

# Só o container principal migra; o worker apenas espera o banco ficar pronto.
if [ "${BIOMA_ROLE:-app}" = "app" ]; then
  php artisan migrate --force
  php artisan db:seed --force
fi

# Contêiner do agendador: lembretes, limpeza diária e conferência da auditoria.
if [ "${BIOMA_ROLE:-app}" = "scheduler" ]; then
  exec php artisan schedule:work
fi

exec "$@"
