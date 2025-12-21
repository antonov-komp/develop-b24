# Изучение Bitrix24 PHP SDK

**Дата:** 2025-12-21 08:45 (UTC+3, Брест)  
**Статус:** Анализ завершен  
**Исполнитель:** Системный администратор (AI Assistant)

---

## Ключевые находки

### 1. Правильный способ работы с токенами пользователя

**SDK поддерживает несколько способов инициализации:**

1. **Placement Request** (рекомендуется для приложений)
   ```php
   use Bitrix24\SDK\Services\ServiceBuilderFactory;
   use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
   use Symfony\Component\HttpFoundation\Request;
   
   $appProfile = ApplicationProfile::initFromArray([
       'BITRIX24_PHP_SDK_APPLICATION_CLIENT_ID' => 'client_id',
       'BITRIX24_PHP_SDK_APPLICATION_CLIENT_SECRET' => 'client_secret',
       'BITRIX24_PHP_SDK_APPLICATION_SCOPE' => 'crm'
   ]);
   
   $b24Service = ServiceBuilderFactory::createServiceBuilderFromPlacementRequest(
       Request::createFromGlobals(),
       $appProfile
   );
   ```

2. **Вебхук** (для простых интеграций)
   ```php
   $b24Service = ServiceBuilderFactory::createServiceBuilderFromWebhook('webhook_url');
   ```

3. **OAuth токены** (для marketplace приложений)
   ```php
   // Используется через Bitrix24Accounts контракт
   ```

### 2. Требования для Placement Request

**Для `AuthToken::initFromPlacementRequest()` требуются:**
- `AUTH_ID` (access_token) - ✅ У нас есть
- `REFRESH_ID` (refresh_token) - ❌ У нас НЕТ
- `AUTH_EXPIRES` (expires) - ❌ У нас НЕТ

**Проблема:** В нашем случае мы получаем только `AUTH_ID` (или `APP_SID`), но не получаем `REFRESH_ID` и `AUTH_EXPIRES`.

### 3. Как SDK передает токен в API

**В `ApiClient.php` строка 208:**
```php
$parameters['auth'] = $this->getCredentials()->getAuthToken()->accessToken;
```

SDK передает токен как параметр `auth` в запросе к Bitrix24 REST API.

### 4. ApplicationProfile

**ApplicationProfile требует:**
- `client_id` - ID приложения
- `client_secret` - Секретный ключ приложения
- `scope` - Права доступа

**Проблема:** Мы используем минимальный профиль `'user'/'user'`, что вызывает ошибку `wrong_client`.

---

## Проблема в нашем коде

### Текущая реализация

```php
// В Bitrix24SdkClient::initializeWithUserToken()
$authToken = new AuthToken(
    $authId,           // AUTH_ID
    null,              // refresh_token - НЕТ!
    time() + 3600      // expires - ПРИДУМАННОЕ ЗНАЧЕНИЕ!
);

$applicationProfile = new ApplicationProfile(
    'user',            // client_id - НЕПРАВИЛЬНО!
    'user',            // client_secret - НЕПРАВИЛЬНО!
    Scope::initFromString('crm')
);
```

### Почему это не работает

1. **Нет REFRESH_ID:** SDK может требовать refresh_token для некоторых операций
2. **Неправильный ApplicationProfile:** `'user'/'user'` не является валидным профилем приложения
3. **Придуманное expires:** Время истечения токена может быть неправильным

---

## Решения

### Решение 1: Использовать правильный ApplicationProfile

**Использовать настройки из settings.json:**
```php
$settings = $this->getSettings();
$clientId = $settings['application_token'] ?? 'user';
$clientSecret = $settings['application_token'] ?? 'user';

$applicationProfile = new ApplicationProfile(
    $clientId,
    $clientSecret,
    Scope::initFromString('crm')
);
```

**✅ Уже реализовано в коде**

### Решение 2: Получить REFRESH_ID и AUTH_EXPIRES из запроса

**Проверить, что приходит в запросе от Bitrix24:**
- Если есть `REFRESH_ID` и `AUTH_EXPIRES` - использовать их
- Если нет - использовать значения по умолчанию

**Реализация:**
```php
// В api/user.php или Bitrix24ApiService
$refreshId = $_REQUEST['REFRESH_ID'] ?? null;
$authExpires = isset($_REQUEST['AUTH_EXPIRES']) 
    ? (int)$_REQUEST['AUTH_EXPIRES'] 
    : time() + 3600;

$authToken = new AuthToken(
    $authId,
    $refreshId,
    $authExpires
);
```

### Решение 3: Использовать PlacementRequest напрямую

**Если запрос приходит как placement:**
```php
use Bitrix24\SDK\Application\Requests\Placement\PlacementRequest;
use Symfony\Component\HttpFoundation\Request;

$request = Request::createFromGlobals();
if (PlacementRequest::isCanProcess($request)) {
    $placementRequest = new PlacementRequest($request);
    $authToken = $placementRequest->getAccessToken();
    // Использовать токен из placement
}
```

### Решение 4: Использовать прямой REST API вызов

**Если SDK не работает, использовать прямой HTTP запрос:**
```php
$url = "https://{$domain}/rest/user.current.json";
$params = ['auth' => $authId];
// Выполнить запрос через curl или HttpClient
```

---

## Рекомендуемый план действий

### Шаг 1: Проверить, что приходит в запросе

**Добавить логирование всех параметров запроса:**
```php
$logger->log('Request parameters', [
    'GET' => $_GET,
    'POST' => $_POST,
    'REQUEST' => $_REQUEST
], 'info');
```

### Шаг 2: Использовать правильный ApplicationProfile

**✅ Уже реализовано** - используем настройки из settings.json

### Шаг 3: Получить REFRESH_ID и AUTH_EXPIRES

**Если они есть в запросе - использовать их:**
```php
$refreshId = $_REQUEST['REFRESH_ID'] ?? null;
$authExpires = isset($_REQUEST['AUTH_EXPIRES']) 
    ? (int)$_REQUEST['AUTH_EXPIRES'] 
    : time() + 3600;
```

### Шаг 4: Если SDK все еще не работает

**Реализовать fallback на прямой REST API вызов:**
```php
try {
    // Попытка через SDK
    $result = $this->client->call('user.current', []);
} catch (\Exception $e) {
    // Fallback на прямой REST API
    $result = $this->callDirectRestApi('user.current', $authId, $domain);
}
```

---

## Выводы

1. **SDK требует правильный ApplicationProfile** - ✅ Исправлено
2. **SDK может требовать REFRESH_ID** - ❓ Нужно проверить
3. **SDK может требовать правильный AUTH_EXPIRES** - ❓ Нужно проверить
4. **Если SDK не работает, можно использовать прямой REST API** - ✅ Резервный вариант

---

## Следующие шаги

1. ✅ Изучить SDK - завершено
2. ⏳ Проверить, что приходит в запросе от Bitrix24
3. ⏳ Исправить инициализацию SDK с правильными параметрами
4. ⏳ Реализовать fallback на прямой REST API (если нужно)

---

**История правок:**
- **2025-12-21 08:45 (UTC+3, Брест):** Создан документ с результатами изучения SDK



