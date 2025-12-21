# План действий V2: Исправление ошибки 404 для API endpoints (альтернативные решения)

**Дата создания:** 2025-12-21 08:22 (UTC+3, Брест)  
**Статус:** В работе  
**Приоритет:** Критический  
**Проблема:** Первое исправление не помогло - 404 ошибка сохраняется

---

## Анализ проблемы

### Что было сделано в первой попытке:
1. ✅ Изменен location блок с использованием именованного capture `(?<filename>...)`
2. ✅ Явно указан путь через `$document_root/APP-B24/api/$filename.php`
3. ❌ **Результат:** Проблема не решена - запросы из браузера все еще возвращают 404

### Обнаруженные факты:
- ✅ Файл `/var/www/backend.antonov-mark.ru/APP-B24/api/user.php` существует
- ✅ Через curl с тестовыми параметрами работает (401)
- ❌ Через curl с реальными параметрами из браузера - 404
- ❌ Из браузера в Bitrix24 - 404
- ✅ Nginx версия: 1.24.0 (Ubuntu)
- ⚠️ Именованные captures могут работать некорректно в некоторых случаях

---

## Альтернативные решения

### Решение 1: Использовать обычный capture вместо именованного

**Проблема:** Именованные captures `(?<filename>...)` могут не работать корректно.

**Решение:** Использовать обычный capture `$1`.

**Конфигурация:**
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

**Преимущества:**
- ✅ Обычные captures работают стабильнее
- ✅ Поддерживаются во всех версиях nginx
- ✅ Проще для отладки

---

### Решение 2: Использовать вложенный location блок

**Проблема:** Регулярное выражение может конфликтовать с другими location блоками.

**Решение:** Использовать обычный location с вложенным блоком для PHP.

**Конфигурация:**
```nginx
# Обычный location для директории API
location /APP-B24/api/ {
    # Вложенный location для PHP файлов
    location ~ \.php$ {
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
}
```

**Преимущества:**
- ✅ Более простая структура
- ✅ Меньше конфликтов с другими location блоками
- ✅ Легче для понимания

---

### Решение 3: Использовать точное совпадение для каждого файла

**Проблема:** Регулярное выражение может не срабатывать в некоторых случаях.

**Решение:** Создать отдельные location блоки для каждого API endpoint.

**Конфигурация:**
```nginx
# Отдельные location для каждого API файла
location = /APP-B24/api/user.php {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root/APP-B24/api/user.php;
    include fastcgi_params;
    
    fastcgi_intercept_errors off;
    fastcgi_read_timeout 300;
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    fastcgi_param PHP_VALUE "error_log=/var/log/php/backend-antonov-mark-php-error.log";
}

location = /APP-B24/api/departments.php {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root/APP-B24/api/departments.php;
    include fastcgi_params;
    
    fastcgi_intercept_errors off;
    fastcgi_read_timeout 300;
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    fastcgi_param PHP_VALUE "error_log=/var/log/php/backend-antonov-mark-php-error.log";
}

# И так далее для каждого файла...
```

**Преимущества:**
- ✅ Точное совпадение - самый надежный способ
- ✅ Нет проблем с регулярными выражениями
- ✅ Легко отлаживать

**Недостатки:**
- ❌ Нужно добавлять блок для каждого нового файла
- ❌ Больше кода в конфигурации

---

### Решение 4: Использовать map для динамического определения файла

**Проблема:** Нужно динамически определять файл из URL.

**Решение:** Использовать `map` для извлечения имени файла.

**Конфигурация:**
```nginx
# В начале server блока, перед location блоками
map $request_uri $api_filename {
    ~^/APP-B24/api/([^/]+)\.php $1;
    default "";
}

# Location блок
location ~ ^/APP-B24/api/[^/]+\.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root/APP-B24/api/$api_filename.php;
    include fastcgi_params;
    
    fastcgi_intercept_errors off;
    fastcgi_read_timeout 300;
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;
    fastcgi_param PHP_VALUE "error_log=/var/log/php/backend-antonov-mark-php-error.log";
}
```

