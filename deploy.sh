#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${ROOT_DIR}/deploy/.env"
ENV_EXAMPLE="${ROOT_DIR}/deploy/.env.example"
COMPOSE_FILE="${ROOT_DIR}/deploy/docker-compose.yml"

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1" >&2
    exit 1
  fi
}

require_cmd docker
require_cmd openssl

if ! docker compose version >/dev/null 2>&1; then
  echo "docker compose is required (Docker Compose v2)." >&2
  exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
  cp "$ENV_EXAMPLE" "$ENV_FILE"
  echo "Created ${ENV_FILE} from ${ENV_EXAMPLE}"
fi

inplace_sed() {
  local expr="$1"
  local file="$2"

  if sed --version >/dev/null 2>&1; then
    sed -i "$expr" "$file"
  else
    sed -i '' "$expr" "$file"
  fi
}

update_env() {
  local key="$1"
  local value="$2"

  if grep -q "^${key}=" "$ENV_FILE"; then
    inplace_sed "s|^${key}=.*|${key}=${value}|" "$ENV_FILE"
  else
    printf "\n%s=%s\n" "$key" "$value" >>"$ENV_FILE"
  fi
}

load_env() {
  set -a
  # shellcheck disable=SC1090
  source "$ENV_FILE"
  set +a
}

gen_password() {
  # 32 chars, URL-safe-ish
  openssl rand -base64 24 | tr -d '\n' | tr '+/' 'Aa'
}

gen_laravel_key() {
  printf 'base64:%s' "$(openssl rand -base64 32 | tr -d '\n')"
}

gen_secret() {
  openssl rand -base64 48 | tr -d '\n'
}

ensure_dir() {
  mkdir -p "$1"
}

load_env

# Defaults (domains)
if [[ -z "${FRONTEND_DOMAIN:-}" ]]; then update_env "FRONTEND_DOMAIN" "ardocco.com"; fi
if [[ -z "${API_DOMAIN:-}" ]]; then update_env "API_DOMAIN" "api.ardocco.com"; fi
if [[ -z "${AI_DOMAIN:-}" ]]; then update_env "AI_DOMAIN" "ai.ardocco.com"; fi
if [[ -z "${LETSENCRYPT_EMAIL:-}" ]]; then update_env "LETSENCRYPT_EMAIL" "admin@ardocco.com"; fi

# Defaults (ports)
if [[ -z "${NGINX_HTTP_PORT:-}" ]]; then update_env "NGINX_HTTP_PORT" "80"; fi
if [[ -z "${NGINX_HTTPS_PORT:-}" ]]; then update_env "NGINX_HTTPS_PORT" "443"; fi
if [[ -z "${AI_PORT:-}" ]]; then update_env "AI_PORT" "8001"; fi

# Defaults (DB)
if [[ -z "${MYSQL_DATABASE:-}" ]]; then update_env "MYSQL_DATABASE" "ardocco"; fi
if [[ -z "${MYSQL_USER:-}" ]]; then update_env "MYSQL_USER" "ardocco"; fi
if [[ -z "${MYSQL_ROOT_PASSWORD:-}" || "${MYSQL_ROOT_PASSWORD}" == "change-me-root" ]]; then
  update_env "MYSQL_ROOT_PASSWORD" "$(gen_password)"
fi
if [[ -z "${MYSQL_PASSWORD:-}" || "${MYSQL_PASSWORD}" == "change-me" ]]; then
  update_env "MYSQL_PASSWORD" "$(gen_password)"
fi

# Defaults (Laravel)
if [[ -z "${LARAVEL_APP_KEY:-}" ]]; then
  update_env "LARAVEL_APP_KEY" "$(gen_laravel_key)"
fi
if [[ -z "${APP_ENV:-}" ]]; then update_env "APP_ENV" "production"; fi
if [[ -z "${APP_DEBUG:-}" ]]; then update_env "APP_DEBUG" "false"; fi
if [[ -z "${LOG_LEVEL:-}" ]]; then update_env "LOG_LEVEL" "info"; fi

# Defaults (Nuxt runtime)
if [[ -z "${NUXT_BACKEND_BASE_URL:-}" ]]; then update_env "NUXT_BACKEND_BASE_URL" "http://backend:8000"; fi
if [[ -z "${NUXT_PUBLIC_GOOGLE_MAPS_API_KEY:-}" ]]; then update_env "NUXT_PUBLIC_GOOGLE_MAPS_API_KEY" ""; fi
if [[ -z "${NUXT_PUBLIC_API_BASE_URL:-}" ]]; then update_env "NUXT_PUBLIC_API_BASE_URL" ""; fi

# Defaults (AI)
if [[ -z "${AI_SECRET_KEY:-}" ]]; then update_env "AI_SECRET_KEY" "$(gen_secret)"; fi

load_env

# Derive URLs from domains (keep user override if already set)
if [[ -z "${APP_URL:-}" ]]; then
  update_env "APP_URL" "https://${API_DOMAIN}"
fi

load_env

ensure_dir "${ROOT_DIR}/deploy/certbot/www"
ensure_dir "${ROOT_DIR}/deploy/certbot/conf"

# Ensure a dummy TLS cert exists so Nginx can start before Let's Encrypt.
CERT_LIVE_DIR="${ROOT_DIR}/deploy/certbot/conf/live/${FRONTEND_DOMAIN}"
CERT_FULLCHAIN="${CERT_LIVE_DIR}/fullchain.pem"
CERT_PRIVKEY="${CERT_LIVE_DIR}/privkey.pem"

if [[ ! -f "${CERT_FULLCHAIN}" || ! -f "${CERT_PRIVKEY}" ]]; then
  echo "Creating a temporary self-signed certificate for ${FRONTEND_DOMAIN} (first run)..."
  ensure_dir "${CERT_LIVE_DIR}"
  openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout "${CERT_PRIVKEY}" \
    -out "${CERT_FULLCHAIN}" \
    -subj "/CN=${FRONTEND_DOMAIN}" >/dev/null 2>&1
fi

echo "Starting ARDOCCO via Docker Compose..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d --build

echo ""
echo "Done."
echo "- Frontend: https://${FRONTEND_DOMAIN}"
echo "- Backend:  https://${API_DOMAIN}"
echo "- AI:       https://${AI_DOMAIN}"
echo ""
echo "Enable SSL (first time): ./deploy/ssl-init.sh"
echo ""
echo "Logs: docker compose -f deploy/docker-compose.yml --env-file deploy/.env logs -f --tail 200"

