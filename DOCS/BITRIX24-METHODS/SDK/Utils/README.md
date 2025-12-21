# Utils — утилиты SDK Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по утилитам SDK Bitrix24

---

## Обзор

**Utils** — набор утилит для работы с данными, строками, датами и другими операциями в Bitrix24 SDK.

---

## Основные утилиты

### BX.util — утилиты для работы с данными

#### BX.util.trim()
**Удаление пробелов:**
```javascript
const text = BX.util.trim('  текст  ');
// 'текст'
```

#### BX.util.htmlspecialchars()
**Экранирование HTML:**
```javascript
const safe = BX.util.htmlspecialchars('<script>alert("XSS")</script>');
```

#### BX.util.urlencode()
**Кодирование URL:**
```javascript
const encoded = BX.util.urlencode('параметр=значение');
```

#### BX.util.date()
**Работа с датами:**
```javascript
const date = new Date();
const formatted = BX.util.date(date, 'd.m.Y H:i:s');
```

---

### BX.clone()
**Клонирование объекта:**
```javascript
const original = { name: 'Иван' };
const cloned = BX.clone(original);
```

### BX.merge()
**Объединение объектов:**
```javascript
const merged = BX.merge({ a: 1 }, { b: 2 });
// { a: 1, b: 2 }
```

### BX.type()
**Определение типа:**
```javascript
BX.type('text'); // 'string'
BX.type(123);    // 'number'
BX.type([]);     // 'array'
```

---

## Работа с DOM

### BX()
**Получение элемента по ID:**
```javascript
const element = BX('element-id');
```

### BX.findChild()
**Поиск дочернего элемента:**
```javascript
const child = BX.findChild(parent, { class: 'child-class' });
```

### BX.addClass() / BX.removeClass()
**Работа с классами:**
```javascript
BX.addClass(element, 'new-class');
BX.removeClass(element, 'old-class');
BX.toggleClass(element, 'active');
```

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по утилитам SDK



