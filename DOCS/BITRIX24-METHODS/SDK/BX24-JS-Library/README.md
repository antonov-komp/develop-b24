# BX24 JS Library — JavaScript библиотека для внешних приложений

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Детальная документация по BX24 JS Library — JavaScript библиотеке для работы с Bitrix24 из внешних приложений

---

## Обзор

**BX24 JS Library** — специализированная JavaScript библиотека для работы с Bitrix24 из внешних приложений. Предоставляет методы для авторизации, работы с API и получения параметров приложений.

**Документация:**
- **Официальная:** https://apidocs.bitrix24.ru/sdk/js/

---

## Подключение

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

## Основные методы

### BX24.init()

**Назначение:** Инициализация библиотеки

**Синтаксис:**
```javascript
BX24.init(function() {
    // Код выполнится после инициализации
});
```

**Пример:**
```javascript
BX24.init(function() {
    console.log('BX24 SDK инициализирован');
    
    // Получение параметров приложения
    const options = BX24.getPlacementOptions();
    console.log('Параметры:', options);
});
```

---

### BX24.getAuth()

**Назначение:** Получение данных авторизации

**Синтаксис:**
```javascript
BX24.getAuth(function(auth) {
    // auth содержит данные авторизации
});
```

**Пример:**
```javascript
BX24.getAuth(function(auth) {
    console.log('Домен:', auth.domain);
    console.log('Пользователь ID:', auth.member_id);
    console.log('Токен:', auth.auth_token);
    console.log('Рефреш токен:', auth.refresh_token);
    console.log('Срок действия:', auth.expires_in);
});
```

**Объект auth содержит:**
- `domain` — домен Bitrix24
- `member_id` — ID пользователя
- `auth_token` — токен авторизации
- `refresh_token` — токен обновления
- `expires_in` — срок действия токена

---

### BX24.callMethod()

**Назначение:** Вызов метода REST API

**Синтаксис:**
```javascript
BX24.callMethod(method, params, callback);
```

**Параметры:**
- `method` (string) — метод REST API
- `params` (object) — параметры запроса
- `callback` (function) — функция обратного вызова

**Пример:**
```javascript
BX24.callMethod('crm.lead.list', {
    filter: { '>ID': 0 },
    select: ['ID', 'NAME', 'EMAIL'],
    order: { ID: 'DESC' }
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
- `result.data()` — данные результата
- `result.error()` — ошибка (если есть)
- `result.hasMore()` — есть ли ещё данные
- `result.total()` — общее количество записей

---

### BX24.callBatch()

**Назначение:** Выполнение батч-запросов

**Синтаксис:**
```javascript
BX24.callBatch(commands, callback, halt);
```

**Пример:**
```javascript
const commands = {
    'lead_1': ['crm.lead.get', { id: 1 }],
    'lead_2': ['crm.lead.get', { id: 2 }],
    'leads': ['crm.lead.list', { filter: { '>ID': 0 } }]
};

BX24.callBatch(commands, function(result) {
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

### BX24.getPlacementOptions()

**Назначение:** Получение параметров placement

**Синтаксис:**
```javascript
const options = BX24.getPlacementOptions();
```

**Пример:**
```javascript
BX24.init(function() {
    const options = BX24.getPlacementOptions();
    
    console.log('ID лида:', options.ID);
    console.log('Тип placement:', options.PLACEMENT);
    console.log('Режим:', options.MODE);
    console.log('Все параметры:', options);
});
```

**Доступные параметры:**
- `ID` — ID сущности (лида, сделки и т.д.)
- `PLACEMENT` — тип placement
- `MODE` — режим отображения
- Другие параметры в зависимости от типа placement

---

### BX24.resizeWindow()

**Назначение:** Изменение размера окна приложения

**Синтаксис:**
```javascript
BX24.resizeWindow(width, height);
```

**Пример:**
```javascript
BX24.resizeWindow(800, 600);
```

---

### BX24.resizeWindowParent()

**Назначение:** Изменение размера родительского окна

**Синтаксис:**
```javascript
BX24.resizeWindowParent(width, height);
```

**Пример:**
```javascript
BX24.resizeWindowParent(1000, 700);
```

---

### BX24.closeApplication()

**Назначение:** Закрытие приложения

**Синтаксис:**
```javascript
BX24.closeApplication();
```

**Пример:**
```javascript
// Закрытие после успешного сохранения
BX24.callMethod('crm.lead.add', {
    fields: { NAME: 'Иван' }
}, function(result) {
    if (!result.error()) {
        BX24.closeApplication();
    }
});
```

---

### BX24.openPath()

**Назначение:** Открытие пути в Bitrix24

**Синтаксис:**
```javascript
BX24.openPath(path);
```

**Пример:**
```javascript
// Открытие карточки лида
BX24.openPath('/crm/lead/details/' + leadId + '/');
```

---

### BX24.openApplication()

**Назначение:** Открытие другого приложения

**Синтаксис:**
```javascript
BX24.openApplication(appId, params);
```

**Пример:**
```javascript
BX24.openApplication('custom.app', {
    leadId: 12345
});
```

---

## Работа с авторизацией

### Получение токена

```javascript
BX24.getAuth(function(auth) {
    const token = auth.auth_token;
    const domain = auth.domain;
    
    // Использование токена для прямых запросов
    fetch(`https://${domain}/rest/crm.lead.list`, {
        headers: {
            'Authorization': 'Bearer ' + token
        }
    });
});
```

### Обновление токена

```javascript
BX24.getAuth(function(auth) {
    if (auth.expires_in < 3600) {
        // Токен скоро истечёт, обновляем
        BX24.refreshAuth(function(newAuth) {
            console.log('Токен обновлён:', newAuth.auth_token);
        });
    }
});
```

---

## Примеры использования

### Пример 1: Placement для карточки лида

```html
<!DOCTYPE html>
<html>
<head>
    <title>Кастомная вкладка лида</title>
    <script src="https://api.bitrix24.com/api/v1/"></script>
