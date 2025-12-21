# Webhooks API — библиотека для работы с вебхуками Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Детальная документация по Webhooks API — библиотеке для работы с вебхуками

---

## Обзор

**Webhooks API** — библиотека для работы с вебхуками Bitrix24. Поддерживает входящие и исходящие вебхуки для интеграции с внешними системами.

**Документация:**
- **REST API:** https://context7.com/bitrix24/rest/webhook/
- **Вебхуки:** https://dev.1c-bitrix.ru/rest_help/general/webhooks.php

---

## Типы вебхуков

### Входящие вебхуки (Incoming Webhooks)

**Назначение:** Отправка данных в Bitrix24 из внешней системы

**Использование:**
- Создание записей в Bitrix24
- Обновление данных
- Интеграция с внешними системами

**Формат URL:**
```
https://your-domain.bitrix24.ru/rest/1/webhook_code/method
```

**Пример:**
```javascript
// Создание лида через входящий вебхук
fetch('https://your-domain.bitrix24.ru/rest/1/abc123def456/crm.lead.add', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        fields: {
            NAME: 'Иван Иванов',
            EMAIL: [{ VALUE: 'ivan@example.com', VALUE_TYPE: 'WORK' }]
        }
    })
})
.then(response => response.json())
.then(data => {
    if (data.error) {
        console.error('Ошибка:', data.error_description);
    } else {
        console.log('Лид создан:', data.result);
    }
});
```

---

### Исходящие вебхуки (Outgoing Webhooks)

**Назначение:** Получение уведомлений о событиях в Bitrix24

**Использование:**
- Синхронизация данных
- Уведомления о событиях
- Интеграция с внешними системами

**Настройка:**
1. Перейти в настройки Bitrix24
2. Создать исходящий вебхук
3. Указать URL обработчика
4. Выбрать события для отслеживания

**Пример обработчика:**
```javascript
// Обработчик исходящего вебхука
app.post('/webhook/bitrix24', function(req, res) {
    const event = req.body.event;
    const data = req.body.data;
    
    switch (event) {
        case 'ONCRMLEADADD':
            // Обработка создания лида
            handleLeadCreated(data.FIELDS);
            break;
            
        case 'ONCRMLEADUPDATE':
            // Обработка обновления лида
            handleLeadUpdated(data.FIELDS);
            break;
            
        case 'ONCRMLEADDELETE':
            // Обработка удаления лида
            handleLeadDeleted(data.FIELDS);
            break;
    }
    
    res.status(200).json({ success: true });
});

function handleLeadCreated(fields) {
    console.log('Создан лид:', fields.ID);
    // Дополнительная обработка
}
```

---

## Работа с вебхуками через SDK

### Использование BX.rest.callMethod()

**Для входящих вебхуков:**
```javascript
// В приложении Bitrix24
BX.rest.callMethod('crm.lead.add', {
    fields: {
        NAME: 'Иван Иванов',
        EMAIL: [{ VALUE: 'ivan@example.com', VALUE_TYPE: 'WORK' }]
    }
}, function(result) {
    if (result.error()) {
        console.error('Ошибка:', result.error());
    } else {
        console.log('Лид создан:', result.data());
    }
});
```

---

## События вебхуков

### CRM события

#### ONCRMLEADADD
**Описание:** Создание лида

**Данные:**
```json
{
    "event": "ONCRMLEADADD",
    "data": {
        "FIELDS": {
            "ID": "12345",
            "NAME": "Иван Иванов",
            "EMAIL": [{"VALUE": "ivan@example.com"}]
        }
    }
}
```

#### ONCRMLEADUPDATE
**Описание:** Обновление лида

#### ONCRMLEADDELETE
**Описание:** Удаление лида

#### ONCRMDEALADD
**Описание:** Создание сделки

#### ONCRMDEALUPDATE
**Описание:** Обновление сделки

#### ONCRMDEALDELETE
**Описание:** Удаление сделки

---

### События задач

#### ONTASKADD
**Описание:** Создание задачи

#### ONTASKUPDATE
**Описание:** Обновление задачи

#### ONTASKDELETE
**Описание:** Удаление задачи

