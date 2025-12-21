# UI Kit — библиотека компонентов интерфейса Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Детальная документация по UI Kit — библиотеке готовых компонентов интерфейса

---

## Обзор

**UI Kit** — библиотека готовых UI-компонентов для создания интерфейсов в Bitrix24. Предоставляет стандартизированные компоненты, соответствующие дизайн-системе Bitrix24.

**Документация:**
- **Официальная:** https://apidocs.bitrix24.ru/sdk/ui.html
- **GitHub:** https://github.com/bitrix24/b24ui

---

## Подключение

UI Kit загружается автоматически в Bitrix24. Для внешних приложений:

```html
<link rel="stylesheet" href="https://api.bitrix24.com/api/v1/bitrix24-ui.css">
<script src="https://api.bitrix24.com/api/v1/"></script>
```

---

## Компоненты

### BX.UI.Button — кнопки

**Назначение:** Создание кнопок различных типов и размеров

**Синтаксис:**
```javascript
const button = new BX.UI.Button({
    text: 'Текст кнопки',
    color: BX.UI.Button.Color.PRIMARY,
    size: BX.UI.Button.Size.MEDIUM,
    onclick: function() {}
});
```

**Параметры:**
- `text` (string) — текст кнопки
- `color` (string) — цвет (PRIMARY, SUCCESS, DANGER, LINK)
- `size` (string) — размер (SMALL, MEDIUM, LARGE)
- `onclick` (function) — обработчик клика
- `disabled` (boolean) — отключена ли кнопка
- `icon` (string) — иконка

**Примеры:**

```javascript
// Основная кнопка
const primaryButton = new BX.UI.Button({
    text: 'Сохранить',
    color: BX.UI.Button.Color.PRIMARY,
    onclick: function() {
        console.log('Кнопка нажата');
    }
});
primaryButton.renderTo(BX('button-container'));

// Кнопка успеха
const successButton = new BX.UI.Button({
    text: 'Готово',
    color: BX.UI.Button.Color.SUCCESS,
    size: BX.UI.Button.Size.LARGE
});
successButton.renderTo(BX('success-container'));

// Кнопка-ссылка
const linkButton = new BX.UI.Button({
    text: 'Подробнее',
    color: BX.UI.Button.Color.LINK,
    onclick: function() {
        window.open('https://example.com');
    }
});
linkButton.renderTo(BX('link-container'));

// Отключенная кнопка
const disabledButton = new BX.UI.Button({
    text: 'Недоступно',
    disabled: true
});
disabledButton.renderTo(BX('disabled-container'));
```

**Методы:**
- `renderTo(element)` — отрисовка в элемент
- `setText(text)` — установка текста
- `setDisabled(disabled)` — установка состояния disabled
- `setOnClick(handler)` — установка обработчика клика

---

### BX.UI.Input — поля ввода

**Назначение:** Создание полей ввода различных типов

**Синтаксис:**
```javascript
const input = new BX.UI.Input({
    name: 'field-name',
    type: 'text',
    title: 'Название поля',
    placeholder: 'Подсказка',
    required: true,
    value: 'Значение по умолчанию'
});
```

**Типы полей:**
- `text` — текстовое поле
- `email` — email
- `tel` — телефон
- `password` — пароль
- `number` — число
- `date` — дата
- `textarea` — многострочное поле

**Примеры:**

```javascript
// Текстовое поле
const textInput = new BX.UI.Input({
    name: 'name',
    type: 'text',
    title: 'Имя',
    placeholder: 'Введите имя',
    required: true
});
textInput.renderTo(BX('form-container'));

// Email поле
const emailInput = new BX.UI.Input({
    name: 'email',
    type: 'email',
    title: 'Email',
    placeholder: 'example@mail.com',
    required: true
});
emailInput.renderTo(BX('form-container'));

// Многострочное поле
const textarea = new BX.UI.Input({
    name: 'description',
    type: 'textarea',
    title: 'Описание',
    rows: 5
});
textarea.renderTo(BX('form-container'));

// Получение значения
const value = textInput.getValue();

// Установка значения
textInput.setValue('Новое значение');

// Валидация
if (textInput.validate()) {
    console.log('Поле валидно');
}
```

---

### BX.UI.Select — выпадающие списки

**Назначение:** Создание выпадающих списков

**Синтаксис:**
```javascript
const select = new BX.UI.Select({
    name: 'status',
    title: 'Статус',
    items: [
        { value: '1', text: 'Новый' },
        { value: '2', text: 'В работе' },
        { value: '3', text: 'Завершён' }
    ],
    value: '1'
});
```

**Пример:**

```javascript
const statusSelect = new BX.UI.Select({
    name: 'status',
    title: 'Статус лида',
    items: [
        { value: 'NEW', text: 'Новый' },
        { value: 'IN_PROCESS', text: 'В работе' },
        { value: 'CONVERTED', text: 'Конвертирован' }
    ],
    value: 'NEW',
    onChange: function(value) {
        console.log('Выбран статус:', value);
    }
});
statusSelect.renderTo(BX('select-container'));

// Получение значения
const selectedValue = statusSelect.getValue();

// Установка значения
statusSelect.setValue('IN_PROCESS');
```

