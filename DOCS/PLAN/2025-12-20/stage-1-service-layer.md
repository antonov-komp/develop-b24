# Этап 1: Выделение сервисного слоя

**Дата создания:** 2025-12-20 20:30 (UTC+3, Брест)  
**Версия:** 1.0  
**Статус:** План  
**Приоритет:** Высокий  
**Оценка времени:** 2-3 дня

---

## Цель этапа

Создать классы-сервисы для работы с Bitrix24 API, пользователями и конфигурацией. Вынести всю бизнес-логику из монолитных файлов в отдельные сервисы с четкой ответственностью.

**Результат:** Все функции работы с API вынесены в сервисы, устранено дублирование кода, улучшена тестируемость.

---

## Задачи этапа

### Задача 1.1: Создание структуры директорий

**Действия:**
1. Создать директорию `APP-B24/src/Services/`
2. Убедиться, что директория доступна для записи

**Проверка:**
```bash
mkdir -p /var/www/backend.antonov-mark.ru/APP-B24/src/Services
chmod 775 /var/www/backend.antonov-mark.ru/APP-B24/src/Services
```

**Критерий:** Директория создана и доступна для записи.

---

### Задача 1.2: Создание класса `LoggerService`

**Файл:** `APP-B24/src/Services/LoggerService.php`

**Назначение:** Унифицированное логирование для всего приложения.

**Методы:**
- `log(string $message, array $context = [], string $type = 'info'): void` — общее логирование
- `logError(string $message, array $context = []): void` — логирование ошибок
- `logAccessCheck(int $userId, array $userDepartments, string $result, string $reason): void` — логирование проверки доступа
- `logConfigCheck(string $message, array $context = []): void` — логирование проверки конфигурации
- `logAuthCheck(string $message, array $context = []): void` — логирование проверки авторизации

**Источники кода:**
- Функция `logConfigCheck()` из `index.php` (строки 19-29)
- Логирование из `auth-check.php` (строки 13-19, 121-123, и т.д.)
- Логирование из `access-control-functions.php` (различные места)

**Пример реализации:**
```php
<?php

namespace App\Services;

/**
 * Сервис для унифицированного логирования
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class LoggerService
{
    protected string $logsDir;
    
    public function __construct()
    {
        $this->logsDir = __DIR__ . '/../../logs/';
    }
    
    /**
     * Общее логирование
     * 
     * @param string $message Сообщение для логирования
     * @param array $context Дополнительный контекст
     * @param string $type Тип лога (info, error, warning)
     */
    public function log(string $message, array $context = [], string $type = 'info'): void
    {
        $logFile = $this->logsDir . $type . '-' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ' . $message;
        
        if (!empty($context)) {
            $logEntry .= ', ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    // ... остальные методы
}
```

**Критерий:** Класс создан, все методы работают корректно.

---

### Задача 1.3: Создание класса `ConfigService`

**Файл:** `APP-B24/src/Services/ConfigService.php`

**Назначение:** Работа с конфигурационными файлами (config.json, access-config.json, settings.json).

**Методы:**
- `getIndexPageConfig(): array` — получение конфигурации главной страницы
- `getAccessConfig(): array` — получение конфигурации прав доступа
- `saveAccessConfig(array $config): array` — сохранение конфигурации прав доступа
- `getSettings(): array` — получение настроек приложения

**Источники кода:**
- Функция `checkIndexPageConfig()` из `index.php` (строки 42-108)
- Функции `getAccessConfig()` и `saveAccessConfig()` из `access-control-functions.php` (строки 14-46, 54-100)

**Пример реализации:**
```php
<?php

namespace App\Services;

/**
 * Сервис для работы с конфигурационными файлами
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class ConfigService
{
    protected string $configDir;
    protected LoggerService $logger;
    
    public function __construct(LoggerService $logger)
    {
        $this->configDir = __DIR__ . '/../../';
        $this->logger = $logger;
    }
    
    /**
     * Получение конфигурации главной страницы
     * 
     * @return array Результат проверки конфига
     */
    public function getIndexPageConfig(): array
    {
        $configFile = $this->configDir . 'config.json';
        $defaultConfig = [
            'enabled' => true,
            'message' => null,
            'last_updated' => null
        ];
        
        if (!file_exists($configFile)) {
            $this->logger->logConfigCheck('CONFIG CHECK ERROR: Config file not found, using default (enabled=true)');
            return $defaultConfig;
        }
        
        // ... остальная логика из checkIndexPageConfig()
        
        return $defaultConfig;
    }
    
    // ... остальные методы
}
```

**Критерий:** Класс создан, все методы работают корректно, тесты проходят.

---

### Задача 1.4: Создание класса `Bitrix24ApiService`

