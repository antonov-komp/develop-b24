# SDK (Software Development Kit) — набор инструментов для разработки под Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по SDK для разработки приложений и интеграций с Bitrix24

---

## Обзор

**SDK (Software Development Kit)** — набор инструментов, библиотек и утилит для разработки приложений под Bitrix24. SDK включает JavaScript-библиотеки, UI-компоненты, утилиты для работы с API и другие инструменты разработки.

**Официальная документация:** https://apidocs.bitrix24.ru/sdk/index.html

---

## Когда использовать

✅ **Используйте SDK, если:**
- Разрабатываете приложения для Bitrix24
- Создаёте встройки (placements) в интерфейс Bitrix24
- Нужны готовые UI-компоненты для интерфейса
- Требуется работа с JavaScript API Bitrix24
- Разрабатываете мобильные приложения

❌ **Не используйте SDK, если:**
- Работаете только с REST API из внешнего приложения
- Не нужны UI-компоненты Bitrix24

---

## Основные компоненты SDK

### 1. JavaScript SDK

**Назначение:** JavaScript-библиотеки для работы с Bitrix24 API и интерфейсом

**Документация:** [JavaScript-SDK/](./JavaScript-SDK/)

**Основные возможности:**
- Работа с REST API через JavaScript
- Управление сессиями и авторизацией
- Работа с событиями Bitrix24
- Утилиты для работы с данными

**Основные объекты:**
- `BX` — глобальный объект Bitrix24
- `BX.ajax()` — AJAX-запросы
- `BX.rest.callMethod()` — вызовы REST API
- `BX.ready()` — инициализация после загрузки

---

### 2. UI Kit

**Назначение:** Готовые UI-компоненты для создания интерфейсов в Bitrix24

**Документация:** [UI-Kit/](./UI-Kit/)

**Основные компоненты:**
- Кнопки (`BX.UI.Button`)
- Формы (`BX.UI.Form`)
- Таблицы (`BX.UI.Table`)
- Модальные окна (`BX.PopupWindow`)
- Уведомления (`BX.UI.Notification`)

---

### 3. REST Client

**Назначение:** Библиотека для работы с REST API Bitrix24

**Документация:** [REST-Client/](./REST-Client/)

**Основные методы:**
- `BX.rest.callMethod()` — вызов метода REST API
- `BX.rest.callBatch()` — батч-запросы

---

### 4. Placements API

**Назначение:** API для создания встроек в интерфейс Bitrix24

**Документация:** [Placements/](./Placements/)

**Основные методы:**
- `placement.bind` — регистрация placement
- `placement.unbind` — отвязка placement
- `placement.list` — получение списка placements

---

### 5. Webhooks API

**Назначение:** API для работы с вебхуками (входящие и исходящие)

**Документация:** [Webhooks/](./Webhooks/)

**Типы вебхуков:**
- Входящие вебхуки (Incoming Webhooks)
- Исходящие вебхуки (Outgoing Webhooks)

---

### 2. UI Kit

**Назначение:** Готовые UI-компоненты для создания интерфейсов в Bitrix24

**Документация:**
- **Официальная:** https://apidocs.bitrix24.ru/sdk/ui.html
- **GitHub:** https://github.com/bitrix24/b24ui

**Основные компоненты:**

#### Кнопки
```javascript
// Создание кнопки
const button = new BX.UI.Button({
    text: 'Сохранить',
    color: BX.UI.Button.Color.PRIMARY,
    size: BX.UI.Button.Size.MEDIUM,
    onclick: function() {
        // Обработка клика
    }
});

button.renderTo(document.getElementById('button-container'));
```

#### Формы
```javascript
// Создание формы
const form = new BX.UI.Form({
    fields: [
        {
            name: 'name',
            type: 'text',
            title: 'Имя',
            required: true
        },
        {
            name: 'email',
            type: 'email',
            title: 'Email',
            required: true
        }
    ],
    onSubmit: function(data) {
        // Обработка отправки формы
        console.log('Данные формы:', data);
    }
});

form.renderTo(document.getElementById('form-container'));
```

#### Таблицы
```javascript
// Создание таблицы
const table = new BX.UI.Table({
    columns: [
        { id: 'id', title: 'ID' },
        { id: 'name', title: 'Имя' },
        { id: 'email', title: 'Email' }
    ],
    data: [
        { id: 1, name: 'Иван', email: 'ivan@example.com' },
        { id: 2, name: 'Пётр', email: 'petr@example.com' }
    ]
});

table.renderTo(document.getElementById('table-container'));
```

#### Модальные окна
```javascript
// Создание модального окна
const popup = new BX.PopupWindow('custom-popup', null, {
    content: '<div>Содержимое попапа</div>',
    width: 600,
    height: 400,
    zIndex: 1000,
    buttons: [
        new BX.PopupWindowButton({
            text: 'Закрыть',
            className: 'ui-btn ui-btn-primary',
            events: {
                click: function() {
                    popup.close();
                }
            }
        })
    ]
});

popup.show();
```

