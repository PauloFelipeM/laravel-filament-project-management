FROM php:8.2-fpm

RUN export CFLAGS="$PHP_CFLAGS" CPPFLAGS="$PHP_CPPFLAGS" LDFLAGS="$PHP_LDFLAGS" && apt-get update && apt-get install -y \
	libcurl3-dev \
	libxml2-dev \
	libmcrypt-dev \
	libreadline-dev \
	libgraphicsmagick1-dev \
	texlive-latex-base \
	wget \
	imagemagick \
	procps \
	libzip-dev \
	zip \
	libonig-dev \
    supervisor \
    nodejs \
    npm \
    curl \
	unzip

RUN pecl install mcrypt-1.0.7 && docker-php-ext-enable mcrypt
RUN pecl install gmagick-2.0.6RC1 && docker-php-ext-enable gmagick
RUN pecl install msgpack-2.1.2 && docker-php-ext-enable msgpack

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN docker-php-ext-install opcache && docker-php-ext-enable opcache
RUN docker-php-ext-install curl && docker-php-ext-enable curl
RUN docker-php-ext-install gd && docker-php-ext-enable gd
RUN docker-php-ext-install zip && docker-php-ext-enable zip
RUN docker-php-ext-install xml && docker-php-ext-enable xml
RUN docker-php-ext-install pdo && docker-php-ext-enable pdo
RUN docker-php-ext-install pdo_mysql && docker-php-ext-enable pdo_mysql
RUN docker-php-ext-install pcntl && docker-php-ext-enable pcntl
RUN docker-php-ext-install calendar && docker-php-ext-enable calendar
RUN docker-php-ext-install intl && docker-php-ext-enable intl
RUN docker-php-ext-install simplexml && docker-php-ext-enable simplexml
RUN docker-php-ext-install exif && docker-php-ext-enable exif
RUN docker-php-ext-install mbstring && docker-php-ext-enable mbstring

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN wget https://github.com/jgm/pandoc/releases/download/2.18/pandoc-2.18-1-amd64.deb
RUN dpkg -i pandoc-2.18-1-amd64.deb
RUN rm pandoc-2.18-1-amd64.deb

COPY docker-compose/php.ini /usr/local/etc/php/php.ini
COPY docker-compose/php.ini /usr/local/etc/php/php-cli.ini
COPY docker-compose/supervisord.app.conf /etc/supervisor/conf.d/supervisord.app.conf

RUN chown -R www-data: /etc/supervisor/
RUN chown -R www-data: /usr/bin/supervisord
RUN chown -R www-data:www-data /var/www/html
RUN chmod 755 /var/www/html

WORKDIR /var/www/html

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.app.conf"]
