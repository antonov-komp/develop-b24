# Bitrix24 REST Приложение

**Версия:** 3.0  
**Дата обновления:** 2025-12-20 22:00 (UTC+3, Брест)

---

## Описание

REST-приложение для Bitrix24 с системой управления правами доступа, анализом токенов и защитой от прямого доступа.

---

## Структура проекта

```
APP-B24/
├── src/                          # Исходный код приложения
│   ├── Services/                  # Сервисы бизнес-логики
│   ├── Controllers/               # Контроллеры (MVC)
│   ├── Clients/                   # Клиенты для внешних API
│   ├── Exceptions/                # Исключения
│   ├── Helpers/                   # Вспомогательные классы
│   └── bootstrap.php              # Инициализация сервисов
├── templates/                     # HTML-шаблоны
├── index.php                      # Главная страница
├── access-control.php             # Управление правами доступа
├── token-analysis.php             # Анализ токена
└── logs/                          # Логи приложения
```

---

## Установка и настройка

### Требования

- PHP 8.4+
- Расширения: `curl`, `json`, `openssl`
- Доступ к Bitrix24 REST API

### Настройка

1. Скопируйте файлы приложения на сервер
2. Настройте `settings.php` с вашими данными Bitrix24:
   ```php
   define('C_REST_CLIENT_ID', 'your_client_id');
   define('C_REST_CLIENT_SECRET', 'your_client_secret');
   ```
3. Установите приложение в Bitrix24 через `install.php`
4. Настройте права доступа через `access-control.php` (только для администраторов)

---

## Использование

### Главная страница

Откройте приложение в Bitrix24 через iframe или напрямую по URL:
```
https://your-domain.com/APP-B24/index.php?AUTH_ID=...&DOMAIN=...
```

### Управление правами доступа

Доступно только для администраторов Bitrix24:
```
https://your-domain.com/APP-B24/access-control.php?AUTH_ID=...&DOMAIN=...
```

### Анализ токена

Доступно только для администраторов Bitrix24:
```
https://your-domain.com/APP-B24/token-analysis.php?AUTH_ID=...&DOMAIN=...
```

---

## Использование сервисов

### Пример: Получение данных пользователя

```php
require_once(__DIR__ . '/src/bootstrap.php');

$user = $userService->getCurrentUser($authId, $domain);
if ($user) {
    echo 'Пользователь: ' . $user['NAME'];
}
```

### Пример: Проверка прав доступа

```php
require_once(__DIR__ . '/src/bootstrap.php');

$hasAccess = $accessControlService->checkUserAccess(
    $userId,
    $userDepartments,
    $authId,
    $domain
);

if ($hasAccess) {
    echo 'Доступ разрешён';
}
```

### Пример: Вызов Bitrix24 API

```php
require_once(__DIR__ . '/src/bootstrap.php');

try {
    $result = $apiService->call('user.current', []);
    $user = $result['result'] ?? null;
} catch (Bitrix24ApiException $e) {
    echo 'Ошибка API: ' . $e->getMessage();
}
```

---

## Сервисы

### LoggerService

Унифицированное логирование:

```php
$logger->log('Сообщение', ['context' => 'data'], 'info');
$logger->logError('Ошибка', ['error' => 'details']);
$logger->logAccessCheck($userId, $departments, 'granted', 'admin');
```

### ConfigService

Работа с конфигурационными файлами:

```php
$config = $configService->getConfig();
$accessConfig = $configService->getAccessConfig();
$settings = $configService->getSettings();
```

### Bitrix24ApiService

Работа с Bitrix24 REST API:

```php
$result = $apiService->call('user.current', []);
$user = $apiService->getCurrentUser($authId, $domain);
$departments = $apiService->getAllDepartments($authId, $domain);
```

### UserService

Работа с пользователями:

```php
$user = $userService->getCurrentUser($authId, $domain);
$isAdmin = $userService->isAdmin($user, $authId, $domain);
$departments = $userService->getUserDepartments($user);
```

### AccessControlService

Управление правами доступа:

```php
$hasAccess = $accessControlService->checkUserAccess(...);
$result = $accessControlService->addDepartment($deptId, $deptName, $addedBy);
$result = $accessControlService->addUser($userId, $userName, $userEmail, $addedBy);
```

### AuthService

Проверка авторизации:

```php
$isValid = $authService->checkAuth($isFromBitrix24);
$isFromBitrix24 = $authService->isRequestFromBitrix24();
```

---

## Контроллеры

### Создание нового контроллера

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
            'title' => 'Моя страница',
            'data' => []
        ]);
    }
}
```

### Использование контроллера

```php
require_once(__DIR__ . '/src/bootstrap.php');
require_once(__DIR__ . '/src/Controllers/MyController.php');

$controller = new App\Controllers\MyController($logger);
$controller->index();
```

---

## Документация

- [Архитектура приложения](../../DOCS/ARCHITECTURE/application-architecture.md)
- [Технологический стек](../../DOCS/ARCHITECTURE/tech-stack.md)
- [Структура API](../../DOCS/ARCHITECTURE/api-structure.md)
- [Гайд по миграции](../../DOCS/GUIDES/refactoring-migration-guide.md)

---

## Логирование

Логи сохраняются в директории `logs/`:

- `access-check-*.log` — проверка прав доступа
- `access-control-*.log` — управление правами доступа
- `auth-check-*.log` — проверка авторизации
- `config-check-*.log` — проверка конфигурации
- `token-analysis-*.log` — анализ токена

---

## Безопасность

- Защита от прямого доступа через `AuthService`
- Валидация всех входящих данных
- Логирование всех операций
- Секреты хранятся в `settings.php` (не коммитить в Git)

---

## Поддержка

При возникновении проблем проверьте:

1. Логи в директории `logs/`
2. Настройки в `settings.php`
3. Конфигурацию в `config.json` и `access-config.json`
4. Документацию Bitrix24 REST API: https://context7.com/bitrix24/rest/

---

**Дата создания:** 2025-12-20 22:00 (UTC+3, Брест)

