# Модуль "Проверка токена": Анализируемые данные

**Дата создания:** 2025-12-21 21:45 (UTC+3, Брест)  
**Версия:** 1.0  
**Тип документа:** Глобальная документация

---

## Обзор

Модуль **"Проверка токена"** (`TokenAnalysisPage.vue`) предоставляет детальный анализ токена авторизации Bitrix24 и связанных с ним данных. Модуль доступен только для администраторов Bitrix24.

**Расположение:**
- Компонент: `APP-B24/frontend/src/components/TokenAnalysisPage.vue`
- API endpoint: `APP-B24/api/routes/token-analysis.php`
- Маршрут: `/token-analysis`

---

## Анализируемые данные

### 1. Информация о пользователе

**Источник:** Метод Bitrix24 API `user.current`  
**Документация:** https://context7.com/bitrix24/rest/user.current

#### Отображаемые поля:

| Поле | Описание | Пример значения |
|------|----------|-----------------|
| **ID** | Уникальный идентификатор пользователя в Bitrix24 | `123` |
| **Имя** | Полное имя пользователя (NAME + LAST_NAME) | `Иван Иванов` |
| **Email** | Email адрес пользователя | `ivan@example.com` |
| **Статус** | Роль пользователя (Администратор/Пользователь) | `Администратор` |

#### Дополнительные данные (в JSON):

- `NAME` — имя пользователя
- `LAST_NAME` — фамилия пользователя
- `SECOND_NAME` — отчество пользователя
- `PERSONAL_PHOTO` — URL фотографии пользователя
- `TIME_ZONE` — часовой пояс пользователя
- `ADMIN` — флаг администратора (`Y`/`N`)
- `ACTIVE` — флаг активности пользователя
- `WORK_POSITION` — должность
- `UF_DEPARTMENT` — массив ID отделов пользователя

---

### 2. Права доступа

**Источник:** Проверка через `UserService` и `AccessControlService`

#### Отображаемые поля:

| Поле | Описание | Тип | Логика проверки |
|------|----------|-----|-----------------|
| **Имеет доступ** | Доступ к приложению | `boolean` | Проверка через `AccessControlService::checkUserAccess()` |
| **Является администратором** | Статус администратора | `boolean` | Проверка через `UserService::isAdmin()` |

#### Логика проверки доступа:

1. **Если пользователь — администратор:**
   - `hasAccess = true` (автоматически)
   - `isAdmin = true`

2. **Если пользователь — не администратор:**
   - `hasAccess = AccessControlService::checkUserAccess()`
   - Проверяется принадлежность к отделам с доступом
   - Проверяется наличие в списке пользователей с доступом
   - `isAdmin = false`

---

### 3. Отделы пользователя

**Источник:** Метод `UserService::getUserDepartments()`  
**Документация:** https://context7.com/bitrix24/rest/user.current (поле `UF_DEPARTMENT`)

#### Отображаемые данные:

- **Список ID отделов** — массив идентификаторов отделов, к которым принадлежит пользователь
- Отображается только если у пользователя есть отделы

#### Пример:

```json
{
  "departments": [1, 5, 12]
}
```

**Примечание:** Названия отделов не отображаются в текущей версии (только ID). Для получения названий используется метод `department.get` (требуются права доступа).

---

### 4. Конфигурация доступа

**Источник:** Метод `ConfigService::getAccessConfig()`  
**Файл конфигурации:** `APP-B24/config/access-config.json`

#### Отображаемые поля:

| Поле | Описание | Тип |
|------|----------|-----|
| **Проверка включена** | Включена ли проверка доступа | `boolean` |
| **Отделов с доступом** | Количество отделов с доступом | `number` |
| **Пользователей с доступом** | Количество пользователей с доступом | `number` |

#### Структура конфигурации:

```json
{
  "access_control": {
    "enabled": true,
    "departments": [1, 5, 12],
    "users": [123, 456, 789]
  }
}
```

---

### 5. Детальный анализ токена авторизации

**Источник:** Параметры запроса (`AUTH_ID`, `DOMAIN`, `REFRESH_ID`, `AUTH_EXPIRES`)

#### Отображаемые данные:

