# Placements API — библиотека для создания встроек в интерфейс Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Детальная документация по Placements API — библиотеке для создания встроек в интерфейс Bitrix24

---

## Обзор

**Placements API** — библиотека для создания встроек (placements) в интерфейс Bitrix24. Позволяет добавлять кастомные вкладки, кнопки и виджеты в различные части интерфейса.

**Документация:**
- **Официальная:** https://apidocs.bitrix24.ru/sdk/placement.html
- **REST API:** https://context7.com/bitrix24/rest/placement/

---

## Типы placements

### CRM Placements

#### CRM_LEAD_DETAIL_TAB
**Назначение:** Вкладка в карточке лида

**Пример:**
```javascript
BX.rest.callMethod('placement.bind', {
    PLACEMENT: 'CRM_LEAD_DETAIL_TAB',
    HANDLER: 'https://your-app.com/placement/lead-detail',
    TITLE: 'Кастомная вкладка',
    ICON_ID: 'custom-icon'
}, function(result) {
    if (!result.error()) {
        console.log('Placement зарегистрирован');
    }
});
```

#### CRM_DEAL_DETAIL_TAB
**Назначение:** Вкладка в карточке сделки

#### CRM_CONTACT_DETAIL_TAB
**Назначение:** Вкладка в карточке контакта

#### CRM_COMPANY_DETAIL_TAB
**Назначение:** Вкладка в карточке компании

---

### Меню и списки

#### CRM_LEAD_LIST_MENU
**Назначение:** Пункт меню в списке лидов

#### CRM_DEAL_LIST_MENU
**Назначение:** Пункт меню в списке сделок

#### TOP_MENU
**Назначение:** Пункт в верхнем меню

---

### Профиль пользователя

#### USER_PROFILE
**Назначение:** Вкладка в профиле пользователя

---

## Регистрация placement

### BX.rest.callMethod('placement.bind')

**Синтаксис:**
```javascript
BX.rest.callMethod('placement.bind', {
    PLACEMENT: 'PLACEMENT_TYPE',
    HANDLER: 'URL',
    TITLE: 'Название',
    ICON_ID: 'icon-id'
}, callback);
```

**Параметры:**
- `PLACEMENT` (string) — тип placement
- `HANDLER` (string) — URL обработчика
- `TITLE` (string) — название placement
- `ICON_ID` (string) — ID иконки (опционально)

**Пример:**
```javascript
BX.rest.callMethod('placement.bind', {
    PLACEMENT: 'CRM_LEAD_DETAIL_TAB',
    HANDLER: 'https://your-app.com/placement/lead-detail.php',
    TITLE: 'Дополнительная информация',
    ICON_ID: 'info-icon'
}, function(result) {
    if (result.error()) {
        console.error('Ошибка регистрации:', result.error());
    } else {
        console.log('Placement успешно зарегистрирован');
    }
});
```

---

## Получение параметров placement

### BX24.getPlacementOptions()

**Назначение:** Получение параметров placement

**Пример:**
```javascript
BX24.init(function() {
    const options = BX24.getPlacementOptions();
    
    console.log('ID лида:', options.ID);
    console.log('Тип placement:', options.PLACEMENT);
    console.log('Все параметры:', options);
});
```

**Доступные параметры:**
- `ID` — ID сущности (лида, сделки и т.д.)
- `PLACEMENT` — тип placement
- `MODE` — режим отображения
- Другие параметры в зависимости от типа placement

---

## Создание обработчика placement

### HTML-страница placement

```html
<!DOCTYPE html>
<html>
<head>
    <title>Кастомная вкладка лида</title>
    <script src="https://api.bitrix24.com/api/v1/"></script>
    <style>
        .placement-container {
            padding: 20px;
        }
        .lead-info {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div id="placement-container" class="placement-container">
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
            BX.rest.callMethod('crm.lead.get', {
                id: leadId
            }, function(result) {
                if (result.error()) {
                    BX('lead-data').innerHTML = '<p>Ошибка загрузки данных</p>';
                    return;
                }
                
                const lead = result.data();
                renderLeadData(lead);
            });
        }
        
        function renderLeadData(lead) {
            const container = BX('lead-data');
            container.innerHTML = `
                <div class="lead-info">
                    <p><strong>ID:</strong> ${lead.ID}</p>
                    <p><strong>Имя:</strong> ${lead.NAME || 'Не указано'}</p>
                    <p><strong>Email:</strong> ${lead.EMAIL?.[0]?.VALUE || 'Не указан'}</p>
                    <p><strong>Телефон:</strong> ${lead.PHONE?.[0]?.VALUE || 'Не указан'}</p>
                    <p><strong>Статус:</strong> ${lead.STATUS_ID || 'Не указан'}</p>
                </div>
            `;
        }
    </script>
</body>
</html>
```

