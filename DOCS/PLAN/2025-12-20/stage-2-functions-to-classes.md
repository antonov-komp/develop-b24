# Этап 2: Рефакторинг функций в классы

**Дата создания:** 2025-12-20 20:30 (UTC+3, Брест)  
**Версия:** 1.0  
**Статус:** План  
**Приоритет:** Высокий  
**Оценка времени:** 2-3 дня

---

## Цель этапа

Преобразовать функции из монолитных файлов в методы классов с четкой ответственностью. Выделить вспомогательные классы для общей логики.

**Результат:** Все функции преобразованы в методы классов, четкое разделение ответственности, улучшена читаемость кода.

---

## Зависимости

**Требуется завершение:** Этап 1 (Выделение сервисного слоя)

**Используемые сервисы:**
- `LoggerService`
- `ConfigService`
- `Bitrix24ApiService`
- `UserService`
- `AccessControlService`

---

## Задачи этапа

### Задача 2.1: Создание класса `AuthService`

**Файл:** `APP-B24/src/Services/AuthService.php`

**Назначение:** Проверка авторизации Bitrix24, редиректы на страницы ошибок.

**Методы:**
- `checkBitrix24Auth(): bool` — проверка авторизации Bitrix24
- `isRequestFromBitrix24(): bool` — проверка, идет ли запрос из Bitrix24
- `redirectToFailure(): void` — редирект на страницу ошибки

**Источники кода:**
- Файл `auth-check.php` полностью:
  - Функция `checkBitrix24Auth()` (строки 10-241)
  - Функция `redirectToFailure()` (строки 246-279)
  - Функция `isRequestFromBitrix24()` (из `index.php`, строки 115-157)

**Пример реализации:**
```php
<?php

namespace App\Services;

require_once(__DIR__ . '/../../crest.php');

/**
 * Сервис для проверки авторизации Bitrix24
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class AuthService
{
    protected ConfigService $configService;
    protected AccessControlService $accessControlService;
    protected LoggerService $logger;
    
    public function __construct(
        ConfigService $configService,
        AccessControlService $accessControlService,
        LoggerService $logger
    ) {
        $this->configService = $configService;
        $this->accessControlService = $accessControlService;
        $this->logger = $logger;
    }
    
    /**
     * Проверка авторизации Bitrix24
     * 
     * @return bool true если авторизация валидна
     */
    public function checkBitrix24Auth(): bool
    {
        // Логика из auth-check.php (строки 10-241)
        // Использовать LoggerService для логирования
        // Использовать ConfigService для работы с настройками
        // Использовать AccessControlService для проверки прав доступа
        
        // ... реализация
        
        return true;
    }
    
    /**
     * Проверка, идет ли запрос из Bitrix24
     * 
     * @return bool true если запрос из Bitrix24
     */
    public function isRequestFromBitrix24(): bool
    {
        // Логика из index.php (строки 115-157)
        
        // Проверка 1: наличие параметров
        if (
            (isset($_REQUEST['DOMAIN']) && !empty($_REQUEST['DOMAIN'])) ||
            (isset($_REQUEST['AUTH_ID']) && !empty($_REQUEST['AUTH_ID'])) ||
            (isset($_REQUEST['APP_SID']) && !empty($_REQUEST['APP_SID']))
        ) {
            return true;
        }
        
        // Проверка 2: Referer header
        // ... остальная логика
        
        return false;
    }
    
    /**
     * Редирект на страницу ошибки доступа
     */
    public function redirectToFailure(): void
    {
        // Логика из auth-check.php (строки 246-279)
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $scriptPath = dirname($_SERVER['PHP_SELF']);
        $scriptPath = rtrim($scriptPath, '/');
        
        if ($scriptPath === '' || $scriptPath === '.') {
            $failureUrl = $protocol . '://' . $host . '/failure.php';
        } else {
            $failureUrl = $protocol . '://' . $host . $scriptPath . '/failure.php';
        }
        
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('HTTP/1.1 403 Forbidden', true, 403);
        header('Location: ' . $failureUrl, true, 302);
        header('Content-Type: text/html; charset=UTF-8');
        
        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($failureUrl) . '"></head><body><p>Redirecting to <a href="' . htmlspecialchars($failureUrl) . '">error page</a>...</p></body></html>';
        
        exit;
    }
}
```