| Поле | Описание | Тип | Пример |
|------|----------|-----|--------|
| **type** | Тип токена | `string` | `user_token` или `installer_token` |
| **auth_id_length** | Длина токена авторизации | `number` | `64` |
| **auth_id_preview** | Preview токена (первые 20 + последние 10 символов) | `string` | `abc123def456...xyz789` |
| **has_refresh_token** | Наличие refresh токена | `boolean` | `true` или `false` |
| **refresh_token_length** | Длина refresh токена | `number` | `64` |
| **refresh_token_preview** | Preview refresh токена | `string` | `def456ghi789...abc123` |
| **expires_at** | Время истечения токена | `string` | `2025-12-22 10:30:00` |
| **expires_timestamp** | Timestamp истечения токена | `number` | `1734858600` |
| **is_expired** | Истёк ли токен | `boolean` | `false` |
| **time_until_expiry** | Осталось секунд до истечения | `number` | `3600` |
| **time_until_expiry_formatted** | Осталось времени (формат HH:MM:SS) | `string` | `01:00:00` |
| **domain** | Домен портала Bitrix24 | `string` | `example.bitrix24.ru` |
| **domain_region** | Регион домена | `string` | `ru`, `eu`, `us`, `cn` |

#### Пример:

```json
{
  "tokenAnalysis": {
    "type": "user_token",
    "auth_id_length": 64,
    "auth_id_preview": "abc123def456ghi789...xyz789",
    "has_refresh_token": true,
    "refresh_token_length": 64,
    "refresh_token_preview": "def456ghi789...abc123",
    "expires_at": "2025-12-22 10:30:00",
    "expires_timestamp": 1734858600,
    "is_expired": false,
    "time_until_expiry": 3600,
    "time_until_expiry_formatted": "01:00:00",
    "domain": "example.bitrix24.ru",
    "domain_region": "ru"
  }
}
```

**Важно:** 
- Полный токен никогда не отображается в интерфейсе для безопасности
- Показываются только preview (первые 20 + последние 10 символов)
- Refresh токен также маскируется

### 6. Настройки из settings.json (Local)

**Источник:** Файл `APP-B24/settings.json` (Local настройки приложения)

#### Отображаемые данные:

| Поле | Описание | Тип | Безопасность |
|------|----------|-----|--------------|
| **domain** | Домен портала из настроек | `string` | Полный домен |
| **client_endpoint** | Client endpoint URL | `string` | Полный URL |
| **has_access_token** | Наличие access token | `boolean` | - |
| **access_token_preview** | Preview access token (первые 20 символов) | `string` | Маскируется |
| **has_refresh_token** | Наличие refresh token | `boolean` | - |
| **refresh_token_preview** | Preview refresh token | `string` | Маскируется |
| **has_client_id** | Наличие client ID | `boolean` | - |
| **client_id_preview** | Preview client ID | `string` | Маскируется |
| **has_client_secret** | Наличие client secret | `boolean` | - |
| **has_application_token** | Наличие application token | `boolean` | - |
| **application_token_preview** | Preview application token | `string` | Маскируется |
| **scope** | Scope приложения | `string` | - |
| **expires_in** | Время истечения токена (секунды) | `number` | - |
| **last_updated** | Дата последнего обновления | `string` | - |

#### Пример:

```json
{
  "localSettings": {
    "domain": "example.bitrix24.ru",
    "client_endpoint": "https://example.bitrix24.ru",
    "has_access_token": true,
    "access_token_preview": "abc123def456ghi789...",
    "has_refresh_token": true,
    "refresh_token_preview": "def456ghi789...",
    "has_client_id": true,
    "client_id_preview": "local.1234567890...",
    "has_client_secret": true,
    "has_application_token": true,
    "application_token_preview": "app.9876543210...",
    "scope": "crm",
    "expires_in": 3600,
    "last_updated": "2025-12-21 20:00:00"
  }
}
```

**Важно:** 
- Все секретные данные маскируются (показываются только первые 20 символов)
- Client secret никогда не показывается (только флаг наличия)
- Данные берутся из файла `settings.json`, который создаётся при установке приложения

---

### 7. Права доступа и доступные методы API

**Источник:** Методы Bitrix24 API `scope`, `method.get`, `methods`  
**Документация:** 
- https://context7.com/bitrix24/rest/scope
- https://context7.com/bitrix24/rest/method.get
- https://context7.com/bitrix24/rest/methods

#### Отображаемые данные:

| Поле | Описание | Тип | Пример |
|------|----------|-----|--------|
| **current_scope** | Текущие права доступа приложения | `array` | `["crm", "user", "department"]` |
| **all_available_scope** | Все возможные права доступа | `array` | `["crm", "user", "department", "task", ...]` |
| **available_methods_count** | Количество доступных методов API | `number` | `150` |
| **available_methods_preview** | Примеры доступных методов (первые 20) | `array` | `["user.current", "crm.lead.list", ...]` |
| **tested_methods** | Результаты проверки ключевых методов | `object` | См. пример ниже |

#### Структура tested_methods:

