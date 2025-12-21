# Модель данных

**Дата создания:** 2025-12-19 11:52 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Описание структуры данных REST приложения Bitrix24

---

## Общая концепция

REST приложение Bitrix24 работает с данными через **Bitrix24 REST API**. Данные хранятся в Bitrix24, приложение получает и отправляет их через API.

---

## Основные сущности Bitrix24

### Лиды (Leads)
- **Метод API:** `crm.lead.*`
- **Документация:** https://context7.com/bitrix24/rest/crm.lead.list
- **Основные поля:**
  - `ID` — уникальный идентификатор
  - `NAME` — имя лида
  - `EMAIL` — email (массив значений)
  - `PHONE` — телефон (массив значений)
  - `STATUS_ID` — статус лида
  - `CREATED_DATE` — дата создания

### Сделки (Deals)
- **Метод API:** `crm.deal.*`
- **Документация:** https://context7.com/bitrix24/rest/crm.deal.list
- **Основные поля:**
  - `ID` — уникальный идентификатор
  - `TITLE` — название сделки
  - `STAGE_ID` — стадия сделки
  - `OPPORTUNITY` — сумма сделки
  - `CURRENCY_ID` — валюта

### Контакты (Contacts)
- **Метод API:** `crm.contact.*`
- **Документация:** https://context7.com/bitrix24/rest/crm.contact.list
- **Основные поля:**
  - `ID` — уникальный идентификатор
  - `NAME` — имя
  - `LAST_NAME` — фамилия
  - `EMAIL` — email (массив значений)
  - `PHONE` — телефон (массив значений)

### Компании (Companies)
- **Метод API:** `crm.company.*`
- **Документация:** https://context7.com/bitrix24/rest/crm.company.list
- **Основные поля:**
  - `ID` — уникальный идентификатор
  - `TITLE` — название компании
  - `EMAIL` — email (массив значений)
  - `PHONE` — телефон (массив значений)

---

## Структура данных в приложении

### Настройки приложения
- **Файл:** `APP-B24/settings.php`
- **Параметры:**
  - `C_REST_CLIENT_ID` — Application ID
  - `C_REST_CLIENT_SECRET` — Application key
  - `C_REST_WEB_HOOK_URL` — URL вебхука (опционально)

### Логи
- **Расположение:** `APP-B24/logs/`
- **Структура:** `YYYY-MM-DD/HH/[timestamp]_[type]_[id]log.json`
- **Формат:** JSON

---

## Связи между сущностями

### Bitrix24 CRM
- Лид → Сделка (конвертация лида в сделку)
- Сделка → Контакт (связь сделки с контактом)
- Сделка → Компания (связь сделки с компанией)
- Контакт → Компания (связь контакта с компанией)

### Документация по связям
- https://context7.com/bitrix24/rest/crm.lead.convert
- https://context7.com/bitrix24/rest/crm.deal.contact.add

---

## Форматы данных

### Входящие данные (от Bitrix24)
- Формат: JSON
- Кодировка: UTF-8
- Структура: согласно документации Bitrix24 REST API

### Исходящие данные (в Bitrix24)
- Формат: JSON
- Кодировка: UTF-8
- Валидация: обязательна перед отправкой

---

## Примеры работы с данными

### Получение лидов
```php
$result = CRest::call('crm.lead.list', [
    'filter' => ['>CREATED_DATE' => '2025-01-01'],
    'select' => ['ID', 'NAME', 'EMAIL', 'PHONE'],
    'order' => ['ID' => 'DESC']
]);
```

### Создание лида
```php
$result = CRest::call('crm.lead.add', [
    'fields' => [
        'NAME' => 'Иван Иванов',
        'EMAIL' => [['VALUE' => 'ivan@example.com']],
        'PHONE' => [['VALUE' => '+375291234567']]
    ]
]);
```

---

## История правок

- 2025-12-19 11:52 (UTC+3, Брест): Создан документ с описанием модели данных






