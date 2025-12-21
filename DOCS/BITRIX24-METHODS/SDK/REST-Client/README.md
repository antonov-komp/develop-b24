# REST Client — библиотека для работы с REST API Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Детальная документация по REST Client — библиотеке для работы с REST API

---

## Обзор

**REST Client** — библиотека для работы с Bitrix24 REST API через JavaScript. Предоставляет удобные методы для вызова API-методов, обработки ответов и управления запросами.

**Документация:**
- **REST API:** https://context7.com/bitrix24/rest/
- **JavaScript SDK:** https://apidocs.bitrix24.ru/sdk/js/

---

## Основные методы

### BX.rest.callMethod()

**Назначение:** Вызов метода REST API

**Синтаксис:**
```javascript
BX.rest.callMethod(method, params, callback);
```

**Параметры:**
- `method` (string) — метод REST API (например, 'crm.lead.list')
- `params` (object) — параметры запроса
- `callback` (function) — функция обратного вызова

**Пример:**
```javascript
BX.rest.callMethod('crm.lead.list', {
    filter: { '>ID': 0 },
    select: ['ID', 'NAME', 'EMAIL'],
    order: { ID: 'DESC' },
    start: 0,
    limit: 50
}, function(result) {
    if (result.error()) {
        console.error('Ошибка:', result.error());
    } else {
        const leads = result.data();
        console.log('Лиды:', leads);
    }
});
```

**Объект результата:**
```javascript
result.data()        // Данные результата
result.error()       // Ошибка (если есть)
result.hasMore()    // Есть ли ещё данные
result.total()      // Общее количество записей
result.next()        // Следующая страница
```

---

### BX.rest.callBatch()

**Назначение:** Выполнение батч-запросов

**Синтаксис:**
```javascript
BX.rest.callBatch(commands, callback, halt);
```

**Параметры:**
- `commands` (object) — объект с командами
- `callback` (function) — функция обратного вызова
- `halt` (number) — остановка при ошибке (0 или 1)

**Пример:**
```javascript
const commands = {
    'lead_1': ['crm.lead.get', { id: 1 }],
    'lead_2': ['crm.lead.get', { id: 2 }],
    'leads': ['crm.lead.list', { filter: { '>ID': 0 } }]
};

BX.rest.callBatch(commands, function(result) {
    if (result.error()) {
        console.error('Ошибка батча:', result.error());
    } else {
        const data = result.data();
        console.log('Лид 1:', data.lead_1);
        console.log('Лид 2:', data.lead_2);
        console.log('Список лидов:', data.leads);
    }
}, 0);
```

---

## Работа с CRM

### Лиды

```javascript
// Получение списка лидов
BX.rest.callMethod('crm.lead.list', {
    filter: { '>CREATED_DATE': '2025-01-01' },
    select: ['ID', 'NAME', 'EMAIL', 'PHONE'],
    order: { ID: 'DESC' }
}, function(result) {
    const leads = result.data();
});

// Получение лида по ID
BX.rest.callMethod('crm.lead.get', {
    id: 12345
}, function(result) {
    const lead = result.data();
});

// Создание лида
BX.rest.callMethod('crm.lead.add', {
    fields: {
        NAME: 'Иван Иванов',
        EMAIL: [{ VALUE: 'ivan@example.com', VALUE_TYPE: 'WORK' }],
        PHONE: [{ VALUE: '+375291234567', VALUE_TYPE: 'WORK' }]
    }
}, function(result) {
    const leadId = result.data();
});

// Обновление лида
BX.rest.callMethod('crm.lead.update', {
    id: 12345,
    fields: {
        NAME: 'Новое имя',
        STATUS_ID: 'IN_PROCESS'
    }
}, function(result) {
    const success = result.data();
});

// Удаление лида
BX.rest.callMethod('crm.lead.delete', {
    id: 12345
}, function(result) {
    const success = result.data();
});
```

### Сделки

```javascript
// Получение списка сделок
BX.rest.callMethod('crm.deal.list', {
    filter: { STAGE_ID: 'NEW' },
    select: ['ID', 'TITLE', 'OPPORTUNITY', 'CURRENCY_ID']
}, function(result) {
    const deals = result.data();
});

// Создание сделки
BX.rest.callMethod('crm.deal.add', {
    fields: {
        TITLE: 'Новая сделка',
        STAGE_ID: 'NEW',
        OPPORTUNITY: 100000,
        CURRENCY_ID: 'BYN'
    }
}, function(result) {
    const dealId = result.data();
});
```

---

## Обработка ошибок

### Типы ошибок

