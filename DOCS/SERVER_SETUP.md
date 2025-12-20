# Настройка сервера для backend.antonov-mark.ru

**Дата создания:** 2025-12-19 11:16 (UTC+3, Брест)  
**Последнее обновление:** 2025-12-20 19:04 (UTC+3, Брест)  
**Версия:** 1.1  
**Описание:** Документация по настройке веб-сервера Nginx для поддомена backend.antonov-mark.ru

---

## Общая информация

**Домен:** `backend.antonov-mark.ru`  
**Корневая директория:** `/var/www/backend.antonov-mark.ru`  
**Конфигурационный файл:** `/etc/nginx/sites-available/backend.antonov-mark.ru`  
**Символическая ссылка:** `/etc/nginx/sites-enabled/backend.antonov-mark.ru`  
**SSL сертификат:** `/etc/letsencrypt/live/antonov-mark.ru/fullchain.pem`

---

## Выполненные настройки

### 1. Создание каталога сайта

```bash
mkdir -p /var/www/backend.antonov-mark.ru
chown -R www-data:www-data /var/www/backend.antonov-mark.ru
chmod 755 /var/www/backend.antonov-mark.ru
```

**Права доступа:**
- Владелец: `www-data:www-data`
- Права: `755` (для директории)

### 2. Тестовые страницы

**Созданы файлы:**
- `/var/www/backend.antonov-mark.ru/index.html` — HTML тестовая страница
- `/var/www/backend.antonov-mark.ru/index.php` — PHP тестовая страница с информацией о сервере

### 2.1. Структура проекта

**Текущая структура директорий:**
- `/var/www/backend.antonov-mark.ru/APP-B24/` — приложение Bitrix24
- `/var/www/backend.antonov-mark.ru/DOCS/` — документация проекта
- `/var/www/backend.antonov-mark.ru/demo/` — демонстрационные файлы
- `/var/www/backend.antonov-mark.ru/.git/` — репозиторий Git

### 3. Конфигурация Nginx

**Файл конфигурации:** `/etc/nginx/sites-available/backend.antonov-mark.ru`

**Основные блоки:**

#### HTTP-сервер: редирект с www на основной поддомен
```nginx
server {
    listen 80;
    server_name www.backend.antonov-mark.ru;
    return 301 https://backend.antonov-mark.ru$request_uri;
}
```

#### HTTP-сервер: редирект на HTTPS
```nginx
server {
    listen 80;
    server_name backend.antonov-mark.ru;
    
    # Разрешить доступ к .well-known для Let's Encrypt
    location ~ /\.well-known/acme-challenge/ {
        root /var/www/antonov-mark.ru;
        allow all;
    }
    
    return 301 https://backend.antonov-mark.ru$request_uri;
}
```

#### HTTPS-сервер: редирект с www на основной поддомен
```nginx
server {
    listen 443 ssl http2;
    server_name www.backend.antonov-mark.ru;

    ssl_certificate /etc/letsencrypt/live/antonov-mark.ru/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/antonov-mark.ru/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    return 301 https://backend.antonov-mark.ru$request_uri;
}
```

#### HTTPS-сервер: основной поддомен
```nginx
server {
    listen 443 ssl http2;
    server_name backend.antonov-mark.ru;

    # SSL сертификаты
    ssl_certificate /etc/letsencrypt/live/antonov-mark.ru/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/antonov-mark.ru/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # Корневая директория
    root /var/www/backend.antonov-mark.ru;
    index index.html index.php;

    # Максимальный размер загружаемых файлов
    client_max_body_size 50M;

    # Логирование Nginx (отдельные логи для поддомена)
    access_log /var/log/nginx/backend-antonov-mark-access.log;
    error_log /var/log/nginx/backend-antonov-mark-error.log;

    # Обработка PHP файлов через PHP-FPM
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Настройки для PHP-FPM
        fastcgi_intercept_errors on;
        fastcgi_read_timeout 300;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        
        # Логирование PHP ошибок в отдельный файл
        fastcgi_param PHP_VALUE "error_log=/var/log/php/backend-antonov-mark-php-error.log";
    }

    # Статические файлы с кешированием
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Защита конфиденциальных файлов
    location ~ ^/(\.env|\.git|\.svn|\.htaccess|config|logs|\.log)/ {
        deny all;
        return 403;
    }

    # Защита конфигурационных файлов
    location ~ \.(json|log|ini|conf|lock)$ {
        deny all;
        return 403;
    }

    # Запрет доступа к скрытым файлам
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Основная директория
    location / {
        try_files $uri $uri/ =404;
    }

    # Обработка ошибок
    error_page 404 /404.html;
    error_page 500 502 503 504 /50x.html;
    location = /50x.html {
        root /usr/share/nginx/html;
    }
}
```

