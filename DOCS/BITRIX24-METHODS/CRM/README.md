# CRM — методы работы с CRM-сущностями в Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по методам работы с CRM-сущностями (лиды, сделки, контакты, компании)

---

## Обзор

**CRM-модуль** Bitrix24 предоставляет методы для работы с основными бизнес-сущностями: лиды, сделки, контакты, компании, продукты, счета и смарт-процессы.

**Документация:**
- **REST API:** https://context7.com/bitrix24/rest/crm/
- **D7 ORM:** https://dev.1c-bitrix.ru/api_d7/orm/

---

## Основные сущности

### Лиды (Leads)
**Описание:** Потенциальные клиенты, которые ещё не стали сделками

**Методы:**
- `crm.lead.list` — получение списка лидов
- `crm.lead.get` — получение лида по ID
- `crm.lead.add` — создание лида
- `crm.lead.update` — обновление лида
- `crm.lead.delete` — удаление лида
- `crm.lead.fields` — получение полей лида

**Документация:** [Leads/](./Leads/)

---

### Сделки (Deals)
**Описание:** Активные продажи и коммерческие предложения

**Методы:**
- `crm.deal.list` — получение списка сделок
- `crm.deal.get` — получение сделки по ID
- `crm.deal.add` — создание сделки
- `crm.deal.update` — обновление сделки
- `crm.deal.delete` — удаление сделки
- `crm.deal.fields` — получение полей сделки

**Документация:** [Deals/](./Deals/)

---

### Контакты (Contacts)
**Описание:** Физические лица — клиенты и партнёры

**Методы:**
- `crm.contact.list` — получение списка контактов
- `crm.contact.get` — получение контакта по ID
- `crm.contact.add` — создание контакта
- `crm.contact.update` — обновление контакта
- `crm.contact.delete` — удаление контакта
- `crm.contact.fields` — получение полей контакта

**Документация:** [Contacts/](./Contacts/)

---

### Компании (Companies)
**Описание:** Юридические лица — организации и компании

**Методы:**
- `crm.company.list` — получение списка компаний
- `crm.company.get` — получение компании по ID
- `crm.company.add` — создание компании
- `crm.company.update` — обновление компании
- `crm.company.delete` — удаление компании
- `crm.company.fields` — получение полей компании

**Документация:** [Companies/](./Companies/)

---

### Продукты (Products)
**Описание:** Товары и услуги, продаваемые компанией

**Методы:**
- `crm.product.list` — получение списка продуктов
- `crm.product.get` — получение продукта по ID
- `crm.product.add` — создание продукта
- `crm.product.update` — обновление продукта
- `crm.product.delete` — удаление продукта
- `crm.product.fields` — получение полей продукта

**Документация:** [Products/](./Products/)

---

### Счета (Invoices)
**Описание:** Счета на оплату для сделок

**Методы:**
- `crm.invoice.list` — получение списка счетов
- `crm.invoice.get` — получение счёта по ID
- `crm.invoice.add` — создание счёта
- `crm.invoice.update` — обновление счёта
- `crm.invoice.delete` — удаление счёта
- `crm.invoice.fields` — получение полей счёта

**Документация:** [Invoices/](./Invoices/)

---

### Смарт-процессы (Smart Processes)
**Описание:** Кастомные бизнес-процессы и сущности

**Методы:**
- `crm.type.list` — получение списка типов смарт-процессов
- `crm.item.list` — получение списка элементов смарт-процесса
- `crm.item.get` — получение элемента по ID
- `crm.item.add` — создание элемента
- `crm.item.update` — обновление элемента
- `crm.item.delete` — удаление элемента

**Документация:** [SmartProcesses/](./SmartProcesses/)

---

## Общие методы

### Статусы и воронки

**Методы:**
- `crm.status.list` — получение списка статусов
- `crm.status.fields` — получение полей статуса
- `crm.stage.list` — получение списка стадий воронки

**Документация:** [Statuses/](./Statuses/)

---

### Связи между сущностями

**Методы:**
- `crm.lead.contact.add` — привязка контакта к лиду
- `crm.lead.contact.delete` — отвязка контакта от лида
- `crm.deal.contact.add` — привязка контакта к сделке
- `crm.deal.contact.delete` — отвязка контакта от сделки
- `crm.deal.productrows.get` — получение товарных позиций сделки
- `crm.deal.productrows.set` — установка товарных позиций сделки

**Документация:** [Relations/](./Relations/)

---

### Дополнительные поля

**Методы:**
- `crm.lead.userfield.list` — получение пользовательских полей лида
- `crm.deal.userfield.list` — получение пользовательских полей сделки
- `crm.contact.userfield.list` — получение пользовательских полей контакта
- `crm.company.userfield.list` — получение пользовательских полей компании

**Документация:** [UserFields/](./UserFields/)

---

## Примеры использования

### Пример 1: Создание лида с контактом

