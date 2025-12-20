# Миграция с CRest на b24phpsdk

**Дата создания:** 2025-12-20 19:45 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** План миграции с библиотеки CRest на официальный Bitrix24 PHP SDK (b24phpsdk)

---

## Обоснование миграции

### Проблемы CRest

1. **Устаревшая библиотека:**
   - Версия 1.36 (последняя версия)
   - Нет активной поддержки
   - Ограниченная функциональность

2. **Отсутствие автокомплита:**
   - Нет поддержки IDE автодополнения
   - Сложно работать без документации под рукой
   - Высокий риск ошибок в названиях методов

3. **Ручное управление токенами:**
   - Необходимость ручного обновления токенов
   - Сложная логика работы с refresh_token
   - Нет автоматического управления сессиями

4. **Ограниченная типизация:**
   - Нет типизации данных
   - Сложно работать с большими объемами данных
   - Нет валидации на уровне библиотеки

### Преимущества b24phpsdk

1. **Официальная библиотека:**
   - Активная поддержка от Bitrix24
   - Регулярные обновления
   - Соответствие актуальным версиям API

2. **Автокомплит и типизация:**
   - Полная поддержка IDE автодополнения
   - Типизированные методы и параметры
   - Улучшенная отладка

3. **Автоматическое управление токенами:**
   - Автоматическое обновление токенов
   - Управление сессиями
   - Обработка ошибок авторизации

4. **Объектно-ориентированный подход:**
   - Удобный API
   - Переиспользуемые компоненты
   - Лучшая структура кода

5. **Поддержка batch-запросов:**
   - Эффективная работа с большими объемами данных
   - Экономное использование памяти
   - Оптимизация производительности

---

## Текущее использование CRest

### Места использования

1. **Bitrix24Client** (`src/Clients/Bitrix24Client.php`):
   - `\CRest::call($method, $params)` — вызовы API
   - `\CRest::callBatch($commands, $halt)` — batch-запросы

2. **Bitrix24ApiService** (`src/Services/Bitrix24ApiService.php`):
   - Прямые вызовы через curl для `user.current`
   - Использование CRest через Bitrix24Client

3. **install.php**:
   - `CRest::installApp()` — установка приложения

4. **Другие контроллеры:**
   - IndexController
   - AccessControlController
   - TokenAnalysisController
   - AuthService

### Текущая архитектура

```
Bitrix24Client (обертка над CRest)
    ↓
CRest::call() / CRest::callBatch()
    ↓
Bitrix24 REST API
```

---

## План миграции

### Этап 1: Установка b24phpsdk

#### 1.1. Установка через Composer

**Создать `composer.json` (если еще нет):**

```json
{
    "name": "bitrix24/rest-app",
    "description": "REST приложение Bitrix24",
    "type": "project",
    "require": {
        "php": ">=8.4",
        "bitrix24/b24phpsdk": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    }
}
```

**Установить зависимости:**
```bash
cd APP-B24
composer install
```

#### 1.2. Настройка автозагрузки

**Обновить `src/bootstrap.php`:**

```php
<?php
// Автозагрузка Composer
require_once(__DIR__ . '/../vendor/autoload.php');

// ... остальной код инициализации
```

### Этап 2: Создание нового Bitrix24Client на b24phpsdk

#### 2.1. Новая структура клиента

**Создать `src/Clients/Bitrix24SdkClient.php`:**

