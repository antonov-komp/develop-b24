# B24 PHP SDK — PHP SDK для Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Детальная документация по B24 PHP SDK — PHP библиотеке для работы с Bitrix24 REST API

---

## Обзор

**B24 PHP SDK** — официальная PHP библиотека для работы с Bitrix24 REST API. Предоставляет удобные методы для вызова API-методов, обработки ответов и управления запросами.

**Документация:**
- **Официальная:** https://github.com/bitrix24/rest-php-sdk
- **REST API:** https://context7.com/bitrix24/rest/

---

## Установка

### Через Composer

```bash
composer require bitrix24/rest-php-sdk
```

### Вручную

Скачать библиотеку с GitHub: https://github.com/bitrix24/rest-php-sdk

---

## Подключение

```php
<?php

require_once 'vendor/autoload.php';

use Bitrix24\SDK\Core\Core;
use Bitrix24\SDK\Core\Credentials\WebhookUrl;
use Bitrix24\SDK\Core\Exceptions\BaseException;

// Инициализация
$webhookUrl = 'https://your-domain.bitrix24.ru/rest/1/webhook_code/';
$core = new Core(new WebhookUrl($webhookUrl));
```

---

## Основные классы

### Core — основной класс

**Назначение:** Основной класс для работы с Bitrix24 API

**Инициализация:**
```php
use Bitrix24\SDK\Core\Core;
use Bitrix24\SDK\Core\Credentials\WebhookUrl;

$webhookUrl = 'https://your-domain.bitrix24.ru/rest/1/webhook_code/';
$core = new Core(new WebhookUrl($webhookUrl));
```

---

## Работа с CRM

### Лиды

```php
use Bitrix24\SDK\Services\CRM\Lead\Service\LeadService;

$leadService = $core->getLeadService();

// Получение списка лидов
$leads = $leadService->list([
    'filter' => ['>CREATED_DATE' => '2025-01-01'],
    'select' => ['ID', 'NAME', 'EMAIL', 'PHONE'],
    'order' => ['ID' => 'DESC']
]);

foreach ($leads->getLeads() as $lead) {
    echo "ID: {$lead->getId()}, Имя: {$lead->getName()}\n";
}

// Получение лида по ID
$lead = $leadService->get(12345);
echo "Имя: {$lead->getName()}\n";

// Создание лида
$newLead = $leadService->add([
    'NAME' => 'Иван Иванов',
    'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
    'PHONE' => [['VALUE' => '+375291234567', 'VALUE_TYPE' => 'WORK']]
]);
echo "Создан лид с ID: {$newLead->getId()}\n";

// Обновление лида
$leadService->update(12345, [
    'NAME' => 'Новое имя',
    'STATUS_ID' => 'IN_PROCESS'
]);

// Удаление лида
$leadService->delete(12345);
```

### Сделки

```php
use Bitrix24\SDK\Services\CRM\Deal\Service\DealService;

$dealService = $core->getDealService();

// Получение списка сделок
$deals = $dealService->list([
    'filter' => ['STAGE_ID' => 'NEW'],
    'select' => ['ID', 'TITLE', 'OPPORTUNITY', 'CURRENCY_ID']
]);

// Создание сделки
$newDeal = $dealService->add([
    'TITLE' => 'Новая сделка',
    'STAGE_ID' => 'NEW',
    'OPPORTUNITY' => 100000,
    'CURRENCY_ID' => 'BYN'
]);
```

### Контакты

```php
use Bitrix24\SDK\Services\CRM\Contact\Service\ContactService;

$contactService = $core->getContactService();

// Получение списка контактов
$contacts = $contactService->list([
    'filter' => ['>ID' => 0],
    'select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL']
]);

// Создание контакта
$newContact = $contactService->add([
    'NAME' => 'Иван',
    'LAST_NAME' => 'Иванов',
    'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']]
]);
```

### Компании

```php
use Bitrix24\SDK\Services\CRM\Company\Service\CompanyService;

$companyService = $core->getCompanyService();

// Получение списка компаний
$companies = $companyService->list([
    'filter' => ['>ID' => 0],
    'select' => ['ID', 'TITLE', 'EMAIL']
]);

// Создание компании
$newCompany = $companyService->add([
    'TITLE' => 'ООО "Компания"',
    'EMAIL' => [['VALUE' => 'info@company.com', 'VALUE_TYPE' => 'WORK']]
]);
```

---

## Работа с задачами

```php
use Bitrix24\SDK\Services\Task\Service\TaskService;

$taskService = $core->getTaskService();

// Получение списка задач
$tasks = $taskService->list([
    'filter' => ['>ID' => 0],
    'select' => ['ID', 'TITLE', 'STATUS']
]);

// Создание задачи
$newTask = $taskService->add([
    'TITLE' => 'Новая задача',
    'RESPONSIBLE_ID' => 1,
    'CREATED_BY' => 1,
    'DEADLINE' => '2025-12-31 23:59:59'
]);
```

---

## Батч-запросы

