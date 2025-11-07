#!/usr/bin/env bash

set -Eeuo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_ROOT"

WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-$WEB_USER}"
PHP_BIN="${PHP_BIN:-php}"

log() {
  printf '[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*"
}

fail() {
  log "ERROR: $*"
  exit 1
}

require_command() {
  local cmd="$1"
  command -v "$cmd" >/dev/null 2>&1 || fail "Missing required command: $cmd"
}

run_as_webuser() {
  if [[ "$(id -un)" == "$WEB_USER" ]]; then
    "$@"
  elif command -v sudo >/dev/null 2>&1; then
    sudo -u "$WEB_USER" -- "$@"
  else
    su -s /bin/sh "$WEB_USER" -c "$(printf '%q ' "$@")"
  fi
}

artisan() {
  run_as_webuser "$PHP_BIN" artisan "$@"
}

ensure_env_setting() {
  local key="$1" desired="$2"
  local env_file=".env"
  [[ -f "$env_file" ]] || return

  if grep -qE "^${key}=" "$env_file"; then
    sed -i "s|^${key}=.*|${key}=${desired}|" "$env_file"
  else
    printf '\n%s=%s\n' "$key" "$desired" >>"$env_file"
  fi
}

require_command "$PHP_BIN"
[[ -f artisan ]] || fail "artisan entry point not found in $APP_ROOT"

log "Ensuring Laravel writable directories exist"
run_as_webuser mkdir -p storage/framework/{cache/data,sessions,views} bootstrap/cache

log "Fixing ownership for storage and bootstrap/cache"
chown -R "$WEB_USER":"$WEB_GROUP" storage bootstrap/cache

log "Setting cooperative permissions"
chmod -R ug+rwX storage bootstrap/cache

if command -v setfacl >/dev/null 2>&1; then
  log "Applying ACL defaults for web user/group"
  setfacl -Rm u:"$WEB_USER":rwx,g:"$WEB_GROUP":rwx storage bootstrap/cache || true
  setfacl -dRm u:"$WEB_USER":rwx,g:"$WEB_GROUP":rwx storage bootstrap/cache || true
fi

log "Hardening production environment flags"
ensure_env_setting "APP_DEBUG" "false"
ensure_env_setting "DEBUGBAR_ENABLED" "false"

log "Clearing and rebuilding Laravel caches"
artisan optimize:clear
artisan config:cache
artisan route:cache

log "Restarting queue workers (if running)"
artisan queue:restart || log "queue:restart reported no workers; continuing"

log "Maintenance script completed successfully"