---

## Безопасность

### Проверка подписи

**Для исходящих вебхуков Bitrix24 отправляет подпись:**

```javascript
app.post('/webhook/bitrix24', function(req, res) {
    const signature = req.headers['x-bitrix-signature'];
    const body = JSON.stringify(req.body);
    
    // Проверка подписи
    const expectedSignature = crypto
        .createHmac('sha256', WEBHOOK_SECRET)
        .update(body)
        .digest('hex');
    
    if (signature !== expectedSignature) {
        res.status(401).json({ error: 'Invalid signature' });
        return;
    }
    
    // Обработка события
    // ...
});
```

---

## Примеры использования

### Пример 1: Синхронизация лидов

```javascript
// Обработчик исходящего вебхука
app.post('/webhook/bitrix24', function(req, res) {
    const event = req.body.event;
    const fields = req.body.data.FIELDS;
    
    if (event === 'ONCRMLEADADD') {
        // Синхронизация с внешней системой
        syncLeadToExternalSystem(fields);
    }
    
    res.status(200).json({ success: true });
});

function syncLeadToExternalSystem(lead) {
    // Отправка данных во внешнюю систему
    fetch('https://external-system.com/api/leads', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + EXTERNAL_API_TOKEN
        },
        body: JSON.stringify({
            id: lead.ID,
            name: lead.NAME,
            email: lead.EMAIL?.[0]?.VALUE,
            phone: lead.PHONE?.[0]?.VALUE
        })
    });
}
```

### Пример 2: Уведомления о событиях

```javascript
// Обработчик исходящего вебхука
app.post('/webhook/bitrix24', function(req, res) {
    const event = req.body.event;
    const fields = req.body.data.FIELDS;
    
    switch (event) {
        case 'ONCRMLEADADD':
            sendNotification('Создан новый лид: ' + fields.NAME);
            break;
            
        case 'ONCRMDEALADD':
            sendNotification('Создана новая сделка: ' + fields.TITLE);
            break;
            
        case 'ONTASKADD':
            sendNotification('Создана новая задача: ' + fields.TITLE);
            break;
    }
    
    res.status(200).json({ success: true });
});

function sendNotification(message) {
    // Отправка уведомления (например, в Telegram)
    fetch('https://api.telegram.org/bot' + TELEGRAM_BOT_TOKEN + '/sendMessage', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            chat_id: TELEGRAM_CHAT_ID,
            text: message
        })
    });
}
```

---

## Лучшие практики

### 1. Обработка ошибок

```javascript
app.post('/webhook/bitrix24', function(req, res) {
    try {
        const event = req.body.event;
        const data = req.body.data;
        
        // Обработка события
        handleEvent(event, data);
        
        res.status(200).json({ success: true });
    } catch (error) {
        console.error('Ошибка обработки вебхука:', error);
        res.status(500).json({ error: 'Internal server error' });
    }
});
```

### 2. Идемпотентность

```javascript
// Хранение обработанных событий
const processedEvents = new Set();

app.post('/webhook/bitrix24', function(req, res) {
    const eventId = req.body.data.FIELDS.ID + '_' + req.body.event;
    
    if (processedEvents.has(eventId)) {
        // Событие уже обработано
        res.status(200).json({ success: true, message: 'Already processed' });
        return;
    }
    
    // Обработка события
    handleEvent(req.body.event, req.body.data);
    
    processedEvents.add(eventId);
    res.status(200).json({ success: true });
});
```

### 3. Логирование

```javascript
app.post('/webhook/bitrix24', function(req, res) {
    const event = req.body.event;
    const timestamp = new Date().toISOString();
    
    console.log(`[${timestamp}] Webhook received: ${event}`, req.body);
    
    // Обработка события
    handleEvent(event, req.body.data);
    
    res.status(200).json({ success: true });
});
```

---

## Ссылки

- **REST API:** [../../Webhook/](../../Webhook/)
- **JavaScript SDK:** [../JavaScript-SDK/](../JavaScript-SDK/)
- **Официальная документация:** https://dev.1c-bitrix.ru/rest_help/general/webhooks.php

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана детальная документация по Webhooks API







