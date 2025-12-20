# REST API — методология работы с данными Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по REST API для работы с данными в Bitrix24

---

## Обзор

**REST API** — основной метод работы с данными в облачной версии Bitrix24. Предоставляет унифицированный интерфейс для доступа к данным через HTTP-запросы.

**Документация:**
- **Context7:** https://context7.com/bitrix24/rest/
- **Официальная:** https://apidocs.bitrix24.ru/

---

## Когда использовать

✅ **Используйте REST API, если:**
- Работаете с облачной версией Bitrix24
- Нужна внешняя интеграция
- Разрабатываете мобильное приложение
- Создаёте веб-приложение вне Bitrix24

❌ **Не используйте REST API, если:**
- Работаете с коробочной версией и нужна максимальная производительность (используйте D7 ORM)
- Нужна прямая работа с БД без ограничений API

---

## Основные принципы

### 1. Авторизация через вебхуки

**Входящие вебхуки (Incoming Webhooks):**
- Используются для отправки данных в Bitrix24
- Создаются в настройках Bitrix24
- Формат URL: `https://your-domain.bitrix24.ru/rest/1/webhook_code/method`

**Пример:**
```php
$webhookUrl = 'https://your-domain.bitrix24.ru/rest/1/abc123def456/';
$method = 'crm.lead.list';
$url = $webhookUrl . $method;
```

### 2. Формат запросов

**Структура запроса:**
```php
$result = CRest::call('method_name', [
    'filter' => [...],    // Фильтр данных
    'select' => [...],    // Выбираемые поля
    'order' => [...],     // Сортировка
    'start' => 0,         // Смещение (пагинация)
    'limit' => 50        // Лимит записей
]);
```

**Структура ответа:**
```php
[
    'result' => [...],    // Данные результата
    'total' => 100,       // Общее количество записей
    'next' => 50          // Следующая страница (если есть)
]
```

### 3. Обработка ошибок

**Формат ошибки:**
```php
[
    'error' => 'ERROR_CODE',
    'error_description' => 'Описание ошибки'
]
```

**Пример обработки:**
```php
$result = CRest::call('crm.lead.list', []);

if (isset($result['error'])) {
    // Обработка ошибки
    $errorCode = $result['error'];
    $errorDescription = $result['error_description'];
    
    // Логирование
    error_log("Bitrix24 API error: {$errorCode} - {$errorDescription}");
    
    // Обработка в зависимости от кода ошибки
    switch ($errorCode) {
        case 'QUERY_LIMIT_EXCEEDED':
            // Превышен лимит запросов
            break;
        case 'INVALID_TOKEN':
            // Неверный токен
            break;
        default:
            // Другая ошибка
    }
}
```

---

## Батч-запросы

**Назначение:** Выполнение нескольких запросов за один HTTP-запрос

**Формат:**
```php
$commands = [
    'lead_1' => ['crm.lead.get', ['id' => 1]],
    'lead_2' => ['crm.lead.get', ['id' => 2]],
    'lead_list' => ['crm.lead.list', ['filter' => ['>ID' => 0]]]
];

$result = CRest::callBatch($commands, 0); // 0 — не останавливаться при ошибке

// Результат:
// [
//     'result' => [
//         'lead_1' => [...],
//         'lead_2' => [...],
//         'lead_list' => [...]
//     ],
//     'result_error' => [...] // Ошибки (если есть)
// ]
```

**Преимущества:**
- Снижение количества HTTP-запросов
- Ускорение работы при множественных операциях
- Атомарность выполнения (опционально)

---

## Ограничения

### Лимиты запросов

**По умолчанию:**
- 2 запроса в секунду на один вебхук
- 50 запросов в минуту

**Рекомендации:**
- Используйте батч-запросы для множественных операций
- Кешируйте данные, которые редко изменяются
- Используйте очереди для фоновых задач

### Размер данных

- Максимальный размер ответа: зависит от настроек сервера
- Рекомендуется использовать пагинацию для больших выборок

---

## Категории методов

### CRM
- [CRM методы](./../CRM/)

### Задачи
- [Tasks методы](./../Tasks/)

### Пользователи
- [Users методы](./../Users/)

### Файлы
- [Files методы](./../Files/)

### Календарь
- [Calendar методы](./../Calendar/)

### Мессенджер
- [IM методы](./../Im/)

### Учёт времени
- [Timeman методы](./../Timeman/)

### Placements
- [Placement методы](./../Placement/)

### Webhooks
- [Webhook методы](./../Webhook/)

---

## Примеры использования

### Пример 1: Получение списка лидов