```json
{
  "tested_methods": {
    "user.current": {
      "is_existing": true,
      "is_available": true,
      "error": null
    },
    "crm.lead.list": {
      "is_existing": true,
      "is_available": true,
      "error": null
    },
    "crm.deal.list": {
      "is_existing": true,
      "is_available": false,
      "error": "insufficient_scope"
    }
  }
}
```

#### Проверяемые ключевые методы:

- `user.current` — получение текущего пользователя
- `user.get` — получение данных пользователя
- `user.admin` — проверка статуса администратора
- `user.access` — проверка прав пользователя
- `department.get` — получение данных отделов
- `crm.lead.list` — получение списка лидов
- `crm.deal.list` — получение списка сделок
- `crm.contact.list` — получение списка контактов
- `scope` — получение прав доступа
- `method.get` — проверка доступности метода
- `methods` — получение списка методов

#### Пример:

```json
{
  "permissions": {
    "current_scope": ["crm", "user", "department"],
    "all_available_scope": ["crm", "user", "department", "task", "calendar", ...],
    "available_methods_count": 150,
    "available_methods_preview": [
      "user.current",
      "user.get",
      "crm.lead.list",
      "department.get",
      ...
    ],
    "tested_methods": {
      "user.current": {
        "is_existing": true,
        "is_available": true
      },
      "crm.lead.list": {
        "is_existing": true,
        "is_available": true
      }
    }
  }
}
```

**Важно:** 
- `current_scope` показывает, какие права выданы вашему приложению
- `all_available_scope` показывает все возможные права в Bitrix24
- `tested_methods` показывает, какие методы доступны для использования
- Если метод недоступен, указывается причина (`error`)

---

### 8. Полные данные (JSON)

**Назначение:** Полный JSON вывод всех анализируемых данных для копирования и отладки.

#### Структура JSON:

```json
{
  "user": {
    "ID": 123,
    "NAME": "Иван",
    "LAST_NAME": "Иванов",
    "EMAIL": "ivan@example.com",
    "ADMIN": "Y",
    "UF_DEPARTMENT": [1, 5, 12],
    // ... другие поля пользователя
  },
  "isAdmin": true,
  "hasAccess": true,
  "departments": [1, 5, 12],
  "accessConfig": {
    "access_control": {
      "enabled": true,
      "departments": [1, 5, 12],
      "users": [123, 456, 789]
    }
  },
  "token": {
    "auth_id": "abc123def456ghi789...",
    "domain": "example.bitrix24.ru"
  }
}
```

**Функционал:**
- Отображение полного JSON в формате с отступами
- Кнопка "Копировать JSON" для копирования в буфер обмена
- Используется для отладки и анализа проблем

---

## API Endpoint

### GET `/api/token-analysis`

**Назначение:** Получение данных анализа токена

**Параметры запроса:**
- `AUTH_ID` (или `APP_SID`) — токен авторизации (query или POST)
- `DOMAIN` — домен портала Bitrix24 (query или POST)

**Требования:**
- ✅ Пользователь должен быть авторизован
- ✅ Пользователь должен быть администратором Bitrix24

**Ответ (успех):**
```json
{
  "success": true,
  "data": {
    "user": { /* данные пользователя */ },
    "isAdmin": true,
    "hasAccess": true,
    "departments": [1, 5, 12],
    "accessConfig": { /* конфигурация доступа */ },
    "token": {
      "auth_id": "abc123def456ghi789...",
      "domain": "example.bitrix24.ru"
    }
  }
}
```

**Ответ (ошибка):**
```json
{
  "success": false,
  "error": "Forbidden",
  "message": "Only administrators can analyze tokens"
}
```

---

## Используемые методы Bitrix24 API

### 1. `user.current`
**Документация:** https://context7.com/bitrix24/rest/user.current

**Назначение:** Получение данных текущего пользователя

**Используется в:** `UserService::getCurrentUser()`

**Возвращает:**
- Полную информацию о пользователе
- Список отделов (`UF_DEPARTMENT`)
- Статус администратора (`ADMIN`)

---

### 2. `user.get`
**Документация:** https://context7.com/bitrix24/rest/user.get

**Назначение:** Получение данных пользователя по ID

**Используется в:** `UserService::isAdmin()` (для проверки поля `ADMIN`)

**Возвращает:**
- Детальную информацию о пользователе
- Поле `ADMIN` (статус администратора)

---

### 3. `user.admin`
**Документация:** https://context7.com/bitrix24/rest/user.admin

**Назначение:** Проверка статуса администратора

**Используется в:** `UserService::isAdmin()`

**Возвращает:**
- `true` если пользователь — администратор
- `false` если пользователь — не администратор

---

### 4. `department.get`
**Документация:** https://context7.com/bitrix24/rest/department.get

