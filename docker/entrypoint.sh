#!/bin/bash
# =============================================================================
# Entrypoint cho container trên Render:
#   1) chép chứng chỉ CA của Aiven sang chỗ www-data đọc được và kiểm tra nó
#   2) kiểm tra biến môi trường bắt buộc, điền PORT vào cấu hình Nginx
#   3) cache cấu hình, chạy migrations (+ seeders nếu bật)
#   4) chạy song song PHP-FPM + Nginx (+ scheduler nếu bật); một cái chết -> dừng container
# =============================================================================
set -Eeuo pipefail

cd /var/www

# --- 1. Chứng chỉ CA MySQL (Render Secret File: /etc/secrets/ca.pem) ----------
# Render secret mounts may be readable by root but not by www-data.
# Copy only the CA certificate at runtime to an app-readable private location.
if [[ -n "${MYSQL_ATTR_SSL_CA:-}" ]]; then
    if [[ ! -f "$MYSQL_ATTR_SSL_CA" || ! -r "$MYSQL_ATTR_SSL_CA" ]]; then
        echo "Cannot read MySQL CA file '$MYSQL_ATTR_SSL_CA'. Check Render Secret Files and MYSQL_ATTR_SSL_CA." >&2
        exit 1
    fi
    (
        umask 077
        mkdir -p /run/app-certificates
        chown root:www-data /run/app-certificates
        chmod 750 /run/app-certificates
        cp "$MYSQL_ATTR_SSL_CA" /run/app-certificates/mysql-ca.pem
        chown www-data:www-data /run/app-certificates/mysql-ca.pem
        chmod 400 /run/app-certificates/mysql-ca.pem
    )
    export MYSQL_ATTR_SSL_CA=/run/app-certificates/mysql-ca.pem
    su-exec www-data php docker/check-ca.php
fi

# Allow maintenance commands with: docker run ... IMAGE php artisan ...
if (( $# > 0 )); then
    exec su-exec www-data "$@"
fi

# --- 2. Biến bắt buộc + PORT ---------------------------------------------------
: "${APP_KEY:?Set a persistent APP_KEY before starting the application}"
: "${APP_URL:?Set APP_URL to the public HTTPS address}"
: "${DB_HOST:?Set DB_HOST (Aiven host)}"

export PORT="${PORT:-10000}"
if [[ ! "$PORT" =~ ^[0-9]{1,5}$ ]] || (( 10#$PORT < 1 || 10#$PORT > 65535 )); then
    echo "PORT must be an integer between 1 and 65535" >&2
    exit 1
fi
# Substitute PORT only; preserve Nginx variables such as $uri and $query_string.
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
         storage/logs storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# --- 3. Cache + database -------------------------------------------------------
su-exec www-data php artisan config:cache

case "${RUN_MIGRATIONS:-true}" in
    true)  su-exec www-data php artisan migrate --force --no-interaction ;;
    false) ;;
    *) echo "RUN_MIGRATIONS must be true or false" >&2; exit 1 ;;
esac

case "${RUN_SEEDERS:-false}" in
    true)  su-exec www-data php artisan db:seed --force --no-interaction ;;
    false) ;;
    *) echo "RUN_SEEDERS must be true or false" >&2; exit 1 ;;
esac

su-exec www-data php artisan route:cache
su-exec www-data php artisan view:cache
su-exec www-data php artisan event:cache

# --- 4. Chạy các tiến trình phục vụ ---------------------------------------------
server_pids=()

cleanup() {
    trap - EXIT TERM INT
    if (( ${#server_pids[@]} )); then
        kill -QUIT "${server_pids[@]}" 2>/dev/null || true
        wait "${server_pids[@]}" 2>/dev/null || true
    fi
}
trap cleanup EXIT
trap 'exit 0' TERM INT

php-fpm -F &
server_pids+=("$!")

nginx -g 'daemon off;' &
server_pids+=("$!")

# Lịch trong routes/console.php (huỷ đơn quá hạn, đồng bộ GHN, tự hoàn thành đơn).
# Render Web Service không có cron -> tuỳ chọn chạy schedule:work ngay trong container.
if [[ "${RUN_SCHEDULER:-false}" == "true" ]]; then
    su-exec www-data php artisan schedule:work &
    server_pids+=("$!")
fi

status=0
wait -n "${server_pids[@]}" || status=$?
echo "A server process exited (status $status); stopping container" >&2
exit 1
