# Использование данных из settings.json (Local)

**Дата создания:** 2025-12-21 22:15 (UTC+3, Брест)  
**Версия:** 1.0  
**Тип документа:** Глобальная документация

---

## Обзор

Данные из файла `settings.json` (Local настройки) используются для инициализации Bitrix24 PHP SDK и работы с Bitrix24 REST API. Этот документ описывает, где и как используются ключевые параметры.

---

## Ключевые параметры

### 1. `client_id` / `application_token`

**Пример значения:** `local.694511b37ff372.43302472`

**Назначение:** Идентификатор приложения Bitrix24

**Где используется:**

#### 1.1. Инициализация SDK с токеном установщика

**Файл:** `APP-B24/src/Clients/Bitrix24SdkClient.php`  
**Метод:** `initializeWithInstallerToken()`

```php
// Строка 79
$clientId = $settings['client_id'] ?? $settings['application_token'] ?? '';

// Если нет client_id, используется application_token как fallback
if (empty($clientId)) {
    $clientId = 'minimal';
}

// Создание ApplicationProfile
$applicationProfile = new ApplicationProfile(
    $clientId,        // Используется здесь
    $clientSecret,
    Scope::initFromString($scope)
);
```

**Приоритет:**
1. `client_id` (если есть)
2. `application_token` (если `client_id` нет)
3. `'minimal'` (если оба отсутствуют)

#### 1.2. Инициализация SDK с токеном пользователя

**Файл:** `APP-B24/src/Clients/Bitrix24SdkClient.php`  
**Метод:** `initializeWithUserToken()`

```php
// Строка 176
$clientId = $settings['client_id'] ?? $settings['application_token'] ?? 'user';

// Если нет настроек, используется application_token как fallback
if (empty($clientId) || $clientId === 'user') {
    $clientId = $settings['application_token'] ?? 'user';
}

// Создание ApplicationProfile
$applicationProfile = new ApplicationProfile(
    $clientId,        // Используется здесь
    $clientSecret,
    Scope::initFromString($scope)
);
```

**Приоритет:**
1. `client_id` (если есть)
2. `application_token` (если `client_id` нет)
3. `'user'` (если оба отсутствуют)

---

### 2. `client_secret`

**Пример значения:** `2CJPRNQ4KMldC9RIaqpSwaUAa8t41bvKGn95UZSHYGXov00eNn`

**Назначение:** Секретный ключ приложения Bitrix24

**Где используется:**

#### 2.1. Инициализация SDK с токеном установщика

**Файл:** `APP-B24/src/Clients/Bitrix24SdkClient.php`  
**Метод:** `initializeWithInstallerToken()`

```php
// Строка 80
$clientSecret = $settings['client_secret'] ?? '';

// Если нет client_secret, используется application_token как fallback
if (empty($clientSecret)) {
    $clientSecret = $settings['application_token'] ?? 'minimal';
}

// Создание ApplicationProfile
$applicationProfile = new ApplicationProfile(
    $clientId,
    $clientSecret,    // Используется здесь
    Scope::initFromString($scope)
);
```

**Приоритет:**
1. `client_secret` (если есть)
2. `application_token` (если `client_secret` нет)
3. `'minimal'` (если оба отсутствуют)

#### 2.2. Инициализация SDK с токеном пользователя

**Файл:** `APP-B24/src/Clients/Bitrix24SdkClient.php`  
**Метод:** `initializeWithUserToken()`

```php
// Строка 177
$clientSecret = $settings['client_secret'] ?? $settings['application_token'] ?? 'user';

// Если нет настроек, используется application_token как fallback
if (empty($clientSecret) || $clientSecret === 'user') {
    $clientSecret = $settings['application_token'] ?? 'user';
}

// Создание ApplicationProfile
$applicationProfile = new ApplicationProfile(
    $clientId,
    $clientSecret,    // Используется здесь
    Scope::initFromString($scope)
);
```

**Приоритет:**
1. `client_secret` (если есть)
2. `application_token` (если `client_secret` нет)
3. `'user'` (если оба отсутствуют)