```php
use Bitrix24\SDK\Core\Batch;

$batch = new Batch($core);

$commands = [
    'lead_1' => ['crm.lead.get', ['id' => 1]],
    'lead_2' => ['crm.lead.get', ['id' => 2]],
    'leads' => ['crm.lead.list', ['filter' => ['>ID' => 0]]]
];

$result = $batch->call($commands);

$lead1 = $result->getResult('lead_1');
$lead2 = $result->getResult('lead_2');
$leads = $result->getResult('leads');
```

---

## Обработка ошибок

```php
use Bitrix24\SDK\Core\Exceptions\BaseException;

try {
    $lead = $leadService->get(12345);
} catch (BaseException $e) {
    echo "Ошибка: {$e->getMessage()}\n";
    echo "Код ошибки: {$e->getCode()}\n";
}

// Проверка конкретных ошибок
try {
    $leadService->add(['NAME' => 'Иван']);
} catch (\Bitrix24\SDK\Core\Exceptions\InvalidArgumentException $e) {
    echo "Неверные параметры: {$e->getMessage()}\n";
} catch (\Bitrix24\SDK\Core\Exceptions\NetworkException $e) {
    echo "Ошибка сети: {$e->getMessage()}\n";
} catch (BaseException $e) {
    echo "Другая ошибка: {$e->getMessage()}\n";
}
```

---

## Примеры использования

### Пример 1: Создание лида с контактом

```php
<?php

require_once 'vendor/autoload.php';

use Bitrix24\SDK\Core\Core;
use Bitrix24\SDK\Core\Credentials\WebhookUrl;
use Bitrix24\SDK\Services\CRM\Lead\Service\LeadService;
use Bitrix24\SDK\Services\CRM\Contact\Service\ContactService;

$webhookUrl = 'https://your-domain.bitrix24.ru/rest/1/webhook_code/';
$core = new Core(new WebhookUrl($webhookUrl));

$contactService = $core->getContactService();
$leadService = $core->getLeadService();

// Создание контакта
$contact = $contactService->add([
    'NAME' => 'Иван',
    'LAST_NAME' => 'Иванов',
    'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
    'PHONE' => [['VALUE' => '+375291234567', 'VALUE_TYPE' => 'WORK']]
]);

$contactId = $contact->getId();

// Создание лида
$lead = $leadService->add([
    'NAME' => 'Иван Иванов',
    'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
    'PHONE' => [['VALUE' => '+375291234567', 'VALUE_TYPE' => 'WORK']],
    'CONTACT_ID' => $contactId
]);

echo "Создан лид с ID: {$lead->getId()}\n";
echo "Создан контакт с ID: {$contactId}\n";
```

### Пример 2: Получение всех лидов с пагинацией

```php
<?php

use Bitrix24\SDK\Core\Core;
use Bitrix24\SDK\Core\Credentials\WebhookUrl;
use Bitrix24\SDK\Services\CRM\Lead\Service\LeadService;

$webhookUrl = 'https://your-domain.bitrix24.ru/rest/1/webhook_code/';
$core = new Core(new WebhookUrl($webhookUrl));

$leadService = $core->getLeadService();

$allLeads = [];
$start = 0;
$limit = 50;

do {
    $leads = $leadService->list([
        'filter' => ['>ID' => 0],
        'select' => ['ID', 'NAME'],
        'start' => $start,
        'limit' => $limit
    ]);
    
    foreach ($leads->getLeads() as $lead) {
        $allLeads[] = $lead;
    }
    
    $start += $limit;
    $hasMore = count($leads->getLeads()) === $limit;
    
} while ($hasMore && $start < 10000);

echo "Всего лидов: " . count($allLeads) . "\n";
```

---

## Лучшие практики

### 1. Использование сервисов

```php
// ✅ Хорошо: использование сервисов
$leadService = $core->getLeadService();
$lead = $leadService->get(12345);

// ❌ Плохо: прямой вызов API
$response = $core->call('crm.lead.get', ['id' => 12345]);
```

### 2. Обработка ошибок

```php
try {
    $lead = $leadService->get(12345);
} catch (BaseException $e) {
    error_log("Ошибка получения лида: {$e->getMessage()}");
    // Обработка ошибки
}
```

### 3. Использование батч-запросов

```php
// ✅ Хорошо: батч-запрос
$batch = new Batch($core);
$commands = [
    'lead_1' => ['crm.lead.get', ['id' => 1]],
    'lead_2' => ['crm.lead.get', ['id' => 2]]
];
$result = $batch->call($commands);

// ❌ Плохо: множественные запросы
$lead1 = $leadService->get(1);
$lead2 = $leadService->get(2);
```

---

## Ссылки

- **GitHub:** https://github.com/bitrix24/rest-php-sdk
- **REST API:** [../../REST-API/](../../REST-API/)
- **CRest PHP SDK:** [../CRest-PHP-SDK/](../CRest-PHP-SDK/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана детальная документация по B24 PHP SDK





