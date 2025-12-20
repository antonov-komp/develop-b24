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
        $credentials = new Credentials(
            new ApplicationProfile(
                $token,
                $portalDomain
            )
        );
        
        $this->apiClient = new ApiClient($credentials);
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
        $this->authId = $authId;
        $this->domain = $domain;
        
        // Создаем API клиент с токеном пользователя
        $credentials = new Credentials(
            new ApplicationProfile(
                $authId,
                $domain
            )
        );
        
        $this->apiClient = new ApiClient($credentials);
    }
    
    /**
     * Инициализация клиента с вебхуком
     * 
     * @param string $webhookUrl URL вебхука
     * @return void
     */
    public function initializeWithWebhook(string $webhookUrl): void
    {
        $credentials = new Credentials(
            new WebhookUrl($webhookUrl)
        );
        
        $this->apiClient = new ApiClient($credentials);
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
            $response = $this->apiClient->call($method, $params);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $result = [
                'result' => $response->getResponseData()->getResult(),
                'total' => $response->getResponseData()->getTotal() ?? null,
                'next' => $response->getResponseData()->getNext() ?? null,
            ];
            
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
            $batchCommands = [];
            foreach ($commands as $key => $command) {
                $batchCommands[$key] = [
                    'method' => $command['method'],
                    'params' => $command['params'] ?? []
                ];
            }
            
            // Вызов batch через SDK
            $response = $this->apiClient->batch($batchCommands, $halt === 1);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $result = $response->getResponseData()->getResult();
            
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
    $auth = $_REQUEST['auth'];
    
    // Сохранение настроек
    $settings = [
        'access_token' => $auth['access_token'],
        'expires_in' => $auth['expires_in'],
        'application_token' => $auth['application_token'],
        'refresh_token' => $auth['refresh_token'],
        'domain' => $auth['domain'],
        'client_endpoint' => 'https://' . $auth['domain'] . '/rest/',
    ];
    
    file_put_contents(__DIR__ . '/settings.json', json_encode($settings, JSON_PRETTY_PRINT));
    
    echo json_encode(['rest_only' => true, 'install' => true]);
    exit;
}

// Обработка установки через PLACEMENT
if ($_REQUEST['PLACEMENT'] == 'DEFAULT') {
    $settings = [
        'access_token' => htmlspecialchars($_REQUEST['AUTH_ID']),
        'expires_in' => htmlspecialchars($_REQUEST['AUTH_EXPIRES']),
        'application_token' => htmlspecialchars($_REQUEST['APP_SID']),
        'refresh_token' => htmlspecialchars($_REQUEST['REFRESH_ID']),
        'domain' => htmlspecialchars($_REQUEST['DOMAIN']),
        'client_endpoint' => 'https://' . htmlspecialchars($_REQUEST['DOMAIN']) . '/rest/',
    ];
    
    file_put_contents(__DIR__ . '/settings.json', json_encode($settings, JSON_PRETTY_PRINT));
    
    echo json_encode(['rest_only' => false, 'install' => true]);
    exit;
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

```php
<?php
// ... существующий код

// Заменяем Bitrix24Client на Bitrix24SdkClient
$bitrix24Client = new App\Clients\Bitrix24SdkClient($logger);
$bitrix24Client->initializeWithInstallerToken();

// Остальные сервисы остаются без изменений
$apiService = new App\Services\Bitrix24ApiService($bitrix24Client, $logger);
// ...
```

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

---

## История правок

- **2025-12-20 19:45 (UTC+3, Брест):** Создан план миграции с CRest на b24phpsdk

---

**Версия документа:** 1.0  
**Последнее обновление:** 2025-12-20 19:45 (UTC+3, Брест)

