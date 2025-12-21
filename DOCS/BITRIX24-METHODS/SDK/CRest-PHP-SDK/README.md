# CRest PHP SDK — PHP библиотека CRest для Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Детальная документация по CRest PHP SDK — простой PHP библиотеке для работы с Bitrix24 REST API

---

## Обзор

**CRest PHP SDK** — легковесная PHP библиотека для работы с Bitrix24 REST API. Предоставляет простой интерфейс для вызова API-методов через вебхуки.

**Документация:**
- **GitHub:** https://github.com/bitrix24/crest
- **REST API:** https://context7.com/bitrix24/rest/

---

## Установка

### Скачать файл

```bash
wget https://raw.githubusercontent.com/bitrix24/crest/master/src/CRest.php
```

### Или через Composer

```bash
composer require bitrix24/crest
```

---

## Подключение

```php
<?php

require_once 'CRest.php';

// Или если установлен через Composer
require_once 'vendor/autoload.php';
```

---

## Настройка

### Конфигурация вебхука

```php
<?php

// Настройка вебхука
define('BITRIX24_WEBHOOK_URL', 'https://your-domain.bitrix24.ru/rest/1/webhook_code/');
```

### Или через массив конфигурации

```php
<?php

$config = [
    'webhook_url' => 'https://your-domain.bitrix24.ru/rest/1/webhook_code/'
];

CRest::setConfig($config);
```

---

## Основные методы

### CRest::call()

**Назначение:** Вызов метода REST API

**Синтаксис:**
```php
$result = CRest::call($method, $params);
```

**Параметры:**
- `$method` (string) — метод REST API (например, 'crm.lead.list')
- `$params` (array) — параметры запроса

**Возвращает:** Массив с результатом или ошибкой

**Пример:**
```php
// Получение списка лидов
$result = CRest::call('crm.lead.list', [
    'filter' => [
        '>CREATED_DATE' => '2025-01-01'
    ],
    'select' => ['ID', 'NAME', 'EMAIL', 'PHONE'],
    'order' => ['ID' => 'DESC'],
    'start' => 0,
    'limit' => 50
]);

if (isset($result['error'])) {
    echo "Ошибка: {$result['error_description']}\n";
} else {
    $leads = $result['result'];
    foreach ($leads as $lead) {
        echo "ID: {$lead['ID']}, Имя: {$lead['NAME']}\n";
    }
}
```

---

### CRest::callBatch()

**Назначение:** Выполнение батч-запросов

**Синтаксис:**
```php
$result = CRest::callBatch($commands, $halt);
```

**Параметры:**
- `$commands` (array) — массив команд
- `$halt` (int) — остановка при ошибке (0 или 1)

**Пример:**
```php
$commands = [
    'lead_1' => [
        'method' => 'crm.lead.get',
        'params' => ['id' => 1]
    ],
    'lead_2' => [
        'method' => 'crm.lead.get',
        'params' => ['id' => 2]
    ],
    'leads' => [
        'method' => 'crm.lead.list',
        'params' => ['filter' => ['>ID' => 0]]
    ]
];

$result = CRest::callBatch($commands, 0);

if (isset($result['error'])) {
    echo "Ошибка батча: {$result['error_description']}\n";
} else {
    $lead1 = $result['result']['lead_1'];
    $lead2 = $result['result']['lead_2'];
    $leads = $result['result']['leads'];
    
    echo "Лид 1: {$lead1['NAME']}\n";
    echo "Лид 2: {$lead2['NAME']}\n";
    echo "Всего лидов: " . count($leads) . "\n";
}
```

---

## Работа с CRM

### Лиды

```php
// Получение списка лидов
$result = CRest::call('crm.lead.list', [
    'filter' => ['>ID' => 0],
    'select' => ['ID', 'NAME', 'EMAIL', 'PHONE']
]);

$leads = $result['result'] ?? [];

// Получение лида по ID
$result = CRest::call('crm.lead.get', [
    'id' => 12345
]);

$lead = $result['result'] ?? null;

// Создание лида
$result = CRest::call('crm.lead.add', [
    'fields' => [
        'NAME' => 'Иван Иванов',
        'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
        'PHONE' => [['VALUE' => '+375291234567', 'VALUE_TYPE' => 'WORK']],
        'STATUS_ID' => 'NEW',
        'SOURCE_ID' => 'WEB'
    ]
]);

$leadId = $result['result'] ?? 0;

// Обновление лида
$result = CRest::call('crm.lead.update', [
    'id' => 12345,
    'fields' => [
        'NAME' => 'Новое имя',
        'STATUS_ID' => 'IN_PROCESS'
    ]
]);

// Удаление лида
$result = CRest::call('crm.lead.delete', [
    'id' => 12345
]);
```