```php
<?php

namespace App\Clients;

use Bitrix24\SDK\Core\ApiClient;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Core\Credentials\Credentials;
use Bitrix24\SDK\Core\Credentials\WebhookUrl;
use Bitrix24\SDK\Core\Exceptions\BaseException;
use App\Services\LoggerService;
use App\Exceptions\Bitrix24ApiException;

/**
 * Клиент для работы с Bitrix24 REST API через официальный SDK
 * 
 * Замена для CRest с использованием b24phpsdk
 * Документация: https://github.com/bitrix24/b24phpsdk
 */
class Bitrix24SdkClient implements ApiClientInterface
{
    protected ApiClient $apiClient;
    protected LoggerService $logger;
    protected ?string $authId = null;
    protected ?string $domain = null;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Инициализация клиента с токеном установщика
     * 
     * @param string|null $accessToken Токен доступа
     * @param string|null $domain Домен портала
     * @return void
     */
    public function initializeWithInstallerToken(?string $accessToken = null, ?string $domain = null): void
    {
        // Получаем настройки из settings.json
        $settings = $this->getSettings();
        
        $token = $accessToken ?? $settings['access_token'] ?? null;
        $portalDomain = $domain ?? $settings['domain'] ?? null;
        
        if (!$token || !$portalDomain) {
            throw new Bitrix24ApiException('Access token or domain not found');
        }
        
        $this->authId = $token;
        $this->domain = $portalDomain;
        
        // Создаем API клиент с токеном установщика
        // Важно: ApplicationProfile требует токен и домен
        // Домен должен быть без протокола (например: "portal.bitrix24.ru", а не "https://portal.bitrix24.ru")
        $portalDomain = preg_replace('#^https?://#', '', $portalDomain);
        $portalDomain = rtrim($portalDomain, '/');
        
        try {
            $credentials = new Credentials(
                new ApplicationProfile(
                    $token,
                    $portalDomain
                )
            );
            
            $this->apiClient = new ApiClient($credentials);
        } catch (\Exception $e) {
            $this->logger->logError('Failed to initialize SDK client', [
                'exception' => $e->getMessage(),
                'domain' => $portalDomain,
                'token_length' => strlen($token)
            ]);
            throw new Bitrix24ApiException(
                "Failed to initialize SDK client: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Инициализация клиента с токеном текущего пользователя
     * 
     * @param string $authId Токен текущего пользователя
     * @param string $domain Домен портала
     * @return void
     */
    public function initializeWithUserToken(string $authId, string $domain): void
    {
        // Валидация параметров
        if (empty($authId) || empty($domain)) {
            throw new Bitrix24ApiException('Auth ID and domain are required');
        }
        
        $this->authId = $authId;
        
        // Очистка домена от протокола и слешей
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        $this->domain = $domain;
        
        // Создаем API клиент с токеном пользователя
        // Важно: для токена пользователя используется тот же ApplicationProfile
        try {
            $credentials = new Credentials(
                new ApplicationProfile(
                    $authId,
                    $domain
                )
            );
            
            $this->apiClient = new ApiClient($credentials);
        } catch (\Exception $e) {
            $this->logger->logError('Failed to initialize SDK client with user token', [
                'exception' => $e->getMessage(),
                'domain' => $domain,
                'auth_id_length' => strlen($authId)
            ]);
            throw new Bitrix24ApiException(
                "Failed to initialize SDK client with user token: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Инициализация клиента с вебхуком
     * 
     * @param string $webhookUrl URL вебхука
     * @return void
     */
    public function initializeWithWebhook(string $webhookUrl): void
    {
        // Валидация URL вебхука
        if (empty($webhookUrl)) {
            throw new Bitrix24ApiException('Webhook URL is required');
        }
        
        // Проверка формата URL
        if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            throw new Bitrix24ApiException('Invalid webhook URL format');
        }
        
        // Проверка, что это URL Bitrix24
        if (strpos($webhookUrl, 'bitrix24') === false && strpos($webhookUrl, '/rest/') === false) {
            $this->logger->log('Warning: Webhook URL does not look like Bitrix24 URL', [
                'url' => $webhookUrl
            ], 'warning');
        }
        
        try {
            $credentials = new Credentials(
                new WebhookUrl($webhookUrl)
            );
            
            $this->apiClient = new ApiClient($credentials);
        } catch (\Exception $e) {
            $this->logger->logError('Failed to initialize SDK client with webhook', [
                'exception' => $e->getMessage(),
                'webhook_url' => substr($webhookUrl, 0, 50) . '...' // Частичное скрытие URL
            ]);
            throw new Bitrix24ApiException(
                "Failed to initialize SDK client with webhook: {$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Вызов метода Bitrix24 REST API
     * 
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @return array Ответ от API
     * @throws Bitrix24ApiException При ошибке API
     */
    public function call(string $method, array $params = []): array
    {
        if (!$this->apiClient) {
            // Автоматическая инициализация с токеном установщика
            $this->initializeWithInstallerToken();
        }
        
        $startTime = microtime(true);
        
        $this->logger->log('Bitrix24 API call (SDK)', [
            'method' => $method,
            'params' => $this->sanitizeParams($params)
        ], 'info');
        
        try {
            // Вызов через SDK
            // Важно: SDK возвращает объект Response, а не массив
            $response = $this->apiClient->call($method, $params);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Преобразование ответа SDK в формат, совместимый с CRest
            // SDK использует объект Response с методами getResponseData()
            $responseData = $response->getResponseData();
            
            $result = [
                'result' => $responseData->getResult(),
                'total' => $responseData->getTotal() ?? null,
                'next' => $responseData->getNext() ?? null,
            ];
            
            // Дополнительная информация из SDK (если доступна)
            if (method_exists($responseData, 'getTime')) {
                $result['time'] = $responseData->getTime();
            }
            
            $this->logger->log('Bitrix24 API success (SDK)', [
                'method' => $method,
                'execution_time_ms' => $executionTime,
                'response_size' => strlen(json_encode($result))
            ], 'info');
            
            return $result;
        } catch (BaseException $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger->logError('Bitrix24 API exception (SDK)', [
                'method' => $method,
                'exception' => $e->getMessage(),
                'execution_time_ms' => $executionTime
            ]);
            
            throw new Bitrix24ApiException(
                "API call failed: {$method}",
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Батч-запросы к Bitrix24 REST API
     * 
     * @param array $commands Массив команд для выполнения
     * @param int $halt Остановка при ошибке (0 или 1)
     * @return array Ответ от API
     * @throws Bitrix24ApiException При ошибке API
     */
    public function callBatch(array $commands, int $halt = 0): array
    {
        if (!$this->apiClient) {
            $this->initializeWithInstallerToken();
        }
        
        $startTime = microtime(true);
        
        $this->logger->log('Bitrix24 API batch call (SDK)', [
            'commands_count' => count($commands),
            'halt' => $halt
        ], 'info');
        
        try {
            // Преобразуем команды в формат SDK
            // Важно: SDK может требовать другой формат команд
            // Проверить документацию SDK для актуального формата
            $batchCommands = [];
            foreach ($commands as $key => $command) {
                // Формат команды для SDK
                $batchCommands[$key] = [
                    'method' => $command['method'],
                    'params' => $command['params'] ?? []
                ];
            }
            
            // Вызов batch через SDK
            // Параметр halt: true = остановка при ошибке, false = продолжение
            $response = $this->apiClient->batch($batchCommands, $halt === 1);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Получение результата из ответа SDK
            $responseData = $response->getResponseData();
            $result = $responseData->getResult();
            
            // Обработка ошибок в batch (если halt = 0)
            if (!$halt && isset($result['result_error'])) {
                // Логирование ошибок в batch, но не прерываем выполнение
                $this->logger->log('Bitrix24 API batch partial errors', [
                    'errors' => $result['result_error']
                ], 'warning');
            }
            
            $this->logger->log('Bitrix24 API batch success (SDK)', [
                'commands_count' => count($commands),
                'execution_time_ms' => $executionTime
            ], 'info');
            
            return $result;
        } catch (BaseException $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger->logError('Bitrix24 API batch exception (SDK)', [
                'exception' => $e->getMessage(),
                'execution_time_ms' => $executionTime
            ]);
            
            throw new Bitrix24ApiException(
                "API batch call failed",
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Получение настроек приложения
     * 
     * @return array Настройки приложения
     */
    public function getSettings(): array
    {
        try {
            $settingsFile = __DIR__ . '/../../settings.json';
            if (file_exists($settingsFile)) {
                $content = file_get_contents($settingsFile);
                return json_decode($content, true) ?? [];
            }
            return [];
        } catch (\Exception $e) {
            $this->logger->logError('Failed to get settings', [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Очистка параметров для логирования (удаление секретов)
     * 
     * @param array $params Параметры запроса
     * @return array Очищенные параметры
     */
    protected function sanitizeParams(array $params): array
    {
        $sanitized = $params;
        
        // Удаляем секретные данные из логов
        $secretFields = ['password', 'secret', 'token', 'key', 'access_token', 'refresh_token', 'auth'];
        foreach ($secretFields as $field) {
            if (isset($sanitized[$field])) {
                $value = $sanitized[$field];
                if (is_string($value) && strlen($value) > 10) {
                    $sanitized[$field] = substr($value, 0, 10) . '...';
                }
            }
        }
        
        return $sanitized;
    }
}
```

