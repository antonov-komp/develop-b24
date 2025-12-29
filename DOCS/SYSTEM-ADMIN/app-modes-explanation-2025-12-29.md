# Режимы работы приложения APP-B24

**Дата:** 2025-12-29 09:25 (UTC+3, Брест)  
**Описание:** Два режима работы приложения Bitrix24

---

## 📋 Режимы работы

### 1. Режим внутри Bitrix24 (iframe)

**URL:** `https://develop.bitrix24.by/marketplace/app/1/`

**Как работает:**
- Приложение открывается через iframe в Bitrix24
- Bitrix24 передаёт параметры авторизации:
  - `AUTH_ID` - токен пользователя
  - `DOMAIN` - домен портала
  - `APP_SID` - идентификатор сессии приложения
- Авторизация происходит автоматически
- `external_access` не требуется (авторизация через Bitrix24)

**Логика:**
```php
// В index.php
if (!$externalAccessEnabled) {
    $authResult = $authService->checkBitrix24Auth();
    // Проверяет наличие AUTH_ID и DOMAIN
    // Проверяет валидность токена через API Bitrix24
}
```

### 2. Режим прямого доступа (standalone)

**URL:** `https://backend.antonov-mark.ru/APP-B24/index.php`

**Как работает:**
- Приложение открывается напрямую в браузере
- Нет параметров авторизации от Bitrix24
- Требуется `external_access: true` в `config.json`
- Авторизация пропускается

**Логика:**
```php
// В index.php
$externalAccessEnabled = isset($config['external_access']) && $config['external_access'] === true;

if (!$externalAccessEnabled) {
    // Требуется авторизация Bitrix24
    $authResult = $authService->checkBitrix24Auth();
    if (!$authResult) {
        // Редирект на failure.php
    }
} else {
    // Внешний доступ включён - пропускаем авторизацию
    $logger->log('Index page external access enabled', [
        'skipping_auth_check' => true
    ], 'info');
}
```

---

## ⚙️ Конфигурация

### Файл: `APP-B24/config.json`

```json
{
  "index_page": {
    "enabled": true,
    "external_access": true,  // ← Включает прямой доступ
    "message": "Интерфейс приложения временно недоступен. Пожалуйста, попробуйте позже.",
    "last_updated": "2025-12-29 09:15:00",
    "updated_by": "system"
  }
}
```

**Параметры:**
- `enabled: true` - приложение включено
- `external_access: true` - разрешён прямой доступ без авторизации Bitrix24
- `external_access: false` - требуется авторизация Bitrix24 (только через iframe)

---

## 🔍 Проверка режима работы

### Внутри Bitrix24 (iframe)
- Есть параметры: `AUTH_ID`, `DOMAIN`
- `is_authenticated: true`
- `external_access: false` (не используется)

### Прямой доступ (standalone)
- Нет параметров: `AUTH_ID`, `DOMAIN`
- `is_authenticated: false`
- `external_access: true`
- `externalAccessEnabled: true`

---

## 📊 Логи

### Режим внутри Bitrix24:
```
Index page config check, {"external_access_enabled":false,"config_enabled":true}
Index page auth check passed, {"external_access_enabled":false}
Index page data prepared for Vue.js, {"is_authenticated":true,"is_admin":true,"has_user":true}
```

### Режим прямого доступа:
```
Index page config check, {"external_access_enabled":true,"config_enabled":true}
Index page external access enabled, {"skipping_auth_check":true}
External access enabled without Bitrix24 auth
Index page data prepared for Vue.js, {"is_authenticated":false,"is_admin":false,"has_user":false,"external_access":true}
```

---

## 🐛 Возможные проблемы

### Проблема: Страница не открывается при прямом доступе

**Причины:**
1. `external_access: false` в config.json
2. Редирект на failure.php
3. Vue.js не загружается (проблема с путями к JS/CSS)
4. Ошибка JavaScript в консоли браузера

**Решение:**
1. Проверить `config.json`: `cat APP-B24/config.json | jq '.index_page.external_access'`
2. Проверить логи: `tail -f APP-B24/logs/info-2025-12-29.log`
3. Открыть консоль браузера (F12) и проверить ошибки
4. Проверить загрузку файлов в Network (F12 → Network)

---

## ✅ Текущий статус

**Конфигурация:**
- ✅ `external_access: true` в config.json
- ✅ `getIndexPageConfig()` возвращает `external_access: true`
- ✅ Логи показывают: `external_access_enabled: true`

**Сервер:**
- ✅ HTTP 200 при обращении к index.php
- ✅ `externalAccessEnabled: true` в ответе
- ✅ JS/CSS файлы доступны

**Проблема:**
- ❓ Что именно видит пользователь в браузере?
- ❓ Есть ли ошибки в консоли браузера?
- ❓ Загружаются ли JS/CSS файлы?

---

*Документ создан системным администратором*



