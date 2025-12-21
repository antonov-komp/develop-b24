# JavaScript SDK — основная библиотека для работы с Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Детальная документация по JavaScript SDK Bitrix24

---

## Обзор

**JavaScript SDK** — основная библиотека для работы с Bitrix24 через JavaScript. Предоставляет глобальный объект `BX` с методами для работы с API, AJAX-запросами, событиями и утилитами.

**Документация:**
- **Официальная:** https://apidocs.bitrix24.ru/sdk/js/

---

## Подключение

### В приложении Bitrix24

```html
<!-- SDK загружается автоматически в Bitrix24 -->
<script>
    BX.ready(function() {
        // Ваш код здесь
    });
</script>
```

### Во внешнем приложении

```html
<script src="https://api.bitrix24.com/api/v1/"></script>
<script>
    BX24.init(function() {
        // Инициализация после загрузки SDK
    });
</script>
```

---

## Основные объекты и методы

### BX — глобальный объект

**Назначение:** Основной объект для работы с Bitrix24

**Основные свойства:**
- `BX.ajax` — работа с AJAX-запросами
- `BX.rest` — работа с REST API
- `BX.UI` — UI-компоненты
- `BX.Main` — основные утилиты
- `BX.Event` — работа с событиями

---

### BX.ready()

**Назначение:** Выполнение кода после загрузки Bitrix24 SDK

**Синтаксис:**
```javascript
BX.ready(function() {
    // Код выполнится после загрузки SDK
});
```

**Пример:**
```javascript
BX.ready(function() {
    console.log('SDK загружен');
    
    // Вызов REST API
    BX.rest.callMethod('crm.lead.list', {}, function(result) {
        console.log('Результат:', result.data());
    });
});
```

---

### BX.ajax()

**Назначение:** Выполнение AJAX-запросов

**Синтаксис:**
```javascript
BX.ajax({
    url: 'URL',
    method: 'POST',
    data: {},
    dataType: 'json',
    onsuccess: function(data) {},
    onfailure: function(error) {}
});
```

**Параметры:**
- `url` (string) — URL запроса
- `method` (string) — метод HTTP (GET, POST, PUT, DELETE)
- `data` (object) — данные для отправки
- `dataType` (string) — тип данных (json, html, text)
- `onsuccess` (function) — обработчик успешного ответа
- `onfailure` (function) — обработчик ошибки
- `timeout` (number) — таймаут запроса в миллисекундах

**Пример:**
```javascript
BX.ajax({
    url: '/local/api/get-leads.php',
    method: 'POST',
    data: {
        filter: { '>ID': 0 },
        select: ['ID', 'NAME']
    },
    dataType: 'json',
    onsuccess: function(data) {
        console.log('Успех:', data);
    },
    onfailure: function(error) {
        console.error('Ошибка:', error);
    }
});
```

**Синхронный запрос:**
```javascript
const result = BX.ajax({
    url: '/local/api/get-data.php',
    async: false
});
```

---

### BX.rest.callMethod()

**Назначение:** Вызов методов Bitrix24 REST API

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
    filter: {
        '>CREATED_DATE': '2025-01-01'
    },
    select: ['ID', 'NAME', 'EMAIL', 'PHONE'],
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

**Обработка ошибок:**
```javascript
BX.rest.callMethod('crm.lead.add', {
    fields: {
        NAME: 'Иван Иванов'
    }
}, function(result) {
    if (result.error()) {
        const error = result.error();
        console.error('Код ошибки:', error.error);
        console.error('Описание:', error.error_description);
        
        // Обработка конкретных ошибок
        if (error.error === 'QUERY_LIMIT_EXCEEDED') {
            // Превышен лимит запросов
        } else if (error.error === 'INVALID_TOKEN') {
            // Неверный токен
        }
    } else {
        const leadId = result.data();
        console.log('Лид создан:', leadId);
    }
});
```

---

### BX.rest.callBatch()

**Назначение:** Выполнение батч-запросов (несколько методов за один запрос)

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
    'lead_list': ['crm.lead.list', { filter: { '>ID': 0 } }]
};