### Сделки

```php
// Получение списка сделок
$result = CRest::call('crm.deal.list', [
    'filter' => ['STAGE_ID' => 'NEW'],
    'select' => ['ID', 'TITLE', 'OPPORTUNITY', 'CURRENCY_ID']
]);

$deals = $result['result'] ?? [];

// Создание сделки
$result = CRest::call('crm.deal.add', [
    'fields' => [
        'TITLE' => 'Новая сделка',
        'STAGE_ID' => 'NEW',
        'OPPORTUNITY' => 100000,
        'CURRENCY_ID' => 'BYN'
    ]
]);

$dealId = $result['result'] ?? 0;
```

### Контакты

```php
// Получение списка контактов
$result = CRest::call('crm.contact.list', [
    'filter' => ['>ID' => 0],
    'select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL']
]);

$contacts = $result['result'] ?? [];

// Создание контакта
$result = CRest::call('crm.contact.add', [
    'fields' => [
        'NAME' => 'Иван',
        'LAST_NAME' => 'Иванов',
        'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
        'PHONE' => [['VALUE' => '+375291234567', 'VALUE_TYPE' => 'WORK']]
    ]
]);

$contactId = $result['result'] ?? 0;
```

### Компании

```php
// Получение списка компаний
$result = CRest::call('crm.company.list', [
    'filter' => ['>ID' => 0],
    'select' => ['ID', 'TITLE', 'EMAIL']
]);

$companies = $result['result'] ?? [];

// Создание компании
$result = CRest::call('crm.company.add', [
    'fields' => [
        'TITLE' => 'ООО "Компания"',
        'EMAIL' => [['VALUE' => 'info@company.com', 'VALUE_TYPE' => 'WORK']]
    ]
]);

$companyId = $result['result'] ?? 0;
```

---

## Обработка ошибок

### Проверка ошибок

```php
$result = CRest::call('crm.lead.add', [
    'fields' => ['NAME' => 'Иван']
]);

if (isset($result['error'])) {
    $errorCode = $result['error'];
    $errorDescription = $result['error_description'];
    
    echo "Ошибка: {$errorCode}\n";
    echo "Описание: {$errorDescription}\n";
    
    // Обработка конкретных ошибок
    switch ($errorCode) {
        case 'QUERY_LIMIT_EXCEEDED':
            echo "Превышен лимит запросов\n";
            break;
            
        case 'INVALID_TOKEN':
            echo "Неверный токен\n";
            break;
            
        default:
            echo "Другая ошибка\n";
    }
} else {
    $leadId = $result['result'];
    echo "Лид создан с ID: {$leadId}\n";
}
```

### Функция-обёртка с обработкой ошибок

```php
function callBitrix24API($method, $params = []) {
    $result = CRest::call($method, $params);
    
    if (isset($result['error'])) {
        error_log("Bitrix24 API error: {$result['error']} - {$result['error_description']}");
        throw new Exception("API error: {$result['error_description']}");
    }
    
    return $result['result'];
}

// Использование
try {
    $leadId = callBitrix24API('crm.lead.add', [
        'fields' => ['NAME' => 'Иван']
    ]);
    echo "Лид создан: {$leadId}\n";
} catch (Exception $e) {
    echo "Ошибка: {$e->getMessage()}\n";
}
```

---

## Пагинация

### Получение всех записей

```php
function getAllLeads($filter = []) {
    $allLeads = [];
    $start = 0;
    $limit = 50;
    
    do {
        $result = CRest::call('crm.lead.list', [
            'filter' => $filter,
            'select' => ['ID', 'NAME'],
            'start' => $start,
            'limit' => $limit
        ]);
        
        if (isset($result['error'])) {
            break;
        }
        
        $leads = $result['result'] ?? [];
        $allLeads = array_merge($allLeads, $leads);
        
        $start += $limit;
        $hasMore = count($leads) === $limit;
        
        // Защита от бесконечного цикла
        if ($start > 10000) {
            break;
        }
        
    } while ($hasMore);
    
    return $allLeads;
}

// Использование
$leads = getAllLeads(['>CREATED_DATE' => '2025-01-01']);
echo "Всего лидов: " . count($leads) . "\n";
```