**Изменения в `auth-check.php`:**
- Заменить функции на использование `AuthService`
- Оставить только подключение и вызов методов сервиса

**Критерий:** Класс создан, `auth-check.php` использует `AuthService`, все тесты проходят.

---

### Задача 2.2: Интеграция функций из `access-control-functions.php` в `AccessControlService`

**Действия:**
1. Перенести все оставшиеся функции из `access-control-functions.php` в `AccessControlService`
2. Обновить `AccessControlService` для использования `Bitrix24ApiService` и `LoggerService`
3. Удалить дублирующийся код

**Функции для переноса:**
- `getCurrentUserDataForAccess()` — получение данных пользователя для проверки доступа
- Все вспомогательные функции

**Изменения в `access-control-functions.php`:**
- Оставить только подключение и вызов методов `AccessControlService`
- Или удалить файл, если все функции перенесены

**Критерий:** Все функции перенесены, `access-control-functions.php` обновлен или удален.

---

### Задача 2.3: Создание класса `DomainResolver`

**Файл:** `APP-B24/src/Helpers/DomainResolver.php`

**Назначение:** Получение домена портала из различных источников.

**Методы:**
- `resolveDomain(): string|null` — получение домена портала

**Источники кода:**
- Логика из `index.php` (строки 490-626)
- Логика из `access-control.php` (строки 23-37)
- Логика из `token-analysis.php` (аналогичная)

**Пример реализации:**
```php
<?php

namespace App\Helpers;

use App\Services\ConfigService;

/**
 * Вспомогательный класс для получения домена портала
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class DomainResolver
{
    protected ConfigService $configService;
    
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }
    
    /**
     * Получение домена портала
     * 
     * Приоритет:
     * 1. Домен из параметров запроса (DOMAIN)
     * 2. Домен из client_endpoint в settings.json
     * 3. Домен из settings.json (domain)
     * 
     * @return string|null Домен портала или null
     */
    public function resolveDomain(): ?string
    {
        // Приоритет 1: Домен из параметров запроса
        if (isset($_REQUEST['DOMAIN']) && !empty($_REQUEST['DOMAIN'])) {
            return $_REQUEST['DOMAIN'];
        }
        
        // Приоритет 2: Домен из client_endpoint в settings.json
        $settings = $this->configService->getSettings();
        if (isset($settings['client_endpoint']) && !empty($settings['client_endpoint'])) {
            $clientEndpoint = $settings['client_endpoint'];
            if (preg_match('#https?://([^/]+)#', $clientEndpoint, $matches)) {
                return $matches[1];
            }
        }
        
        // Приоритет 3: Домен из settings.json
        if (isset($settings['domain']) && !empty($settings['domain'])) {
            $domainFromSettings = $settings['domain'];
            if ($domainFromSettings !== 'oauth.bitrix.info') {
                return $domainFromSettings;
            }
        }
        
        return null;
    }
}
```

**Критерий:** Класс создан, используется во всех файлах вместо дублирующейся логики.

---

### Задача 2.4: Создание класса `AdminChecker`

**Файл:** `APP-B24/src/Helpers/AdminChecker.php`

**Назначение:** Проверка статуса администратора пользователя.

**Методы:**
- `check(array $user, string $authId, string $domain): bool` — проверка статуса администратора

**Источники кода:**
- Логика из `index.php` (строки 636-694)
- Логика из `token-analysis.php` (аналогичная)

