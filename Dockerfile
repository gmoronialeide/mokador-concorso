FROM php:8.3-fpm

# Installa dipendenze di sistema + nginx + supervisord
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    nginx \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configurazione OPcache
RUN echo '\
opcache.enable=1\n\
opcache.memory_consumption=256\n\
opcache.interned_strings_buffer=16\n\
opcache.max_accelerated_files=20000\n\
opcache.validate_timestamps=1\n\
opcache.revalidate_freq=2\n\
opcache.save_comments=1\n\
' > /usr/local/etc/php/conf.d/opcache.ini

# Configurazione PHP-FPM per performance
RUN echo '[www]\n\
pm = dynamic\n\
pm.max_children = 50\n\
pm.start_servers = 10\n\
pm.min_spare_servers = 5\n\
pm.max_spare_servers = 20\n\
pm.max_requests = 1000\n\
' > /usr/local/etc/php-fpm.d/zzz-performance.conf

# Configurazione PHP custom
COPY docker/php.ini /usr/local/etc/php/conf.d/zzz-custom.ini

# Configurazione Nginx
RUN rm /etc/nginx/sites-enabled/default
COPY docker/nginx.conf /etc/nginx/sites-enabled/default

# Configurazione Supervisord
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Installa Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Installa Node.js per Vite (Laravel)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Imposta la working directory
WORKDIR /var/www/html

# Permessi per storage e cache di Laravel
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
