# Sử dụng PHP 7.4 FPM
FROM php:7.4-fpm

# Cài đặt các tiện ích cần thiết cho Yii2
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip pcntl

# Cài đặt Composer (quản lý các gói PHP)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Cài đặt Node.js và NPM
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Thiết lập thư mục làm việc
WORKDIR /var/www

# Copy mã nguồn của bạn vào Docker container (sau này sẽ dùng khi đã có source code)
COPY . /var/www

# Cấp quyền cho các thư mục quan trọng của Yii2
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

EXPOSE 9000
CMD ["php-fpm"]