**Назначение:** Получение информации об отделе по ID

**Используется в:** (опционально, для получения названий отделов)

**Примечание:** В текущей версии названия отделов не отображаются, только ID.

---

## Безопасность

### Ограничения доступа

1. **Проверка авторизации:**
   - Пользователь должен быть авторизован в Bitrix24
   - Токен авторизации должен быть валидным

2. **Проверка прав:**
   - Только администраторы Bitrix24 могут использовать модуль
   - Проверка выполняется на сервере (PHP), не на клиенте

3. **Маскировка токена:**
   - Полный токен никогда не отображается
   - Показываются только первые 20 символов
   - Токен не передается в логах полностью

### Логирование

Все попытки доступа логируются:
- Успешные запросы
- Ошибки авторизации
- Ошибки проверки прав
- Ошибки получения данных

---

## Использование модуля

### Для администраторов

1. **Открыть модуль:**
   - Перейти на маршрут `/token-analysis`
   - Или нажать кнопку "Проверка токена" на главной странице

2. **Просмотр данных:**
   - Информация о пользователе
   - Права доступа
   - Отделы пользователя
   - Конфигурация доступа
   - Информация о токене

3. **Копирование данных:**
   - Нажать кнопку "Копировать JSON"
   - Данные скопируются в буфер обмена
   - Можно использовать для отладки

### Для разработчиков

1. **API запрос:**
   ```javascript
   const response = await bitrix24Api.analyzeToken();
   const data = response.data;
   ```

2. **Использование данных:**
   ```javascript
   const user = data.user;
   const isAdmin = data.isAdmin;
   const hasAccess = data.hasAccess;
   const departments = data.departments;
   const accessConfig = data.accessConfig;
   ```

---

## Примеры использования

### Пример 1: Проверка прав доступа

```javascript
// В компоненте Vue.js
const analysis = await bitrix24Api.analyzeToken();

if (analysis.data.isAdmin) {
  console.log('Пользователь является администратором');
} else if (analysis.data.hasAccess) {
  console.log('Пользователь имеет доступ');
} else {
  console.log('Пользователь не имеет доступа');
}
```

### Пример 2: Получение отделов пользователя

```javascript
const analysis = await bitrix24Api.analyzeToken();
const departments = analysis.data.departments;

console.log('Пользователь принадлежит к отделам:', departments);
```

### Пример 3: Проверка конфигурации доступа

```javascript
const analysis = await bitrix24Api.analyzeToken();
const accessConfig = analysis.data.accessConfig;

if (accessConfig.access_control.enabled) {
  console.log('Проверка доступа включена');
  console.log('Отделов с доступом:', accessConfig.access_control.departments.length);
  console.log('Пользователей с доступом:', accessConfig.access_control.users.length);
}
```

---

## Связанные компоненты

### PHP сервисы

- `UserService` — работа с пользователями
- `AccessControlService` — проверка прав доступа
- `ConfigService` — работа с конфигурацией

### Vue.js компоненты

- `TokenAnalysisPage.vue` — компонент страницы анализа токена
- `bitrix24Api.js` — сервис для работы с API

### API endpoints

- `GET /api/token-analysis` — получение данных анализа токена

---

## История правок

- **2025-12-21 21:45 (UTC+3, Брест):** Создан глобальный документ с описанием всех анализируемых данных в модуле "Проверка токена"
- **2025-12-21 22:00 (UTC+3, Брест):** Расширен модуль анализа токена:
  - ✅ Добавлен детальный анализ токена авторизации (длина, тип, время истечения, регион домена)
  - ✅ Добавлен анализ данных из settings.json (Local настройки приложения)
  - ✅ Все секретные данные маскируются для безопасности
  - ✅ Обновлен компонент TokenAnalysisPage.vue для отображения новых данных
  - ✅ Обновлена документация
- **2025-12-21 22:30 (UTC+3, Брест):** Добавлена проверка прав доступа и доступных методов API:
  - ✅ Добавлены методы в Bitrix24ApiService для проверки scope и доступных методов
  - ✅ Расширен API endpoint для получения информации о правах доступа
  - ✅ Добавлено отображение текущих прав (scope) и всех возможных прав
  - ✅ Добавлена проверка доступности ключевых методов API
  - ✅ Обновлен компонент TokenAnalysisPage.vue для отображения прав доступа
  - ✅ Обновлена документация

---

## Дополнительные ресурсы

- [Документация Bitrix24 REST API](https://context7.com/bitrix24/rest/)
- [Анализ файла token-analysis.php](../ANALYSIS/2025-12-21-token-analysis-file-analysis.md)
- [Архитектура приложения](../ARCHITECTURE/application-architecture.md)

