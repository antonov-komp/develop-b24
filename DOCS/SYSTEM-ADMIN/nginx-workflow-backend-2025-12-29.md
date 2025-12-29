# Описание работы NGINX на поддомене backend.antonov-mark.ru

**Дата создания:** 2025-12-29 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Детальное описание работы веб-сервера NGINX для поддомена backend.antonov-mark.ru

---

## Общая информация

**Домен:** `backend.antonov-mark.ru`  
**Корневая директория:** `/var/www/backend.antonov-mark.ru`  
**Конфигурационный файл:** `/etc/nginx/sites-available/backend.antonov-mark.ru`  
**Активная конфигурация:** `/etc/nginx/sites-enabled/backend.antonov-mark.ru` (символическая ссылка)  
**Версия NGINX:** 1.24.0 (Ubuntu)

---

## Архитектура обработки запросов

### Схема работы NGINX

```
Клиент → NGINX (порт 80/443) → PHP-FPM (unix socket) → Приложение
                              ↓
                         Статические файлы (JS, CSS, изображения)
```

### Этапы обработки запроса

1. **Получение запроса** — NGINX принимает HTTP/HTTPS запрос
2. **Определение виртуального хоста** — по `Host` заголовку (`backend.antonov-mark.ru`)
3. **Проверка безопасности** — блокировка доступа к защищённым файлам
4. **Маршрутизация** — определение типа запроса (PHP, статика, директория)
5. **Обработка** — передача PHP в PHP-FPM или отдача статики
6. **Отправка ответа** — возврат результата клиенту

---

## Конфигурация серверов

### 1. HTTP сервер (порт 80) — редирект на HTTPS

**Назначение:** Перенаправление всех HTTP запросов на HTTPS

```nginx
server {
    listen 80;
    server_name backend.antonov-mark.ru www.backend.antonov-mark.ru;
    
    # Разрешить доступ к .well-known для Let's Encrypt
    location ~ /\.well-known/acme-challenge/ {
        root /var/www/antonov-mark.ru;
        allow all;
    }
    
    # Редирект всех остальных запросов на HTTPS
    return 301 https://backend.antonov-mark.ru$request_uri;
}
```

**Как работает:**
- Все запросы на порт 80 перенаправляются на порт 443 с кодом 301 (постоянный редирект)
- Исключение: путь `/\.well-known/acme-challenge/` для обновления SSL сертификата Let's Encrypt
- Редирект с `www.backend.antonov-mark.ru` на `backend.antonov-mark.ru`

### 2. HTTPS сервер (порт 443) — основной сервер

**Назначение:** Обработка всех HTTPS запросов к приложению