---

### 3. `application_token`

**Пример значения:** `27219980fd0f954ea54f...`

**Назначение:** Токен приложения Bitrix24 (используется как fallback для `client_id` и `client_secret`)

**Где используется:**

#### 3.1. Как fallback для client_id и client_secret

**Файл:** `APP-B24/src/Clients/Bitrix24SdkClient.php`

Используется в обоих методах инициализации:
- `initializeWithInstallerToken()` — строки 79, 90
- `initializeWithUserToken()` — строки 176, 177, 183, 186

#### 3.2. При установке приложения

**Файл:** `APP-B24/install.php`

```php
// Строка 45
'application_token' => $auth['application_token'] ?? '',

// Строка 130
'application_token' => isset($_REQUEST['APP_SID']) ? htmlspecialchars($_REQUEST['APP_SID'], ENT_QUOTES, 'UTF-8') : '',
```

Сохраняется в `settings.json` при установке приложения.

---

## Как работает ApplicationProfile

### Назначение

`ApplicationProfile` — это объект в Bitrix24 PHP SDK, который содержит:
- `client_id` — идентификатор приложения
- `client_secret` — секретный ключ приложения
- `scope` — область доступа (например, `crm`)

### Использование в SDK

**Файл SDK:** `vendor/bitrix24/b24phpsdk/src/Core/ApiClient.php`

```php
// Строка 109-110
'client_id' => $this->getCredentials()->getApplicationProfile()->clientId,
'client_secret' => $this->getCredentials()->getApplicationProfile()->clientSecret,
```

SDK использует эти данные для:
1. **Валидации токенов** — проверка соответствия токена приложению
2. **Обновления токенов** — refresh токенов через OAuth
3. **Безопасности** — проверка подписи запросов

---

## Схема использования

```
┌─────────────────────────────────────────────────────────────┐
│ settings.json (Local)                                       │
│                                                             │
│ {                                                           │
│   "client_id": "local.694511b37ff372.43302472",            │
│   "client_secret": "2CJPRNQ4KMldC9RIaqpSwaUAa8t...",       │
│   "application_token": "27219980fd0f954ea54f...",         │
│   "access_token": "...",                                    │
│   "domain": "develop.bitrix24.by"                           │
│ }                                                           │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ ConfigService::getSettings()                                │
│                                                             │
│ Читает settings.json и возвращает массив настроек          │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Bitrix24SdkClient::initializeWithInstallerToken()           │
│ или                                                         │
│ Bitrix24SdkClient::initializeWithUserToken()                │
│                                                             │
│ 1. Получает настройки из settings.json                     │
│ 2. Извлекает client_id / client_secret / application_token │
│ 3. Создаёт ApplicationProfile                              │
│ 4. Создаёт Credentials с ApplicationProfile                │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ Bitrix24 PHP SDK                                            │
│                                                             │
│ Использует ApplicationProfile для:                          │
│ - Валидации токенов                                         │
│ - Обновления токенов (refresh)                             │
│ - Проверки подписи запросов                                │
│ - Выполнения API запросов                                  │
└─────────────────────────────────────────────────────────────┘
```

---

## Приоритет использования

### Для токена установщика (`initializeWithInstallerToken`)

**client_id:**
1. `$settings['client_id']` (если есть)
2. `$settings['application_token']` (если `client_id` нет)
3. `'minimal'` (если оба отсутствуют)

**client_secret:**
1. `$settings['client_secret']` (если есть)
2. `$settings['application_token']` (если `client_secret` нет)
3. `'minimal'` (если оба отсутствуют)

### Для токена пользователя (`initializeWithUserToken`)

**client_id:**
1. `$settings['client_id']` (если есть)
2. `$settings['application_token']` (если `client_id` нет)
3. `'user'` (если оба отсутствуют)

**client_secret:**
1. `$settings['client_secret']` (если есть)
2. `$settings['application_token']` (если `client_secret` нет)
3. `'user'` (если оба отсутствуют)

