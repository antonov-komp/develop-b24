# Гайд по миграции на новую архитектуру

**Дата создания:** 2025-12-20 22:00 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Инструкции по миграции кода на новую архитектуру после рефакторинга

---

## Изменения в структуре

### Старая структура

```
APP-B24/
├── index.php                    # Вся логика и HTML в одном файле
├── access-control.php            # Вся логика и HTML в одном файле
├── token-analysis.php            # Вся логика и HTML в одном файле
├── access-control-functions.php  # Функции для работы с правами доступа
└── ...
```

### Новая структура

```
APP-B24/
├── src/
│   ├── Services/                 # Сервисы бизнес-логики
│   ├── Controllers/              # Контроллеры (MVC)
│   ├── Clients/                  # Клиенты для внешних API
│   ├── Exceptions/               # Исключения
│   ├── Helpers/                  # Вспомогательные классы
│   └── bootstrap.php             # Инициализация сервисов
├── templates/                    # HTML-шаблоны
│   ├── layout.php                # Базовый шаблон
│   ├── index.php                 # Шаблон главной страницы
│   └── ...
├── index.php                     # Точка входа (только инициализация)
├── access-control.php            # Точка входа (только инициализация)
└── token-analysis.php            # Точка входа (только инициализация)
```

---

## Миграция кода

### Использование сервисов

#### Было (старый подход):

```php
// Прямой вызов функции
$user = getCurrentUserData($authId, $domain);

// Прямой вызов CRest
$result = CRest::call('user.current', []);

// Прямая работа с конфигом
$config = json_decode(file_get_contents('config.json'), true);
```

#### Стало (новый подход):

```php
// Использование сервисов через bootstrap.php
require_once(__DIR__ . '/src/bootstrap.php');

// Получение пользователя через UserService
$user = $userService->getCurrentUser($authId, $domain);

// Вызов API через Bitrix24ApiService
$result = $apiService->call('user.current', []);

// Работа с конфигом через ConfigService
$config = $configService->getConfig();
```

### Использование контроллеров

#### Было (старый подход):

```php
// index.php - вся логика в одном файле
<?php
require_once('auth-check.php');
checkBitrix24Auth();

$authId = $_REQUEST['AUTH_ID'] ?? null;
$domain = $_REQUEST['DOMAIN'] ?? null;

$user = getCurrentUserData($authId, $domain);
// ... HTML код ...
?>
```

#### Стало (новый подход):

```php
// index.php - только инициализация контроллера
<?php
require_once(__DIR__ . '/src/bootstrap.php');
require_once(__DIR__ . '/src/Controllers/BaseController.php');
require_once(__DIR__ . '/src/Controllers/IndexController.php');

$controller = new App\Controllers\IndexController(
    $logger,
    $configService,
    $apiService,
    $userService,
    $accessControlService,
    $authService,
    $domainResolver,
    $adminChecker
);

$controller->index();
```

### Использование шаблонов

#### Было (старый подход):

```php
// HTML код прямо в PHP файле
echo '<h1>Привет, ' . htmlspecialchars($user['NAME']) . '</h1>';
```

#### Стало (новый подход):

```php
// В контроллере
$this->render('index', [
    'user' => $user,
    'title' => 'Главная страница'
]);

// В шаблоне templates/index.php
<h1>Привет, <?= htmlspecialchars($user['NAME']) ?></h1>
```

### Обработка ошибок

#### Было (старый подход):

```php
$result = CRest::call('user.current', []);
if (isset($result['error'])) {
    die('Ошибка: ' . $result['error_description']);
}
```

#### Стало (новый подход):

```php
try {
    $result = $apiService->call('user.current', []);
} catch (Bitrix24ApiException $e) {
    $this->logger->logError('Ошибка получения пользователя', [
        'error' => $e->getApiError(),
        'error_description' => $e->getApiErrorDescription()
    ]);
    // Обработка ошибки
}
```

---

## Примеры использования

