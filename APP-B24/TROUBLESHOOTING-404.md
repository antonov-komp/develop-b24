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

📖 **Полный план действий:** `DOCS/PLAN/2025-12-21-fix-404-api-endpoints.md`


