# Timeman Library — библиотека для работы с учётом времени

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по библиотеке для работы с учётом времени через SDK

---

## Обзор

**Timeman Library** — библиотека для работы с учётом рабочего времени Bitrix24 через JavaScript SDK.

---

## Основные методы

### Работа с учётом времени

```javascript
// Получение статуса учёта времени
BX.rest.callMethod('timeman.status', {}, function(result) {
    const status = result.data();
});

// Начало рабочего дня
BX.rest.callMethod('timeman.open', {}, function(result) {
    const success = result.data();
});

// Окончание рабочего дня
BX.rest.callMethod('timeman.close', {}, function(result) {
    const success = result.data();
});
```

---

## Документация

- **Timeman методы:** [../../Timeman/](../../Timeman/)
- **REST Client:** [../REST-Client/](../REST-Client/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по Timeman Library