#### 2.1. Базовые настройки

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
}
```

**Параметры:**
- **HTTP/2:** Включён для улучшения производительности
- **SSL:** Let's Encrypt сертификат от основного домена `antonov-mark.ru`
- **Root:** `/var/www/backend.antonov-mark.ru` — корневая директория сайта
- **Index:** `index.html index.php` — файлы по умолчанию
- **client_max_body_size:** 50M — максимальный размер загружаемых файлов

#### 2.2. Логирование

```nginx
access_log /var/log/nginx/backend-antonov-mark-access.log;
error_log /var/log/nginx/backend-antonov-mark-error.log;
```

**Логи:**
- **Access log:** Все успешные запросы к серверу
- **Error log:** Ошибки NGINX и проблемы с обработкой запросов
- **PHP error log:** `/var/log/php/backend-antonov-mark-php-error.log` (настраивается через PHP-FPM)

---

## Обработка различных типов запросов

### 1. PHP файлы (динамический контент)

**Location блок:**
```nginx
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
    
    # Логирование PHP ошибок
    fastcgi_param PHP_VALUE "error_log=/var/log/php/backend-antonov-mark-php-error.log";
}
```

**Как работает:**
1. **Регулярное выражение:** `~ \.php$` — совпадение всех файлов с расширением `.php`
2. **Проверка существования:** `try_files $uri =404` — если файл не найден, возвращается 404
3. **Разделение пути:** `fastcgi_split_path_info` — разделение пути на скрипт и дополнительный путь
4. **Передача в PHP-FPM:** `fastcgi_pass unix:/run/php/php8.3-fpm.sock` — передача через Unix socket
5. **Параметры FastCGI:** Установка `SCRIPT_FILENAME` и других параметров
6. **Таймауты:** 300 секунд на выполнение PHP скрипта
7. **Буферы:** 16 буферов по 16KB для обработки ответа

**Пример запроса:**
```
Запрос: GET /APP-B24/index.php
→ NGINX находит файл /var/www/backend.antonov-mark.ru/APP-B24/index.php
→ Передаёт в PHP-FPM через unix socket
→ PHP-FPM выполняет скрипт
→ Результат возвращается через NGINX клиенту
```

### 2. Статические файлы (JS, CSS, изображения)

**Location блок:**
```nginx
location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
}
```

**Как работает:**
1. **Регулярное выражение:** `~*` — регистронезависимое совпадение расширений файлов
2. **Кеширование:** `expires 1y` — файлы кешируются на 1 год
3. **Cache-Control:** `public, immutable` — файлы не изменяются, можно кешировать агрессивно
4. **Логирование:** `access_log off` — не логировать запросы к статике (экономия места)

**Пример запроса:**
```
Запрос: GET /APP-B24/public/dist/assets/main-DYnjAQE_.js
→ NGINX находит файл в /var/www/backend.antonov-mark.ru/APP-B24/public/dist/assets/main-DYnjAQE_.js
→ Отдаёт файл напрямую (без PHP-FPM)
→ Добавляет заголовки кеширования
→ Клиент кеширует файл на 1 год
```

**Типы статических файлов:**
- **JavaScript:** `.js`
- **Стили:** `.css`
- **Изображения:** `.png`, `.jpg`, `.jpeg`, `.gif`, `.ico`, `.svg`
- **Шрифты:** `.woff`, `.woff2`, `.ttf`, `.eot`

### 3. Защита конфиденциальных файлов

**Location блоки:**
```nginx
# Защита конфиденциальных файлов и директорий
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
```

**Как работает:**
1. **Проверка пути:** Регулярные выражения проверяют путь запроса
2. **Блокировка:** `deny all` — запрет доступа для всех
3. **Ответ:** `return 403` — возврат ошибки "Forbidden"
4. **Логирование:** Отключено для скрытых файлов (экономия места)

**Защищённые файлы и директории:**
- `.env` — переменные окружения
- `.git` — репозиторий Git
- `.svn` — репозиторий SVN
- `.htaccess` — конфигурация Apache
- `config/`, `logs/` — конфигурация и логи
- `.json`, `.log`, `.ini`, `.conf`, `.lock` — конфигурационные файлы
- Все файлы, начинающиеся с `.` (скрытые файлы)

**Пример запроса:**
```
Запрос: GET /.env
→ NGINX проверяет путь через регулярное выражение
→ Совпадение с защищённым паттерном
→ Возврат 403 Forbidden
→ Файл не отдаётся клиенту
```

### 4. Основная директория (fallback)

**Location блок:**
```nginx
location / {
    try_files $uri $uri/ =404;
}
```

**Как работает:**
1. **Попытка 1:** `$uri` — проверка существования файла по запрошенному пути
2. **Попытка 2:** `$uri/` — проверка существования директории
3. **Fallback:** `=404` — если ничего не найдено, возвращается 404

**Пример запроса:**
```
Запрос: GET /
→ NGINX проверяет /var/www/backend.antonov-mark.ru/index.html
→ Если не найден, проверяет /var/www/backend.antonov-mark.ru/index.php
→ Если найден, отдаёт файл
→ Если не найден, возвращает 404
```

### 5. Обработка ошибок

**Location блоки:**
```nginx
error_page 404 /404.html;
error_page 500 502 503 504 /50x.html;
location = /50x.html {
    root /usr/share/nginx/html;
}
```

**Как работает:**
- **404 ошибка:** Отображение кастомной страницы `/404.html` (если существует)
- **5xx ошибки:** Отображение страницы `/50x.html` из стандартной директории NGINX

---

## Интеграция с PHP-FPM

### Схема взаимодействия

```
NGINX → FastCGI Protocol → PHP-FPM (unix socket) → PHP скрипт → Результат
```

### Параметры FastCGI

**Основные параметры:**
- **fastcgi_pass:** `unix:/run/php/php8.3-fpm.sock` — Unix socket для связи с PHP-FPM
- **fastcgi_param SCRIPT_FILENAME:** Полный путь к PHP файлу
- **fastcgi_read_timeout:** 300 секунд — максимальное время ожидания ответа
- **fastcgi_buffers:** 16 буферов по 16KB — буферизация ответа
- **fastcgi_intercept_errors:** Включено — перехват ошибок PHP для обработки NGINX

### Процесс выполнения PHP запроса

1. **NGINX получает запрос:** `GET /APP-B24/index.php`
2. **Проверка файла:** Существует ли `/var/www/backend.antonov-mark.ru/APP-B24/index.php`
3. **Подготовка FastCGI запроса:** Формирование параметров (SCRIPT_FILENAME, QUERY_STRING и т.д.)
4. **Отправка в PHP-FPM:** Через Unix socket `/run/php/php8.3-fpm.sock`
5. **PHP-FPM обрабатывает:** Выполняет PHP скрипт
6. **Получение результата:** PHP-FPM возвращает HTML/JSON/другой контент
7. **Отправка клиенту:** NGINX отправляет результат с правильными заголовками

---

## Безопасность

### 1. SSL/TLS

**Настройки:**
- **Сертификат:** Let's Encrypt (автоматическое обновление)
- **Протоколы:** TLS 1.2 и выше
- **Шифры:** Современные шифры (настраиваются через `/etc/letsencrypt/options-ssl-nginx.conf`)
- **HTTP/2:** Включён для улучшения производительности

### 2. Защита файлов

**Блокировка доступа к:**
- Конфиденциальным файлам (`.env`, `.git`)
- Конфигурационным файлам (`.json`, `.log`, `.ini`, `.conf`)
- Скрытым файлам (начинающимся с `.`)
- Директориям с конфигурацией (`config/`, `logs/`)

### 3. Ограничения

**Максимальный размер загружаемых файлов:** 50MB
```nginx
client_max_body_size 50M;
```

---

## Производительность

### 1. Кеширование статических файлов

**Настройки:**
- **Время кеширования:** 1 год (`expires 1y`)
- **Cache-Control:** `public, immutable` — файлы не изменяются
- **Логирование:** Отключено для статики (экономия места на диске)

### 2. HTTP/2

**Преимущества:**
- Мультиплексирование запросов (несколько запросов в одном соединении)
- Сжатие заголовков
- Приоритизация запросов

### 3. Буферизация FastCGI

**Настройки:**
- **Буферы:** 16 буферов по 16KB
- **Размер буфера:** 32KB
- **Таймаут:** 300 секунд

---

## Логирование

### Типы логов

1. **Access log** (`/var/log/nginx/backend-antonov-mark-access.log`)
   - Все успешные запросы к серверу
   - Формат: IP адрес, время, метод, URL, статус код, размер ответа

2. **Error log** (`/var/log/nginx/backend-antonov-mark-error.log`)
   - Ошибки NGINX
   - Проблемы с обработкой запросов
   - Ошибки FastCGI

3. **PHP error log** (`/var/log/php/backend-antonov-mark-php-error.log`)
   - Ошибки выполнения PHP скриптов
   - Настраивается через `fastcgi_param PHP_VALUE`

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

---

## Примеры обработки запросов

### Пример 1: Запрос к PHP файлу

```
Клиент: GET https://backend.antonov-mark.ru/APP-B24/index.php

