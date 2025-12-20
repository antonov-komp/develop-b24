# Руководство по разработке

**Дата создания:** 2025-12-19 11:52 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Руководство по разработке REST приложения Bitrix24

---

## Быстрый старт

### 1. Настройка окружения

1. **Проверьте настройки:**
   ```php
   // APP-B24/settings.php
   define('C_REST_CLIENT_ID','your_client_id');
   define('C_REST_CLIENT_SECRET','your_client_secret');
   // или
   define('C_REST_WEB_HOOK_URL','your_webhook_url');
   ```

2. **Проверьте сервер:**
   ```bash
   php APP-B24/checkserver.php
   ```

### 2. Первый API-запрос

```php
<?php
require_once (__DIR__.'/crest.php');

$result = CRest::call('profile', []);

echo '<pre>';
print_r($result);
echo '</pre>';
```

---

## Структура проекта

### Основные файлы
- `APP-B24/crest.php` — библиотека CRest
- `APP-B24/settings.php` — настройки приложения
- `APP-B24/index.php` — точка входа
- `APP-B24/install.php` — установка приложения
- `APP-B24/checkserver.php` — проверка сервера

### Логи
- `APP-B24/logs/` — директория для логов
- Формат: `YYYY-MM-DD/HH/[timestamp]_[type]_[id]log.json`

---

## Работа с Bitrix24 REST API

### Базовый запрос

```php
require_once (__DIR__.'/crest.php');

$result = CRest::call(
    'crm.lead.list',        // Метод API
    [                        // Параметры
        'filter' => [
            '>CREATED_DATE' => '2025-01-01'
        ],
        'select' => ['ID', 'NAME', 'EMAIL'],
        'order' => ['ID' => 'DESC']
    ]
);

if (isset($result['error'])) {
    // Обработка ошибки
    echo "Ошибка: " . $result['error_description'];
} else {
    // Обработка результата
    print_r($result['result']);
}
```

### Создание лида

```php
$result = CRest::call('crm.lead.add', [
    'fields' => [
        'NAME' => 'Иван Иванов',
        'EMAIL' => [
            ['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']
        ],
        'PHONE' => [
            ['VALUE' => '+375291234567', 'VALUE_TYPE' => 'WORK']
        ]
    ]
]);

if (isset($result['result'])) {
    echo "Лид создан с ID: " . $result['result'];
}
```

### Обновление лида

```php
$result = CRest::call('crm.lead.update', [
    'id' => 12345,
    'fields' => [
        'NAME' => 'Новое имя',
        'STATUS_ID' => 'NEW'
    ]
]);
```

---

## Обработка ошибок

### Проверка ошибок API

```php
$result = CRest::call('crm.lead.list', []);

if (isset($result['error'])) {
    switch ($result['error']) {
        case 'ERROR_METHOD_NOT_FOUND':
            echo "Метод не найден";
            break;
        case 'ERROR_INVALID_TOKEN':
            echo "Неверный токен";
            break;
        default:
            echo "Ошибка: " . $result['error_description'];
    }
}
```

### Логирование ошибок

```php
// Включить логирование в settings.php
define('C_REST_LOG_TYPE_DUMP', true);
define('C_REST_LOGS_DIR', __DIR__ .'/logs/');
```

---

## Лучшие практики

### 1. Валидация данных
- Всегда проверяйте входящие данные
- Используйте фильтры для запросов
- Валидируйте обязательные поля перед созданием

### 2. Обработка ошибок
- Всегда проверяйте наличие `error` в ответе
- Логируйте ошибки для отладки
- Предоставляйте понятные сообщения пользователю

### 3. Оптимизация запросов
- Используйте `select` для получения только нужных полей
- Применяйте фильтры для уменьшения объёма данных
- Кешируйте часто запрашиваемые данные

### 4. Безопасность
- Не храните секреты в коде
- Используйте переменные окружения
- Валидируйте все входящие данные

---

## Документация Bitrix24

### Основные ссылки
- **REST API:** https://context7.com/bitrix24/rest/
- **Официальная документация:** https://apidocs.bitrix24.ru/
- **UI Kit:** https://apidocs.bitrix24.ru/sdk/ui.html

### Полезные методы
- `crm.lead.*` — работа с лидами
- `crm.deal.*` — работа со сделками
- `crm.contact.*` — работа с контактами
- `crm.company.*` — работа с компаниями
- `profile` — профиль пользователя

---

## Тестирование

### Проверка подключения
```php
$result = CRest::call('profile', []);
if (isset($result['result'])) {
    echo "Подключение успешно";
}
```

### Проверка создания лида
```php
$result = CRest::call('crm.lead.add', [
    'fields' => ['NAME' => 'Тестовый лид']
]);
if (isset($result['result'])) {
    echo "Лид создан: " . $result['result'];
}
```

---

## История правок

- 2025-12-19 11:52 (UTC+3, Брест): Создано руководство по разработке