#### Уведомления
```javascript
// Показ уведомления
BX.UI.Notification.Center.notify({
    content: 'Операция выполнена успешно',
    autoHideDelay: 5000
});

// Показ ошибки
BX.UI.Notification.Center.notify({
    content: 'Произошла ошибка',
    autoHideDelay: 5000,
    type: 'error'
});
```

---

### 3. Mobile Client Protocol (MCP)

**Назначение:** Протокол для разработки мобильных приложений

**Документация:**
- **Официальная:** https://apidocs.bitrix24.ru/sdk/mcp.html

**Особенности:**
- Оптимизация для мобильных устройств
- Поддержка офлайн-режима
- Инкрементальная синхронизация
- Сжатие данных

**Подробнее:** [../MCP/](../MCP/)

---

### 4. Placements API

**Назначение:** API для создания встроек в интерфейс Bitrix24

**Документация:**
- **Официальная:** https://apidocs.bitrix24.ru/sdk/placement.html
- **REST API:** https://context7.com/bitrix24/rest/placement/

**Основные типы placements:**
- `CRM_LEAD_DETAIL_TAB` — вкладка в карточке лида
- `CRM_DEAL_DETAIL_TAB` — вкладка в карточке сделки
- `CRM_CONTACT_DETAIL_TAB` — вкладка в карточке контакта
- `CRM_COMPANY_DETAIL_TAB` — вкладка в карточке компании
- `TOP_MENU` — пункт в верхнем меню
- `USER_PROFILE` — вкладка в профиле пользователя

**Пример создания placement:**
```javascript
// Регистрация placement
BX.rest.callMethod('placement.bind', {
    PLACEMENT: 'CRM_LEAD_DETAIL_TAB',
    HANDLER: 'https://your-app.com/placement/lead-detail',
    TITLE: 'Кастомная вкладка',
    ICON_ID: 'custom-icon'
}, function(result) {
    if (result.error()) {
        console.error('Ошибка регистрации placement:', result.error());
    } else {
        console.log('Placement зарегистрирован');
    }
});
```

**Подробнее:** [../Placement/](../Placement/)

---

### 5. Webhooks API

**Назначение:** API для работы с вебхуками (входящие и исходящие)

**Документация:**
- **REST API:** https://context7.com/bitrix24/rest/webhook/
- **Вебхуки:** https://dev.1c-bitrix.ru/rest_help/general/webhooks.php

**Подробнее:** [../Webhook/](../Webhook/)

---

## Структура SDK

### JavaScript API

**Глобальные объекты:**
- `BX` — основной объект Bitrix24
- `BX.rest` — работа с REST API
- `BX.ajax` — AJAX-запросы
- `BX.UI` — UI-компоненты
- `BX.PopupWindow` — модальные окна
- `BX.Main` — основные утилиты

**Основные методы:**
```javascript
// Инициализация
BX.ready(function() {
    // Код выполнится после загрузки Bitrix24
});

// AJAX-запрос
BX.ajax({
    url: '/local/api/endpoint.php',
    method: 'POST',
    data: { key: 'value' },
    dataType: 'json',
    onsuccess: function(data) {
        console.log('Успех:', data);
    },
    onfailure: function(error) {
        console.error('Ошибка:', error);
    }
});

// Вызов REST API
BX.rest.callMethod('crm.lead.list', {
    filter: { '>ID': 0 },
    select: ['ID', 'NAME']
}, function(result) {
    if (result.error()) {
        console.error('Ошибка:', result.error());
    } else {
        console.log('Данные:', result.data());
    }
});
```

---

## Интеграция с приложениями

### Создание приложения для Bitrix24

**Шаги:**
1. Регистрация приложения в Bitrix24
2. Настройка placements (если нужны)
3. Разработка интерфейса с использованием UI Kit
4. Интеграция с REST API через JavaScript SDK

**Пример структуры приложения:**
```
your-app/
├── index.html          # Главная страница
├── placement/
│   └── lead-detail.html # Placement для карточки лида
├── js/
│   ├── app.js         # Основная логика
│   └── api.js         # Работа с API
└── css/
    └── styles.css     # Стили
```

---

## Лучшие практики

### 1. Использование BX.ready()

**Всегда инициализируйте код после загрузки Bitrix24:**
```javascript
// ❌ Плохо: код может выполниться до загрузки SDK
document.addEventListener('DOMContentLoaded', function() {
    BX.rest.callMethod('crm.lead.list', {}, function(result) {
        // ...
    });
});

// ✅ Хорошо: код выполнится после загрузки SDK
BX.ready(function() {
    BX.rest.callMethod('crm.lead.list', {}, function(result) {
        // ...
    });
});
```