1. NGINX получает запрос на порт 443
2. Проверяет SSL сертификат
3. Определяет виртуальный хост (backend.antonov-mark.ru)
4. Проверяет путь: /APP-B24/index.php
5. Совпадение с location ~ \.php$
6. Проверяет существование файла: /var/www/backend.antonov-mark.ru/APP-B24/index.php
7. Формирует FastCGI запрос
8. Отправляет в PHP-FPM через unix:/run/php/php8.3-fpm.sock
9. PHP-FPM выполняет скрипт
10. Получает результат (HTML/JSON)
11. Отправляет клиенту с заголовками HTTP/2
```

### Пример 2: Запрос к статическому файлу

```
Клиент: GET https://backend.antonov-mark.ru/APP-B24/public/dist/assets/main.js

1. NGINX получает запрос на порт 443
2. Проверяет SSL сертификат
3. Определяет виртуальный хост
4. Проверяет путь: /APP-B24/public/dist/assets/main.js
5. Совпадение с location ~* \.(js|css|...)$
6. Находит файл: /var/www/backend.antonov-mark.ru/APP-B24/public/dist/assets/main.js
7. Отдаёт файл напрямую (без PHP-FPM)
8. Добавляет заголовки: Cache-Control: public, immutable, expires: 1y
9. Отправляет клиенту
10. Клиент кеширует файл на 1 год
```

### Пример 3: Попытка доступа к защищённому файлу

```
Клиент: GET https://backend.antonov-mark.ru/.env

1. NGINX получает запрос на порт 443
2. Проверяет SSL сертификат
3. Определяет виртуальный хост
4. Проверяет путь: /.env
5. Совпадение с location ~ ^/(\.env|\.git|...)/ (защита)
6. Выполняет deny all
7. Возвращает 403 Forbidden
8. Файл не отдаётся клиенту
```

### Пример 4: Редирект HTTP → HTTPS

```
Клиент: GET http://backend.antonov-mark.ru/APP-B24/

