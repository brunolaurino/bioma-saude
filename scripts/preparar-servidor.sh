#!/usr/bin/env bash
# Prepara um servidor Ubuntu 24.04 novo para o ambiente de teste.
# Uso, já conectado por SSH:  sudo bash scripts/preparar-servidor.sh
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then echo "Rode com sudo." >&2; exit 1; fi
. /etc/os-release
[ "${ID:-}" = "ubuntu" ] || echo "Aviso: testado só no Ubuntu 24.04; seguindo mesmo assim."

echo "==> Atualizando o sistema"
export DEBIAN_FRONTEND=noninteractive
apt-get update -q && apt-get upgrade -yq
apt-get install -yq ca-certificates curl ufw fail2ban unattended-upgrades openssl

echo "==> Atualizações automáticas de segurança"
dpkg-reconfigure -f noninteractive unattended-upgrades

echo "==> Firewall: só SSH, HTTP e HTTPS"
ufw default deny incoming
ufw default allow outgoing
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 443/udp
ufw --force enable

echo "==> SSH só com chave, sem senha e sem login direto de root"
cat > /etc/ssh/sshd_config.d/10-bioma.conf <<'CONF'
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitRootLogin no
CONF
sshd -t && systemctl reload ssh

echo "==> fail2ban protegendo o SSH"
systemctl enable --now fail2ban

if ! command -v docker >/dev/null; then
  echo "==> Instalando Docker"
  install -m 0755 -d /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
  echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" > /etc/apt/sources.list.d/docker.list
  apt-get update -q && apt-get install -yq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
fi

# O Docker abre portas por fora do UFW; aqui só o Caddy publica 80 e 443, de propósito.
usermod -aG docker "${SUDO_USER:-ubuntu}"
echo "Servidor pronto. Saia e entre de novo no SSH para o grupo docker valer."