### Этап 3: Миграция установки приложения

#### 3.1. Обновление install.php

**Создать новый метод установки с b24phpsdk:**

```php
<?php
/**
 * Установка приложения через b24phpsdk
 */

require_once(__DIR__ . '/src/bootstrap.php');

use Bitrix24\SDK\Core\ApiClient;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Core\Credentials\Credentials;

// Обработка установки приложения
if ($_REQUEST['event'] == 'ONAPPINSTALL' && !empty($_REQUEST['auth'])) {
    try {
        $auth = $_REQUEST['auth'];
        
        // Валидация данных
        if (empty($auth['access_token']) || empty($auth['domain'])) {
            throw new \Exception('Missing required fields: access_token or domain');
        }
        
        // Очистка домена от протокола
        $domain = preg_replace('#^https?://#', '', $auth['domain']);
        $domain = rtrim($domain, '/');
        
        // Сохранение настроек
        $settings = [
            'access_token' => $auth['access_token'],
            'expires_in' => $auth['expires_in'] ?? 3600,
            'application_token' => $auth['application_token'] ?? '',
            'refresh_token' => $auth['refresh_token'] ?? '',
            'domain' => $domain,
            'client_endpoint' => 'https://' . $domain . '/rest/',
            'installed_at' => date('Y-m-d H:i:s'),
            'installed_by' => 'ONAPPINSTALL'
        ];
        
        $settingsFile = __DIR__ . '/settings.json';
        $settingsDir = dirname($settingsFile);
        
        // Проверка прав на запись
        if (!is_writable($settingsDir)) {
            throw new \Exception('Settings directory is not writable: ' . $settingsDir);
        }
        
        // Сохранение с блокировкой файла
        $result = file_put_contents(
            $settingsFile,
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        
        if ($result === false) {
            throw new \Exception('Failed to write settings.json');
        }
        
        // Установка прав доступа (только для чтения владельцем)
        chmod($settingsFile, 0600);
        
        // Логирование успешной установки
        if (isset($logger)) {
            $logger->log('Application installed via ONAPPINSTALL', [
                'domain' => $domain,
                'token_length' => strlen($auth['access_token'])
            ], 'info');
        }
        
        echo json_encode([
            'rest_only' => true,
            'install' => true,
            'domain' => $domain
        ]);
        exit;
        
    } catch (\Exception $e) {
        // Логирование ошибки
        error_log('Install error (ONAPPINSTALL): ' . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'rest_only' => true,
            'install' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Обработка установки через PLACEMENT
if ($_REQUEST['PLACEMENT'] == 'DEFAULT') {
    try {
        // Валидация обязательных параметров
        $requiredParams = ['AUTH_ID', 'DOMAIN'];
        foreach ($requiredParams as $param) {
            if (empty($_REQUEST[$param])) {
                throw new \Exception("Missing required parameter: {$param}");
            }
        }
        
        // Очистка и валидация данных
        $authId = htmlspecialchars(trim($_REQUEST['AUTH_ID']));
        $domain = htmlspecialchars(trim($_REQUEST['DOMAIN']));
        $authExpires = isset($_REQUEST['AUTH_EXPIRES']) ? (int)$_REQUEST['AUTH_EXPIRES'] : 3600;
        $appSid = isset($_REQUEST['APP_SID']) ? htmlspecialchars(trim($_REQUEST['APP_SID'])) : '';
        $refreshId = isset($_REQUEST['REFRESH_ID']) ? htmlspecialchars(trim($_REQUEST['REFRESH_ID'])) : '';
        
        // Очистка домена от протокола
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');
        
        // Валидация формата домена
        if (!preg_match('/^[a-z0-9.-]+\.bitrix24\.(ru|com|by|kz)$/i', $domain)) {
            throw new \Exception('Invalid domain format: ' . $domain);
        }
        
        // Сохранение настроек
        $settings = [
            'access_token' => $authId,
            'expires_in' => $authExpires,
            'application_token' => $appSid,
            'refresh_token' => $refreshId,
            'domain' => $domain,
            'client_endpoint' => 'https://' . $domain . '/rest/',
            'installed_at' => date('Y-m-d H:i:s'),
            'installed_by' => 'PLACEMENT_DEFAULT'
        ];
        
        $settingsFile = __DIR__ . '/settings.json';
        $settingsDir = dirname($settingsFile);
        
        // Проверка прав на запись
        if (!is_writable($settingsDir)) {
            throw new \Exception('Settings directory is not writable: ' . $settingsDir);
        }
        
        // Сохранение с блокировкой файла
        $result = file_put_contents(
            $settingsFile,
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
        
        if ($result === false) {
            throw new \Exception('Failed to write settings.json');
        }
        
        // Установка прав доступа
        chmod($settingsFile, 0600);
        
        // Логирование успешной установки
        if (isset($logger)) {
            $logger->log('Application installed via PLACEMENT', [
                'domain' => $domain,
                'auth_id_length' => strlen($authId)
            ], 'info');
        }
        
        echo json_encode([
            'rest_only' => false,
            'install' => true,
            'domain' => $domain
        ]);
        exit;
        
    } catch (\Exception $e) {
        // Логирование ошибки
        error_log('Install error (PLACEMENT): ' . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'rest_only' => false,
            'install' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}
```