**Файл:** `APP-B24/src/Services/Bitrix24ApiService.php`

**Назначение:** Работа с Bitrix24 REST API (вызовы методов, получение данных).

**Методы:**
- `call(string $method, array $params = []): array` — вызов метода API
- `getCurrentUser(string $authId, string $domain): array|null` — получение текущего пользователя
- `getUser(int $userId, string $authId, string $domain): array|null` — получение пользователя по ID
- `getDepartment(int $departmentId, string $authId, string $domain): array|null` — получение отдела
- `getAllDepartments(string $authId, string $domain): array` — получение всех отделов
- `getAllUsers(string $authId, string $domain, ?string $search = null): array` — получение всех пользователей
- `checkIsAdmin(string $authId, string $domain): bool` — проверка статуса администратора

**Источники кода:**
- Функция `getCurrentUserData()` из `index.php` (строки 305-380)
- Функция `getDepartmentData()` из `index.php` (строки 393-474)
- Функции из `access-control-functions.php`: `getAllDepartments()`, `getAllUsers()`, `checkIsAdmin()`

**Пример реализации:**
```php
<?php

namespace App\Services;

require_once(__DIR__ . '/../../crest.php');

/**
 * Сервис для работы с Bitrix24 REST API
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class Bitrix24ApiService
{
    protected LoggerService $logger;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Вызов метода Bitrix24 REST API
     * 
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @return array Ответ от API
     */
    public function call(string $method, array $params = []): array
    {
        $result = CRest::call($method, $params);
        
        if (isset($result['error'])) {
            $this->logger->logError('Bitrix24 API error', [
                'method' => $method,
                'error' => $result['error']
            ]);
        }
        
        return $result;
    }
    
    /**
     * Получение текущего пользователя
     * 
     * Метод: user.current
     * Документация: https://context7.com/bitrix24/rest/user.current
     * 
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Данные пользователя или null
     */
    public function getCurrentUser(string $authId, string $domain): ?array
    {
        if (empty($authId) || empty($domain)) {
            return null;
        }
        
        $url = 'https://' . $domain . '/rest/user.current.json';
        $requestParams = ['auth' => $authId];
        $params = http_build_query($requestParams);
        
        // ... логика из getCurrentUserData()
        
        return $result['result'] ?? null;
    }
    
    // ... остальные методы
}
```

**Критерий:** Класс создан, все методы работают корректно, тесты проходят.

---

### Задача 1.5: Создание класса `UserService`

**Файл:** `APP-B24/src/Services/UserService.php`

**Назначение:** Работа с пользователями (получение данных, проверка статуса).

**Методы:**
- `getCurrentUser(string $authId, string $domain): array|null` — получение текущего пользователя
- `getUserById(int $userId, string $authId, string $domain): array|null` — получение пользователя по ID
- `isAdmin(array $user, string $authId, string $domain): bool` — проверка статуса администратора
- `getUserDepartments(array $user): array` — получение отделов пользователя

**Источники кода:**
- Логика проверки администратора из `index.php` (строки 636-694)
- Логика получения отделов из `index.php` (строки 719-780)

**Пример реализации:**
```php
<?php

namespace App\Services;

/**
 * Сервис для работы с пользователями
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class UserService
{
    protected Bitrix24ApiService $apiService;
    protected LoggerService $logger;
    
    public function __construct(Bitrix24ApiService $apiService, LoggerService $logger)
    {
        $this->apiService = $apiService;
        $this->logger = $logger;
    }
    
    /**
     * Проверка статуса администратора
     * 
     * @param array $user Данные пользователя
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return bool true если администратор
     */
    public function isAdmin(array $user, string $authId, string $domain): bool
    {
        // Логика из index.php (строки 636-694)
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
        
        // Дополнительная проверка через user.admin
        return $this->apiService->checkIsAdmin($authId, $domain);
    }
    
    // ... остальные методы
}
```

**Критерий:** Класс создан, все методы работают корректно, тесты проходят.

---

### Задача 1.6: Создание класса `AccessControlService`

**Файл:** `APP-B24/src/Services/AccessControlService.php`

**Назначение:** Управление правами доступа (проверка, добавление/удаление отделов и пользователей).

**Методы:**
- `checkUserAccess(int $userId, array $userDepartments, string $authId, string $domain): bool` — проверка прав доступа
- `addDepartment(int $departmentId, string $departmentName, array $addedBy): array` — добавление отдела
- `removeDepartment(int $departmentId): array` — удаление отдела
- `addUser(int $userId, string $userName, string $userEmail, array $addedBy): array` — добавление пользователя
- `removeUser(int $userId): array` — удаление пользователя
- `toggleAccessControl(bool $enabled, array $updatedBy): array` — включение/выключение проверки

