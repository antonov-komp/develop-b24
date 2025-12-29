# Руководство по параметрам конфигурации APP-B24

**Дата создания:** 2025-12-29 (UTC+3, Брест)  
**Файл:** `APP-B24/config.json`

---

## Параметры конфигурации

### 1. `enabled` — Главный выключатель приложения

**Назначение:** Полное включение/выключение приложения

**Значения:**
- `false` → Приложение полностью отключено (показывается страница ошибки)
- `true` → Приложение работает, далее проверяются остальные параметры

**Приоритет:** Высший (проверяется первым)

**Пример:**
```json
{
  "index_page": {
    "enabled": false  // ← Приложение отключено, остальные параметры не проверяются
  }
}
```

---

### 2. `external_access` — Режим доступа

**Назначение:** Разрешает/запрещает прямой доступ без авторизации Bitrix24

**Работает только если:** `enabled: true`

**Значения:**
- `false` → Только через Bitrix24 (требуется авторизация)
- `true` → Разрешён прямой доступ без авторизации

**Пример:**
```json
{
  "index_page": {
    "enabled": true,
    "external_access": false  // ← Только через Bitrix24
  }
}
```

---

### 3. `block_bitrix24_iframe` — Блокировка доступа из Bitrix24 iframe

**Назначение:** Блокирует доступ из Bitrix24 iframe при включённом внешнем доступе

**Работает только если:** `enabled: true` И `external_access: true`

**Значения:**
- `false` → Доступ из Bitrix24 iframe разрешён
- `true` → Доступ из Bitrix24 iframe заблокирован

**Пример:**
```json
{
  "index_page": {
    "enabled": true,
    "external_access": true,
    "block_bitrix24_iframe": true  // ← Блокирует Bitrix24 iframe
  }
}
```

---

## Таблица всех режимов работы

| Режим | `enabled` | `external_access` | `block_bitrix24_iframe` | Прямой доступ | Bitrix24 iframe | Описание |
|-------|-----------|-------------------|------------------------|---------------|-----------------|----------|
| **Отключено** | `false` | любое | любое | ❌ Блокирован | ❌ Блокирован | Приложение полностью недоступно |
| **Только Bitrix24** | `true` | `false` | любое | ❌ Блокирован | ✅ Работает | Идеальный режим для работы внутри Bitrix24 |
| **Везде** | `true` | `true` | `false` | ✅ Работает | ✅ Работает | Работает везде (внутри и снаружи) |
| **Только внешний** | `true` | `true` | `true` | ✅ Работает | ❌ Блокирован | Только прямой доступ, без Bitrix24 |

---

## Примеры конфигураций

### Пример 1: Полное отключение приложения
```json
{
  "index_page": {
    "enabled": false,
    "external_access": false,
    "block_bitrix24_iframe": false,
    "message": "Приложение временно недоступно. Ведутся технические работы.",
    "last_updated": "2025-12-29 10:00:00",
    "updated_by": "admin"
  }
}
```
**Результат:** Приложение полностью недоступно, показывается страница ошибки с сообщением.

---

### Пример 2: Только через Bitrix24 (идеальный режим)
```json
{
  "index_page": {
    "enabled": true,
    "external_access": false,
    "block_bitrix24_iframe": false,
    "message": null,
    "last_updated": "2025-12-29 10:00:00",
    "updated_by": "admin"
  }
}
```
**Результат:** 
- ✅ Работает внутри Bitrix24 iframe
- ❌ Прямой доступ заблокирован

---

### Пример 3: Везде (внутри и снаружи)
```json
{
  "index_page": {
    "enabled": true,
    "external_access": true,
    "block_bitrix24_iframe": false,
    "message": null,
    "last_updated": "2025-12-29 10:00:00",
    "updated_by": "admin"
  }
}
```
**Результат:**
- ✅ Работает внутри Bitrix24 iframe
- ✅ Работает при прямом доступе

---

### Пример 4: Только внешний доступ (без Bitrix24)
```json
{
  "index_page": {
    "enabled": true,
    "external_access": true,
    "block_bitrix24_iframe": true,
    "message": null,
    "last_updated": "2025-12-29 10:00:00",
    "updated_by": "admin"
  }
}
```
**Результат:**
- ✅ Работает при прямом доступе
- ❌ Доступ из Bitrix24 iframe заблокирован

---

## Рекомендации по использованию

### Для включения/выключения приложения:
**Используйте только `enabled`:**
- `enabled: false` → Полное отключение
- `enabled: true` → Включение (далее настраивайте режим доступа)

### Для выбора режима доступа:
1. **Только Bitrix24:** `enabled: true, external_access: false`
2. **Везде:** `enabled: true, external_access: true, block_bitrix24_iframe: false`
3. **Только внешний:** `enabled: true, external_access: true, block_bitrix24_iframe: true`

---

## Логика проверки в коде

```php
// 1. Проверка enabled (главный выключатель)
if (!$appEnabled) {
    // Показываем страницу ошибки, exit
}

// 2. Проверка external_access
if (!$externalAccessEnabled) {
    // Требуется авторизация Bitrix24
    if (!$hasUserToken) {
        // Блокируем прямой доступ
    }
} else {
    // Внешний доступ включен
    if ($hasUserToken && $blockBitrix24Iframe) {
        // Блокируем доступ из Bitrix24 iframe
    }
}
```

---

## История изменений

- 2025-12-29 (UTC+3, Брест): Добавлен параметр `block_bitrix24_iframe` для блокировки доступа из Bitrix24 iframe