</head>
<body>
    <div id="app-container">
        <h2>Дополнительная информация</h2>
        <div id="lead-data"></div>
    </div>
    
    <script>
        BX24.init(function() {
            const options = BX24.getPlacementOptions();
            const leadId = options.ID;
            
            if (leadId) {
                loadLeadData(leadId);
            }
        });
        
        function loadLeadData(leadId) {
            BX24.callMethod('crm.lead.get', {
                id: leadId
            }, function(result) {
                if (result.error()) {
                    document.getElementById('lead-data').innerHTML = 
                        '<p>Ошибка загрузки данных</p>';
                    return;
                }
                
                const lead = result.data();
                renderLeadData(lead);
            });
        }
        
        function renderLeadData(lead) {
            const container = document.getElementById('lead-data');
            container.innerHTML = `
                <p><strong>ID:</strong> ${lead.ID}</p>
                <p><strong>Имя:</strong> ${lead.NAME || 'Не указано'}</p>
                <p><strong>Email:</strong> ${lead.EMAIL?.[0]?.VALUE || 'Не указан'}</p>
                <p><strong>Телефон:</strong> ${lead.PHONE?.[0]?.VALUE || 'Не указан'}</p>
            `;
        }
    </script>
</body>
</html>
```

### Пример 2: Создание лида с закрытием приложения

```javascript
BX24.init(function() {
    const form = document.getElementById('lead-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const leadData = {
            NAME: formData.get('name'),
            EMAIL: [{ VALUE: formData.get('email'), VALUE_TYPE: 'WORK' }],
            PHONE: [{ VALUE: formData.get('phone'), VALUE_TYPE: 'WORK' }]
        };
        
        BX24.callMethod('crm.lead.add', {
            fields: leadData
        }, function(result) {
            if (result.error()) {
                alert('Ошибка: ' + result.error().error_description);
            } else {
                alert('Лид успешно создан!');
                BX24.closeApplication();
            }
        });
    });
});
```

### Пример 3: Работа с авторизацией

```javascript
BX24.init(function() {
    BX24.getAuth(function(auth) {
        console.log('Авторизация получена');
        console.log('Домен:', auth.domain);
        console.log('Пользователь:', auth.member_id);
        
        // Сохранение токена для использования вне SDK
        localStorage.setItem('bitrix24_token', auth.auth_token);
        localStorage.setItem('bitrix24_domain', auth.domain);
        
        // Загрузка данных
        loadData();
    });
});

function loadData() {
    BX24.callMethod('crm.lead.list', {
        filter: { '>ID': 0 },
        select: ['ID', 'NAME']
    }, function(result) {
        if (!result.error()) {
            const leads = result.data();
            console.log('Загружено лидов:', leads.length);
        }
    });
}
```

---

## Отличия от B24 JS SDK

| Функция | B24 JS SDK | BX24 JS Library |
|---------|------------|-----------------|
| Использование | Внутри Bitrix24 | Внешние приложения |
| Инициализация | `BX.ready()` | `BX24.init()` |
| Авторизация | Автоматическая | Через `BX24.getAuth()` |
| Работа с окнами | `BX.Main` | `BX24.resizeWindow()` |
| Placements | Через `BX24.getPlacementOptions()` | Через `BX24.getPlacementOptions()` |

---

## Лучшие практики

### 1. Всегда используйте BX24.init()

```javascript
// ❌ Плохо
BX24.callMethod('crm.lead.list', {}, function(result) {});

// ✅ Хорошо
BX24.init(function() {
    BX24.callMethod('crm.lead.list', {}, function(result) {});
});
```

### 2. Проверка параметров placement

```javascript
BX24.init(function() {
    const options = BX24.getPlacementOptions();
    
    if (!options.ID) {
        document.getElementById('app-container').innerHTML = 
            '<p>ID не передан</p>';
        return;
    }
    
    // Продолжение работы
});
```

### 3. Обработка ошибок авторизации

```javascript
BX24.getAuth(function(auth) {
    if (!auth.auth_token) {
        console.error('Токен не получен');
        return;
    }
    
    // Использование токена
});
```

---

## Ссылки

- **Официальная документация:** https://apidocs.bitrix24.ru/sdk/js/
- **B24 JS SDK:** [../B24-JS-SDK/](../B24-JS-SDK/)
- **Placements:** [../Placements/](../Placements/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана детальная документация по BX24 JS Library