**Пример реализации:**
```php
<?php

namespace App\Helpers;

use App\Services\Bitrix24ApiService;

/**
 * Вспомогательный класс для проверки статуса администратора
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class AdminChecker
{
    protected Bitrix24ApiService $apiService;
    
    public function __construct(Bitrix24ApiService $apiService)
    {
        $this->apiService = $apiService;
    }
    
    /**
     * Проверка статуса администратора
     * 
     * @param array $user Данные пользователя
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return bool true если администратор
     */
    public function check(array $user, string $authId, string $domain): bool
    {
        // Проверка поля ADMIN в данных пользователя
        if (isset($user['ADMIN'])) {
            $adminValue = $user['ADMIN'];
            return (
                $adminValue === 'Y' || 
                $adminValue === 'y' || 
                $adminValue == 1 || 
                $adminValue === 1 || 
                $adminValue === true ||
                $adminValue === '1'
            );
        }
        
        // Проверка через user.admin API
        return $this->apiService->checkIsAdmin($authId, $domain);
    }
}
```

**Критерий:** Класс создан, используется вместо дублирующейся логики.

---

### Задача 2.5: Обновление существующих файлов

**Файлы для обновления:**
1. `index.php`
2. `token-analysis.php`
3. `access-control.php`
4. `auth-check.php`

**Действия для каждого файла:**

#### 2.5.1. Обновление `index.php`

**Изменения:**
1. Подключить все необходимые сервисы и хелперы
2. Заменить функции на вызовы методов сервисов:
   - `checkIndexPageConfig()` → `$configService->getIndexPageConfig()`
   - `getCurrentUserData()` → `$apiService->getCurrentUser()`
   - `getDepartmentData()` → `$apiService->getDepartment()`
   - Логика получения домена → `$domainResolver->resolveDomain()`
   - Логика проверки администратора → `$adminChecker->check()`
3. Использовать `LoggerService` для логирования
4. Оставить только логику страницы и HTML

**Пример:**
```php
<?php

require_once(__DIR__ . '/src/Services/LoggerService.php');
require_once(__DIR__ . '/src/Services/ConfigService.php');
require_once(__DIR__ . '/src/Services/Bitrix24ApiService.php');
require_once(__DIR__ . '/src/Services/UserService.php');
require_once(__DIR__ . '/src/Services/AccessControlService.php');
require_once(__DIR__ . '/src/Services/AuthService.php');
require_once(__DIR__ . '/src/Helpers/DomainResolver.php');
require_once(__DIR__ . '/src/Helpers/AdminChecker.php');

require_once(__DIR__ . '/auth-check.php');

// Инициализация сервисов
$logger = new App\Services\LoggerService();
$configService = new App\Services\ConfigService($logger);
$apiService = new App\Services\Bitrix24ApiService($logger);
$userService = new App\Services\UserService($apiService, $logger);
$accessControlService = new App\Services\AccessControlService($configService, $apiService, $userService, $logger);
$authService = new App\Services\AuthService($configService, $accessControlService, $logger);
$domainResolver = new App\Helpers\DomainResolver($configService);
$adminChecker = new App\Helpers\AdminChecker($apiService);

// Проверка авторизации
if (!$authService->checkBitrix24Auth()) {
    $authService->redirectToFailure();
}

// Получение домена
$portalDomain = $domainResolver->resolveDomain();

// ... остальная логика с использованием сервисов
```

**Критерий:** `index.php` использует сервисы, код стал короче и понятнее.

#### 2.5.2. Обновление `token-analysis.php`

**Изменения:**
1. Подключить все необходимые сервисы
2. Заменить функции на вызовы методов сервисов
3. Использовать `LoggerService` для логирования

**Критерий:** `token-analysis.php` использует сервисы, функционал работает идентично.

#### 2.5.3. Обновление `access-control.php`

**Изменения:**
1. Подключить все необходимые сервисы
2. Заменить функции на вызовы методов `AccessControlService`
3. Использовать `LoggerService` для логирования

