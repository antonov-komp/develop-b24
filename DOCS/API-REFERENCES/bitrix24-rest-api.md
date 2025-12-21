# Справочник Bitrix24 REST API

**Дата создания:** 2025-12-19 11:52 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Справочник по методам Bitrix24 REST API, используемым в проекте

---

## Основные источники документации

- **Context7:** https://context7.com/bitrix24/rest/
- **Официальная документация:** https://apidocs.bitrix24.ru/

---

## Методы работы с лидами (Leads)

### crm.lead.list
**Описание:** Получение списка лидов  
**Документация:** https://context7.com/bitrix24/rest/crm.lead.list

**Пример:**
```php
$result = CRest::call('crm.lead.list', [
    'filter' => [
        '>CREATED_DATE' => '2025-01-01'
    ],
    'select' => ['ID', 'NAME', 'EMAIL', 'PHONE', 'STATUS_ID'],
    'order' => ['ID' => 'DESC']
]);
```

### crm.lead.get
**Описание:** Получение лида по ID  
**Документация:** https://context7.com/bitrix24/rest/crm.lead.get

**Пример:**
```php
$result = CRest::call('crm.lead.get', [
    'id' => 12345
]);
```

### crm.lead.add
**Описание:** Создание лида  
**Документация:** https://context7.com/bitrix24/rest/crm.lead.add

**Пример:**
```php
$result = CRest::call('crm.lead.add', [
    'fields' => [
        'NAME' => 'Иван Иванов',
        'EMAIL' => [['VALUE' => 'ivan@example.com']],
        'PHONE' => [['VALUE' => '+375291234567']]
    ]
]);
```

### crm.lead.update
**Описание:** Обновление лида  
**Документация:** https://context7.com/bitrix24/rest/crm.lead.update

**Пример:**
```php
$result = CRest::call('crm.lead.update', [
    'id' => 12345,
    'fields' => [
        'NAME' => 'Новое имя',
        'STATUS_ID' => 'NEW'
    ]
]);
```

### crm.lead.delete
**Описание:** Удаление лида  
**Документация:** https://context7.com/bitrix24/rest/crm.lead.delete

**Пример:**
```php
$result = CRest::call('crm.lead.delete', [
    'id' => 12345
]);
```

---

## Методы работы со сделками (Deals)

### crm.deal.list
**Описание:** Получение списка сделок  
**Документация:** https://context7.com/bitrix24/rest/crm.deal.list

**Пример:**
```php
$result = CRest::call('crm.deal.list', [
    'filter' => [
        '>CREATED_DATE' => '2025-01-01'
    ],
    'select' => ['ID', 'TITLE', 'STAGE_ID', 'OPPORTUNITY'],
    'order' => ['ID' => 'DESC']
]);
```

### crm.deal.get
**Описание:** Получение сделки по ID  
**Документация:** https://context7.com/bitrix24/rest/crm.deal.get

### crm.deal.add
**Описание:** Создание сделки  
**Документация:** https://context7.com/bitrix24/rest/crm.deal.add

### crm.deal.update
**Описание:** Обновление сделки  
**Документация:** https://context7.com/bitrix24/rest/crm.deal.update

---

## Методы работы с контактами (Contacts)

### crm.contact.list
**Описание:** Получение списка контактов  
**Документация:** https://context7.com/bitrix24/rest/crm.contact.list

### crm.contact.get
**Описание:** Получение контакта по ID  
**Документация:** https://context7.com/bitrix24/rest/crm.contact.get

### crm.contact.add
**Описание:** Создание контакта  
**Документация:** https://context7.com/bitrix24/rest/crm.contact.add

---

## Методы работы с компаниями (Companies)

### crm.company.list
**Описание:** Получение списка компаний  
**Документация:** https://context7.com/bitrix24/rest/crm.company.list

### crm.company.get
**Описание:** Получение компании по ID  
**Документация:** https://context7.com/bitrix24/rest/crm.company.get

### crm.company.add
**Описание:** Создание компании  
**Документация:** https://context7.com/bitrix24/rest/crm.company.add

---

## Профиль пользователя

### profile
**Описание:** Получение профиля текущего пользователя  
**Документация:** https://context7.com/bitrix24/rest/profile

**Пример:**
```php
$result = CRest::call('profile', []);
```

---

## Обработка ошибок

### Типичные ошибки

- `ERROR_METHOD_NOT_FOUND` — метод не найден
- `ERROR_INVALID_TOKEN` — неверный токен
- `ERROR_ACCESS_DENIED` — доступ запрещён
- `ERROR_QUERY_LIMIT_EXCEEDED` — превышен лимит запросов

### Пример обработки

```php
$result = CRest::call('crm.lead.list', []);

if (isset($result['error'])) {
    echo "Ошибка: " . $result['error'];
    echo "Описание: " . $result['error_description'];
}
```

---

## История правок

- 2025-12-19 11:52 (UTC+3, Брест): Создан справочник Bitrix24 REST API