BX.rest.callBatch(commands, function(result) {
    if (result.error()) {
        console.error('Ошибка батча:', result.error());
    } else {
        const data = result.data();
        console.log('Лид 1:', data.lead_1);
        console.log('Лид 2:', data.lead_2);
        console.log('Список лидов:', data.lead_list);
    }
}, 0); // 0 — не останавливаться при ошибке
```

---

### BX24 — объект для внешних приложений

**Назначение:** Работа с Bitrix24 из внешних приложений

**Основные методы:**

#### BX24.init()

**Инициализация SDK:**
```javascript
BX24.init(function() {
    console.log('SDK инициализирован');
    
    // Получение параметров приложения
    const params = BX24.getPlacementOptions();
    console.log('Параметры:', params);
});
```

#### BX24.getAuth()

**Получение данных авторизации:**
```javascript
BX24.getAuth(function(auth) {
    console.log('Домен:', auth.domain);
    console.log('Пользователь ID:', auth.member_id);
});
```

#### BX24.callMethod()

**Вызов метода REST API:**
```javascript
BX24.callMethod('crm.lead.list', {
    filter: { '>ID': 0 }
}, function(result) {
    if (result.error()) {
        console.error('Ошибка:', result.error());
    } else {
        console.log('Результат:', result.data());
    }
});
```

#### BX24.getPlacementOptions()

**Получение параметров placement:**
```javascript
const options = BX24.getPlacementOptions();
console.log('ID лида:', options.ID);
console.log('Тип placement:', options.PLACEMENT);
```

---

### BX.Main — основные утилиты

#### BX.Main.ajax()

**Альтернативный способ выполнения AJAX:**
```javascript
BX.Main.ajax({
    url: '/local/api/endpoint.php',
    method: 'POST',
    data: { key: 'value' },
    onsuccess: function(data) {
        console.log('Успех:', data);
    }
});
```

#### BX.Main.showWait()

**Показ индикатора загрузки:**
```javascript
BX.Main.showWait('Загрузка данных...');

// После завершения операции
BX.Main.closeWait();
```

#### BX.Main.reload()

**Перезагрузка страницы:**
```javascript
BX.Main.reload();
```

#### BX.Main.redirect()

**Перенаправление:**
```javascript
BX.Main.redirect('/local/pages/custom-page.php');
```

---

### BX.Event — работа с событиями

#### BX.Event.EventEmitter

**Создание объекта для событий:**
```javascript
const emitter = new BX.Event.EventEmitter();

// Подписка на событие
emitter.subscribe('custom-event', function(data) {
    console.log('Событие получено:', data);
});

// Отправка события
emitter.emit('custom-event', { message: 'Привет' });
```

#### BX.addCustomEvent()

**Добавление кастомного события:**
```javascript
BX.addCustomEvent('onAjaxSuccess', function(data) {
    console.log('AJAX успешен:', data);
});
```

#### BX.onCustomEvent()

**Подписка на кастомное событие:**
```javascript
BX.onCustomEvent('onCustomEvent', function(data) {
    console.log('Кастомное событие:', data);
});
```

---

### BX.util — утилиты

#### BX.util.trim()

**Удаление пробелов:**
```javascript
const text = BX.util.trim('  текст  ');
console.log(text); // 'текст'
```

#### BX.util.htmlspecialchars()

**Экранирование HTML:**
```javascript
const safe = BX.util.htmlspecialchars('<script>alert("XSS")</script>');
console.log(safe); // '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;'
```

#### BX.util.urlencode()

**Кодирование URL:**
```javascript
const encoded = BX.util.urlencode('параметр=значение');
console.log(encoded); // 'параметр%3Dзначение'
```

#### BX.util.date()

**Работа с датами:**
```javascript
const date = new Date();
const formatted = BX.util.date(date, 'd.m.Y H:i:s');
console.log(formatted); // '20.12.2025 19:09:00'
```

---

## Работа с DOM

### BX()

**Получение элемента по ID:**
```javascript
const element = BX('element-id');
element.innerHTML = 'Новый текст';
```

### BX.findChild()

**Поиск дочернего элемента:**
```javascript
const parent = BX('parent-id');
const child = BX.findChild(parent, { class: 'child-class' });
```

### BX.findChildren()

**Поиск всех дочерних элементов:**
```javascript
const parent = BX('parent-id');
const children = BX.findChildren(parent, { class: 'child-class' });
```

### BX.addClass()

**Добавление класса:**
```javascript
const element = BX('element-id');
BX.addClass(element, 'new-class');
```

### BX.removeClass()

**Удаление класса:**
```javascript
const element = BX('element-id');
BX.removeClass(element, 'old-class');
```

### BX.toggleClass()

**Переключение класса:**
```javascript
const element = BX('element-id');
BX.toggleClass(element, 'active');
```

### BX.hasClass()

**Проверка наличия класса:**
```javascript
const element = BX('element-id');
if (BX.hasClass(element, 'active')) {
    console.log('Элемент активен');
}
```

---

## Работа с данными

### BX.clone()

**Клонирование объекта:**
```javascript
const original = { name: 'Иван', age: 30 };
const cloned = BX.clone(original);
```

### BX.merge()

**Объединение объектов:**
```javascript
const obj1 = { a: 1, b: 2 };
const obj2 = { b: 3, c: 4 };
const merged = BX.merge(obj1, obj2);
// { a: 1, b: 3, c: 4 }
```

### BX.type()

**Определение типа:**
```javascript
BX.type('text'); // 'string'
BX.type(123); // 'number'
BX.type([]); // 'array'
BX.type({}); // 'object'
```

---

## Лучшие практики

### 1. Всегда используйте BX.ready()

```javascript
// ❌ Плохо
BX.rest.callMethod('crm.lead.list', {}, function(result) {
    // SDK может быть не загружен
});