---

## Примеры использования

### Пример 1: Создание лида с контактом

```php
<?php

require_once 'CRest.php';

// Настройка вебхука
define('BITRIX24_WEBHOOK_URL', 'https://your-domain.bitrix24.ru/rest/1/webhook_code/');

// Создание контакта
$contactResult = CRest::call('crm.contact.add', [
    'fields' => [
        'NAME' => 'Иван',
        'LAST_NAME' => 'Иванов',
        'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
        'PHONE' => [['VALUE' => '+375291234567', 'VALUE_TYPE' => 'WORK']]
    ]
]);

if (isset($contactResult['error'])) {
    die("Ошибка создания контакта: {$contactResult['error_description']}\n");
}

$contactId = $contactResult['result'];

// Создание лида
$leadResult = CRest::call('crm.lead.add', [
    'fields' => [
        'NAME' => 'Иван Иванов',
        'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
        'PHONE' => [['VALUE' => '+375291234567', 'VALUE_TYPE' => 'WORK']],
        'CONTACT_ID' => $contactId,
        'STATUS_ID' => 'NEW'
    ]
]);

if (isset($leadResult['error'])) {
    die("Ошибка создания лида: {$leadResult['error_description']}\n");
}

$leadId = $leadResult['result'];

echo "Создан лид с ID: {$leadId}\n";
echo "Создан контакт с ID: {$contactId}\n";
```

### Пример 2: Батч-запрос для получения связанных данных

```php
<?php

require_once 'CRest.php';

define('BITRIX24_WEBHOOK_URL', 'https://your-domain.bitrix24.ru/rest/1/webhook_code/');

$leadId = 12345;

$commands = [
    'lead' => [
        'method' => 'crm.lead.get',
        'params' => ['id' => $leadId]
    ],
    'contacts' => [
        'method' => 'crm.lead.contact.items.get',
        'params' => ['id' => $leadId]
    ]
];

$result = CRest::callBatch($commands, 0);

if (isset($result['error'])) {
    die("Ошибка батча: {$result['error_description']}\n");
}

$lead = $result['result']['lead'];
$contacts = $result['result']['contacts'];

echo "Лид: {$lead['NAME']}\n";
echo "Контактов: " . count($contacts) . "\n";
```

---

## Лучшие практики

### 1. Использование константы для вебхука

```php
// ✅ Хорошо: использование константы
define('BITRIX24_WEBHOOK_URL', 'https://your-domain.bitrix24.ru/rest/1/webhook_code/');
$result = CRest::call('crm.lead.list', []);

// ❌ Плохо: хардкод URL
$result = CRest::call('crm.lead.list', [], 'https://your-domain.bitrix24.ru/rest/1/webhook_code/');
```

### 2. Обработка ошибок

```php
// ✅ Хорошо: проверка ошибок
$result = CRest::call('crm.lead.add', ['fields' => ['NAME' => 'Иван']]);
if (isset($result['error'])) {
    error_log("Ошибка: {$result['error_description']}");
    return false;
}

// ❌ Плохо: игнорирование ошибок
$result = CRest::call('crm.lead.add', ['fields' => ['NAME' => 'Иван']]);
$leadId = $result['result']; // Может быть ошибка!
```

### 3. Использование батч-запросов

```php
// ✅ Хорошо: батч-запрос
$commands = [
    'lead_1' => ['method' => 'crm.lead.get', 'params' => ['id' => 1]],
    'lead_2' => ['method' => 'crm.lead.get', 'params' => ['id' => 2]]
];
$result = CRest::callBatch($commands, 0);

// ❌ Плохо: множественные запросы
$lead1 = CRest::call('crm.lead.get', ['id' => 1]);
$lead2 = CRest::call('crm.lead.get', ['id' => 2]);
```

---

## Сравнение с B24 PHP SDK

| Критерий | CRest PHP SDK | B24 PHP SDK |
|----------|---------------|-------------|
| Простота | Проще | Сложнее |
| Размер | Меньше | Больше |
| Функциональность | Базовая | Расширенная |
| Типизация | Нет | Есть |
| Обработка ошибок | Ручная | Автоматическая |

---

## Ссылки

- **GitHub:** https://github.com/bitrix24/crest
- **REST API:** [../../REST-API/](../../REST-API/)
- **B24 PHP SDK:** [../B24-PHP-SDK/](../B24-PHP-SDK/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана детальная документация по CRest PHP SDK