---

## Важные моменты

### 1. Безопасность

- ✅ `client_secret` никогда не отображается в интерфейсе
- ✅ Все секретные данные маскируются в логах
- ✅ Файл `settings.json` защищён от прямого доступа через `.htaccess`

### 2. Fallback логика

Если `client_id` или `client_secret` отсутствуют, используется `application_token` как fallback. Это позволяет работать даже если не все параметры настроены.

### 3. Ошибки "wrong_client"

Если используются неправильные `client_id`/`client_secret`, SDK может вернуть ошибку `wrong_client`. В этом случае нужно:
1. Проверить правильность значений в `settings.json`
2. Убедиться, что приложение установлено корректно
3. Переустановить приложение, если необходимо

---

## Примеры использования

### Пример 1: Инициализация с токеном установщика

```php
// В Bitrix24SdkClient::initializeWithInstallerToken()

$settings = $this->getSettings();

// Получение client_id
$clientId = $settings['client_id'] ?? $settings['application_token'] ?? '';
// Результат: "local.694511b37ff372.43302472"

// Получение client_secret
$clientSecret = $settings['client_secret'] ?? $settings['application_token'] ?? '';
// Результат: "2CJPRNQ4KMldC9RIaqpSwaUAa8t41bvKGn95UZSHYGXov00eNn"

// Создание ApplicationProfile
$applicationProfile = new ApplicationProfile(
    $clientId,      // "local.694511b37ff372.43302472"
    $clientSecret,  // "2CJPRNQ4KMldC9RIaqpSwaUAa8t41bvKGn95UZSHYGXov00eNn"
    Scope::initFromString('crm')
);
```

### Пример 2: Инициализация с токеном пользователя

```php
// В Bitrix24SdkClient::initializeWithUserToken()

$settings = $this->getSettings();

// Получение client_id (с fallback на application_token)
$clientId = $settings['client_id'] ?? $settings['application_token'] ?? 'user';
// Если client_id нет, но есть application_token:
// Результат: "27219980fd0f954ea54f..." (application_token)

// Получение client_secret (с fallback на application_token)
$clientSecret = $settings['client_secret'] ?? $settings['application_token'] ?? 'user';
// Если client_secret нет, но есть application_token:
// Результат: "27219980fd0f954ea54f..." (application_token)
```

---

## Где хранятся данные

### Файл: `APP-B24/settings.json`

```json
{
  "client_id": "local.694511b37ff372.43302472",
  "client_secret": "2CJPRNQ4KMldC9RIaqpSwaUAa8t41bvKGn95UZSHYGXov00eNn",
  "application_token": "27219980fd0f954ea54f...",
  "access_token": "0eec4669007f3178007f...",
  "refresh_token": "fe6a6e69007f3178007f...",
  "domain": "develop.bitrix24.by",
  "client_endpoint": "https://develop.bitrix24.by/rest/",
  "scope": "crm",
  "expires_in": 3600
}
```

**Безопасность:**
- Файл в `.gitignore` (не коммитится в Git)
- Защищён от прямого доступа через `.htaccess`
- Содержит секретные данные

---

## Связанные компоненты

### PHP сервисы

- `ConfigService::getSettings()` — получение настроек из `settings.json`
- `Bitrix24SdkClient` — использование настроек для инициализации SDK

### Файлы

- `APP-B24/settings.json` — файл с настройками
- `APP-B24/src/Clients/Bitrix24SdkClient.php` — использование настроек
- `APP-B24/src/Services/ConfigService.php` — чтение настроек
- `APP-B24/install.php` — сохранение настроек при установке

---

## История правок

- **2025-12-21 22:15 (UTC+3, Брест):** Создан документ с описанием использования данных из settings.json

---

## Дополнительные ресурсы

- [Документация Bitrix24 REST API](https://context7.com/bitrix24/rest/)
- [Bitrix24 PHP SDK Documentation](https://github.com/bitrix24/b24phpsdk)
- [Анализ токена](../GUIDES/token-analysis-data.md)