### 2. Обработка ошибок

**Всегда обрабатывайте ошибки при вызове API:**
```javascript
BX.rest.callMethod('crm.lead.add', {
    fields: {
        NAME: 'Иван Иванов'
    }
}, function(result) {
    if (result.error()) {
        // Обработка ошибки
        const error = result.error();
        console.error('Код ошибки:', error.error);
        console.error('Описание:', error.error_description);
        
        // Показ уведомления пользователю
        BX.UI.Notification.Center.notify({
            content: 'Ошибка: ' + error.error_description,
            autoHideDelay: 5000,
            type: 'error'
        });
    } else {
        // Обработка успешного результата
        const leadId = result.data();
        console.log('Лид создан:', leadId);
    }
});
```

### 3. Использование UI Kit компонентов

**Используйте готовые компоненты вместо кастомных:**
```javascript
// ❌ Плохо: кастомная кнопка
const button = document.createElement('button');
button.textContent = 'Сохранить';
button.className = 'custom-button';
button.onclick = function() { /* ... */ };

// ✅ Хорошо: компонент UI Kit
const button = new BX.UI.Button({
    text: 'Сохранить',
    color: BX.UI.Button.Color.PRIMARY,
    onclick: function() { /* ... */ }
});
button.renderTo(container);
```

### 4. Кеширование данных

**Кешируйте данные, которые редко изменяются:**
```javascript
// Кеширование списка статусов
let statusesCache = null;

function getStatuses(callback) {
    if (statusesCache) {
        callback(statusesCache);
        return;
    }
    
    BX.rest.callMethod('crm.status.list', {
        filter: { ENTITY_ID: 'STATUS' }
    }, function(result) {
        if (!result.error()) {
            statusesCache = result.data();
            callback(statusesCache);
        }
    });
}
```

---

## Примеры использования

### Пример 1: Создание формы создания лида

```javascript
BX.ready(function() {
    const form = new BX.UI.Form({
        fields: [
            {
                name: 'name',
                type: 'text',
                title: 'Имя',
                required: true
            },
            {
                name: 'email',
                type: 'email',
                title: 'Email',
                required: true
            },
            {
                name: 'phone',
                type: 'tel',
                title: 'Телефон'
            }
        ],
        onSubmit: function(data) {
            // Отправка данных через REST API
            BX.rest.callMethod('crm.lead.add', {
                fields: {
                    NAME: data.name,
                    EMAIL: [{ VALUE: data.email, VALUE_TYPE: 'WORK' }],
                    PHONE: [{ VALUE: data.phone, VALUE_TYPE: 'WORK' }]
                }
            }, function(result) {
                if (result.error()) {
                    BX.UI.Notification.Center.notify({
                        content: 'Ошибка создания лида: ' + result.error().error_description,
                        autoHideDelay: 5000,
                        type: 'error'
                    });
                } else {
                    BX.UI.Notification.Center.notify({
                        content: 'Лид успешно создан',
                        autoHideDelay: 5000
                    });
                    form.reset();
                }
            });
        }
    });
    
    form.renderTo(document.getElementById('lead-form-container'));
});
```

### Пример 2: Таблица лидов с пагинацией

```javascript
BX.ready(function() {
    let currentPage = 0;
    const pageSize = 50;
    
    function loadLeads(page) {
        BX.rest.callMethod('crm.lead.list', {
            filter: { '>ID': 0 },
            select: ['ID', 'NAME', 'EMAIL', 'PHONE', 'STATUS_ID'],
            order: { ID: 'DESC' },
            start: page * pageSize,
            limit: pageSize
        }, function(result) {
            if (!result.error()) {
                const leads = result.data();
                renderTable(leads);
                
                // Кнопка "Загрузить ещё"
                if (leads.length === pageSize) {
                    showLoadMoreButton();
                }
            }
        });
    }
    
    function renderTable(leads) {
        const table = new BX.UI.Table({
            columns: [
                { id: 'id', title: 'ID' },
                { id: 'name', title: 'Имя' },
                { id: 'email', title: 'Email' },
                { id: 'phone', title: 'Телефон' },
                { id: 'status', title: 'Статус' }
            ],
            data: leads.map(lead => ({
                id: lead.ID,
                name: lead.NAME || '',
                email: lead.EMAIL?.[0]?.VALUE || '',
                phone: lead.PHONE?.[0]?.VALUE || '',
                status: lead.STATUS_ID || ''
            }))
        });
        
        table.renderTo(document.getElementById('leads-table-container'));
    }
    
    function showLoadMoreButton() {
        const button = new BX.UI.Button({
            text: 'Загрузить ещё',
            onclick: function() {
                currentPage++;
                loadLeads(currentPage);
            }
        });
        
        button.renderTo(document.getElementById('load-more-container'));
    }
    
    // Загрузка первой страницы
    loadLeads(0);
});
```

