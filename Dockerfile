# Gunakan base image PHP dengan Apache
FROM php:8.2-apache

# 1. Install System Dependencies & PYTHON
# Kita install Python3, Pip, dan library pendukung
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    python3-venv \
    libzip-dev \
    unzip \
    git \
    libpng-dev \
    && docker-php-ext-install pdo_mysql zip gd

# --- TRIK SUPAYA KODINGAN PHP KAMU TIDAK PERLU DIUBAH ---
# Kita buat "jalan pintas" (shortcut).
# Jadi kalau Laravel panggil 'python', server akan menjalankannya pakai 'python3'
RUN ln -s /usr/bin/python3 /usr/bin/python

# 2. INSTALL LIBRARY PYTHON (OR-TOOLS)
# Wajib install ini karena solver kamu pakai ortools
RUN pip3 install ortools --break-system-packages

# 3. SETTING APACHE (SOLUSI 404 NOT FOUND)
# Aktifkan mod_rewrite (Wajib buat Laravel)
RUN a2enmod rewrite

# Ubah Document Root Apache ke folder public Laravel
# Ini kuncinya supaya saat buka website, dia langsung masuk ke index.php di folder public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4. Copy semua file project ke dalam container
COPY . /var/www/html

# 5. Install Composer (Manajer paket PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Jalankan install dependency Laravel
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# 7. Set permissions agar Laravel bisa nulis log/cache/session
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Buka Port 80
EXPOSE 80