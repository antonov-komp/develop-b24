# План действий: Исправление ошибки 404 для API endpoints в Bitrix24

**Дата создания:** 2025-12-21 08:20 (UTC+3, Брест)  
**Статус:** В работе  
**Приоритет:** Высокий  
**Проблема:** Vue.js приложение получает 404 при запросах к `/APP-B24/api/user.php` из Bitrix24

---

## Контекст проблемы

### Симптомы
- Vue.js приложение успешно монтируется в Bitrix24
- Запрос к API: `GET /APP-B24/api/user.php?action=current&AUTH_ID=...&DOMAIN=...`
- Nginx возвращает **404** вместо обработки PHP файла
- Прямой запрос через curl возвращает **401** (файл обрабатывается PHP-FPM)

### Обнаруженные факты
1. ✅ Файл `/var/www/backend.antonov-mark.ru/APP-B24/api/user.php` существует
2. ✅ Nginx конфигурация содержит location для `/APP-B24/api/[^/]+\.php$`
3. ✅ PHP-FPM настроен: `unix:/run/php/php8.3-fpm.sock`
4. ❌ Nginx логи показывают 404 для запросов из браузера
5. ✅ Прямой curl запрос возвращает 401 (PHP обрабатывается)

---

## План действий

### Этап 1: Диагностика проблемы

#### Действие 1.1: Проверка логов PHP-FPM
**Цель:** Определить, доходит ли запрос до PHP-FPM

**Команды:**
```bash
# Проверка логов PHP-FPM
sudo tail -f /var/log/php/backend-antonov-mark-php-error.log

# Или общие логи PHP-FPM
sudo tail -f /var/log/php8.3-fpm.log
```

**Ожидаемый результат:**
- Если запрос доходит до PHP-FPM — увидим ошибки PHP или логи
- Если запрос не доходит — логов не будет (проблема в nginx)

**Критерий успеха:** Определено, обрабатывается ли запрос PHP-FPM

---

#### Действие 1.2: Проверка конфигурации nginx
**Цель:** Убедиться, что location блок правильно настроен

**Команды:**
```bash
# Проверка синтаксиса nginx
sudo nginx -t

# Просмотр текущей конфигурации
sudo cat /etc/nginx/sites-available/backend.antonov-mark.ru | grep -A 15 "APP-B24/api"
```

**Проверяемые параметры:**
- ✅ `root /var/www/backend.antonov-mark.ru;` — правильный корень
- ✅ `location ~ ^/APP-B24/api/[^/]+\.php$` — правильное регулярное выражение
- ✅ `fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;` — правильный путь к файлу

**Критерий успеха:** Конфигурация nginx корректна

---

#### Действие 1.3: Проверка прав доступа к файлам
**Цель:** Убедиться, что nginx может читать файлы

**Команды:**
```bash
# Проверка прав доступа к файлу
ls -la /var/www/backend.antonov-mark.ru/APP-B24/api/user.php

# Проверка прав на директорию
ls -la /var/www/backend.antonov-mark.ru/APP-B24/api/

# Проверка владельца
stat /var/www/backend.antonov-mark.ru/APP-B24/api/user.php
```

**Ожидаемый результат:**
- Файл: `-rw-r--r-- www-data www-data` или `-rw-r--r-- root root`
- Директория: `drwxr-xr-x www-data www-data` или `drwxr-xr-x root root`

**Критерий успеха:** Права доступа корректны

---

#### Действие 1.4: Тестирование запроса напрямую
**Цель:** Проверить, работает ли файл при прямом запросе

**Команды:**
```bash
# Тест через curl (локально)
curl -v "https://backend.antonov-mark.ru/APP-B24/api/user.php?action=current&AUTH_ID=test&DOMAIN=test"

# Тест через curl с заголовками
curl -I "https://backend.antonov-mark.ru/APP-B24/api/user.php?action=current&AUTH_ID=test&DOMAIN=test"

# Тест с логированием запроса
curl -v -H "User-Agent: Mozilla/5.0" "https://backend.antonov-mark.ru/APP-B24/api/user.php?action=current&AUTH_ID=test&DOMAIN=test" 2>&1 | grep -E "HTTP|404|401|500"
```