### Пример 3: Placement для карточки лида

```html
<!DOCTYPE html>
<html>
<head>
    <title>Кастомная вкладка лида</title>
    <script src="https://api.bitrix24.com/api/v1/"></script>
</head>
<body>
    <div id="lead-details-container">
        <h2>Дополнительная информация</h2>
        <div id="lead-data"></div>
    </div>
    
    <script>
        BX.ready(function() {
            // Получение ID лида из параметров placement
            const leadId = BX24.getPlacementOptions().ID;
            
            if (leadId) {
                // Загрузка данных лида
                BX.rest.callMethod('crm.lead.get', {
                    id: leadId
                }, function(result) {
                    if (!result.error()) {
                        const lead = result.data();
                        renderLeadData(lead);
                    }
                });
            }
        });
        
        function renderLeadData(lead) {
            const container = document.getElementById('lead-data');
            container.innerHTML = `
                <p><strong>ID:</strong> ${lead.ID}</p>
                <p><strong>Имя:</strong> ${lead.NAME || 'Не указано'}</p>
                <p><strong>Email:</strong> ${lead.EMAIL?.[0]?.VALUE || 'Не указано'}</p>
                <p><strong>Телефон:</strong> ${lead.PHONE?.[0]?.VALUE || 'Не указано'}</p>
            `;
        }
    </script>
</body>
</html>
```

---

## Решение проблем

### Проблема: SDK не загружается

**Причина:** Скрипт SDK не подключён или загружается после вашего кода

**Решение:**
```javascript
// Всегда используйте BX.ready()
BX.ready(function() {
    // Ваш код здесь
});
```

### Проблема: Ошибка авторизации при вызове API

**Причина:** Неверные параметры авторизации или истёкший токен

**Решение:**
- Проверьте настройки приложения в Bitrix24
- Убедитесь, что используете правильный метод авторизации
- Проверьте срок действия токена

### Проблема: UI-компоненты не отображаются

**Причина:** Не подключены стили UI Kit или неправильная инициализация

**Решение:**
- Убедитесь, что подключены стили UI Kit
- Проверьте, что компонент правильно инициализирован
- Используйте метод `renderTo()` для отображения компонента

---

## Ссылки

- **Официальная документация SDK:** https://apidocs.bitrix24.ru/sdk/index.html
- **JavaScript API:** https://apidocs.bitrix24.ru/sdk/js/
- **UI Kit:** https://apidocs.bitrix24.ru/sdk/ui.html
- **GitHub UI Kit:** https://github.com/bitrix24/b24ui
- **Placements:** [../Placement/](../Placement/)
- **Webhooks:** [../Webhook/](../Webhook/)
- **REST API:** [../REST-API/](../REST-API/)

---

## Основные библиотеки SDK

### B24 JS SDK
**Документация:** [B24-JS-SDK/](./B24-JS-SDK/)  
**Описание:** Основная JavaScript библиотека для работы с Bitrix24 (внутри Bitrix24)

### BX24 JS Library
**Документация:** [BX24-JS-Library/](./BX24-JS-Library/)  
**Описание:** JavaScript библиотека для работы с Bitrix24 из внешних приложений

### B24 PHP SDK
**Документация:** [B24-PHP-SDK/](./B24-PHP-SDK/)  
**Описание:** Официальная PHP библиотека для работы с Bitrix24 REST API

### CRest PHP SDK
**Документация:** [CRest-PHP-SDK/](./CRest-PHP-SDK/)  
**Описание:** Легковесная PHP библиотека CRest для работы с Bitrix24 REST API

---

## Дополнительные библиотеки

### CRM Library
**Документация:** [CRM-Library/](./CRM-Library/)  
**Описание:** Библиотека для работы с CRM-сущностями (лиды, сделки, контакты, компании)

### Tasks Library
**Документация:** [Tasks-Library/](./Tasks-Library/)  
**Описание:** Библиотека для работы с задачами и проектами

### Calendar Library
**Документация:** [Calendar-Library/](./Calendar-Library/)  
**Описание:** Библиотека для работы с календарём и событиями

### Drive Library
**Документация:** [Drive-Library/](./Drive-Library/)  
**Описание:** Библиотека для работы с файлами и облачным хранилищем

### IM Library
**Документация:** [IM-Library/](./IM-Library/)  
**Описание:** Библиотека для работы с мессенджером

### Timeman Library
**Документация:** [Timeman-Library/](./Timeman-Library/)  
**Описание:** Библиотека для работы с учётом рабочего времени

### Utils
**Документация:** [Utils/](./Utils/)  
**Описание:** Утилиты для работы с данными, строками, датами

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Добавлена детальная документация по всем библиотекам SDK
- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по SDK Bitrix24