---

### BX.UI.Table — таблицы

**Назначение:** Создание таблиц с данными

**Синтаксис:**
```javascript
const table = new BX.UI.Table({
    columns: [
        { id: 'id', title: 'ID' },
        { id: 'name', title: 'Имя' }
    ],
    data: [
        { id: 1, name: 'Иван' },
        { id: 2, name: 'Пётр' }
    ]
});
```

**Пример:**

```javascript
const leadsTable = new BX.UI.Table({
    columns: [
        { id: 'id', title: 'ID', sortable: true },
        { id: 'name', title: 'Имя', sortable: true },
        { id: 'email', title: 'Email' },
        { id: 'phone', title: 'Телефон' },
        { 
            id: 'actions', 
            title: 'Действия',
            render: function(cell, row) {
                const button = new BX.UI.Button({
                    text: 'Открыть',
                    size: BX.UI.Button.Size.SMALL,
                    onclick: function() {
                        openLeadDetails(row.id);
                    }
                });
                button.renderTo(cell);
            }
        }
    ],
    data: [
        { id: 1, name: 'Иван Иванов', email: 'ivan@example.com', phone: '+375291234567' },
        { id: 2, name: 'Пётр Петров', email: 'petr@example.com', phone: '+375297654321' }
    ],
    pagination: {
        pageSize: 10,
        currentPage: 1
    },
    onSort: function(columnId, direction) {
        console.log('Сортировка по:', columnId, direction);
    }
});

leadsTable.renderTo(BX('table-container'));

// Обновление данных
leadsTable.setData([
    { id: 3, name: 'Новый лид', email: 'new@example.com', phone: '+375290000000' }
]);
```

---

### BX.PopupWindow — модальные окна

**Назначение:** Создание модальных окон (попапов)

**Синтаксис:**
```javascript
const popup = new BX.PopupWindow('popup-id', null, {
    content: 'Содержимое попапа',
    width: 600,
    height: 400,
    buttons: []
});
```

**Пример:**

```javascript
// Простое модальное окно
const simplePopup = new BX.PopupWindow('simple-popup', null, {
    content: '<div>Простое содержимое</div>',
    width: 500,
    height: 300
});
simplePopup.show();

// Модальное окно с кнопками
const popupWithButtons = new BX.PopupWindow('popup-buttons', null, {
    content: '<div>Вы уверены?</div>',
    width: 400,
    height: 200,
    buttons: [
        new BX.PopupWindowButton({
            text: 'Да',
            className: 'ui-btn ui-btn-primary',
            events: {
                click: function() {
                    console.log('Нажато "Да"');
                    popupWithButtons.close();
                }
            }
        }),
        new BX.PopupWindowButton({
            text: 'Нет',
            className: 'ui-btn ui-btn-link',
            events: {
                click: function() {
                    popupWithButtons.close();
                }
            }
        })
    ]
});
popupWithButtons.show();

// Модальное окно с динамическим содержимым
function showLeadDetails(leadId) {
    BX.rest.callMethod('crm.lead.get', { id: leadId }, function(result) {
        if (!result.error()) {
            const lead = result.data();
            const content = `
                <div>
                    <h3>${BX.util.htmlspecialchars(lead.NAME || 'Без имени')}</h3>
                    <p>Email: ${lead.EMAIL?.[0]?.VALUE || 'Не указан'}</p>
                    <p>Телефон: ${lead.PHONE?.[0]?.VALUE || 'Не указан'}</p>
                </div>
            `;
            
            const popup = new BX.PopupWindow('lead-details', null, {
                content: content,
                width: 600,
                height: 400
            });
            popup.show();
        }
    });
}

// Методы попапа
popup.show();        // Показать
popup.close();       // Закрыть
popup.setContent();  // Установить содержимое
popup.setWidth();    // Установить ширину
popup.setHeight();   // Установить высоту
```

---

### BX.UI.Notification — уведомления

**Назначение:** Показ уведомлений пользователю

**Синтаксис:**
```javascript
BX.UI.Notification.Center.notify({
    content: 'Текст уведомления',
    autoHideDelay: 5000,
    type: 'info'
});
```

**Типы уведомлений:**
- `info` — информационное (по умолчанию)
- `success` — успех
- `error` — ошибка
- `warning` — предупреждение

**Примеры:**

```javascript
// Информационное уведомление
BX.UI.Notification.Center.notify({
    content: 'Операция выполнена',
    autoHideDelay: 5000
});

// Успешное уведомление
BX.UI.Notification.Center.notify({
    content: 'Лид успешно создан',
    autoHideDelay: 5000,
    type: 'success'
});

// Ошибка
BX.UI.Notification.Center.notify({
    content: 'Произошла ошибка',
    autoHideDelay: 5000,
    type: 'error'
});

// Предупреждение
BX.UI.Notification.Center.notify({
    content: 'Внимание: проверьте данные',
    autoHideDelay: 5000,
    type: 'warning'
});

// Уведомление с кнопкой
BX.UI.Notification.Center.notify({
    content: 'Новое сообщение',
    autoHideDelay: 0, // Не скрывать автоматически
    actions: [
        {
            text: 'Открыть',
            onclick: function() {
                window.open('/messages');
            }
        }
    ]
});
```