### Пример 1: Получение данных пользователя

```php
// Старый подход
function getCurrentUserData($authId, $domain) {
    $url = 'https://' . $domain . '/rest/user.current.json';
    // ... cURL запрос ...
    return json_decode($response, true);
}

// Новый подход
$user = $userService->getCurrentUser($authId, $domain);
```

### Пример 2: Проверка прав доступа

```php
// Старый подход
function checkUserAccess($userId, $userDepartments, $authId, $domain) {
    $config = json_decode(file_get_contents('access-config.json'), true);
    // ... логика проверки ...
}

// Новый подход
$hasAccess = $accessControlService->checkUserAccess(
    $userId,
    $userDepartments,
    $authId,
    $domain
);
```

### Пример 3: Логирование

```php
// Старый подход
$logFile = 'logs/access-check-' . date('Y-m-d') . '.log';
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Новый подход
$logger->logAccessCheck($userId, $userDepartments, 'granted', 'admin');
```

---

## FAQ

### Вопрос: Как использовать новые сервисы?

**Ответ:** Все сервисы инициализируются в `bootstrap.php` и доступны как глобальные переменные. Просто подключите `bootstrap.php` и используйте нужные сервисы:

```php
require_once(__DIR__ . '/src/bootstrap.php');

// Используйте сервисы
$user = $userService->getCurrentUser($authId, $domain);
$config = $configService->getConfig();
```

### Вопрос: Что делать со старыми функциями?

**Ответ:** Старые функции из `access-control-functions.php` были перенесены в сервисы. Используйте соответствующие методы сервисов:

- `getCurrentUserData()` → `UserService::getCurrentUser()`
- `checkUserAccess()` → `AccessControlService::checkUserAccess()`
- `addDepartmentToAccess()` → `AccessControlService::addDepartment()`

### Вопрос: Как создать новый контроллер?

**Ответ:** Создайте класс, наследующийся от `BaseController`:

```php
<?php

namespace App\Controllers;

class MyController extends BaseController
{
    protected LoggerService $logger;
    
    public function __construct(LoggerService $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }
    
    public function index(): void
    {
        $this->render('my-template', [
            'title' => 'Моя страница'
        ]);
    }
}
```

### Вопрос: Как добавить новый сервис?

**Ответ:** Создайте класс в `src/Services/` и добавьте инициализацию в `bootstrap.php`:

```php
// src/Services/MyService.php
<?php

namespace App\Services;

class MyService
{
    protected LoggerService $logger;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }
    
    public function doSomething(): void
    {
        $this->logger->log('Doing something', [], 'info');
    }
}

// src/bootstrap.php
require_once(__DIR__ . '/Services/MyService.php');
$myService = new App\Services\MyService($logger);
```

### Вопрос: Как обрабатывать ошибки API?

**Ответ:** Используйте исключения:

```php
try {
    $result = $apiService->call('user.current', []);
} catch (Bitrix24ApiException $e) {
    // Обработка ошибки API
    $error = $e->getApiError();
    $description = $e->getApiErrorDescription();
}
```

### Вопрос: Как логировать операции?

**Ответ:** Используйте `LoggerService`:

```php
// Общее логирование
$logger->log('Операция выполнена', ['user_id' => 123], 'info');

// Логирование ошибок
$logger->logError('Ошибка операции', ['error' => 'details']);

// Специализированное логирование
$logger->logAccessCheck($userId, $departments, 'granted', 'admin');
```

---

## Обратная совместимость

Старые функции из `access-control-functions.php` **не удалены** для обратной совместимости, но рекомендуется использовать новые сервисы.

---

## Полезные ссылки

- [Архитектура приложения](../ARCHITECTURE/application-architecture.md)
- [Технологический стек](../ARCHITECTURE/tech-stack.md)
- [Структура API](../ARCHITECTURE/api-structure.md)

---

**Дата создания:** 2025-12-20 22:00 (UTC+3, Брест)


