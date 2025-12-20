# Этап 3: Разделение логики и представления

**Дата создания:** 2025-12-20 20:30 (UTC+3, Брест)  
**Версия:** 1.0  
**Статус:** План  
**Приоритет:** Высокий  
**Оценка времени:** 2-3 дня

---

## Цель этапа

Выделить HTML-шаблоны из PHP-файлов и создать контроллеры для обработки логики страниц. Разделить бизнес-логику и представление.

**Результат:** Четкое разделение логики и представления, переиспользуемые шаблоны, упрощенная поддержка и изменение интерфейса.

---

## Зависимости

**Требуется завершение:** Этап 2 (Рефакторинг функций в классы)

**Используемые сервисы:**
- Все сервисы из предыдущих этапов
- Новые контроллеры

---

## Задачи этапа

### Задача 3.1: Создание структуры директорий для шаблонов

**Действия:**
1. Создать директорию `APP-B24/templates/`
2. Убедиться, что директория доступна для записи

**Проверка:**
```bash
mkdir -p /var/www/backend.antonov-mark.ru/APP-B24/templates
chmod 775 /var/www/backend.antonov-mark.ru/APP-B24/templates
```

**Критерий:** Директория создана и доступна для записи.

---

### Задача 3.2: Создание базового шаблона `layout.php`

**Файл:** `APP-B24/templates/layout.php`

**Назначение:** Базовый шаблон с общими элементами (HTML-структура, стили, скрипты).

**Структура:**
```php
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Bitrix24 Приложение' ?></title>
    <style>
        /* Общие стили */
    </style>
</head>
<body>
    <?= $content ?? '' ?>
</body>
</html>
```

**Критерий:** Базовый шаблон создан, содержит общую структуру HTML.

---

### Задача 3.3: Создание базового контроллера `BaseController`

**Файл:** `APP-B24/src/Controllers/BaseController.php`

**Назначение:** Базовый класс для всех контроллеров с общими методами.

**Методы:**
- `render(string $template, array $data = []): void` — рендеринг шаблона
- `redirect(string $url): void` — редирект
- `json(array $data): void` — вывод JSON
- `getRequestParam(string $key, $default = null)` — получение параметра запроса

**Пример реализации:**
```php
<?php

namespace App\Controllers;

/**
 * Базовый контроллер для всех контроллеров приложения
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class BaseController
{
    protected string $templatesDir;
    
    public function __construct()
    {
        $this->templatesDir = __DIR__ . '/../../templates/';
    }
    
    /**
     * Рендеринг шаблона
     * 
     * @param string $template Имя шаблона (без расширения)
     * @param array $data Данные для передачи в шаблон
     */
    protected function render(string $template, array $data = []): void
    {
        $templateFile = $this->templatesDir . $template . '.php';
        
        if (!file_exists($templateFile)) {
            throw new \Exception("Template not found: {$template}");
        }
        
        // Извлекаем переменные из массива $data
        extract($data);
        
        // Начинаем буферизацию вывода
        ob_start();
        include $templateFile;
        $content = ob_get_clean();
        
        // Рендерим базовый шаблон
        $layoutFile = $this->templatesDir . 'layout.php';
        if (file_exists($layoutFile)) {
            extract(['content' => $content] + $data);
            include $layoutFile;
        } else {
            echo $content;
        }
    }
    
    /**
     * Редирект на указанный URL
     * 
     * @param string $url URL для редиректа
     */
    protected function redirect(string $url): void
    {
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Location: ' . $url, true, 302);
        exit;
    }
    
    /**
     * Вывод JSON-ответа
     * 
     * @param array $data Данные для вывода
     */
    protected function json(array $data): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Получение параметра запроса
     * 
     * @param string $key Ключ параметра
     * @param mixed $default Значение по умолчанию
     * @return mixed Значение параметра
     */
    protected function getRequestParam(string $key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }
}
```

**Критерий:** Базовый контроллер создан, все методы работают корректно.

---

### Задача 3.4: Создание контроллера `IndexController`

**Файл:** `APP-B24/src/Controllers/IndexController.php`

**Назначение:** Обработка логики главной страницы.

**Методы:**
- `index(): void` — отображение главной страницы

**Источники кода:**
- Логика из `index.php` (вся логика до HTML)

