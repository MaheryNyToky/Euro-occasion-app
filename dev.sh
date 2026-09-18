#!/usr/bin/env bash

set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$ROOT_DIR/apps/api"
CLIENT_DIR="$ROOT_DIR/apps/client"
COMPOSE_FILE="$ROOT_DIR/infra/docker/docker-compose.yml"
API_HOST="${API_HOST:-127.0.0.1}"
API_PORT="${API_PORT:-8000}"
WEB_HOST="${WEB_HOST:-127.0.0.1}"
WEB_PORT="${WEB_PORT:-3000}"
RUNTIME_DIR="${TMPDIR:-/tmp}/eurocasion-dev"
API_LOG="$RUNTIME_DIR/api.log"
FLUTTER_LOG="$RUNTIME_DIR/flutter.log"
API_PID_FILE="$RUNTIME_DIR/api.pid"
FLUTTER_PID_FILE="$RUNTIME_DIR/flutter.pid"

mkdir -p "$RUNTIME_DIR"

log() {
  printf '[dev] %s\n' "$*"
}

fail() {
  printf '[dev] erreur : %s\n' "$*" >&2
  exit 1
}

require_command() {
  command -v "$1" >/dev/null 2>&1 || fail "commande introuvable : $1"
}

read_pid() {
  local file="$1"
  if [[ -f "$file" ]]; then
    tr -d '[:space:]' < "$file"
  fi
}

stop_process() {
  local name="$1"
  local pid_file="$2"
  local pid

  pid="$(read_pid "$pid_file")"
  if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then
    log "arrêt de $name (PID $pid)"
    kill "$pid" 2>/dev/null || true
    for _ in {1..20}; do
      kill -0 "$pid" 2>/dev/null || break
      sleep 0.25
    done
    kill -9 "$pid" 2>/dev/null || true
  fi
  rm -f "$pid_file"
}

stop_apps() {
  stop_process "Flutter" "$FLUTTER_PID_FILE"
  stop_process "Laravel" "$API_PID_FILE"
}

show_status() {
  local api_pid flutter_pid
  api_pid="$(read_pid "$API_PID_FILE")"
  flutter_pid="$(read_pid "$FLUTTER_PID_FILE")"
  printf 'API    : %s\n' "$(if [[ -n "$api_pid" ]] && kill -0 "$api_pid" 2>/dev/null; then printf 'active (PID %s)' "$api_pid"; else printf 'arrêtée'; fi)"
  printf 'Flutter : %s\n' "$(if [[ -n "$flutter_pid" ]] && kill -0 "$flutter_pid" 2>/dev/null; then printf 'actif (PID %s)' "$flutter_pid"; else printf 'arrêté'; fi)"
  printf 'Web    : http://%s:%s\n' "$WEB_HOST" "$WEB_PORT"
  printf 'API    : http://%s:%s/api/v1/health\n' "$API_HOST" "$API_PORT"
}

start_infrastructure() {
  require_command docker
  docker compose -f "$COMPOSE_FILE" up -d postgres redis minio mailpit
}

start_api() {
  [[ -f "$API_DIR/artisan" ]] || fail "Laravel introuvable dans $API_DIR"
  [[ -d "$API_DIR/vendor" ]] || fail "dépendances Laravel absentes : lance composer install dans apps/api"
  [[ -f "$API_DIR/.env" ]] || fail "apps/api/.env absent : copie .env.example et configure l'environnement"

  log "exécution des migrations Laravel"
  (
    cd "$API_DIR"
    php artisan migrate --force
  )

  log "démarrage de Laravel sur http://$API_HOST:$API_PORT"
  (
    cd "$API_DIR"
    exec php artisan serve --host="$API_HOST" --port="$API_PORT"
  ) >"$API_LOG" 2>&1 &
  printf '%s' "$!" > "$API_PID_FILE"
}

start_flutter() {
  [[ -f "$CLIENT_DIR/pubspec.yaml" ]] || fail "application Flutter introuvable dans $CLIENT_DIR"
  command -v flutter >/dev/null 2>&1 || fail "commande introuvable : flutter"

  log "démarrage de Flutter Web sur http://$WEB_HOST:$WEB_PORT"
  (
    cd "$CLIENT_DIR"
    exec flutter run -d web-server --web-hostname "$WEB_HOST" --web-port "$WEB_PORT"
  ) >"$FLUTTER_LOG" 2>&1 &
  printf '%s' "$!" > "$FLUTTER_PID_FILE"
}

wait_for_http() {
  local url="$1"
  local label="$2"
  for _ in {1..40}; do
    if curl --silent --show-error --fail --max-time 1 "$url" >/dev/null 2>&1; then
      log "$label disponible : $url"
      return 0
    fi
    sleep 0.5
  done
  log "$label ne répond pas encore ; consulte les logs dans $RUNTIME_DIR"
  return 0
}

start() {
  require_command curl
  require_command php
  require_command docker
  require_command flutter

  stop_apps
  start_infrastructure
  start_api
  start_flutter

  wait_for_http "http://$API_HOST:$API_PORT/api/v1/health" "API Laravel"
  wait_for_http "http://$WEB_HOST:$WEB_PORT" "Flutter Web"

  printf '\nApplication disponible sur : http://%s:%s\n' "$WEB_HOST" "$WEB_PORT"
  printf 'API disponible sur         : http://%s:%s\n' "$API_HOST" "$API_PORT"
  printf 'Logs                       : %s\n' "$RUNTIME_DIR"
  printf 'Ctrl+C arrête Laravel et Flutter ; Docker reste actif.\n\n'

  trap stop_apps EXIT INT TERM
  wait
}

case "${1:-start}" in
  start)
    start
    ;;
  stop)
    stop_apps
    ;;
  status)
    show_status
    ;;
  restart)
    stop_apps
    start
    ;;
  *)
    printf 'Usage: ./dev.sh [start|stop|restart|status]\n' >&2
    exit 2
    ;;
esac