### Этап 4: Обновление сервисов

#### 4.1. Обновление Bitrix24ApiService

**Использовать новый Bitrix24SdkClient:**

```php
<?php

namespace App\Services;

use App\Clients\Bitrix24SdkClient; // Вместо Bitrix24Client
use App\Exceptions\Bitrix24ApiException;

class Bitrix24ApiService
{
    protected Bitrix24SdkClient $client; // Изменено
    protected LoggerService $logger;
    
    public function __construct(Bitrix24SdkClient $client, LoggerService $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }
    
    /**
     * Получение текущего пользователя через SDK
     */
    public function getCurrentUser(string $authId, string $domain): ?array
    {
        // Инициализируем клиент с токеном пользователя
        $this->client->initializeWithUserToken($authId, $domain);
        
        // Вызываем через SDK
        $result = $this->client->call('user.current', []);
        
        if (isset($result['error']) || !isset($result['result'])) {
            return null;
        }
        
        return $result['result'];
    }
    
    // ... остальные методы
}
```

### Этап 5: Обновление bootstrap.php

#### 5.1. Замена клиента в DI

**Текущая структура bootstrap.php:**

```php
<?php
// Подключение исключений
require_once(__DIR__ . '/Exceptions/...');

// Подключение клиентов
require_once(__DIR__ . '/Clients/ApiClientInterface.php');
require_once(__DIR__ . '/Clients/Bitrix24Client.php'); // ← Заменить

// Подключение сервисов
require_once(__DIR__ . '/Services/...');

// Инициализация сервисов
$logger = new App\Services\LoggerService();
$configService = new App\Services\ConfigService($logger);

// Инициализация клиента API
$bitrix24Client = new App\Clients\Bitrix24Client($logger); // ← Заменить

// Инициализация сервисов с зависимостями
$apiService = new App\Services\Bitrix24ApiService($bitrix24Client, $logger);
// ...
```