**Пример реализации:**
```php
<?php

namespace App\Controllers;

use App\Services\LoggerService;
use App\Services\ConfigService;
use App\Services\Bitrix24ApiService;
use App\Services\UserService;
use App\Services\AccessControlService;
use App\Services\AuthService;
use App\Helpers\DomainResolver;
use App\Helpers\AdminChecker;

/**
 * Контроллер главной страницы
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class IndexController extends BaseController
{
    protected LoggerService $logger;
    protected ConfigService $configService;
    protected Bitrix24ApiService $apiService;
    protected UserService $userService;
    protected AccessControlService $accessControlService;
    protected AuthService $authService;
    protected DomainResolver $domainResolver;
    protected AdminChecker $adminChecker;
    
    public function __construct(
        LoggerService $logger,
        ConfigService $configService,
        Bitrix24ApiService $apiService,
        UserService $userService,
        AccessControlService $accessControlService,
        AuthService $authService,
        DomainResolver $domainResolver,
        AdminChecker $adminChecker
    ) {
        parent::__construct();
        
        $this->logger = $logger;
        $this->configService = $configService;
        $this->apiService = $apiService;
        $this->userService = $userService;
        $this->accessControlService = $accessControlService;
        $this->authService = $authService;
        $this->domainResolver = $domainResolver;
        $this->adminChecker = $adminChecker;
    }
    
    /**
     * Отображение главной страницы
     */
    public function index(): void
    {
        // Проверка авторизации
        if (!$this->authService->checkBitrix24Auth()) {
            $this->authService->redirectToFailure();
            return;
        }
        
        // Проверка конфигурации доступа
        $isFromBitrix24 = $this->authService->isRequestFromBitrix24();
        if (!$isFromBitrix24) {
            $indexConfig = $this->configService->getIndexPageConfig();
            if (!$indexConfig['enabled']) {
                // Редирект на страницу ошибки конфигурации
                $this->redirect('config-error.php?message=' . urlencode($indexConfig['message'] ?? ''));
                return;
            }
        }
        
        // Получение данных пользователя
        $currentUserAuthId = $this->getRequestParam('AUTH_ID');
        $portalDomain = $this->domainResolver->resolveDomain();
        
        $user = null;
        if ($currentUserAuthId && $portalDomain) {
            $user = $this->apiService->getCurrentUser($currentUserAuthId, $portalDomain);
        }
        
        if (!$user) {
            die('<h1>Ошибка: данные пользователя не получены</h1>');
        }
        
        // Проверка статуса администратора
        $isAdmin = $this->adminChecker->check($user, $currentUserAuthId, $portalDomain);
        
        // Получение данных об отделе
        $departmentId = null;
        $departmentName = null;
        if (isset($user['UF_DEPARTMENT']) && is_array($user['UF_DEPARTMENT']) && !empty($user['UF_DEPARTMENT'])) {
            $departmentId = (int)$user['UF_DEPARTMENT'][0];
            if ($departmentId > 0) {
                $department = $this->apiService->getDepartment($departmentId, $currentUserAuthId, $portalDomain);
                if ($department) {
                    $departmentName = $department['NAME'] ?? null;
                }
            }
        }
        
        // Формирование данных для шаблона
        $data = [
            'title' => 'Приветствие - Bitrix24 Приложение',
            'user' => $user,
            'userFullName' => trim(($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? '')),
            'isAdmin' => $isAdmin,
            'adminStatus' => $isAdmin ? 'Администратор на портале' : 'Пользователь',
            'portalDomain' => $portalDomain,
            'departmentId' => $departmentId,
            'departmentName' => $departmentName,
            'userPhoto' => $user['PERSONAL_PHOTO'] ?? null,
            'isCurrentUserToken' => !empty($currentUserAuthId)
        ];
        
        // Рендеринг шаблона
        $this->render('index', $data);
    }
}
```

**Критерий:** Контроллер создан, логика вынесена из `index.php`.

---

### Задача 3.5: Выделение HTML из `index.php` в шаблон

**Файл:** `APP-B24/templates/index.php`

**Назначение:** HTML-шаблон главной страницы.

**Источники кода:**
- HTML из `index.php` (строки 783-1124)

