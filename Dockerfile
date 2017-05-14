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

RUN rm /etc/nginx/sites-enabled/*
ADD app/config/civicrm-docs.conf /etc/nginx/sites-enabled
