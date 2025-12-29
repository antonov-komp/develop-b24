# Исправление работы приложения в режиме внешнего доступа

**Дата создания:** 2025-12-29 18:15 (UTC+3, Брест)  
**Статус:** ✅ Исправлено  
**Проблема:** Приложение пыталось загрузить данные пользователя через API даже при `external_access: true`

---

## Проблема

При запуске приложения напрямую (не через iframe Bitrix24) с `external_access: true` в `config.json`:

1. ✅ Приложение загружалось корректно
2. ❌ Vue.js пытался загрузить данные пользователя через API `/user/current`
3. ❌ API возвращал 401 Unauthorized (нет токена)
4. ❌ Router пытался сделать редирект, обнаруживал бесконечный цикл
5. ⚠️ В консоли появлялись ошибки и предупреждения

**Логи:**
```
Приложение запущено вне Bitrix24 iframe
URL params: {AUTH_ID: 'missing', DOMAIN: 'missing'}
Missing AUTH_ID/APP_SID or DOMAIN in production mode
Router: Infinite redirect detected! Missing AUTH_ID or DOMAIN. Already on index page.
Router: Allowing access without auth to prevent infinite loop
GET /APP-B24/api/index.php?route=user%2Fcurrent 401 (Unauthorized)
Ошибка авторизации
```

---

## Решение

### 1. Исправление `userStore.fetchCurrentUser()`

**Файл:** `APP-B24/frontend/src/stores/userStore.js`

**Изменения:**

1. **Проверка в начале функции:**
   - Если `externalAccessEnabled: true` и нет токена в URL, проверяем `sessionStorage`
   - Если токена нет, не делаем запрос к API

2. **Проверка перед запросом:**
   - Если после всех попыток получить токен (URL, sessionStorage, BX24) токен отсутствует
   - И `externalAccessEnabled: true`, то не делаем запрос

**Код:**

```javascript
async fetchCurrentUser() {
  this.loading = true;
  this.error = null;
  
  // ... получение параметров ...
  
  // Проверка: если внешний доступ включен и нет токена, не делаем запрос
  if (this.externalAccessEnabled && !authId && !domain) {
    const storedAuth = sessionStorage.getItem('bitrix24_auth');
    if (!storedAuth) {
      console.log('UserStore: External access enabled, but no auth token. Skipping API request.');
      this.loading = false;
      this.isAuthenticated = false;
      this.currentUser = null;
      return; // Не делаем запрос
    }
  }
  
  // ... попытки получить токен ...
  
  // Проверка перед запросом
  if (this.externalAccessEnabled && (!authId || !domain)) {
    console.log('UserStore: External access enabled, but no auth token after all attempts. Skipping API request.');
    this.loading = false;
    this.isAuthenticated = false;
    this.currentUser = null;
    return; // Не делаем запрос
  }
  
  // Если нет токена и внешний доступ не включен, это ошибка
  if (!authId || !domain) {
    throw new Error('AUTH_ID and DOMAIN are required for API requests');
  }
  
  // ... выполнение запроса ...
}
```

---

### 2. Исправление Router

**Файл:** `APP-B24/frontend/src/router/index.js`

**Изменения:**

- Добавлена проверка `externalAccessEnabled` в `router.beforeEach`
- Если внешний доступ включен, пропускаем проверку авторизации

**Код:**

```javascript
router.beforeEach((to, from, next) => {
  // ... другие проверки ...
  
  // Проверка авторизации
  if (to.meta.requiresAuth) {
    // Проверяем, включен ли внешний доступ
    let externalAccessEnabled = false;
    try {
      const appDataStr = sessionStorage.getItem('app_data');
      if (appDataStr) {
        const appData = JSON.parse(appDataStr);
        externalAccessEnabled = appData.externalAccessEnabled || false;
      }
    } catch (e) {
      // Игнорируем ошибки парсинга
    }
    
    // Если внешний доступ включен, пропускаем проверку авторизации
    if (externalAccessEnabled) {
      console.log('Router: External access enabled, skipping auth check');
      redirectAttempts = 0;
      next();
      return;
    }
    
    // ... остальная логика проверки авторизации ...
  }
});
```

---

## Результат

После исправления:

