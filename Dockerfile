FROM php:8.3-apache

# Instalar dependencias del sistema
COPY php.ini /usr/local/etc/php/
COPY forwarded.conf /etc/apache2/conf-available/forwarded.conf

RUN apt-get update && \
    apt-get install --no-install-recommends -y \
      zlib1g-dev \
      libc-client-dev \
      libkrb5-dev \
      libfreetype6-dev \
      libjpeg62-turbo-dev \
      libpng-dev \
      libxml2-dev \
      libzip-dev \
      cron \
      rsyslog \
      zip \
      unzip \
      socat \
      vim \
      nano && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install imap exif mysqli pdo pdo_mysql zip gd xml

RUN curl -o composer -sL https://getcomposer.org/composer.phar && \
    mv composer /usr/local/bin/composer && \
    chmod +x /usr/local/bin/composer && \
    composer global require javanile/http-robot:0.0.2 && \
    composer clearcache

RUN a2enconf forwarded

RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

RUN echo "ServerName crm.mabecenter.org" >> /etc/apache2/apache2.conf

# Descargar e instalar vtigercrm
RUN rm -rf /var/www/html \
    && mkdir -p /var/www/html \
    && curl -s -L https://cfhcable.dl.sourceforge.net/project/vtigercrm/vtiger%20CRM%208.3.0/Core%20Product/vtigercrm8.3.0.tar.gz?viasf=1 \
       | tar xfvz - --strip-components=1 -C /var/www/html \
    && chown -R www-data:www-data /var/www/html

RUN chmod -R 775 /var/www/html/cache \   
    && chmod -R 775 /var/www/html/logs

COPY patch/ /var/www/html/patch/
COPY install.php /var/www/html/install.php

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["apache2-foreground"]

# Exponer el puerto 80
EXPOSE 80