```javascript
BX.rest.callMethod('crm.lead.add', {
    fields: { NAME: 'Иван' }
}, function(result) {
    if (result.error()) {
        const error = result.error();
        
        switch (error.error) {
            case 'QUERY_LIMIT_EXCEEDED':
                // Превышен лимит запросов
                console.error('Превышен лимит запросов');
                break;
                
            case 'INVALID_TOKEN':
                // Неверный токен
                console.error('Неверный токен');
                break;
                
            case 'NO_AUTH_FOUND':
                // Не найдена авторизация
                console.error('Не найдена авторизация');
                break;
                
            default:
                console.error('Ошибка:', error.error_description);
        }
    }
});
```

### Обработка с повторными попытками

```javascript
function callWithRetry(method, params, callback, maxRetries) {
    maxRetries = maxRetries || 3;
    let attempts = 0;
    
    function attempt() {
        attempts++;
        
        BX.rest.callMethod(method, params, function(result) {
            if (result.error()) {
                const error = result.error();
                
                if (error.error === 'QUERY_LIMIT_EXCEEDED' && attempts < maxRetries) {
                    // Повторная попытка после задержки
                    setTimeout(attempt, 1000);
                } else {
                    callback(result);
                }
            } else {
                callback(result);
            }
        });
    }
    
    attempt();
}

// Использование
callWithRetry('crm.lead.list', {}, function(result) {
    if (!result.error()) {
        console.log('Лиды:', result.data());
    }
});
```

---

## Пагинация

### Получение всех записей

```javascript
function getAllLeads(filter, callback) {
    const allLeads = [];
    let start = 0;
    const limit = 50;
    
    function loadPage() {
        BX.rest.callMethod('crm.lead.list', {
            filter: filter,
            select: ['ID', 'NAME'],
            start: start,
            limit: limit
        }, function(result) {
            if (result.error()) {
                callback(result);
                return;
            }
            
            const leads = result.data();
            allLeads.push(...leads);
            
            if (leads.length === limit && result.hasMore()) {
                start += limit;
                loadPage();
            } else {
                callback({ data: function() { return allLeads; } });
            }
        });
    }
    
    loadPage();
}

// Использование
getAllLeads({ '>ID': 0 }, function(result) {
    const leads = result.data();
    console.log('Всего лидов:', leads.length);
});
```

---

## Кеширование

### Кеширование данных

```javascript
const cache = {};

function getCachedLeads(filter, callback) {
    const cacheKey = JSON.stringify(filter);
    
    if (cache[cacheKey]) {
        callback({ data: function() { return cache[cacheKey]; } });
        return;
    }
    
    BX.rest.callMethod('crm.lead.list', {
        filter: filter,
        select: ['ID', 'NAME']
    }, function(result) {
        if (!result.error()) {
            cache[cacheKey] = result.data();
            // Очистка кеша через 5 минут
            setTimeout(function() {
                delete cache[cacheKey];
            }, 5 * 60 * 1000);
        }
        callback(result);
    });
}
```

---

## Примеры использования

### Пример 1: Создание лида с контактом

```javascript
function createLeadWithContact(leadData, contactData, callback) {
    // Создание контакта
    BX.rest.callMethod('crm.contact.add', {
        fields: contactData
    }, function(contactResult) {
        if (contactResult.error()) {
            callback(contactResult);
            return;
        }
        
        const contactId = contactResult.data();
        
        // Создание лида
        BX.rest.callMethod('crm.lead.add', {
            fields: {
                ...leadData,
                CONTACT_ID: contactId
            }
        }, function(leadResult) {
            if (leadResult.error()) {
                callback(leadResult);
                return;
            }
            
            const leadId = leadResult.data();
            
            // Привязка контакта к лиду
            BX.rest.callMethod('crm.lead.contact.add', {
                id: leadId,
                fields: {
                    CONTACT_ID: contactId
                }
            }, function(linkResult) {
                callback({
                    leadId: leadId,
                    contactId: contactId,
                    linked: !linkResult.error()
                });
            });
        });
    });
}
```

### Пример 2: Батч-запрос для получения связанных данных

```javascript
function getLeadWithRelated(leadId, callback) {
    const commands = {
        'lead': ['crm.lead.get', { id: leadId }],
        'contacts': ['crm.lead.contact.items.get', { id: leadId }],
        'company': function(result) {
            const lead = result.data().lead;
            if (lead.COMPANY_ID) {
                return ['crm.company.get', { id: lead.COMPANY_ID }];
            }
            return null;
        }
    };
    
    BX.rest.callBatch(commands, function(result) {
        if (result.error()) {
            callback(result);
            return;
        }
        
        const data = result.data();
        callback({
            lead: data.lead,
            contacts: data.contacts,
            company: data.company || null
        });
    });
}
```

---

## Ссылки

- **REST API:** [../../REST-API/](../../REST-API/)
- **JavaScript SDK:** [../JavaScript-SDK/](../JavaScript-SDK/)
- **CRM методы:** [../../CRM/](../../CRM/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана детальная документация по REST Client