1. NGINX получает запрос на порт 80
2. Определяет виртуальный хост (backend.antonov-mark.ru)
3. Проверяет путь: не /\.well-known/acme-challenge/
4. Выполняет return 301 https://backend.antonov-mark.ru$request_uri
5. Отправляет клиенту редирект: Location: https://backend.antonov-mark.ru/APP-B24/
6. Клиент автоматически переходит на HTTPS
```

---

## Проверка работоспособности

### 1. Проверка конфигурации

```bash
sudo nginx -t
```

**Ожидаемый результат:**
```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

### 2. Проверка статуса NGINX

```bash
sudo systemctl status nginx
```

**Ожидаемый результат:**
```
Active: active (running)
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

```bash
curl https://backend.antonov-mark.ru/index.php
```

**Ожидаемый результат:** HTML страница с информацией о PHP

### 6. Проверка статических файлов

```bash
curl -I https://backend.antonov-mark.ru/APP-B24/public/dist/assets/main.js
```

**Ожидаемый результат:**
```
HTTP/2 200
Cache-Control: public, immutable
Expires: [дата через 1 год]
```

---

## Управление конфигурацией

### Перезагрузка NGINX

**После изменения конфигурации:**
```bash
sudo nginx -t                    # Проверка конфигурации
sudo systemctl reload nginx      # Перезагрузка без прерывания работы
```

**Полная перезагрузка:**
```bash
sudo systemctl restart nginx      # Полная перезагрузка (прерывает соединения)
```

### Просмотр активной конфигурации

```bash
sudo nginx -T | grep -A 200 "server_name backend.antonov-mark.ru"
```

### Проверка активных соединений

```bash
sudo netstat -tulpn | grep nginx
```

---

## Структура файлов

```
/etc/nginx/
├── sites-available/
│   └── backend.antonov-mark.ru          # Конфигурация (источник)
└── sites-enabled/
    └── backend.antonov-mark.ru -> ../sites-available/backend.antonov-mark.ru  # Символическая ссылка

/var/www/backend.antonov-mark.ru/         # Корневая директория сайта
├── index.html
├── index.php
├── APP-B24/                             # Приложение Bitrix24
│   ├── index.php
│   ├── public/
│   │   └── dist/
│   │       └── assets/
│   └── ...
└── DOCS/

/var/log/
├── nginx/
│   ├── backend-antonov-mark-access.log  # Access log
│   └── backend-antonov-mark-error.log   # Error log
└── php/
    └── backend-antonov-mark-php-error.log # PHP error log
```

---

## Технические характеристики

### Версии ПО

- **NGINX:** 1.24.0 (Ubuntu)
- **PHP-FPM:** 8.3.6
- **SSL:** Let's Encrypt
- **HTTP/2:** Включён

### Настройки производительности

- **Максимальный размер загружаемых файлов:** 50MB
- **Таймаут FastCGI:** 300 секунд
- **Кеширование статики:** 1 год
- **Буферы FastCGI:** 16 × 16KB

### Безопасность

- **SSL/TLS:** TLS 1.2+
- **Защита файлов:** Блокировка доступа к конфиденциальным файлам
- **Логирование:** Отдельные логи для поддомена

---

## Известные особенности

### 1. Предупреждение о переопределении SSL опций

**Проблема:**
```
[warn] protocol options redefined for 0.0.0.0:443
```

**Причина:** Несколько конфигураций используют один порт 443

**Статус:** Не критично, работа сервера не нарушена

### 2. Отсутствие файла 50x.html

**Проблема:** В логах могут быть ошибки о недоступности `/usr/share/nginx/html/50x.html`

**Решение:** Файл создаётся автоматически или используется стандартная страница NGINX

### 3. Запросы к favicon.ico

**Проблема:** В логах встречаются запросы к несуществующему `/favicon.ico`

**Решение:** Можно добавить в конфигурацию:
```nginx
location = /favicon.ico {
    log_not_found off;
    access_log off;
    return 204;
}
```

---

## Резюме

NGINX на поддомене `backend.antonov-mark.ru` работает как:

1. **Обратный прокси** — принимает HTTP/HTTPS запросы и маршрутизирует их
2. **Статический сервер** — отдаёт статические файлы (JS, CSS, изображения) с кешированием
3. **FastCGI прокси** — передаёт PHP запросы в PHP-FPM через Unix socket
4. **Защитный барьер** — блокирует доступ к конфиденциальным файлам
5. **SSL терминатор** — обрабатывает SSL/TLS соединения

**Основные преимущества:**
- Высокая производительность (HTTP/2, кеширование)
- Безопасность (SSL, защита файлов)
- Гибкость (настройка обработки различных типов запросов)
- Логирование (отдельные логи для поддомена)

---

**История правок:**
- 2025-12-29 (UTC+3, Брест): Создан документ с описанием работы NGINX