```php
use App\Clients\Bitrix24Client;

$client = new Bitrix24Client($logger);

$result = $client->call('crm.lead.list', [
    'filter' => [
        '>CREATED_DATE' => '2025-01-01',
        'STATUS_ID' => 'NEW'
    ],
    'select' => ['ID', 'NAME', 'EMAIL', 'PHONE', 'STATUS_ID'],
    'order' => ['ID' => 'DESC'],
    'start' => 0,
    'limit' => 50
]);

$leads = $result['result'] ?? [];
```

### Пример 2: Создание лида

```php
$result = $client->call('crm.lead.add', [
    'fields' => [
        'NAME' => 'Иван Иванов',
        'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
        'PHONE' => [['VALUE' => '+375291234567', 'VALUE_TYPE' => 'WORK']],
        'STATUS_ID' => 'NEW',
        'SOURCE_ID' => 'WEB'
    ]
]);

$leadId = $result['result'] ?? 0;
```

### Пример 3: Обновление лида

```php
$result = $client->call('crm.lead.update', [
    'id' => 12345,
    'fields' => [
        'NAME' => 'Новое имя',
        'STATUS_ID' => 'IN_PROCESS'
    ]
]);

$success = isset($result['result']);
```

### Пример 4: Батч-запрос

```php
$commands = [
    'lead' => ['crm.lead.get', ['id' => 12345]],
    'contact' => ['crm.contact.get', ['id' => 67890]],
    'company' => ['crm.company.get', ['id' => 11111]]
];

$result = $client->callBatch($commands);

$lead = $result['result']['lead'] ?? null;
$contact = $result['result']['contact'] ?? null;
$company = $result['result']['company'] ?? null;
```

---

## Лучшие практики

### 1. Кеширование данных

```php
// Кеширование списка статусов (редко изменяются)
$cacheKey = 'bitrix24_lead_statuses';
$statuses = Cache::remember($cacheKey, 3600, function() use ($client) {
    return $client->call('crm.status.list', [
        'filter' => ['ENTITY_ID' => 'STATUS']
    ])['result'] ?? [];
});
```

### 2. Обработка пагинации

```php
function getAllLeads(Bitrix24Client $client, array $filter = []): array
{
    $allLeads = [];
    $start = 0;
    $limit = 50;
    
    do {
        $result = $client->call('crm.lead.list', [
            'filter' => $filter,
            'select' => ['ID', 'NAME'],
            'start' => $start,
            'limit' => $limit
        ]);
        
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
```

### 3. Обработка ошибок с повторными попытками

```php
function callWithRetry(Bitrix24Client $client, string $method, array $params, int $maxRetries = 3): array
{
    $attempt = 0;
    
    while ($attempt < $maxRetries) {
        try {
            $result = $client->call($method, $params);
            
            if (isset($result['error'])) {
                // Если ошибка не критична, повторяем
                if ($result['error'] === 'QUERY_LIMIT_EXCEEDED') {
                    $attempt++;
                    sleep(1); // Ждём 1 секунду
                    continue;
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                throw $e;
            }
            sleep(1);
        }
    }
    
    throw new \Exception("Failed after {$maxRetries} attempts");
}
```

### 4. Логирование запросов

```php
class Bitrix24Client
{
    public function call(string $method, array $params = []): array
    {
        $startTime = microtime(true);
        
        // Логирование запроса
        $this->logger->log('Bitrix24 API request', [
            'method' => $method,
            'params' => $this->sanitizeParams($params)
        ]);
        
        $result = CRest::call($method, $params);
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Логирование ответа
        $this->logger->log('Bitrix24 API response', [
            'method' => $method,
            'execution_time_ms' => $executionTime,
            'has_error' => isset($result['error'])
        ]);
        
        return $result;
    }
}
```

---

## Решение проблем

### Проблема: QUERY_LIMIT_EXCEEDED

**Причина:** Превышен лимит запросов в секунду

**Решение:**
- Используйте батч-запросы
- Добавьте задержки между запросами
- Кешируйте данные

### Проблема: INVALID_TOKEN

**Причина:** Неверный или истёкший токен вебхука

**Решение:**
- Проверьте правильность URL вебхука
- Пересоздайте вебхук в настройках Bitrix24

### Проблема: Медленные запросы

**Причина:** Большой объём данных или сложные фильтры

**Решение:**
- Используйте пагинацию
- Оптимизируйте фильтры
- Используйте батч-запросы для множественных операций

---

## Ссылки

- **Список всех методов:** [methods-list.md](./methods-list.md)
- **Примеры:** [examples/](./examples/)
- **Лучшие практики:** [best-practices.md](./best-practices.md)
- **Решение проблем:** [troubleshooting.md](./troubleshooting.md)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по REST API


