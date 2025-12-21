# Быстрое решение: Ошибка 404 для API endpoints

**Дата:** 2025-12-21 08:17 (UTC+3, Брест)  
**Проблема:** Vue.js получает 404 при запросах к `/APP-B24/api/user.php`

---

## Быстрая диагностика

```bash
# 1. Проверить логи PHP-FPM
sudo tail -f /var/log/php/backend-antonov-mark-php-error.log

# 2. Проверить конфигурацию nginx
sudo nginx -t
sudo cat /etc/nginx/sites-available/backend.antonov-mark.ru | grep -A 15 "APP-B24/api"

# 3. Тест запроса
curl -I "https://backend.antonov-mark.ru/APP-B24/api/user.php?action=current&AUTH_ID=test&DOMAIN=test"
```

---

## Возможные решения

### Решение 1: Добавить try_files в location блок

```nginx
location ~ ^/APP-B24/api/[^/]+\.php$ {
    try_files $uri =404;  # ← Добавить эту строку
    
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    # ... остальная конфигурация
}
```

### Решение 2: Исправить SCRIPT_FILENAME

```nginx
location ~ ^/APP-B24/api/([^/]+)\.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root/APP-B24/api/$1.php;
    include fastcgi_params;
}
```

### Решение 3: Исправить права доступа

```bash
sudo chown -R www-data:www-data /var/www/backend.antonov-mark.ru/APP-B24/api/
sudo chmod 644 /var/www/backend.antonov-mark.ru/APP-B24/api/*.php
```

---

## После исправления

```bash
# Проверить синтаксис
sudo nginx -t

# Перезагрузить nginx
sudo systemctl reload nginx

# Проверить работу
curl -I "https://backend.antonov-mark.ru/APP-B24/api/user.php?action=current&AUTH_ID=test&DOMAIN=test"
```

**Ожидаемый результат:** HTTP 401 (не 404) — файл обрабатывается PHP

---

## Финальное решение (работает)

**Конфигурация nginx:**
```nginx
location ~ ^/APP-B24/api/([^/]+)\.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root/APP-B24/api/$1.php;
    include fastcgi_params;
    # ... остальные параметры
}
```

**Изменения:**
1. ✅ Использован обычный capture `$1` вместо именованного `(?<filename>...)`
2. ✅ Явно указан путь к файлу через `$document_root/APP-B24/api/$1.php`
3. ✅ Убран `fastcgi_split_path_info` (не нужен для простых запросов)

---

## Важно

**Если запрос доходит до PHP, но возвращает 404:**

Это может быть правильное поведение PHP, когда пользователь не найден:
```php
if (!$user) {
    http_response_code(404);  // ← PHP устанавливает код 404
    echo json_encode([
        'success' => false,
        'error' => 'User not found'
    ]);
}
```

**Решение:** Вернуть HTTP 200 с ошибкой в JSON вместо 404:
```php
if (!$user) {
    http_response_code(200);  // ← Возвращаем 200, но с ошибкой в JSON
    echo json_encode([
        'success' => false,
        'error' => 'User not found',
        'message' => 'Unable to get current user from Bitrix24'
    ]);
}
```

---

📖 **Полный план действий:** `DOCS/PLAN/2025-12-21-fix-404-api-endpoints.md`

---

**История правок:**
- **2025-12-21 08:17 (UTC+3, Брест):** Создан документ с быстрым решением проблемы 404