**Обновленная структура:**

```php
<?php
/**
 * Файл инициализации сервисов
 *
 * Подключает все необходимые сервисы и хелперы
 * Использует b24phpsdk вместо CRest
 * Документация: https://github.com/bitrix24/b24phpsdk
 */

// Автозагрузка Composer (для b24phpsdk)
require_once(__DIR__ . '/../vendor/autoload.php');

// Подключение исключений
require_once(__DIR__ . '/Exceptions/Bitrix24ApiException.php');
require_once(__DIR__ . '/Exceptions/AccessDeniedException.php');
require_once(__DIR__ . '/Exceptions/ConfigException.php');

// Подключение клиентов
require_once(__DIR__ . '/Clients/ApiClientInterface.php');
require_once(__DIR__ . '/Clients/Bitrix24SdkClient.php'); // ← Новый клиент

// Подключение сервисов
require_once(__DIR__ . '/Services/LoggerService.php');
require_once(__DIR__ . '/Services/ConfigService.php');
require_once(__DIR__ . '/Services/Bitrix24ApiService.php');
require_once(__DIR__ . '/Services/UserService.php');
require_once(__DIR__ . '/Services/AccessControlService.php');
require_once(__DIR__ . '/Services/AuthService.php');

// Подключение хелперов
require_once(__DIR__ . '/Helpers/DomainResolver.php');
require_once(__DIR__ . '/Helpers/AdminChecker.php');

// Инициализация сервисов
$logger = new App\Services\LoggerService();
$configService = new App\Services\ConfigService($logger);

// Инициализация клиента API (новый SDK клиент)
$bitrix24Client = new App\Clients\Bitrix24SdkClient($logger);

// Инициализация с токеном установщика
try {
    $bitrix24Client->initializeWithInstallerToken();
    $logger->log('SDK client initialized with installer token', [], 'info');
} catch (\Exception $e) {
    $logger->logError('Failed to initialize SDK client', [
        'exception' => $e->getMessage()
    ]);
    // В development режиме можно показать ошибку
    if (getenv('APP_ENV') === 'development') {
        throw $e;
    }
    // В production продолжаем работу (может быть установка не завершена)
}

// Инициализация сервисов с зависимостями
$apiService = new App\Services\Bitrix24ApiService($bitrix24Client, $logger);
$userService = new App\Services\UserService($apiService, $logger);
$accessControlService = new App\Services\AccessControlService(
    $configService,
    $apiService,
    $userService,
    $logger
);
$authService = new App\Services\AuthService(
    $configService,
    $accessControlService,
    $apiService,
    $userService,
    $logger
);

// Инициализация хелперов
$domainResolver = new App\Helpers\DomainResolver($configService);
$adminChecker = new App\Helpers\AdminChecker($apiService);
```

**Детали изменений:**
1. Добавлена автозагрузка Composer в начале файла
2. Заменен `Bitrix24Client` на `Bitrix24SdkClient`
3. Добавлена инициализация клиента с токеном установщика
4. Добавлена обработка ошибок инициализации
5. Добавлено логирование инициализации

**Важно:**
- Автозагрузка Composer должна быть первой (для загрузки SDK классов)
- Инициализация клиента должна быть до создания сервисов
- Обработка ошибок позволяет работать даже если установка не завершена

---

## Сравнение: CRest vs b24phpsdk

### Примеры кода

#### CRest (старый подход):
```php
require_once(__DIR__ . '/crest.php');

$result = CRest::call('user.current', []);
if (isset($result['error'])) {
    // Обработка ошибки
}
$user = $result['result'];
```

#### b24phpsdk (новый подход):
```php
use Bitrix24\SDK\Core\ApiClient;
use Bitrix24\SDK\Core\Credentials\ApplicationProfile;
use Bitrix24\SDK\Core\Credentials\Credentials;

$credentials = new Credentials(
    new ApplicationProfile($accessToken, $domain)
);
$apiClient = new ApiClient($credentials);

$response = $apiClient->call('user.current', []);
$user = $response->getResponseData()->getResult();
```