**Ожидаемый результат:**
- 401 — файл обрабатывается PHP (правильно)
- 404 — файл не найден nginx (проблема)
- 500 — ошибка PHP (другая проблема)

**Критерий успеха:** Определен тип ошибки

---

#### Действие 1.5: Проверка переменных nginx в runtime
**Цель:** Убедиться, что `$document_root` и `$fastcgi_script_name` правильные

**Создать тестовый файл:**
```bash
# Создать тестовый PHP файл для отладки
sudo tee /var/www/backend.antonov-mark.ru/APP-B24/api/debug.php << 'EOF'
<?php
header('Content-Type: application/json');
echo json_encode([
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'not set',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
    'file_exists' => file_exists(__FILE__),
    'realpath' => realpath(__FILE__),
], JSON_PRETTY_PRINT);
EOF
```

**Тест:**
```bash
curl "https://backend.antonov-mark.ru/APP-B24/api/debug.php"
```

**Ожидаемый результат:**
- `document_root` = `/var/www/backend.antonov-mark.ru`
- `script_filename` = `/var/www/backend.antonov-mark.ru/APP-B24/api/debug.php`
- `file_exists` = `true`

**Критерий успеха:** Переменные nginx корректны

---

### Этап 2: Исправление проблемы

#### Действие 2.1: Исправление location блока в nginx (если нужно)
**Цель:** Убедиться, что location блок правильно обрабатывает запросы

**Текущая конфигурация:**
```nginx
location ~ ^/APP-B24/api/[^/]+\.php$ {
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
    
    fastcgi_intercept_errors off;
    fastcgi_read_timeout 300;
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    fastcgi_param PHP_VALUE "error_log=/var/log/php/backend-antonov-mark-php-error.log";
}
```

**Возможные исправления:**

**Вариант 1: Добавить try_files (если файл не найден)**
```nginx
location ~ ^/APP-B24/api/[^/]+\.php$ {
    try_files $uri =404;
    
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
    
    fastcgi_intercept_errors off;
    fastcgi_read_timeout 300;
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    fastcgi_param PHP_VALUE "error_log=/var/log/php/backend-antonov-mark-php-error.log";
}
```

**Вариант 2: Использовать более точное регулярное выражение**
```nginx
location ~ ^/APP-B24/api/([^/]+)\.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root/APP-B24/api/$1.php;
    include fastcgi_params;
    
    fastcgi_intercept_errors off;
    fastcgi_read_timeout 300;
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    fastcgi_param PHP_VALUE "error_log=/var/log/php/backend-antonov-mark-php-error.log";
}
```

**Команды:**
```bash
# Редактирование конфигурации
sudo nano /etc/nginx/sites-available/backend.antonov-mark.ru

# Проверка синтаксиса
sudo nginx -t

# Перезагрузка nginx
sudo systemctl reload nginx
```

**Критерий успеха:** Nginx конфигурация обновлена и перезагружена

---

#### Действие 2.2: Проверка конфликтов location блоков
**Цель:** Убедиться, что другие location блоки не перехватывают запросы

**Проверка:**
```bash
# Просмотр всех location блоков
sudo grep -n "location" /etc/nginx/sites-available/backend.antonov-mark.ru

# Проверка приоритета (более специфичные location должны быть выше)
```

**Важно:**
- Location с регулярными выражениями (`~`) имеют приоритет над обычными
- Более специфичные location должны быть выше в конфигурации
- Location для `/APP-B24/api/` должен быть **ПЕРЕД** общим `location /`

**Критерий успеха:** Нет конфликтов location блоков

---

#### Действие 2.3: Исправление прав доступа (если нужно)
**Цель:** Убедиться, что nginx может читать файлы

**Команды:**
```bash
# Если файлы принадлежат root, изменить владельца
sudo chown -R www-data:www-data /var/www/backend.antonov-mark.ru/APP-B24/api/

# Установить правильные права
sudo chmod 644 /var/www/backend.antonov-mark.ru/APP-B24/api/*.php
sudo chmod 755 /var/www/backend.antonov-mark.ru/APP-B24/api/
```

**Критерий успеха:** Права доступа исправлены

---

### Этап 3: Тестирование решения

#### Действие 3.1: Тест через curl
**Цель:** Проверить, что API endpoint работает

