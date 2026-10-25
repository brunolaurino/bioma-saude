# Bioma CD

App de acompanhamento de saúde em duas partes, no mesmo sistema:

- **App do paciente** (celular, instalável como PWA): metas diárias, sintomas, bioimpedância, fotos de evolução, exames, nutrição, treinos, desabafos, lembretes e privacidade.
- **Painel do profissional** (navegador): pacientes, prontuário por abas, preparação de consulta, alertas, desabafos, agenda, configurações e telas de LGPD.

Cada médico tem um **ambiente isolado** (este pacote inteiro: app, banco e Redis próprios). Dentro dele, o **master** liga e desliga funções para todos ou para um paciente, convida a equipe e escolhe o que cada profissional vê.

> **Use apenas dados fictícios** enquanto o ambiente não tiver backup, KMS e revisão jurídica.

## Rodar no seu computador (desenvolvimento)

Precisa de PHP 8.3+ com as extensões `gd`, `exif`, `zip`, `intl` e `sodium`, e do Composer.

```bash
cd app
composer install
cp .env.example .env
php artisan key:generate
php -r 'echo "BIOMA_KEK=1:".base64_encode(random_bytes(32)), PHP_EOL, "BIOMA_INDEX_KEY=".base64_encode(random_bytes(32)), PHP_EOL;' >> .env
touch database/database.sqlite
php artisan migrate
php artisan bioma:demo     # cria pacientes e equipe fictícios e mostra as chaves do 2FA
php artisan serve
```

Abra `http://127.0.0.1:8000`. Senha de todas as contas de demonstração: `bioma-demo-2026`.
Profissionais pedem o código do app autenticador: cadastre a chave mostrada pelo `bioma:demo` no Google Authenticator, 1Password ou similar.

| Conta | E-mail |
| --- | --- |
| Master (Medicina) | marina@demo.bioma.local |
| Psicologia | helena@demo.bioma.local |
| Educação física | rafael@demo.bioma.local |
| Nutrição | julia@demo.bioma.local |
| Paciente | ana@demo.bioma.local (e carlos, beatriz, pedro) |
| Paciente convidada | Luiza: o `bioma:demo` mostra o código de primeiro acesso |

Testes: `cd app && php artisan test`.

## Subir num servidor (Docker)

```bash
sudo bash scripts/preparar-servidor.sh   # firewall, SSH só com chave, fail2ban, Docker
cp .env.example .env && nano .env         # DOMAIN, ACME_EMAIL e SMTP
bash scripts/subir.sh                     # gera segredos (inclusive as chaves de cifra), constrói e sobe
docker compose exec app php artisan bioma:master   # link de uso único para criar o usuário master
```

O master abre o link, cria a conta, ativa o 2FA e passa pelo assistente: consultório, controlador e encarregado (LGPD), funções do app e aviso de privacidade.

**Guarde `secrets/` fora do servidor.** Sem `bioma_kek` os dados de saúde ficam ilegíveis para sempre.

## Segurança e LGPD (resumo)

- Dados de saúde cifrados por campo (XChaCha20-Poly1305), com uma chave por paciente. As chaves dos pacientes ficam cifradas pela chave mestra (KEK), que pode ser trocada com `bioma:chaves-rotacionar`.
- Arquivos (laudos, fotos, exames, planos) cifrados no disco, conferidos pelo conteúdo real e com fotos redesenhadas no servidor (somem GPS e outros metadados).
- E-mails guardados cifrados e achados por índice cego (HMAC). Senhas com Argon2id. Profissionais com 2FA obrigatório e sessão de 15 minutos parada.
- Auditoria só de inserção, encadeada por hash: quem abriu qual prontuário, aba, arquivo e desabafo. O paciente vê quem acessou os dados dele.
- Desabafos são privados por padrão. Fotos pedem consentimento, e retirar o consentimento apaga as fotos.
- Paciente baixa todos os dados (ZIP com JSON, planilhas e leitura fácil), faz pedidos de titular e pode encerrar a conta. O master responde os pedidos com prazo de 15 dias e registra incidentes.
- CSP rígida, sem script nem estilo inline. Tudo funciona mesmo sem JavaScript.

## Comandos

| Para | Comando |
| --- | --- |
| Criar o link do master | `php artisan bioma:master` |
| Listar ou mudar funções | `php artisan bioma:modulos [ligar\|desligar] [modulo]` |
| Conferir a auditoria | `php artisan bioma:auditoria` |
| Recifrar chaves com a KEK nova | `php artisan bioma:chaves-rotacionar` |
| Lembretes (agendado a cada 15 min) | `php artisan bioma:lembretes` |
| Limpeza diária (agendada) | `php artisan bioma:retencao` |
| Dados de demonstração | `php artisan bioma:demo` (só fora de produção e com banco vazio) |

## Ainda não incluído

- Entrar com biometria (passkeys) e notificações push no celular: hoje os avisos ficam dentro do app.
- Fotos HEIC do iPhone são recusadas com instrução para enviar em JPG.
- A exportação de dados sai em ZIP (JSON, CSV e uma página para ler), não em PDF.
- KMS do provedor de nuvem para a KEK e banco separado para as chaves (o código já aceita `BIOMA_KEYRING_CONNECTION`).
- Backup automático cifrado.