### 4. Логирование

#### Логи Nginx

**Access log:** `/var/log/nginx/backend-antonov-mark-access.log`  
**Error log:** `/var/log/nginx/backend-antonov-mark-error.log`

**Права доступа:**
- Владелец: `www-data:adm`
- Права: `640`

#### Логи PHP

**Директория:** `/var/log/php/`  
**Файл логов PHP:** `/var/log/php/backend-antonov-mark-php-error.log`

**Настройка:**
- Логирование PHP ошибок настроено через `fastcgi_param PHP_VALUE` в конфигурации Nginx
- Директория создана с правами `www-data:www-data` и `755`

**Создание директории:**
```bash
mkdir -p /var/log/php
chown www-data:www-data /var/log/php
chmod 755 /var/log/php
```

### 5. SSL сертификат

**Сертификат:** Let's Encrypt  
**Путь к сертификату:** `/etc/letsencrypt/live/antonov-mark.ru/fullchain.pem`  
**Путь к приватному ключу:** `/etc/letsencrypt/live/antonov-mark.ru/privkey.pem`

**Статус:** Сертификат действителен, включает поддомен `backend.antonov-mark.ru`

**Срок действия:**
- Выдан: 2025-12-12
- Истекает: 2026-03-12 (81 день до истечения)
- Автоматическое обновление: настроено через certbot

**Проверка сертификата:**
```bash
certbot certificates | grep -A 10 "backend.antonov-mark.ru"
```

**Проверка через OpenSSL:**
```bash
openssl s_client -connect backend.antonov-mark.ru:443 -servername backend.antonov-mark.ru </dev/null 2>/dev/null | openssl x509 -noout -dates -subject
```

### 6. Редирект HTTP → HTTPS

**Настроено:**
- Все HTTP запросы (порт 80) перенаправляются на HTTPS (порт 443)
- Редирект с www.backend.antonov-mark.ru на backend.antonov-mark.ru
- Код редиректа: `301` (постоянный редирект)

**Проверка:**
```bash
curl -I http://backend.antonov-mark.ru
# Должен вернуть: HTTP/1.1 301 Moved Permanently
# Location: https://backend.antonov-mark.ru
```

---

## Проверка работоспособности

### 1. Проверка конфигурации Nginx

```bash
nginx -t
```

**Ожидаемый результат:**
```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

**Примечание:** При проверке может появиться предупреждение:
```
[warn] protocol options redefined for 0.0.0.0:443 in /etc/nginx/sites-enabled/back.antonov-mark.ru:38
```
Это предупреждение не критично и связано с переопределением SSL-опций в других конфигурационных файлах. Работа сервера не нарушена.

### 2. Перезагрузка Nginx

```bash
systemctl reload nginx
```

**Проверка статуса:**
```bash
systemctl status nginx
```

### 3. Проверка HTTPS соединения

```bash
curl -I https://backend.antonov-mark.ru
```

**Ожидаемый результат:**
```
HTTP/2 200
server: nginx/1.24.0 (Ubuntu)
```

### 4. Проверка редиректа HTTP → HTTPS

```bash
curl -I http://backend.antonov-mark.ru
```

**Ожидаемый результат:**
```
HTTP/1.1 301 Moved Permanently
Location: https://backend.antonov-mark.ru
```

### 5. Проверка PHP

Откройте в браузере: `https://backend.antonov-mark.ru/index.php`

**Ожидаемый результат:** Страница с информацией о PHP и сервере

---

## Структура файлов

```
/var/www/backend.antonov-mark.ru/
├── index.html              # HTML тестовая страница
├── index.php               # PHP тестовая страница
├── README.md               # Описание проекта
├── .git/                   # Git репозиторий
├── APP-B24/                # Приложение Bitrix24
│   ├── src/                # Исходный код
│   ├── templates/          # Шаблоны
│   ├── tools/              # Инструменты
│   └── logs/               # Логи приложения
├── demo/                   # Демонстрационные файлы
└── DOCS/                   # Документация проекта
    ├── SERVER_SETUP.md     # Эта документация
    ├── TASKS/              # Задачи проекта
    ├── ARCHITECTURE/       # Архитектура
    ├── ANALYSIS/           # Анализ
    ├── PLAN/               # Планы
    ├── GUIDES/             # Руководства
    └── API-REFERENCES/     # Справочники API

/etc/nginx/
├── sites-available/
│   └── backend.antonov-mark.ru    # Конфигурация Nginx
└── sites-enabled/
    └── backend.antonov-mark.ru -> ../sites-available/backend.antonov-mark.ru

/var/log/
├── nginx/
│   ├── backend-antonov-mark-access.log    # Access log Nginx
│   └── backend-antonov-mark-error.log     # Error log Nginx
└── php/
    └── backend-antonov-mark-php-error.log # PHP error log
```