```php
use App\Clients\Bitrix24Client;

$client = new Bitrix24Client($logger);

// 1. Создание контакта
$contactResult = $client->call('crm.contact.add', [
    'fields' => [
        'NAME' => 'Иван',
        'LAST_NAME' => 'Иванов',
        'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
        'PHONE' => [['VALUE' => '+375291234567', 'VALUE_TYPE' => 'WORK']]
    ]
]);

$contactId = $contactResult['result'] ?? 0;

// 2. Создание лида
$leadResult = $client->call('crm.lead.add', [
    'fields' => [
        'NAME' => 'Иван Иванов',
        'EMAIL' => [['VALUE' => 'ivan@example.com', 'VALUE_TYPE' => 'WORK']],
        'PHONE' => [['VALUE' => '+375291234567', 'VALUE_TYPE' => 'WORK']],
        'STATUS_ID' => 'NEW',
        'SOURCE_ID' => 'WEB'
    ]
]);

$leadId = $leadResult['result'] ?? 0;

// 3. Привязка контакта к лиду
if ($contactId && $leadId) {
    $client->call('crm.lead.contact.add', [
        'id' => $leadId,
        'fields' => [
            'CONTACT_ID' => $contactId
        ]
    ]);
}
```

### Пример 2: Конвертация лида в сделку

```php
// Получение лида
$lead = $client->call('crm.lead.get', ['id' => $leadId])['result'] ?? null;

if ($lead) {
    // Создание сделки на основе лида
    $dealResult = $client->call('crm.deal.add', [
        'fields' => [
            'TITLE' => $lead['TITLE'] ?? 'Сделка из лида #' . $leadId,
            'CONTACT_ID' => $lead['CONTACT_ID'] ?? null,
            'COMPANY_ID' => $lead['COMPANY_ID'] ?? null,
            'OPPORTUNITY' => $lead['OPPORTUNITY'] ?? 0,
            'CURRENCY_ID' => $lead['CURRENCY_ID'] ?? 'BYN',
            'STAGE_ID' => 'NEW'
        ]
    ]);
    
    $dealId = $dealResult['result'] ?? 0;
    
    // Обновление статуса лида
    if ($dealId) {
        $client->call('crm.lead.update', [
            'id' => $leadId,
            'fields' => [
                'STATUS_ID' => 'CONVERTED'
            ]
        ]);
    }
}
```

### Пример 3: Получение сделок с продуктами

```php
// Получение сделок
$deals = $client->call('crm.deal.list', [
    'filter' => ['STAGE_ID' => 'WON'],
    'select' => ['ID', 'TITLE', 'OPPORTUNITY', 'CURRENCY_ID']
])['result'] ?? [];

foreach ($deals as $deal) {
    // Получение товарных позиций
    $products = $client->call('crm.deal.productrows.get', [
        'id' => $deal['ID']
    ])['result'] ?? [];
    
    echo "Сделка: {$deal['TITLE']}\n";
    echo "Товаров: " . count($products) . "\n";
}
```

---

## Лучшие практики

### 1. Использование батч-запросов для множественных операций

```php
$commands = [];
foreach ($leads as $lead) {
    $commands["lead_{$lead['id']}"] = [
        'crm.lead.get',
        ['id' => $lead['id']]
    ];
}

$result = $client->callBatch($commands);
```

### 2. Валидация данных перед созданием

```php
function validateLeadData(array $data): array
{
    $errors = [];
    
    if (empty($data['NAME'])) {
        $errors[] = 'Имя обязательно';
    }
    
    if (!empty($data['EMAIL'])) {
        foreach ($data['EMAIL'] as $email) {
            if (!filter_var($email['VALUE'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Неверный email: {$email['VALUE']}";
            }
        }
    }
    
    return $errors;
}
```

### 3. Обработка связей между сущностями

```php
function linkContactToDeal(int $contactId, int $dealId): bool
{
    try {
        $result = $client->call('crm.deal.contact.add', [
            'id' => $dealId,
            'fields' => [
                'CONTACT_ID' => $contactId
            ]
        ]);
        
        return isset($result['result']);
    } catch (\Exception $e) {
        // Логирование ошибки
        error_log("Failed to link contact to deal: {$e->getMessage()}");
        return false;
    }
}
```

---

## Структура документации

Каждая сущность имеет свою папку с документацией:
- `Leads/` — документация по лидам
- `Deals/` — документация по сделкам
- `Contacts/` — документация по контактам
- `Companies/` — документация по компаниям
- `Products/` — документация по продуктам
- `Invoices/` — документация по счетам
- `SmartProcesses/` — документация по смарт-процессам

---

## Ссылки

- **REST API методы:** [../REST-API/](../REST-API/)
- **D7 ORM методы:** [../D7-ORM/](../D7-ORM/)
- **Примеры:** [examples/](./examples/)
- **Лучшие практики:** [best-practices.md](./best-practices.md)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по CRM-методам