### Преимущества нового подхода

1. **Типизация:**
   - Автокомплит в IDE
   - Проверка типов на этапе разработки
   - Меньше ошибок

2. **Обработка ошибок:**
   - Типизированные исключения
   - Детальная информация об ошибках
   - Улучшенная отладка

3. **Производительность:**
   - Оптимизированные batch-запросы
   - Кеширование соединений
   - Экономное использование памяти

---

## Чек-лист миграции

### Подготовка
- [ ] Установлен Composer
- [ ] Создан `composer.json`
- [ ] Установлен b24phpsdk (`composer require bitrix24/b24phpsdk`)
- [ ] Настроена автозагрузка

### Реализация
- [ ] Создан `Bitrix24SdkClient`
- [ ] Обновлен `Bitrix24ApiService`
- [ ] Обновлен `install.php`
- [ ] Обновлен `bootstrap.php`
- [ ] Удалены все `require_once('crest.php')`

### Тестирование
- [ ] Протестирована установка приложения
- [ ] Протестированы все API вызовы
- [ ] Протестированы batch-запросы
- [ ] Протестирована работа с токенами
- [ ] Протестирована обработка ошибок

### Очистка
- [ ] Удален файл `crest.php`
- [ ] Обновлена документация
- [ ] Обновлены комментарии в коде

---

## Обратная совместимость

### Стратегия миграции

1. **Фаза 1:** Обе библиотеки работают параллельно
   - Bitrix24SdkClient создан и протестирован
   - Старый Bitrix24Client остается для fallback

2. **Фаза 2:** Постепенная замена
   - Новые функции используют b24phpsdk
   - Старые функции постепенно мигрируются

3. **Фаза 3:** Полный переход
   - Удаление CRest
   - Удаление старого Bitrix24Client
   - Только b24phpsdk

---

## Документация b24phpsdk

### Полезные ссылки

- **GitHub:** https://github.com/bitrix24/b24phpsdk
- **Документация:** https://github.com/bitrix24/b24phpsdk/blob/main/README.md
- **Примеры:** https://github.com/bitrix24/b24phpsdk/tree/main/examples

### Основные классы

- `ApiClient` — основной клиент для работы с API
- `Credentials` — управление авторизацией
- `ApplicationProfile` — профиль приложения
- `WebhookUrl` — работа с вебхуками

### Проверка версии SDK

**После установки проверить версию:**
```bash
cd APP-B24
composer show bitrix24/b24phpsdk
```

**В коде проверить доступность классов:**
```php
// Проверка загрузки SDK
if (!class_exists('Bitrix24\SDK\Core\ApiClient')) {
    throw new \Exception('b24phpsdk not installed. Run: composer require bitrix24/b24phpsdk');
}

// Проверка версии (если доступно)
if (defined('Bitrix24\SDK\Core\ApiClient::VERSION')) {
    $version = Bitrix24\SDK\Core\ApiClient::VERSION;
    error_log("b24phpsdk version: {$version}");
}
```

### Важные замечания о версиях SDK

**Проверить совместимость версий:**
- SDK может иметь breaking changes между версиями
- Рекомендуется зафиксировать версию в `composer.json`:
  ```json
  "require": {
      "bitrix24/b24phpsdk": "^1.0"  // или конкретная версия "1.0.0"
  }
  ```

**Обновление SDK:**
```bash
# Проверить доступные обновления
composer outdated bitrix24/b24phpsdk

# Обновить до последней версии в пределах ^1.0
composer update bitrix24/b24phpsdk

# Обновить до конкретной версии
composer require bitrix24/b24phpsdk:1.0.1
```

---

## Детали инициализации и работы с токенами

### Инициализация с токеном установщика

**Токен установщика** — это токен, который создается при установке приложения в Bitrix24. Он хранится в `settings.json` и используется для операций от имени приложения.

**Пример инициализации:**
```php
$client = new Bitrix24SdkClient($logger);

// Автоматическая инициализация из settings.json
$client->initializeWithInstallerToken();

// Или с явным указанием токена
$client->initializeWithInstallerToken($accessToken, $domain);
```

**Важно:**
- Токен установщика имеет расширенные права
- Используется для операций, требующих прав приложения
- Не должен использоваться для операций от имени пользователя

### Инициализация с токеном пользователя

**Токен пользователя** — это токен текущего пользователя, который открыл приложение. Он передается в параметрах `AUTH_ID` и используется для операций от имени пользователя.

**Пример инициализации:**
```php
$client = new Bitrix24SdkClient($logger);

// Инициализация с токеном пользователя
$client->initializeWithUserToken($authId, $domain);

// Теперь все вызовы будут от имени этого пользователя
$result = $client->call('user.current', []);
```

