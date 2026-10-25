#!/usr/bin/env bash
# Gera as senhas e a chave do Laravel em ./secrets, legíveis só pelo dono.
# Rode uma vez. Rodar de novo NÃO sobrescreve segredos que já existem.
set -euo pipefail
cd "$(dirname "$0")/.."
umask 077
mkdir -p secrets

gerar() { [ -s "secrets/$1" ] && echo "secrets/$1 já existe, mantido" || { printf '%s' "$2" > "secrets/$1"; echo "secrets/$1 criado"; }; }

gerar app_key "base64:$(openssl rand -base64 32)"
gerar db_password "$(openssl rand -hex 24)"
gerar db_root_password "$(openssl rand -hex 24)"
gerar redis_password "$(openssl rand -hex 24)"
# Chaves de cifra dos dados de saúde. Sem elas os dados ficam ilegíveis: guarde cópia segura.
gerar bioma_kek "1:$(openssl rand -base64 32)"
gerar bioma_index_key "$(openssl rand -base64 32)"

# A pasta fica fechada (700) para outros usuários do servidor. Os arquivos ficam 644 porque o
# Docker os monta direto no container, onde o app roda como www-data e sem privilégios de root.
chmod 700 secrets
chmod 644 secrets/*
echo "Pronto. Guarde uma cópia de secrets/ fora do servidor (gerenciador de senhas)."
