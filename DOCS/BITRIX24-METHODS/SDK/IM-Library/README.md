# IM Library — библиотека для работы с мессенджером

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по библиотеке для работы с мессенджером через SDK

---

## Обзор

**IM Library** — библиотека для работы с мессенджером Bitrix24 через JavaScript SDK.

---

## Основные методы

### Работа с сообщениями

```javascript
// Отправка сообщения
BX.rest.callMethod('im.message.add', {
    DIALOG_ID: 'chat-id',
    MESSAGE: 'Текст сообщения'
}, function(result) {
    const messageId = result.data();
});

// Получение информации о чате
BX.rest.callMethod('im.chat.get', {
    CHAT_ID: 'chat-id'
}, function(result) {
    const chat = result.data();
});
```

---

## Документация

- **IM методы:** [../../Im/](../../Im/)
- **REST Client:** [../REST-Client/](../REST-Client/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по IM Library