1. ✅ Приложение загружается без ошибок
2. ✅ Не делаются запросы к API без токена
3. ✅ Router не пытается делать редирект
4. ✅ Показывается корректное сообщение о внешнем доступе
5. ✅ Нет ошибок в консоли

**Ожидаемое поведение:**

- При `external_access: true` и отсутствии токена:
  - Показывается сообщение: "ℹ️ Внешний доступ включен"
  - Не делаются запросы к API
  - Router разрешает доступ без авторизации

---

## Тестирование

### Тест 1: Прямой доступ с `external_access: true`

1. Установить `external_access: true` в `config.json`
2. Открыть `https://develop.antonov-mark.ru/APP-B24/`
3. **Ожидаемый результат:**
   - ✅ Приложение загружается
   - ✅ Показывается сообщение о внешнем доступе
   - ✅ Нет ошибок в консоли
   - ✅ Нет запросов к API `/user/current`

### Тест 2: Доступ через Bitrix24 iframe

1. Открыть приложение через Bitrix24
2. **Ожидаемый результат:**
   - ✅ Приложение загружается с токеном
   - ✅ Данные пользователя загружаются через API
   - ✅ Показывается информация о пользователе

---

## Конфигурация

**Файл:** `APP-B24/config.json`

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
- `external_access: true` — разрешён прямой доступ без авторизации Bitrix24
- `external_access: false` — требуется авторизация Bitrix24 (только через iframe)

---

## Связанные документы

- `/DOCS/SYSTEM-ADMIN/app-modes-explanation-2025-12-29.md` — описание режимов работы
- `/DOCS/ANALYSIS/2025-12-29-rest-app-initial-launch-analysis.md` — анализ первого запуска
- `/DOCS/SYSTEM-ADMIN/fix-infinite-redirect-bx24-sdk-2025-12-29.md` — исправление бесконечного редиректа

---

## Обновление: Использование глобального токена

**Дата:** 2025-12-29 18:25 (UTC+3, Брест)

### Проблема

При `external_access: true` приложение не использовало глобальный токен из `settings.json` для загрузки данных пользователя.

### Решение

1. **Изменён `buildAuthInfo()` в `index.php`:**
   - При `externalAccessEnabled: true` и отсутствии токена в запросе, используется токен из `settings.json`
   - Загружаются данные пользователя, чей токен используется

2. **Изменён `VueAppService::load()`:**
   - Использует токен из `appData['authInfo']` (если есть) перед токеном из запроса
   - Передаёт токен в JavaScript через `buildAuthScript()`

3. **Изменён `userStore.fetchCurrentUser()`:**
   - Проверяет токен в `app_data` (переданный из PHP) перед проверкой URL
   - Использует токен для загрузки данных пользователя

### Результат

Теперь при `external_access: true`:
- ✅ Используется глобальный токен из `settings.json`
- ✅ Загружаются данные пользователя, чей токен используется
- ✅ Показывается интерфейс с данными пользователя
- ✅ Работают все функции приложения

---

## Обновление: Отключение загрузки SDK при внешнем доступе

**Дата:** 2025-12-29 18:30 (UTC+3, Брест)

### Проблема

При `external_access: true` Bitrix24 SDK пытался загрузиться и инициализироваться, но SDK работает только внутри iframe Bitrix24. Это вызывало ошибки в консоли:
- `Uncaught Error: Unable to initialize Bitrix24 JS library!`
- `Bitrix24 SDK not loaded after 50 attempts. App may work in limited mode.`

### Решение

Изменён `VueAppService::buildAuthScript()`:
- При `external_access: true` и наличии токена из PHP SDK не загружается
- SDK загружается только если:
  - `external_access: false` (работа внутри Bitrix24)
  - Или токен не передан из PHP (нужно получить через SDK)

### Результат

Теперь при `external_access: true`:
- ✅ SDK не загружается (нет ошибок в консоли)
- ✅ Токен используется из `settings.json`
- ✅ Приложение работает без ошибок

---

## История правок

- 2025-12-29 18:15 (UTC+3, Брест): Исправлена работа приложения в режиме внешнего доступа
- 2025-12-29 18:25 (UTC+3, Брест): Добавлено использование глобального токена из settings.json
- 2025-12-29 18:30 (UTC+3, Брест): Отключена загрузка SDK при внешнем доступе