**Важно:**
- Токен пользователя имеет права только этого пользователя
- Используется для операций, которые должны выполняться от имени пользователя
- Передается в каждом запросе из Bitrix24

### Работа с вебхуками

**Вебхук** — это специальный URL для доступа к Bitrix24 API без авторизации. Используется для внешних интеграций.

**Пример инициализации:**
```php
$client = new Bitrix24SdkClient($logger);

// Инициализация с вебхуком
$webhookUrl = 'https://your-domain.bitrix24.ru/rest/1/your_webhook_code/';
$client->initializeWithWebhook($webhookUrl);

// Теперь можно делать запросы
$result = $client->call('user.current', []);
```

**Важно:**
- Вебхук создается в настройках Bitrix24
- Имеет права, указанные при создании
- Не требует авторизации пользователя

---

## Troubleshooting (Решение проблем)

### Проблемы с установкой b24phpsdk

**Проблема 1: Composer не найден**
```
bash: composer: command not found
```
**Решение:**
```bash
# Установка Composer (если не установлен)
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

**Проблема 2: Ошибка при установке пакета**
```
Your requirements could not be resolved to an installable set of packages.
```
**Решение:**
- Проверить версию PHP (должна быть >= 8.4)
- Обновить Composer: `composer self-update`
- Очистить кеш: `composer clear-cache`

**Проблема 3: Классы SDK не найдены**
```
Class 'Bitrix24\SDK\Core\ApiClient' not found
```
**Решение:**
- Проверить, что автозагрузка подключена: `require_once('vendor/autoload.php');`
- Выполнить: `composer dump-autoload`
- Проверить, что пакет установлен: `composer show bitrix24/b24phpsdk`

### Проблемы с инициализацией клиента

**Проблема 1: Ошибка "Access token or domain not found"**
**Решение:**
- Проверить, что `settings.json` существует
- Проверить формат файла (валидный JSON)
- Проверить наличие полей `access_token` и `domain`

**Проблема 2: Ошибка при создании Credentials**
**Решение:**
- Проверить формат токена (не должен быть пустым)
- Проверить формат домена (должен быть без протокола)
- Проверить версию SDK (может быть несовместимость)

**Проблема 3: Ошибка авторизации**
```
API error: Invalid token
```
**Решение:**
- Проверить, что токен не истек
- Проверить, что токен соответствует домену
- Переустановить приложение для получения нового токена

### Проблемы с API вызовами

**Проблема 1: Ошибка формата ответа**
```
Call to undefined method getResponseData()
```
**Решение:**
- Проверить версию SDK (может быть старая версия)
- Проверить документацию SDK для правильного использования
- Обновить SDK: `composer update bitrix24/b24phpsdk`

**Проблема 2: Batch-запросы не работают**
**Решение:**
- Проверить формат команд (должен быть массив с ключами)
- Проверить параметр `halt` (0 или 1)
- Проверить документацию SDK для batch-запросов

**Проблема 3: Ошибка "Method not found"**
**Решение:**
- Проверить правильность названия метода
- Проверить права доступа токена
- Проверить документацию Bitrix24 API

### Проблемы с миграцией

**Проблема 1: Старый код все еще использует CRest**
**Решение:**
- Выполнить поиск: `grep -r "CRest" APP-B24/src/`
- Заменить все использования на Bitrix24SdkClient
- Обновить bootstrap.php

**Проблема 2: Ошибки после миграции**
**Решение:**
- Проверить логи: `APP-B24/logs/*.log`
- Сравнить формат ответов (может отличаться от CRest)
- Проверить обработку ошибок

**Проблема 3: Производительность ухудшилась**
**Решение:**
- Проверить кеширование (SDK может кешировать по-другому)
- Проверить количество запросов
- Оптимизировать batch-запросы

---

## Примеры реальных сценариев

### Сценарий 1: Получение списка лидов

**С CRest:**
```php
$result = CRest::call('crm.lead.list', [
    'filter' => ['>CREATED_DATE' => '2025-01-01'],
    'select' => ['ID', 'TITLE', 'NAME'],
    'order' => ['ID' => 'DESC']
]);
$leads = $result['result'] ?? [];
```

**С b24phpsdk:**
```php
$client = new Bitrix24SdkClient($logger);
$client->initializeWithInstallerToken();

$result = $client->call('crm.lead.list', [
    'filter' => ['>CREATED_DATE' => '2025-01-01'],
    'select' => ['ID', 'TITLE', 'NAME'],
    'order' => ['ID' => 'DESC']
]);
$leads = $result['result'] ?? [];
```

**Различия:**
- Формат ответа идентичен
- Обработка ошибок через исключения
- Логирование автоматическое

### Сценарий 2: Batch-запрос для получения нескольких сущностей

**С CRest:**
```php
$commands = [
    'user' => ['method' => 'user.current', 'params' => []],
    'departments' => ['method' => 'department.get', 'params' => []],
    'profile' => ['method' => 'profile', 'params' => []]
];
$result = CRest::callBatch($commands, 0);
```

**С b24phpsdk:**
```php
$client = new Bitrix24SdkClient($logger);
$client->initializeWithInstallerToken();

$commands = [
    'user' => ['method' => 'user.current', 'params' => []],
    'departments' => ['method' => 'department.get', 'params' => []],
    'profile' => ['method' => 'profile', 'params' => []]
];
$result = $client->callBatch($commands, 0);
```

**Различия:**
- Формат команд идентичен
- Обработка ошибок улучшена
- Производительность может быть лучше

---

## План отката (Rollback Plan)

### Когда нужен откат

- Критические ошибки после миграции
- Проблемы с производительностью
- Несовместимость с текущей версией Bitrix24
- Потеря функциональности

### Процедура отката

**1. Восстановление старого кода:**
```bash
# Через Git (если код в репозитории)
git checkout <commit-before-migration>

# Или вручную восстановить файлы из резервной копии
```

**2. Восстановление зависимостей:**
```bash
# Удалить vendor/ (если нужно)
rm -rf APP-B24/vendor/

# Восстановить старый код (если CRest был в проекте)
# Или оставить vendor/ если не мешает
```

**3. Проверка работоспособности:**
- Проверить все страницы
- Проверить логи на ошибки
- Протестировать все функции

### Резервное копирование перед миграцией

**Создать резервную копию:**
```bash
# Создать резервную копию проекта
cd /var/www/backend.antonov-mark.ru
tar -czf backup-before-sdk-migration-$(date +%Y%m%d).tar.gz APP-B24/

# Или через Git
git commit -am "Backup before SDK migration"
git tag backup-before-sdk-migration
```

**Важные файлы для резервного копирования:**
- `APP-B24/crest.php` — старый клиент
- `APP-B24/src/Clients/Bitrix24Client.php` — старый клиент
- `APP-B24/src/bootstrap.php` — старая инициализация
- `APP-B24/settings.json` — настройки (если есть важные данные)

---

## Проверка работоспособности после миграции

### Чек-лист проверки

**1. Проверка установки:**
- [ ] Composer зависимости установлены
- [ ] SDK классы загружаются
- [ ] Автозагрузка работает

**2. Проверка инициализации:**
- [ ] Bitrix24SdkClient создается без ошибок
- [ ] Инициализация с токеном установщика работает
- [ ] Инициализация с токеном пользователя работает

**3. Проверка API вызовов:**
- [ ] Простые вызовы работают (user.current)
- [ ] Batch-запросы работают
- [ ] Обработка ошибок работает

**4. Проверка страниц:**
- [ ] Главная страница открывается
- [ ] Управление правами работает
- [ ] Анализ токена работает

**5. Проверка логов:**
- [ ] Логи создаются
- [ ] Нет критических ошибок
- [ ] Логирование работает корректно

### Команды для проверки

**Проверка установки SDK:**
```bash
cd APP-B24
composer show bitrix24/b24phpsdk
php -r "require 'vendor/autoload.php'; echo class_exists('Bitrix24\SDK\Core\ApiClient') ? 'OK' : 'FAIL';"
```

**Проверка загрузки классов:**
```bash
php -r "
require 'APP-B24/src/bootstrap.php';
echo 'LoggerService: ' . (class_exists('App\Services\LoggerService') ? 'OK' : 'FAIL') . PHP_EOL;
echo 'Bitrix24SdkClient: ' . (class_exists('App\Clients\Bitrix24SdkClient') ? 'OK' : 'FAIL') . PHP_EOL;
"
```

**Проверка API вызова:**
```php
<?php
// test-api-call.php
require_once(__DIR__ . '/APP-B24/src/bootstrap.php');

try {
    $result = $bitrix24Client->call('user.current', []);
    echo "API call successful\n";
    print_r($result);
} catch (\Exception $e) {
    echo "API call failed: " . $e->getMessage() . "\n";
}
```

---

## История правок

- **2025-12-20 19:45 (UTC+3, Брест):** Создан план миграции с CRest на b24phpsdk
- **2025-12-20 20:15 (UTC+3, Брест):** Добавлены детали инициализации, работа с токенами, Troubleshooting, примеры реальных сценариев
- **2025-12-20 20:20 (UTC+3, Брест):** Добавлены детали валидации, обработки ошибок, проверка версий SDK, план отката, проверка работоспособности

---

**Версия документа:** 1.1  
**Последнее обновление:** 2025-12-20 20:15 (UTC+3, Брест)

