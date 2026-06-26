FROM dunglas/frankenphp:1-php8.2-alpine

# Pasang dependensi sistem yang diperlukan untuk ekstensi PHP
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    libxml2-dev

# Pasang ekstensi PHP yang diperlukan
RUN install-php-extensions \
    mysqli \
    pdo_mysql \
    intl \
    gd \
    zip \
    bcmath \
    opcache

# Setel direktori kerja
WORKDIR /app

# Salin semua file proyek
COPY . .

# Setel kepemilikan dan izin folder untuk CodeIgniter 4
RUN chown -R www-data:www-data writable \
    && chmod -R 775 writable

# Setel port default FrankenPHP
EXPOSE 8080 443 80

# Gunakan Caddyfile kustom
ENV CADDY_CONFIG=/app/Caddyfile

CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]