---

### BX.UI.Alert — алерты

**Назначение:** Показ алертов (всплывающих сообщений)

**Пример:**

```javascript
// Простой алерт
BX.UI.Alert.show('Простое сообщение');

// Алерт с заголовком
BX.UI.Alert.show('Сообщение', 'Заголовок');

// Алерт с кнопками
BX.UI.Alert.show('Вы уверены?', 'Подтверждение', [
    {
        text: 'Да',
        onclick: function() {
            console.log('Нажато "Да"');
        }
    },
    {
        text: 'Нет',
        onclick: function() {
            console.log('Нажато "Нет"');
        }
    }
]);
```

---

### BX.UI.Form — формы

**Назначение:** Создание форм с валидацией

**Синтаксис:**
```javascript
const form = new BX.UI.Form({
    fields: [
        {
            name: 'field-name',
            type: 'text',
            title: 'Название поля',
            required: true
        }
    ],
    onSubmit: function(data) {}
});
```

**Пример:**

```javascript
const leadForm = new BX.UI.Form({
    fields: [
        {
            name: 'name',
            type: 'text',
            title: 'Имя',
            placeholder: 'Введите имя',
            required: true,
            validator: function(value) {
                if (!value || value.length < 2) {
                    return 'Имя должно содержать минимум 2 символа';
                }
                return true;
            }
        },
        {
            name: 'email',
            type: 'email',
            title: 'Email',
            placeholder: 'example@mail.com',
            required: true,
            validator: function(value) {
                if (!value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    return 'Неверный формат email';
                }
                return true;
            }
        },
        {
            name: 'phone',
            type: 'tel',
            title: 'Телефон',
            placeholder: '+375291234567'
        },
        {
            name: 'status',
            type: 'select',
            title: 'Статус',
            items: [
                { value: 'NEW', text: 'Новый' },
                { value: 'IN_PROCESS', text: 'В работе' }
            ],
            value: 'NEW'
        }
    ],
    onSubmit: function(data) {
        console.log('Данные формы:', data);
        
        BX.rest.callMethod('crm.lead.add', {
            fields: {
                NAME: data.name,
                EMAIL: [{ VALUE: data.email, VALUE_TYPE: 'WORK' }],
                PHONE: [{ VALUE: data.phone, VALUE_TYPE: 'WORK' }],
                STATUS_ID: data.status
            }
        }, function(result) {
            if (result.error()) {
                BX.UI.Notification.Center.notify({
                    content: 'Ошибка: ' + result.error().error_description,
                    type: 'error'
                });
            } else {
                BX.UI.Notification.Center.notify({
                    content: 'Лид успешно создан',
                    type: 'success'
                });
                leadForm.reset();
            }
        });
    }
});

leadForm.renderTo(BX('form-container'));

// Методы формы
const formData = leadForm.getData();  // Получение данных
leadForm.reset();                      // Сброс формы
leadForm.validate();                   // Валидация
```

---

## Стилизация

### CSS-классы

UI Kit использует стандартные CSS-классы Bitrix24:

```css
/* Кнопки */
.ui-btn { }
.ui-btn-primary { }
.ui-btn-success { }
.ui-btn-danger { }
.ui-btn-link { }

/* Поля ввода */
.ui-input { }
.ui-input-error { }

/* Таблицы */
.ui-table { }
.ui-table-row { }
.ui-table-cell { }

/* Модальные окна */
.popup-window { }
.popup-window-content { }
```

### Кастомизация

```javascript
// Кастомные стили для кнопки
const button = new BX.UI.Button({
    text: 'Кастомная кнопка',
    className: 'custom-button-class'
});
```

---

## Лучшие практики

### 1. Использование готовых компонентов

```javascript
// ❌ Плохо: кастомная кнопка
const button = document.createElement('button');
button.textContent = 'Сохранить';
button.className = 'custom-button';

// ✅ Хорошо: компонент UI Kit
const button = new BX.UI.Button({
    text: 'Сохранить',
    color: BX.UI.Button.Color.PRIMARY
});
```

### 2. Валидация форм

```javascript
const form = new BX.UI.Form({
    fields: [
        {
            name: 'email',
            type: 'email',
            validator: function(value) {
                if (!value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    return 'Неверный формат email';
                }
                return true;
            }
        }
    ]
});
```

### 3. Обработка ошибок

```javascript
BX.rest.callMethod('crm.lead.add', {
    fields: { NAME: 'Иван' }
}, function(result) {
    if (result.error()) {
        BX.UI.Notification.Center.notify({
            content: 'Ошибка: ' + result.error().error_description,
            type: 'error'
        });
    }
});
```

---

## Ссылки

- **Официальная документация:** https://apidocs.bitrix24.ru/sdk/ui.html
- **GitHub:** https://github.com/bitrix24/b24ui
- **JavaScript SDK:** [../JavaScript-SDK/](../JavaScript-SDK/)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана детальная документация по UI Kit



