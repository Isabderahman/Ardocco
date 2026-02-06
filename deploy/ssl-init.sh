#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ROOT_DIR}/deploy/.env"
COMPOSE_FILE="${ROOT_DIR}/deploy/docker-compose.yml"

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing required command: $1" >&2
    exit 1
  fi
}

require_cmd docker

if ! docker compose version >/dev/null 2>&1; then
  echo "docker compose is required (Docker Compose v2)." >&2
  exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing ${ENV_FILE}. Run ./deploy.sh first." >&2
  exit 1
fi

load_env() {
  set -a
  # shellcheck disable=SC1090
  source "$ENV_FILE"
  set +a
}

load_env

if [[ -z "${FRONTEND_DOMAIN:-}" || -z "${API_DOMAIN:-}" || -z "${AI_DOMAIN:-}" ]]; then
  echo "Missing required domains in deploy/.env (FRONTEND_DOMAIN, API_DOMAIN, AI_DOMAIN)." >&2
  exit 1
fi

if [[ -z "${LETSENCRYPT_EMAIL:-}" ]]; then
  echo "Missing LETSENCRYPT_EMAIL in deploy/.env." >&2
  exit 1
fi

echo "Initializing Let's Encrypt certificates..."
echo "- ${FRONTEND_DOMAIN}"
echo "- ${API_DOMAIN}"
echo "- ${AI_DOMAIN}"

mkdir -p "${ROOT_DIR}/deploy/certbot/www" "${ROOT_DIR}/deploy/certbot/conf"

# Ensure nginx is running to answer the http-01 challenge.
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d nginx

# Remove any existing (or dummy) cert so certbot can create a clean one.
rm -rf "${ROOT_DIR}/deploy/certbot/conf/live/${FRONTEND_DOMAIN}" \
  "${ROOT_DIR}/deploy/certbot/conf/archive/${FRONTEND_DOMAIN}" \
  "${ROOT_DIR}/deploy/certbot/conf/renewal/${FRONTEND_DOMAIN}.conf"

docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" --profile tools run --rm certbot \
  certonly --webroot \
  -w /var/www/certbot \
  --email "${LETSENCRYPT_EMAIL}" \
  --agree-tos \
  --no-eff-email \
  --non-interactive \
  --rsa-key-size 4096 \
  -d "${FRONTEND_DOMAIN}" \
  -d "${API_DOMAIN}" \
  -d "${AI_DOMAIN}"

docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec nginx nginx -s reload

echo "SSL enabled successfully."