// ✅ Хорошо
BX.ready(function() {
    BX.rest.callMethod('crm.lead.list', {}, function(result) {
        // SDK гарантированно загружен
    });
});
```

### 2. Обработка ошибок

```javascript
BX.rest.callMethod('crm.lead.add', {
    fields: { NAME: 'Иван' }
}, function(result) {
    if (result.error()) {
        const error = result.error();
        console.error('Ошибка:', error.error_description);
        
        // Показ уведомления пользователю
        BX.UI.Notification.Center.notify({
            content: 'Ошибка: ' + error.error_description,
            autoHideDelay: 5000,
            type: 'error'
        });
    } else {
        console.log('Успех:', result.data());
    }
});
```

### 3. Использование батч-запросов

```javascript
// ❌ Плохо: множественные запросы
BX.rest.callMethod('crm.lead.get', { id: 1 }, function(r1) {});
BX.rest.callMethod('crm.lead.get', { id: 2 }, function(r2) {});
BX.rest.callMethod('crm.lead.get', { id: 3 }, function(r3) {});

// ✅ Хорошо: один батч-запрос
const commands = {
    'lead_1': ['crm.lead.get', { id: 1 }],
    'lead_2': ['crm.lead.get', { id: 2 }],
    'lead_3': ['crm.lead.get', { id: 3 }]
};

BX.rest.callBatch(commands, function(result) {
    const data = result.data();
    console.log('Все лиды:', data);
});
```

---

## Примеры использования

### Пример 1: Загрузка списка лидов

```javascript
BX.ready(function() {
    function loadLeads() {
        BX.Main.showWait('Загрузка лидов...');
        
        BX.rest.callMethod('crm.lead.list', {
            filter: { '>CREATED_DATE': '2025-01-01' },
            select: ['ID', 'NAME', 'EMAIL', 'PHONE', 'STATUS_ID'],
            order: { ID: 'DESC' },
            start: 0,
            limit: 50
        }, function(result) {
            BX.Main.closeWait();
            
            if (result.error()) {
                console.error('Ошибка:', result.error());
                return;
            }
            
            const leads = result.data();
            renderLeads(leads);
        });
    }
    
    function renderLeads(leads) {
        const container = BX('leads-container');
        container.innerHTML = '';
        
        leads.forEach(function(lead) {
            const div = document.createElement('div');
            div.className = 'lead-item';
            div.innerHTML = `
                <h3>${BX.util.htmlspecialchars(lead.NAME || 'Без имени')}</h3>
                <p>Email: ${lead.EMAIL?.[0]?.VALUE || 'Не указан'}</p>
                <p>Телефон: ${lead.PHONE?.[0]?.VALUE || 'Не указан'}</p>
            `;
            container.appendChild(div);
        });
    }
    
    // Загрузка при инициализации
    loadLeads();
});
```

### Пример 2: Создание лида с валидацией

```javascript
BX.ready(function() {
    const form = BX('lead-form');
    
    BX.bind(form, 'submit', function(e) {
        e.preventDefault();
        
        const name = BX('lead-name').value.trim();
        const email = BX('lead-email').value.trim();
        
        // Валидация
        if (!name) {
            alert('Имя обязательно');
            return;
        }
        
        if (!email || !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            alert('Неверный email');
            return;
        }
        
        // Отправка данных
        BX.Main.showWait('Создание лида...');
        
        BX.rest.callMethod('crm.lead.add', {
            fields: {
                NAME: name,
                EMAIL: [{ VALUE: email, VALUE_TYPE: 'WORK' }]
            }
        }, function(result) {
            BX.Main.closeWait();
            
            if (result.error()) {
                alert('Ошибка: ' + result.error().error_description);
            } else {
                alert('Лид успешно создан!');
                form.reset();
            }
        });
    });
});
```

---

## Ссылки

- **Официальная документация:** https://apidocs.bitrix24.ru/sdk/js/
- **REST API:** [../REST-Client/](../REST-Client/)
- **UI Kit:** [../UI-Kit/](../UI-Kit/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана детальная документация по JavaScript SDK





