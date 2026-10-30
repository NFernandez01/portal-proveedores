FROM php:8.3-apache

# Driver de PostgreSQL para PDO + herramientas que usa Composer
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev unzip git \
 && docker-php-ext-install pdo_pgsql \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

# Composer, copiado desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache sirve SOLO la carpeta public/ (el resto del código no es accesible por URL)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && printf '<Directory /var/www/html/public>\n  AllowOverride All\n</Directory>\n' > /etc/apache2/conf-available/portal.conf \
 && a2enconf portal

# Límite de subida: PDFs de hasta 5 MB (con margen)
RUN printf 'upload_max_filesize=6M\npost_max_size=8M\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/portal.ini

WORKDIR /var/www/html