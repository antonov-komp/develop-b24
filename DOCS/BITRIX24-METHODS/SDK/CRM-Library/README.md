# CRM Library — библиотека для работы с CRM-сущностями

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по библиотеке для работы с CRM-сущностями через SDK

---

## Обзор

**CRM Library** — библиотека для работы с CRM-сущностями Bitrix24 (лиды, сделки, контакты, компании) через JavaScript SDK.

---

## Основные методы

### Работа с лидами

```javascript
// Получение списка лидов
BX.rest.callMethod('crm.lead.list', {
    filter: { '>ID': 0 },
    select: ['ID', 'NAME', 'EMAIL']
}, function(result) {
    const leads = result.data();
});

// Создание лида
BX.rest.callMethod('crm.lead.add', {
    fields: {
        NAME: 'Иван Иванов',
        EMAIL: [{ VALUE: 'ivan@example.com' }]
    }
}, function(result) {
    const leadId = result.data();
});
```

---

## Документация

- **CRM методы:** [../../CRM/](../../CRM/)
- **REST Client:** [../REST-Client/](../REST-Client/)
- **JavaScript SDK:** [../JavaScript-SDK/](../JavaScript-SDK/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по CRM Library



