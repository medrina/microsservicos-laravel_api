FROM php:8.2-fpm

ARG SUPERVISOR_FILE
ARG MICROSERVICE

# Dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    nano \
    zip \
    unzip \
    supervisor \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    # redis------------------------
    $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    #-------------------------------
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Extensões PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    sockets

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Diretório da aplicação
WORKDIR /var/www

# oculta a versão do PHP do cabeçalho de resposta da requisição HTTP
COPY docker/php/custom-php.ini /usr/local/etc/php/conf.d/custom-php.ini
RUN chmod +x /usr/local/etc/php/conf.d/custom-php.ini

# copia o script responsável que vai executar alguns comandos no terminal de cada container de cada microsserviço
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# copia o script responsável da fila para cada argumento do referente microsserviço definido no docker compose
COPY ${MICROSERVICE}/supervisor/${SUPERVISOR_FILE} /etc/supervisor/conf.d/${SUPERVISOR_FILE}
RUN mkdir -p /var/log/supervisor

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-n"]