**Команды:**
```bash
# Тест с правильными параметрами
curl -v "https://backend.antonov-mark.ru/APP-B24/api/user.php?action=current&AUTH_ID=test&DOMAIN=test"

# Проверка статуса
curl -I "https://backend.antonov-mark.ru/APP-B24/api/user.php?action=current&AUTH_ID=test&DOMAIN=test"
```

**Ожидаемый результат:**
- HTTP 401 (требуется авторизация) — **правильно** (файл обрабатывается)
- HTTP 200 (успех) — **отлично** (если переданы правильные параметры)
- HTTP 404 — **проблема не решена**

**Критерий успеха:** Запрос обрабатывается PHP (не 404)

---

#### Действие 3.2: Тест из Bitrix24
**Цель:** Проверить, что Vue.js приложение может делать запросы

**Шаги:**
1. Открыть приложение в Bitrix24
2. Открыть консоль браузера (F12)
3. Проверить запрос к `/APP-B24/api/user.php`
4. Проверить ответ сервера

**Ожидаемый результат:**
- Запрос успешен (200 или 401, но не 404)
- В консоли нет ошибок 404

**Критерий успеха:** Vue.js приложение успешно делает запросы

---

#### Действие 3.3: Проверка логов после исправления
**Цель:** Убедиться, что ошибки 404 больше не появляются

**Команды:**
```bash
# Мониторинг логов nginx в реальном времени
sudo tail -f /var/log/nginx/backend-antonov-mark-access.log | grep "user.php"

# Проверка ошибок
sudo tail -f /var/log/nginx/backend-antonov-mark-error.log
```

**Ожидаемый результат:**
- В access.log видны запросы с кодом 200 или 401 (не 404)
- В error.log нет ошибок 404 для `/APP-B24/api/user.php`

**Критерий успеха:** Ошибки 404 устранены

---

### Этап 4: Очистка и документация

#### Действие 4.1: Удаление тестовых файлов
**Цель:** Удалить временные файлы для отладки

**Команды:**
```bash
# Удалить debug.php (если создавали)
sudo rm /var/www/backend.antonov-mark.ru/APP-B24/api/debug.php
```

**Критерий успеха:** Тестовые файлы удалены

---

#### Действие 4.2: Обновление документации
**Цель:** Задокументировать решение проблемы

**Файлы для обновления:**
- `APP-B24/NGINX-CONFIG-REQUIRED.md` — обновить, если конфигурация изменилась
- `DOCS/SERVER_SETUP.md` — добавить информацию о настройке API endpoints

**Критерий успеха:** Документация обновлена

---

## Чек-лист выполнения

### Диагностика
- [ ] Действие 1.1: Проверены логи PHP-FPM
- [ ] Действие 1.2: Проверена конфигурация nginx
- [ ] Действие 1.3: Проверены права доступа
- [ ] Действие 1.4: Протестирован запрос напрямую
- [ ] Действие 1.5: Проверены переменные nginx

### Исправление
- [ ] Действие 2.1: Исправлен location блок (если нужно)
- [ ] Действие 2.2: Проверены конфликты location блоков
- [ ] Действие 2.3: Исправлены права доступа (если нужно)

### Тестирование
- [ ] Действие 3.1: Протестирован запрос через curl
- [ ] Действие 3.2: Протестировано из Bitrix24
- [ ] Действие 3.3: Проверены логи после исправления

### Очистка
- [ ] Действие 4.1: Удалены тестовые файлы
- [ ] Действие 4.2: Обновлена документация

---

## Ожидаемые результаты

### После выполнения плана:
1. ✅ API endpoints обрабатываются PHP-FPM (не 404)
2. ✅ Vue.js приложение успешно делает запросы к API
3. ✅ В логах nginx нет ошибок 404 для `/APP-B24/api/*.php`
4. ✅ Документация обновлена с решением проблемы

---

## История правок

- **2025-12-21 08:20 (UTC+3, Брест):** Создан план действий для исправления ошибки 404

---

## Примечания

- Все команды выполняются от имени root или с sudo
- Перед изменением конфигурации nginx всегда проверяйте синтаксис: `sudo nginx -t`
- После изменений перезагружайте nginx: `sudo systemctl reload nginx`
- Сохраняйте резервные копии конфигурации перед изменениями