**Источники кода:**
- Все функции из `access-control-functions.php`:
  - `checkUserAccess()` (строки 102-200)
  - `addDepartmentToAccess()` (строки 202-280)
  - `removeDepartmentFromAccess()` (строки 282-360)
  - `addUserToAccess()` (строки 362-480)
  - `removeUserFromAccess()` (строки 482-560)
  - `toggleAccessControl()` (строки 562-640)

**Пример реализации:**
```php
<?php

namespace App\Services;

/**
 * Сервис для управления правами доступа
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class AccessControlService
{
    protected ConfigService $configService;
    protected Bitrix24ApiService $apiService;
    protected UserService $userService;
    protected LoggerService $logger;
    
    public function __construct(
        ConfigService $configService,
        Bitrix24ApiService $apiService,
        UserService $userService,
        LoggerService $logger
    ) {
        $this->configService = $configService;
        $this->apiService = $apiService;
        $this->userService = $userService;
        $this->logger = $logger;
    }
    
    /**
     * Проверка прав доступа пользователя
     * 
     * @param int $userId ID пользователя
     * @param array $userDepartments Массив ID отделов пользователя
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return bool true если доступ разрешён
     */
    public function checkUserAccess(int $userId, array $userDepartments, string $authId, string $domain): bool
    {
        // Получаем данные пользователя
        $user = $this->apiService->getCurrentUser($authId, $domain);
        
        if (!$user) {
            return false;
        }
        
        // Проверяем статус администратора
        if ($this->userService->isAdmin($user, $authId, $domain)) {
            $this->logger->logAccessCheck($userId, $userDepartments, 'granted', 'admin');
            return true;
        }
        
        // Получаем конфигурацию прав доступа
        $accessConfig = $this->configService->getAccessConfig();
        
        // ... остальная логика из checkUserAccess()
        
        return false;
    }
    
    // ... остальные методы
}
```

**Критерий:** Класс создан, все методы работают корректно, тесты проходят.

---

## Порядок выполнения

1. **Создать структуру директорий** (Задача 1.1)
2. **Создать LoggerService** (Задача 1.2) — базовый сервис, используется другими
3. **Создать ConfigService** (Задача 1.3) — используется другими сервисами
4. **Создать Bitrix24ApiService** (Задача 1.4) — используется другими сервисами
5. **Создать UserService** (Задача 1.5) — использует Bitrix24ApiService
6. **Создать AccessControlService** (Задача 1.6) — использует все предыдущие сервисы

---

## Тестирование

### Тест 1: LoggerService
```php
$logger = new LoggerService();
$logger->log('Test message', ['context' => 'test'], 'info');
// Проверить, что файл создан и содержит запись
```

### Тест 2: ConfigService
```php
$configService = new ConfigService($logger);
$config = $configService->getIndexPageConfig();
// Проверить структуру и значения
```

### Тест 3: Bitrix24ApiService
```php
$apiService = new Bitrix24ApiService($logger);
$user = $apiService->getCurrentUser($authId, $domain);
// Проверить, что данные пользователя получены
```

### Тест 4: UserService
```php
$userService = new UserService($apiService, $logger);
$isAdmin = $userService->isAdmin($user, $authId, $domain);
// Проверить корректность проверки
```

### Тест 5: AccessControlService
```php
$accessService = new AccessControlService($configService, $apiService, $userService, $logger);
$hasAccess = $accessService->checkUserAccess($userId, $departments, $authId, $domain);
// Проверить корректность проверки доступа
```

---

## Критерии приёмки этапа

- [ ] Все сервисы созданы в директории `APP-B24/src/Services/`
- [ ] Все методы сервисов работают корректно
- [ ] Нет дублирования кода между сервисами
- [ ] Все тесты проходят успешно
- [ ] Код соответствует стандартам PSR-12
- [ ] Добавлены PHPDoc комментарии ко всем методам
- [ ] Логирование работает через LoggerService

---

## Риски и митигация

### Риск 1: Зависимости между сервисами
**Митигация:** Создавать сервисы в правильном порядке (LoggerService → ConfigService → Bitrix24ApiService → UserService → AccessControlService)

### Риск 2: Потеря функциональности при переносе
**Митигация:** Тщательно копировать логику из исходных функций, тестировать каждый метод отдельно

### Риск 3: Проблемы с автозагрузкой классов
**Митигация:** Использовать require_once для подключения сервисов на первом этапе, позже добавить автозагрузку

---

## История правок

- **2025-12-20 20:30 (UTC+3, Брест):** Создан детальный план этапа 1

---

**Статус:** План готов к реализации  
**Дата создания:** 2025-12-20 20:30 (UTC+3, Брест)



