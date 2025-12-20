# Drive Library — библиотека для работы с файлами

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по библиотеке для работы с файлами через SDK

---

## Обзор

**Drive Library** — библиотека для работы с файлами и облачным хранилищем (Disk) Bitrix24 через JavaScript SDK.

---

## Основные методы

### Работа с файлами

```javascript
// Получение содержимого папки
BX.rest.callMethod('disk.folder.getchildren', {
    id: '0'
}, function(result) {
    const items = result.data();
});

// Загрузка файла
BX.rest.callMethod('disk.file.uploadversion', {
    id: 'file-id',
    fileContent: fileContent
}, function(result) {
    const fileId = result.data();
});
```

---

## Документация

- **Files методы:** [../../Files/](../../Files/)
- **REST Client:** [../REST-Client/](../REST-Client/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по Drive Library

