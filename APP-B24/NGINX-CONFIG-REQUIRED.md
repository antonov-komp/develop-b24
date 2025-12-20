# ⚠️ Требуется настройка nginx

## Проблема

Nginx возвращает 404 для файлов в `/APP-B24/api/`. Это означает, что nginx не настроен для обработки PHP файлов в этой директории.

## Решение

Добавьте в конфигурацию nginx для домена `backend.antonov-mark.ru`:

```nginx
location /APP-B24/api/ {
    try_files $uri $uri/ /APP-B24/api/index.php?$query_string;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

Или, если используется другой сокет PHP-FPM:

```nginx
location /APP-B24/api/ {
    try_files $uri $uri/ /APP-B24/api/index.php?$query_string;
    
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;  # Или другой адрес
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## После изменения конфигурации

1. Проверьте конфигурацию: `sudo nginx -t`
2. Перезагрузите nginx: `sudo systemctl reload nginx`
3. Проверьте работу: `curl http://localhost/APP-B24/api/user.php?action=current&AUTH_ID=test&DOMAIN=test`

## Альтернативное решение

Если нельзя изменить конфигурацию nginx, можно использовать отдельные файлы для каждого endpoint (уже созданы):
- `/APP-B24/api/user.php`
- `/APP-B24/api/departments.php`
- `/APP-B24/api/access-control.php`
- `/APP-B24/api/token-analysis.php`

Но для их работы все равно нужна правильная настройка nginx для обработки PHP файлов.
