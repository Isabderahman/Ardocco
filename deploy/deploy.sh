#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEPLOY_DIR="${ROOT_DIR}/deploy"
ENV_FILE="${DEPLOY_DIR}/.env"
COMPOSE_FILE="${DEPLOY_DIR}/docker-compose.yml"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_header() {
  echo -e "${BLUE}========================================${NC}"
  echo -e "${BLUE}  ARDOCCO Deployment Script${NC}"
  echo -e "${BLUE}========================================${NC}"
}

print_success() {
  echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
  echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
  echo -e "${RED}✗ $1${NC}"
}

print_info() {
  echo -e "${BLUE}→ $1${NC}"
}

require_cmd() {
  if ! command -v "$1" >/dev/null 2>&1; then
    print_error "Missing required command: $1"
    exit 1
  fi
}

check_requirements() {
  print_info "Checking requirements..."
  require_cmd docker

  if ! docker compose version >/dev/null 2>&1; then
    print_error "docker compose is required (Docker Compose v2)."
    exit 1
  fi
  print_success "All requirements met"
}

check_env() {
  if [[ ! -f "$ENV_FILE" ]]; then
    print_warning "Missing ${ENV_FILE}"
    print_info "Creating from .env.example..."
    cp "${DEPLOY_DIR}/.env.example" "$ENV_FILE"
    print_warning "Please edit ${ENV_FILE} with your configuration"
    exit 1
  fi
  print_success "Environment file found"
}

load_env() {
  set -a
  # shellcheck disable=SC1090
  source "$ENV_FILE"
  set +a
}

show_status() {
  print_info "Service status:"
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" ps
}

show_logs() {
  local service="${1:-}"
  if [[ -n "$service" ]]; then
    docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" logs -f "$service"
  else
    docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" logs -f
  fi
}

build_all() {
  print_info "Building all services..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" build
  print_success "Build complete"
}

build_service() {
  local service="$1"
  print_info "Building $service..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" build "$service"
  print_success "$service built"
}

start_all() {
  print_info "Starting all services..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d
  print_success "All services started"
}

start_service() {
  local service="$1"
  print_info "Starting $service..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d "$service"
  print_success "$service started"
}

stop_all() {
  print_info "Stopping all services..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" down
  print_success "All services stopped"
}

stop_service() {
  local service="$1"
  print_info "Stopping $service..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" stop "$service"
  print_success "$service stopped"
}

restart_all() {
  print_info "Restarting all services..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" restart
  print_success "All services restarted"
}

restart_service() {
  local service="$1"
  print_info "Restarting $service..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" restart "$service"
  print_success "$service restarted"
}

rebuild_service() {
  local service="$1"
  print_info "Rebuilding and restarting $service..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" build "$service"
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d "$service"
  print_success "$service rebuilt and restarted"
}

deploy_full() {
  print_header
  check_requirements
  check_env
  load_env

  print_info "Full deployment starting..."

  # Build all services
  build_all

  # Start services
  start_all

  # Wait for services to be healthy
  print_info "Waiting for services to be ready..."
  sleep 10

  # Show status
  show_status

  echo ""
  print_success "Deployment complete!"
  echo ""
  echo "Services:"
  echo "  - Frontend:  https://${FRONTEND_DOMAIN:-ardocco.com}"
  echo "  - API:       https://${API_DOMAIN:-api.ardocco.com}"
  echo "  - Dashboard: https://${APP_DOMAIN:-app.ardocco.com}"
  echo "  - AI:        https://${AI_DOMAIN:-ai.ardocco.com}"
}

deploy_update() {
  print_header
  check_requirements
  check_env
  load_env

  print_info "Pulling latest changes..."
  cd "$ROOT_DIR"
  git pull origin main

  print_info "Rebuilding services..."
  build_all

  print_info "Restarting services..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d

  show_status
  print_success "Update complete!"
}

run_migrations() {
  print_info "Running database migrations..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec backend php artisan migrate --force
  print_success "Migrations complete"
}

run_seed() {
  print_info "Seeding database..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec backend php artisan db:seed --force
  print_success "Seeding complete"
}

clear_cache() {
  print_info "Clearing application caches..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec backend php artisan cache:clear
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec backend php artisan config:clear
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec backend php artisan route:clear
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" exec backend php artisan view:clear
  print_success "Caches cleared"
}

show_usage() {
  echo "Usage: $0 <command> [service]"
  echo ""
  echo "Commands:"
  echo "  deploy        Full deployment (build and start all services)"
  echo "  update        Pull latest code and redeploy"
  echo "  start         Start all services (or specific service)"
  echo "  stop          Stop all services (or specific service)"
  echo "  restart       Restart all services (or specific service)"
  echo "  rebuild       Rebuild and restart a specific service"
  echo "  build         Build all services (or specific service)"
  echo "  status        Show status of all services"
  echo "  logs          Show logs (optionally for specific service)"
  echo "  migrate       Run database migrations"
  echo "  seed          Seed the database"
  echo "  cache:clear   Clear application caches"
  echo "  ssl:init      Initialize SSL certificates"
  echo "  ssl:renew     Renew SSL certificates"
  echo ""
  echo "Services:"
  echo "  db            MySQL database"
  echo "  backend       Laravel API (api.ardocco.com)"
  echo "  frontend      Nuxt public site (ardocco.com)"
  echo "  app-dashboard Nuxt dashboard (app.ardocco.com)"
  echo "  ai            AI service (ai.ardocco.com)"
  echo "  nginx         Reverse proxy"
  echo ""
  echo "Examples:"
  echo "  $0 deploy              # Full deployment"
  echo "  $0 rebuild frontend    # Rebuild and restart frontend"
  echo "  $0 logs backend        # View backend logs"
  echo "  $0 restart nginx       # Restart nginx"
}

# Main
case "${1:-}" in
  deploy)
    deploy_full
    ;;
  update)
    deploy_update
    ;;
  start)
    check_requirements
    check_env
    if [[ -n "${2:-}" ]]; then
      start_service "$2"
    else
      start_all
    fi
    ;;
  stop)
    check_requirements
    if [[ -n "${2:-}" ]]; then
      stop_service "$2"
    else
      stop_all
    fi
    ;;
  restart)
    check_requirements
    if [[ -n "${2:-}" ]]; then
      restart_service "$2"
    else
      restart_all
    fi
    ;;
  rebuild)
    check_requirements
    check_env
    if [[ -z "${2:-}" ]]; then
      print_error "Please specify a service to rebuild"
      exit 1
    fi
    rebuild_service "$2"
    ;;
  build)
    check_requirements
    check_env
    if [[ -n "${2:-}" ]]; then
      build_service "$2"
    else
      build_all
    fi
    ;;
  status)
    check_requirements
    show_status
    ;;
  logs)
    check_requirements
    show_logs "${2:-}"
    ;;
  migrate)
    check_requirements
    run_migrations
    ;;
  seed)
    check_requirements
    run_seed
    ;;
  cache:clear)
    check_requirements
    clear_cache
    ;;
  ssl:init)
    "${DEPLOY_DIR}/ssl-init.sh"
    ;;
  ssl:renew)
    "${DEPLOY_DIR}/ssl-renew.sh"
    ;;
  *)
    show_usage
    exit 1
    ;;
esac