**Преимущества:**
- ✅ Гибкое решение
- ✅ Можно использовать для других целей

**Недостатки:**
- ❌ Более сложная конфигурация
- ❌ Может быть избыточно для простых случаев

---

## Рекомендуемый порядок применения

### Шаг 1: Попробовать Решение 1 (обычный capture)
**Причина:** Самое простое изменение, минимальный риск.

**Действия:**
1. Заменить именованный capture на обычный `$1`
2. Проверить синтаксис: `nginx -t`
3. Перезагрузить nginx: `systemctl reload nginx`
4. Протестировать запрос

**Если не помогло → Шаг 2**

---

### Шаг 2: Попробовать Решение 2 (вложенный location)
**Причина:** Более простая структура, меньше конфликтов.

**Действия:**
1. Заменить location блок на вложенную структуру
2. Проверить синтаксис: `nginx -t`
3. Перезагрузить nginx: `systemctl reload nginx`
4. Протестировать запрос

**Если не помогло → Шаг 3**

---

### Шаг 3: Попробовать Решение 3 (точное совпадение)
**Причина:** Самый надежный способ, гарантированно работает.

**Действия:**
1. Создать location блоки для каждого API файла
2. Проверить синтаксис: `nginx -t`
3. Перезагрузить nginx: `systemctl reload nginx`
4. Протестировать запрос

---

## Дополнительная диагностика

### Проверка, какой location блок срабатывает

**Добавить логирование:**
```nginx
location ~ ^/APP-B24/api/([^/]+)\.php$ {
    # Временное логирование для отладки
    access_log /var/log/nginx/api-debug.log;
    
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    # ...
}
```

**Проверить логи:**
```bash
tail -f /var/log/nginx/api-debug.log
```

### Проверка переменных в runtime

**Создать тестовый PHP файл:**
```php
<?php
header('Content-Type: application/json');
echo json_encode([
    'request_uri' => $_SERVER['REQUEST_URI'],
    'script_name' => $_SERVER['SCRIPT_NAME'],
    'script_filename' => $_SERVER['SCRIPT_FILENAME'],
    'document_root' => $_SERVER['DOCUMENT_ROOT'],
], JSON_PRETTY_PRINT);
```

### Проверка конфликтов location блоков

**Команда:**
```bash
# Проверить все location блоки и их приоритет
nginx -T 2>/dev/null | grep -B 5 -A 10 "location.*APP-B24"
```

---

## Чек-лист выполнения

### Решение 1 (обычный capture)
- [ ] Заменен именованный capture на `$1`
- [ ] Проверен синтаксис nginx
- [ ] Перезагружен nginx
- [ ] Протестирован запрос через curl
- [ ] Протестирован запрос из браузера
- [ ] Проверены логи nginx

### Решение 2 (вложенный location)
- [ ] Создан вложенный location блок
- [ ] Проверен синтаксис nginx
- [ ] Перезагружен nginx
- [ ] Протестирован запрос через curl
- [ ] Протестирован запрос из браузера
- [ ] Проверены логи nginx

### Решение 3 (точное совпадение)
- [ ] Созданы location блоки для каждого файла
- [ ] Проверен синтаксис nginx
- [ ] Перезагружен nginx
- [ ] Протестирован запрос через curl
- [ ] Протестирован запрос из браузера
- [ ] Проверены логи nginx

---

## История правок

- **2025-12-21 08:22 (UTC+3, Брест):** Создан план альтернативных решений после неудачи первой попытки

---

## Примечания

- Все решения должны быть протестированы по порядку
- После каждого изменения проверять работу через curl и браузер
- Сохранять резервные копии конфигурации перед каждым изменением
- Если ни одно решение не помогает, провести дополнительную диагностику




