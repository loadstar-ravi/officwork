# Local development image for the Laravel API and queue worker.
# Production would use php-fpm + nginx (or FrankenPHP) on ECS/Fargate,
# with dev dependencies left out.
FROM php:8.5-cli-alpine

# unzip lets Composer install packages from dist archives.
RUN apk add --no-cache unzip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first so this layer is cached across code changes.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize \
    && mkdir -p storage/data storage/logs storage/framework/cache storage/framework/sessions storage/framework/views

EXPOSE 8000

ENTRYPOINT ["sh", "/app/docker/entrypoint.sh"]
# PHP's built-in server rather than `artisan serve`: artisan serve only passes
# a whitelist of environment variables to the server process, so settings
# from docker compose (e.g. DB_DATABASE) would be silently ignored.
# Laravel's router script resolves the public dir from the working directory.
CMD ["sh", "-c", "cd public && exec php -S 0.0.0.0:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"]
