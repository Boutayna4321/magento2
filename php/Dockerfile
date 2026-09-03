FROM php:8.2-fpm

ARG USER=www-data
ARG GROUP=www-data
# Host UID/GID so that the bind-mounted ./src stays writable by BOTH php-fpm
# (inside the container) and the developer's own account (on the host).
# Without this, entrypoint.sh chowns the bind mount to the image's www-data
# (uid 33) and the host user can no longer edit or git-checkout its own files.
ARG UID=1000
ARG GID=1000

# System dependencies for Magento 2.4.8
RUN apt-get update && apt-get install -y \
    wget \
    curl \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libxslt1-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    libgd-dev \
    libsodium-dev \
    libpq-dev \
    pkg-config \
    libfcgi-bin \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by Magento 2.4.8
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        calendar \
        exif \
        ftp \
        gd \
        intl \
        mbstring \
        mysqli \
        opcache \
        pcntl \
        pdo \
        pdo_mysql \
        soap \
        sockets \
        sodium \
        xsl \
        zip

# Install PHP extensions from PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Align the in-container www-data with the host developer's UID/GID.
# Done before any COPY/chown so everything downstream gets consistent ownership.
RUN if [ "$(id -u ${USER})" != "${UID}" ]; then \
        usermod  -u "${UID}" "${USER}" && \
        groupmod -g "${GID}" "${GROUP}" && \
        chown -R "${UID}:${GID}" /var/www; \
    fi

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# PHP configuration
# NOTE: the build context is ./php (see docker-compose.yml), so these paths are
# relative to the php/ directory itself -- NOT to the repository root.
COPY php.ini /usr/local/etc/php/conf.d/99-magento.ini

# OPcache configuration
COPY opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# PHP-FPM pool: pins `listen = 0.0.0.0:9000` and enables /ping for healthchecks
COPY zz-magento-pool.conf /usr/local/etc/php-fpm.d/zz-magento-pool.conf

# Set working directory
WORKDIR /var/www/html

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

# Verify php-fpm is actually accepting FastCGI requests on 9000, so that
# nginx (depends_on: service_healthy) can never start before FPM is ready.
HEALTHCHECK --interval=10s --timeout=5s --start-period=30s --retries=5 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