---

## Технические характеристики

### Версии ПО

- **Nginx:** 1.24.0 (Ubuntu)
- **PHP (CLI):** 8.3.6 (built: Jul 14 2025)
- **PHP-FPM:** 8.3.6 (fpm-fcgi)
- **SSL:** Let's Encrypt
- **Git:** установлен (репозиторий в корне проекта)

### Настройки

- **Максимальный размер загружаемых файлов:** 50M
- **Таймаут FastCGI:** 300 секунд
- **Кеширование статических файлов:** 1 год
- **HTTP/2:** Включен

---

## Безопасность

### Защищённые директории и файлы

- `.env`, `.git`, `.svn`, `.htaccess`
- `config/`, `logs/`
- Файлы: `.json`, `.log`, `.ini`, `.conf`, `.lock`
- Скрытые файлы (начинающиеся с `.`)

### SSL/TLS

- TLS 1.2 и выше
- Современные шифры
- HSTS (если настроен в основном конфиге)

---

## Обслуживание

### Просмотр логов

**Access log:**
```bash
tail -f /var/log/nginx/backend-antonov-mark-access.log
```

**Error log:**
```bash
tail -f /var/log/nginx/backend-antonov-mark-error.log
```

**PHP error log:**
```bash
tail -f /var/log/php/backend-antonov-mark-php-error.log
```

**Размеры логов (по состоянию на 2025-12-20):**
- Access log: ~32 KB
- Error log: ~8 KB
- PHP error log: ~130 KB

### Известные проблемы

**1. Отсутствие файла 50x.html**
- **Проблема:** В логах Nginx встречаются ошибки о недоступности `/usr/share/nginx/html/50x.html`
- **Причина:** Файл страницы ошибки не создан
- **Решение:** Создать кастомную страницу ошибки или использовать стандартную:
```bash
# Создать кастомную страницу ошибки
echo "<h1>Ошибка сервера</h1>" > /var/www/backend.antonov-mark.ru/50x.html

# Или обновить конфигурацию для использования стандартной страницы
# (уже настроено в конфиге, но файл может отсутствовать)
```

**2. Отсутствие favicon.ico**
- **Проблема:** В логах встречаются запросы к несуществующему `/favicon.ico`
- **Причина:** Файл favicon не создан
- **Решение:** Создать favicon.ico или добавить в конфигурацию Nginx:
```nginx
location = /favicon.ico {
    log_not_found off;
    access_log off;
    return 204;
}
```

**3. Предупреждение Nginx о переопределении протокольных опций**
- **Проблема:** При проверке конфигурации появляется предупреждение о переопределении SSL-опций
- **Причина:** Несколько конфигураций используют один и тот же порт 443
- **Статус:** Не критично, работа сервера не нарушена

### Обновление SSL сертификата

Сертификат обновляется автоматически через certbot. Для ручного обновления:

```bash
certbot renew
systemctl reload nginx
```

---

## История изменений

- **2025-12-20 19:04 (UTC+3, Брест):** Обновлена документация
  - Добавлена информация о текущей структуре проекта (APP-B24, DOCS, demo)
  - Обновлена информация о SSL сертификате (срок действия до 2026-03-12)
  - Добавлена информация о версиях ПО (PHP 8.3.6)
  - Добавлен раздел "Известные проблемы" с описанием найденных ошибок в логах
  - Добавлена информация о предупреждении Nginx при проверке конфигурации
  - Обновлена структура файлов с актуальными директориями

- **2025-12-19 11:16 (UTC+3, Брест):** Создана конфигурация для поддомена backend.antonov-mark.ru
  - Создана директория сайта
  - Добавлены тестовые страницы (HTML и PHP)
  - Настроена конфигурация Nginx с отдельными логами
  - Настроено логирование PHP
  - Проверен SSL сертификат
  - Настроен редирект HTTP → HTTPS

---

## Контакты и поддержка

При возникновении проблем проверьте:
1. Статус Nginx: `systemctl status nginx`
2. Логи ошибок: `/var/log/nginx/backend-antonov-mark-error.log`
3. Логи PHP: `/var/log/php/backend-antonov-mark-php-error.log`
4. Конфигурацию: `nginx -t`
5. Доступность сайта: `curl -I https://backend.antonov-mark.ru`
6. SSL сертификат: `certbot certificates`

### Статус сервера (по состоянию на 2025-12-20)

- **Nginx:** активен и работает (running)
- **PHP-FPM:** работает (unix:/run/php/php8.3-fpm.sock)
- **SSL:** сертификат действителен до 2026-03-12
- **HTTPS:** работает корректно (HTTP/2 200)
- **Редирект HTTP→HTTPS:** работает
- **Логирование:** настроено и работает