**Пример реализации:**
```php
<?php
/**
 * Шаблон главной страницы
 * 
 * Переменные:
 * - $user - данные пользователя
 * - $userFullName - полное имя пользователя
 * - $isAdmin - статус администратора
 * - $adminStatus - текст статуса
 * - $portalDomain - домен портала
 * - $departmentId - ID отдела
 * - $departmentName - название отдела
 * - $userPhoto - фото пользователя
 * - $isCurrentUserToken - используется ли токен текущего пользователя
 */
?>

<div class="welcome-container">
    <div class="welcome-header">
        <h1 class="welcome-title">Добро пожаловать!</h1>
        <?php if ($userPhoto): ?>
            <img src="<?= htmlspecialchars($userPhoto) ?>" alt="Фото пользователя" class="user-photo">
        <?php endif; ?>
        <div class="user-name"><?= htmlspecialchars($userFullName) ?></div>
    </div>
    
    <div class="user-info">
        <div class="info-row">
            <span class="info-label">ID пользователя:</span>
            <span class="info-value">#<?= htmlspecialchars($user['ID']) ?></span>
        </div>
        
        <div class="info-row">
            <span class="info-label">Статус:</span>
            <span class="info-value">
                <?php if ($isAdmin): ?>
                    <span class="admin-badge"><?= htmlspecialchars($adminStatus) ?></span>
                <?php else: ?>
                    <span class="user-badge"><?= htmlspecialchars($adminStatus) ?></span>
                <?php endif; ?>
            </span>
        </div>
        
        <?php if (isset($user['EMAIL']) && !empty($user['EMAIL'])): ?>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value"><?= htmlspecialchars($user['EMAIL']) ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($departmentId): ?>
        <div class="info-row">
            <span class="info-label">Отдел:</span>
            <span class="info-value">
                <?php if ($departmentName): ?>
                    <?= htmlspecialchars($departmentName) ?> (ID: <?= htmlspecialchars($departmentId) ?>)
                <?php else: ?>
                    ID: <?= htmlspecialchars($departmentId) ?>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="domain-info">
        <div class="domain-label">Домен портала:</div>
        <div class="domain-value"><?= htmlspecialchars($portalDomain) ?></div>
    </div>
    
    <div class="footer">
        <p>Приложение успешно авторизовано и готово к работе</p>
        <?php if (!$isCurrentUserToken): ?>
            <p style="color: #f5576c; margin-top: 10px; font-size: 11px;">
                ⚠️ Используется токен установщика (владельца приложения).
            </p>
        <?php else: ?>
            <p style="color: #28a745; margin-top: 10px; font-size: 11px;">
                ✓ Используется токен текущего пользователя
            </p>
        <?php endif; ?>
        
        <?php if ($isAdmin): ?>
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef; display: flex; gap: 15px; flex-wrap: wrap;">
            <form method="POST" action="token-analysis.php">
                <?php if (!empty($_REQUEST['AUTH_ID'])): ?>
                    <input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($_REQUEST['AUTH_ID']) ?>">
                <?php endif; ?>
                <?php if (!empty($_REQUEST['DOMAIN'])): ?>
                    <input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($_REQUEST['DOMAIN']) ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">
                    🔍 Анализ токена и прав доступа
                </button>
            </form>
            <form method="POST" action="access-control.php">
                <?php if (!empty($_REQUEST['AUTH_ID'])): ?>
                    <input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($_REQUEST['AUTH_ID']) ?>">
                <?php endif; ?>
                <?php if (!empty($_REQUEST['DOMAIN'])): ?>
                    <input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($_REQUEST['DOMAIN']) ?>">
                <?php endif; ?>
                <button type="submit" class="btn btn-secondary">
                    🔐 Управление правами доступа
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Стили из index.php */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    /* ... остальные стили */
</style>
```

**Критерий:** Шаблон создан, HTML вынесен из `index.php`.

---

### Задача 3.6: Обновление `index.php` для использования контроллера

**Файл:** `APP-B24/index.php` (или `APP-B24/public/index.php`)

**Изменения:**
1. Подключить все необходимые сервисы и контроллер
2. Создать экземпляр контроллера
3. Вызвать метод `index()`

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
require_once(__DIR__ . '/src/Controllers/IndexController.php');

// Инициализация сервисов
$logger = new App\Services\LoggerService();
$configService = new App\Services\ConfigService($logger);
$apiService = new App\Services\Bitrix24ApiService($logger);
$userService = new App\Services\UserService($apiService, $logger);
$accessControlService = new App\Services\AccessControlService($configService, $apiService, $userService, $logger);
$authService = new App\Services\AuthService($configService, $accessControlService, $logger);
$domainResolver = new App\Helpers\DomainResolver($configService);
$adminChecker = new App\Helpers\AdminChecker($apiService);

// Создание контроллера
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

