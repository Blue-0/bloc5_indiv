FROM php:7.4-apache

# System deps for Composer + common PHP extensions
RUN apt-get update \
	&& apt-get install -y --no-install-recommends git unzip libzip-dev \
	&& docker-php-ext-install pdo_mysql zip \
	&& a2enmod rewrite \
	&& rm -rf /var/lib/apt/lists/*

# Composer (copied from the official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache must serve /public (front controller) so routing + .htaccess work
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
	&& printf '%s\n' \
		'<Directory /var/www/html/public>' \
		'    AllowOverride All' \
		'    Require all granted' \
		'</Directory>' \
		> /etc/apache2/conf-available/videgrenier.conf \
	&& a2enconf videgrenier

WORKDIR /var/www/html

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]
