# syntax=docker/dockerfile:1
# =============================================================================
# Cây Cảnh Shop (lar_vidu1) — image chạy trên Render (Web Service, Docker)
# Giai đoạn: php-base (PHP + extension) -> build (Composer) -> production
# LƯU Ý: composer.lock hiện yêu cầu PHP >= 8.4.1 và config/database.php dùng
# Pdo\Mysql  => phải dùng php:8.4 (tài liệu mẫu dùng 8.2 sẽ lỗi platform check).
# =============================================================================
FROM php:8.4-fpm-alpine AS php-base

RUN apk add --no-cache bash nginx curl gettext su-exec tini ca-certificates \
        libpng libjpeg-turbo libwebp freetype libzip oniguruma icu-libs \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
        libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev libzip-dev oniguruma-dev icu-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip gd bcmath intl opcache \
    && apk del .build-deps

WORKDIR /var/www

# -----------------------------------------------------------------------------
FROM php-base AS build
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Cài thư viện trước (tận dụng cache layer khi chỉ sửa code)
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress \
        --no-scripts --no-autoloader

COPY . .
RUN mkdir -p bootstrap/cache \
        storage/framework/cache/data storage/framework/sessions storage/framework/views \
        storage/logs storage/app/public \
    && composer dump-autoload --no-dev --optimize --no-interaction \
    && composer check-platform-reqs --no-dev

# -----------------------------------------------------------------------------
FROM php-base AS production
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=info \
    DB_CONNECTION=mysql SESSION_DRIVER=database SESSION_SECURE_COOKIE=true \
    CACHE_STORE=database QUEUE_CONNECTION=sync FILESYSTEM_DISK=local \
    PORT=10000 RUN_MIGRATIONS=true RUN_SEEDERS=false RUN_SCHEDULER=false

COPY --from=build --chown=www-data:www-data /var/www /var/www
COPY docker/nginx.conf /etc/nginx/templates/default.conf.template
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
# Đặt tên zzz-* để nạp SAU zz-docker.conf của image gốc (nếu không, listen bị ghi đè)
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zzz-app.conf
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/app-entrypoint

# public/storage -> storage/app/public (thay cho `php artisan storage:link`)
RUN mkdir -p /run/nginx /etc/nginx/http.d \
    && ln -sfn /var/www/storage/app/public /var/www/public/storage \
    && chmod -R ug+rwX /var/www/storage /var/www/bootstrap/cache

EXPOSE 10000
HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
    CMD curl --fail --silent "http://127.0.0.1:${PORT}/up" > /dev/null || exit 1
ENTRYPOINT ["/sbin/tini", "--", "/usr/local/bin/app-entrypoint"]