---

## Интерактивные placements

### Форма в placement

```javascript
BX24.init(function() {
    const options = BX24.getPlacementOptions();
    const leadId = options.ID;
    
    // Создание формы
    const form = new BX.UI.Form({
        fields: [
            {
                name: 'comment',
                type: 'textarea',
                title: 'Комментарий',
                rows: 5
            }
        ],
        onSubmit: function(data) {
            // Сохранение комментария
            BX.rest.callMethod('crm.lead.update', {
                id: leadId,
                fields: {
                    COMMENTS: data.comment
                }
            }, function(result) {
                if (result.error()) {
                    BX.UI.Notification.Center.notify({
                        content: 'Ошибка сохранения',
                        type: 'error'
                    });
                } else {
                    BX.UI.Notification.Center.notify({
                        content: 'Комментарий сохранён',
                        type: 'success'
                    });
                }
            });
        }
    });
    
    form.renderTo(BX('form-container'));
});
```

---

## Отвязка placement

### BX.rest.callMethod('placement.unbind')

**Синтаксис:**
```javascript
BX.rest.callMethod('placement.unbind', {
    PLACEMENT: 'PLACEMENT_TYPE',
    HANDLER: 'URL'
}, callback);
```

**Пример:**
```javascript
BX.rest.callMethod('placement.unbind', {
    PLACEMENT: 'CRM_LEAD_DETAIL_TAB',
    HANDLER: 'https://your-app.com/placement/lead-detail.php'
}, function(result) {
    if (!result.error()) {
        console.log('Placement отвязан');
    }
});
```

---

## Получение списка placements

### BX.rest.callMethod('placement.list')

**Синтаксис:**
```javascript
BX.rest.callMethod('placement.list', {
    PLACEMENT: 'PLACEMENT_TYPE'
}, callback);
```

**Пример:**
```javascript
BX.rest.callMethod('placement.list', {
    PLACEMENT: 'CRM_LEAD_DETAIL_TAB'
}, function(result) {
    if (!result.error()) {
        const placements = result.data();
        console.log('Зарегистрированные placements:', placements);
    }
});
```

---

## Лучшие практики

### 1. Проверка параметров

```javascript
BX24.init(function() {
    const options = BX24.getPlacementOptions();
    
    if (!options.ID) {
        BX('placement-container').innerHTML = '<p>ID не передан</p>';
        return;
    }
    
    // Продолжение работы
});
```

### 2. Обработка ошибок

```javascript
BX.rest.callMethod('crm.lead.get', {
    id: leadId
}, function(result) {
    if (result.error()) {
        const error = result.error();
        BX.UI.Notification.Center.notify({
            content: 'Ошибка: ' + error.error_description,
            type: 'error'
        });
        return;
    }
    
    // Обработка данных
});
```

### 3. Оптимизация загрузки

```javascript
// Кеширование данных
let leadCache = null;

function loadLeadData(leadId) {
    if (leadCache && leadCache.id === leadId) {
        renderLeadData(leadCache.data);
        return;
    }
    
    BX.rest.callMethod('crm.lead.get', {
        id: leadId
    }, function(result) {
        if (!result.error()) {
            leadCache = {
                id: leadId,
                data: result.data()
            };
            renderLeadData(leadCache.data);
        }
    });
}
```

---

## Примеры использования

### Пример 1: Placement с таблицей связанных сделок

```javascript
BX24.init(function() {
    const options = BX24.getPlacementOptions();
    const leadId = options.ID;
    
    // Получение связанных сделок
    BX.rest.callMethod('crm.deal.list', {
        filter: { LEAD_ID: leadId },
        select: ['ID', 'TITLE', 'STAGE_ID', 'OPPORTUNITY', 'CURRENCY_ID']
    }, function(result) {
        if (result.error()) {
            return;
        }
        
        const deals = result.data();
        
        // Создание таблицы
        const table = new BX.UI.Table({
            columns: [
                { id: 'id', title: 'ID' },
                { id: 'title', title: 'Название' },
                { id: 'stage', title: 'Стадия' },
                { id: 'opportunity', title: 'Сумма' }
            ],
            data: deals.map(function(deal) {
                return {
                    id: deal.ID,
                    title: deal.TITLE,
                    stage: deal.STAGE_ID,
                    opportunity: deal.OPPORTUNITY + ' ' + deal.CURRENCY_ID
                };
            })
        });
        
        table.renderTo(BX('deals-table-container'));
    });
});
```

---

## Ссылки

- **Официальная документация:** https://apidocs.bitrix24.ru/sdk/placement.html
- **REST API:** [../../Placement/](../../Placement/)
- **JavaScript SDK:** [../JavaScript-SDK/](../JavaScript-SDK/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана детальная документация по Placements API