// Вызов метода контроллера
$controller->index();
```

**Критерий:** `index.php` использует контроллер, код стал короче и понятнее.

---

### Задача 3.7: Создание контроллера `TokenAnalysisController`

**Файл:** `APP-B24/src/Controllers/TokenAnalysisController.php`

**Назначение:** Обработка логики страницы анализа токена.

**Методы:**
- `index(): void` — отображение страницы анализа токена

**Источники кода:**
- Логика из `token-analysis.php` (вся логика до HTML)

**Критерий:** Контроллер создан, логика вынесена из `token-analysis.php`.

---

### Задача 3.8: Выделение HTML из `token-analysis.php` в шаблон

**Файл:** `APP-B24/templates/token-analysis.php`

**Назначение:** HTML-шаблон страницы анализа токена.

**Источники кода:**
- HTML из `token-analysis.php`

**Критерий:** Шаблон создан, HTML вынесен из `token-analysis.php`.

---

### Задача 3.9: Создание контроллера `AccessControlController`

**Файл:** `APP-B24/src/Controllers/AccessControlController.php`

**Назначение:** Обработка логики страницы управления правами доступа.

**Методы:**
- `index(): void` — отображение страницы управления правами
- `addDepartment(): void` — добавление отдела
- `removeDepartment(): void` — удаление отдела
- `addUser(): void` — добавление пользователя
- `removeUser(): void` — удаление пользователя
- `toggleAccessControl(): void` — включение/выключение проверки

**Источники кода:**
- Логика из `access-control.php` (вся логика до HTML)

**Критерий:** Контроллер создан, логика вынесена из `access-control.php`.

---

### Задача 3.10: Выделение HTML из `access-control.php` в шаблон

**Файл:** `APP-B24/templates/access-control.php`

**Назначение:** HTML-шаблон страницы управления правами доступа.

**Источники кода:**
- HTML из `access-control.php`

**Критерий:** Шаблон создан, HTML вынесен из `access-control.php`.

---

### Задача 3.11: Создание шаблонов для страниц ошибок

**Файлы:**
- `APP-B24/templates/failure.php` — страница ошибки доступа
- `APP-B24/templates/config-error.php` — страница ошибки конфигурации

**Источники кода:**
- HTML из `failure.php`
- HTML из `config-error.php`

**Критерий:** Шаблоны созданы, HTML вынесен из файлов.

---

## Порядок выполнения

1. **Создать структуру директорий** (Задача 3.1)
2. **Создать базовый шаблон** (Задача 3.2)
3. **Создать BaseController** (Задача 3.3)
4. **Создать IndexController** (Задача 3.4)
5. **Выделить HTML из index.php** (Задача 3.5)
6. **Обновить index.php** (Задача 3.6)
7. **Создать TokenAnalysisController** (Задача 3.7)
8. **Выделить HTML из token-analysis.php** (Задача 3.8)
9. **Создать AccessControlController** (Задача 3.9)
10. **Выделить HTML из access-control.php** (Задача 3.10)
11. **Создать шаблоны для страниц ошибок** (Задача 3.11)

---

## Тестирование

### Тест 1: BaseController
```php
$controller = new BaseController();
$controller->render('test', ['title' => 'Test']);
// Проверить, что шаблон отрендерился корректно
```

### Тест 2: IndexController
- Проверить, что главная страница отображается корректно
- Проверить, что все данные передаются в шаблон
- Проверить, что интерфейс выглядит идентично

### Тест 3: TokenAnalysisController
- Проверить, что страница анализа токена работает корректно

### Тест 4: AccessControlController
- Проверить, что страница управления правами работает корректно

---

## Критерии приёмки этапа

- [ ] Все HTML вынесены в шаблоны
- [ ] Созданы контроллеры для всех страниц
- [ ] Базовый контроллер работает корректно
- [ ] Весь функционал работает идентично до рефакторинга
- [ ] Интерфейс выглядит идентично
- [ ] Код соответствует стандартам PSR-12
- [ ] Все тесты проходят успешно

---

## Риски и митигация

### Риск 1: Потеря стилей или функциональности
**Митигация:** Тщательно копировать все стили и скрипты в шаблоны, тестировать каждый шаблон отдельно

### Риск 2: Проблемы с передачей данных в шаблоны
**Митигация:** Использовать extract() для передачи данных, тестировать каждый шаблон с разными данными

### Риск 3: Нарушение работы форм
**Митигация:** Сохранить все скрытые поля и атрибуты форм, тестировать отправку форм

---

## История правок

- **2025-12-20 20:30 (UTC+3, Брест):** Создан детальный план этапа 3

---

**Статус:** План готов к реализации  
**Дата создания:** 2025-12-20 20:30 (UTC+3, Брест)


