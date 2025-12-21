# Tasks Library — библиотека для работы с задачами

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по библиотеке для работы с задачами через SDK

---

## Обзор

**Tasks Library** — библиотека для работы с задачами и проектами Bitrix24 через JavaScript SDK.

---

## Основные методы

### Работа с задачами

```javascript
// Получение списка задач
BX.rest.callMethod('tasks.task.list', {
    filter: { '>ID': 0 },
    select: ['ID', 'TITLE', 'STATUS']
}, function(result) {
    const tasks = result.data();
});

// Создание задачи
BX.rest.callMethod('tasks.task.add', {
    fields: {
        TITLE: 'Новая задача',
        RESPONSIBLE_ID: 1,
        CREATED_BY: 1
    }
}, function(result) {
    const taskId = result.data();
});
```

---

## Документация

- **Tasks методы:** [../../Tasks/](../../Tasks/)
- **REST Client:** [../REST-Client/](../REST-Client/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по Tasks Library