**Критерий:** `access-control.php` использует сервисы, функционал работает идентично.

#### 2.5.4. Обновление `auth-check.php`

**Изменения:**
1. Подключить `AuthService`
2. Заменить функции на вызовы методов `AuthService`
3. Оставить только обертку для обратной совместимости

**Пример:**
```php
<?php

require_once(__DIR__ . '/src/Services/LoggerService.php');
require_once(__DIR__ . '/src/Services/ConfigService.php');
require_once(__DIR__ . '/src/Services/Bitrix24ApiService.php');
require_once(__DIR__ . '/src/Services/UserService.php');
require_once(__DIR__ . '/src/Services/AccessControlService.php');
require_once(__DIR__ . '/src/Services/AuthService.php');

// Инициализация сервисов
$logger = new App\Services\LoggerService();
$configService = new App\Services\ConfigService($logger);
$apiService = new App\Services\Bitrix24ApiService($logger);
$userService = new App\Services\UserService($apiService, $logger);
$accessControlService = new App\Services\AccessControlService($configService, $apiService, $userService, $logger);
$authService = new App\Services\AuthService($configService, $accessControlService, $logger);

// Обертки для обратной совместимости
function checkBitrix24Auth() {
    global $authService;
    return $authService->checkBitrix24Auth();
}

function redirectToFailure() {
    global $authService;
    $authService->redirectToFailure();
}
```

**Критерий:** `auth-check.php` использует `AuthService`, обратная совместимость сохранена.

---

## Порядок выполнения

1. **Создать AuthService** (Задача 2.1)
2. **Обновить AccessControlService** (Задача 2.2)
3. **Создать DomainResolver** (Задача 2.3)
4. **Создать AdminChecker** (Задача 2.4)
5. **Обновить auth-check.php** (Задача 2.5.4)
6. **Обновить index.php** (Задача 2.5.1)
7. **Обновить token-analysis.php** (Задача 2.5.2)
8. **Обновить access-control.php** (Задача 2.5.3)

---

## Тестирование

### Тест 1: AuthService
```php
$authService = new AuthService($configService, $accessControlService, $logger);
$result = $authService->checkBitrix24Auth();
// Проверить корректность проверки авторизации
```

### Тест 2: DomainResolver
```php
$domainResolver = new DomainResolver($configService);
$domain = $domainResolver->resolveDomain();
// Проверить корректность получения домена
```

### Тест 3: AdminChecker
```php
$adminChecker = new AdminChecker($apiService);
$isAdmin = $adminChecker->check($user, $authId, $domain);
// Проверить корректность проверки администратора
```

### Тест 4: Обновленные файлы
- Проверить, что `index.php` работает идентично
- Проверить, что `token-analysis.php` работает идентично
- Проверить, что `access-control.php` работает идентично
- Проверить, что `auth-check.php` работает идентично

---

## Критерии приёмки этапа

- [ ] Все функции преобразованы в методы классов
- [ ] Созданы классы `AuthService`, `DomainResolver`, `AdminChecker`
- [ ] Обновлены все существующие файлы для использования сервисов
- [ ] Весь функционал работает идентично до рефакторинга
- [ ] Код соответствует стандартам PSR-12
- [ ] Нет дублирования кода
- [ ] Все тесты проходят успешно

---

## Риски и митигация

### Риск 1: Потеря функциональности при переносе
**Митигация:** Тщательно копировать логику из исходных функций, тестировать каждый файл отдельно

### Риск 2: Проблемы с зависимостями
**Митигация:** Создавать классы в правильном порядке, использовать dependency injection

### Риск 3: Нарушение обратной совместимости
**Митигация:** Оставить обертки функций в `auth-check.php` для обратной совместимости

---

## История правок

- **2025-12-20 20:30 (UTC+3, Брест):** Создан детальный план этапа 2

---

**Статус:** План готов к реализации  
**Дата создания:** 2025-12-20 20:30 (UTC+3, Брест)




