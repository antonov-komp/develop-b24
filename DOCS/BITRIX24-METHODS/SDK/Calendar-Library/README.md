# Calendar Library — библиотека для работы с календарём

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по библиотеке для работы с календарём через SDK

---

## Обзор

**Calendar Library** — библиотека для работы с календарём и событиями Bitrix24 через JavaScript SDK.

---

## Основные методы

### Работа с событиями

```javascript
// Получение списка событий
BX.rest.callMethod('calendar.event.get', {
    type: 'user',
    ownerId: 1
}, function(result) {
    const events = result.data();
});

// Создание события
BX.rest.callMethod('calendar.event.add', {
    fields: {
        NAME: 'Встреча',
        DATE_FROM: '2025-12-25T10:00:00',
        DATE_TO: '2025-12-25T11:00:00'
    }
}, function(result) {
    const eventId = result.data();
});
```

---

## Документация

- **Calendar методы:** [../../Calendar/](../../Calendar/)
- **REST Client:** [../REST-Client/](../REST-Client/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по Calendar Library



