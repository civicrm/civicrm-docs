FROM richarvey/nginx-php-fpm

RUN apk update \
  && apk add \
    git \
    curl \
    unzip \
    vim \
    python \
    python-dev \
    py-pip

RUN pip install mkdocs mkdocs-material pygments pymdown-extensions

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# xdebug
RUN apk add --update autoconf alpine-sdk
RUN pecl install xdebug

RUN echo 'zend_extension="/usr/local/lib/php/extensions/no-debug-non-zts-20160303/xdebug.so"' > /usr/local/etc/php/conf.d/xdebug.ini
RUN echo xdebug.idekey = PHPSTORM >> /usr/local/etc/php/conf.d/xdebug.ini
RUN echo xdebug.remote_enable = 1 >> /usr/local/etc/php/conf.d/xdebug.ini
RUN echo xdebug.remote_autostart = 0 >> /usr/local/etc/php/conf.d/xdebug.ini
RUN echo xdebug.remote_connect_back = 0 >> /usr/local/etc/php/conf.d/xdebug.ini
RUN echo xdebug.remote_host=172.17.0.1 >> /usr/local/etc/php/conf.d/xdebug.ini

RUN rm /etc/nginx/sites-enabled/*
ADD app/config/civicrm-docs.conf /etc/nginx/sites-enabled
