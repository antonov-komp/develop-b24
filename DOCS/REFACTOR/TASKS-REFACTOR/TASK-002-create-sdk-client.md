# TASK-002: Создание Bitrix24SdkClient

**Дата создания:** 2025-12-20 19:48 (UTC+3, Брест)  
**Статус:** Новая  
**Приоритет:** Критический  
**Исполнитель:** Bitrix24 Программист (Vanilla JS)

---

## Описание

Создание нового клиента `Bitrix24SdkClient` для работы с Bitrix24 REST API через официальный SDK (b24phpsdk). Этот клиент заменит существующий `Bitrix24Client`, который использует CRest.

---

## Контекст

**Часть задачи:** TASK-000 (Миграция CRest → b24phpsdk)  
**Зависит от:** TASK-001 (Установка b24phpsdk)  
**Зависимости от этой задачи:** TASK-003, TASK-004, TASK-005

**Цель:** Создать новый клиент, который использует b24phpsdk вместо CRest, сохраняя совместимость с существующим интерфейсом `ApiClientInterface`.

---

## Модули и компоненты

**Создаваемые файлы:**
- `APP-B24/src/Clients/Bitrix24SdkClient.php` — новый клиент на основе SDK

**Используемые интерфейсы:**
- `APP-B24/src/Clients/ApiClientInterface.php` — интерфейс клиента (существующий)

**Используемые сервисы:**
- `APP-B24/src/Services/LoggerService.php` — логирование
- `APP-B24/src/Exceptions/Bitrix24ApiException.php` — исключения

**Используемые классы SDK:**
- `Bitrix24\SDK\Core\ApiClient`
- `Bitrix24\SDK\Core\Credentials\ApplicationProfile`
- `Bitrix24\SDK\Core\Credentials\Credentials`
- `Bitrix24\SDK\Core\Credentials\WebhookUrl`
- `Bitrix24\SDK\Core\Exceptions\BaseException`

---

## Зависимости

**От каких задач зависит:**
- TASK-001 (Установка b24phpsdk) — необходим для использования классов SDK

**Какие задачи зависят от этой:**
- TASK-003 (Обновление Bitrix24ApiService) — использует новый клиент
- TASK-004 (Миграция установки) — может использовать клиент
- TASK-005 (Обновление bootstrap) — инициализирует новый клиент

---

## Ступенчатые подзадачи

### 1. Изучение ApiClientInterface

1. **Открыть `APP-B24/src/Clients/ApiClientInterface.php`**

2. **Изучить методы интерфейса:**
   - `call(string $method, array $params = []): array`
   - `callBatch(array $commands, int $halt = 0): array`
   - Другие методы (если есть)

3. **Понять ожидаемый формат ответов**

### 2. Изучение существующего Bitrix24Client

1. **Открыть `APP-B24/src/Clients/Bitrix24Client.php`**

2. **Изучить реализацию:**
   - Как работает `call()`
   - Как работает `callBatch()`
   - Как обрабатываются ошибки
   - Как работает логирование
   - Как работает sanitizeParams()

3. **Понять логику инициализации**

### 3. Изучение документации b24phpsdk

1. **Изучить GitHub репозиторий:** https://github.com/bitrix24/b24phpsdk

2. **Изучить примеры использования:**
   - Инициализация ApiClient
   - Вызов методов API
   - Batch-запросы
   - Обработка ошибок

3. **Понять структуру ответов SDK**

### 4. Создание Bitrix24SdkClient

1. **Создать файл `APP-B24/src/Clients/Bitrix24SdkClient.php`**

2. **Реализовать структуру класса:**
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

   class Bitrix24SdkClient implements ApiClientInterface
   {
       // ...
   }
   ```

3. **Реализовать конструктор:**
   - Принимает LoggerService
   - Инициализирует свойства

4. **Реализовать метод `initializeWithInstallerToken()`:**
   - Читает настройки из settings.json
   - **Валидация:** Проверка наличия access_token и domain
   - **Очистка домена:** Удаление протокола и слешей
   - Создает Credentials с ApplicationProfile
   - **Обработка ошибок:** Try-catch с логированием
   - Инициализирует ApiClient
   
   **Детали реализации:**
   ```php
   public function initializeWithInstallerToken(?string $accessToken = null, ?string $domain = null): void
   {
       $settings = $this->getSettings();
       
       $token = $accessToken ?? $settings['access_token'] ?? null;
       $portalDomain = $domain ?? $settings['domain'] ?? null;
       
       // Валидация
       if (!$token || !$portalDomain) {
           throw new Bitrix24ApiException('Access token or domain not found');
       }
       
       // Очистка домена
       $portalDomain = preg_replace('#^https?://#', '', $portalDomain);
       $portalDomain = rtrim($portalDomain, '/');
       
       try {
           $credentials = new Credentials(
               new ApplicationProfile($token, $portalDomain)
           );
           $this->apiClient = new ApiClient($credentials);
       } catch (\Exception $e) {
           $this->logger->logError('Failed to initialize SDK client', [
               'exception' => $e->getMessage(),
               'domain' => $portalDomain
           ]);
           throw new Bitrix24ApiException("Failed to initialize: {$e->getMessage()}", 0, $e);
       }
   }
   ```

5. **Реализовать метод `initializeWithUserToken()`:**
   - Принимает authId и domain
   - **Валидация:** Проверка непустых значений
   - **Очистка домена:** Удаление протокола и слешей
   - Создает Credentials с ApplicationProfile
   - **Обработка ошибок:** Try-catch с логированием
   - Инициализирует ApiClient
   
   **Детали реализации:**
   ```php
   public function initializeWithUserToken(string $authId, string $domain): void
   {
       // Валидация
       if (empty($authId) || empty($domain)) {
           throw new Bitrix24ApiException('Auth ID and domain are required');
       }
       
       // Очистка домена
       $domain = preg_replace('#^https?://#', '', $domain);
       $domain = rtrim($domain, '/');
       
       try {
           $credentials = new Credentials(
               new ApplicationProfile($authId, $domain)
           );
           $this->apiClient = new ApiClient($credentials);
       } catch (\Exception $e) {
           $this->logger->logError('Failed to initialize with user token', [
               'exception' => $e->getMessage(),
               'domain' => $domain
           ]);
           throw new Bitrix24ApiException("Failed to initialize: {$e->getMessage()}", 0, $e);
       }
   }
   ```

6. **Реализовать метод `initializeWithWebhook()`:**
   - Принимает webhookUrl
   - **Валидация:** Проверка формата URL
   - **Проверка Bitrix24 URL:** Валидация что это URL Bitrix24
   - Создает Credentials с WebhookUrl
   - **Обработка ошибок:** Try-catch с логированием
   - Инициализирует ApiClient
   
   **Детали реализации:**
   ```php
   public function initializeWithWebhook(string $webhookUrl): void
   {
       // Валидация
       if (empty($webhookUrl)) {
           throw new Bitrix24ApiException('Webhook URL is required');
       }
       
       // Проверка формата URL
       if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
           throw new Bitrix24ApiException('Invalid webhook URL format');
       }
       
       // Проверка Bitrix24 URL
       if (strpos($webhookUrl, 'bitrix24') === false && strpos($webhookUrl, '/rest/') === false) {
           $this->logger->log('Warning: Webhook URL does not look like Bitrix24 URL', [
               'url' => substr($webhookUrl, 0, 50) . '...'
           ], 'warning');
       }
       
       try {
           $credentials = new Credentials(new WebhookUrl($webhookUrl));
           $this->apiClient = new ApiClient($credentials);
       } catch (\Exception $e) {
           $this->logger->logError('Failed to initialize with webhook', [
               'exception' => $e->getMessage()
           ]);
           throw new Bitrix24ApiException("Failed to initialize: {$e->getMessage()}", 0, $e);
       }
   }
   ```

7. **Реализовать метод `call()`:**
   - Проверяет инициализацию ApiClient (автоинициализация если нужно)
   - **Логирование:** Логирует запрос с sanitized параметрами
   - **Измерение времени:** Замеряет время выполнения
   - Вызывает SDK метод через `$this->apiClient->call()`
   - **Преобразование ответа:** SDK возвращает объект Response, нужно преобразовать в массив
   - **Обработка ошибок:** Catch BaseException, логирование, преобразование в Bitrix24ApiException
   - **Логирование результата:** Логирует успех с временем выполнения
   
   **Детали реализации:**
   ```php
   public function call(string $method, array $params = []): array
   {
       if (!$this->apiClient) {
           $this->initializeWithInstallerToken();
       }
       
       $startTime = microtime(true);
       
       // Логирование запроса
       $this->logger->log('Bitrix24 API call (SDK)', [
           'method' => $method,
           'params' => $this->sanitizeParams($params)
       ], 'info');
       
       try {
           // Вызов через SDK
           $response = $this->apiClient->call($method, $params);
           $executionTime = round((microtime(true) - $startTime) * 1000, 2);
           
           // Преобразование ответа SDK в формат CRest
           $responseData = $response->getResponseData();
           $result = [
               'result' => $responseData->getResult(),
               'total' => $responseData->getTotal() ?? null,
               'next' => $responseData->getNext() ?? null,
           ];
           
           // Логирование успеха
           $this->logger->log('Bitrix24 API success (SDK)', [
               'method' => $method,
               'execution_time_ms' => $executionTime
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
   ```

8. **Реализовать метод `callBatch()`:**
   - Проверяет инициализацию ApiClient
   - **Преобразование команд:** Преобразует команды в формат SDK
   - **Логирование:** Логирует количество команд
   - **Измерение времени:** Замеряет время выполнения
   - Вызывает SDK batch метод через `$this->apiClient->batch()`
   - **Преобразование ответа:** SDK возвращает объект Response, нужно преобразовать
   - **Обработка частичных ошибок:** Если halt=0, логирует ошибки но не прерывает
   - **Обработка ошибок:** Catch BaseException, логирование, преобразование в Bitrix24ApiException
   - **Логирование результата:** Логирует успех с временем выполнения
   
   **Детали реализации:**
   ```php
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
           // Параметр halt: true = остановка при ошибке, false = продолжение
           $response = $this->apiClient->batch($batchCommands, $halt === 1);
           $executionTime = round((microtime(true) - $startTime) * 1000, 2);
           
           // Получение результата из ответа SDK
           $responseData = $response->getResponseData();
           $result = $responseData->getResult();
           
           // Обработка ошибок в batch (если halt = 0)
           if (!$halt && isset($result['result_error'])) {
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
               'commands_count' => count($commands),
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
   ```

9. **Реализовать метод `getSettings()`:**
   - Читает settings.json
   - Возвращает массив настроек

10. **Реализовать метод `sanitizeParams()`:**
    - Копирует логику из Bitrix24Client
    - Удаляет секреты из параметров для логирования

### 5. Тестирование клиента

1. **Создать тестовый скрипт `APP-B24/test-sdk-client.php`**

2. **Протестировать инициализацию:**
   - С токеном установщика
   - С токеном пользователя
   - С вебхуком

3. **Протестировать метод `call()`:**
   - Простой вызов (например, `user.current`)
   - Проверка формата ответа
   - Проверка обработки ошибок

4. **Протестировать метод `callBatch()`:**
   - Batch-запрос с несколькими командами
   - Проверка формата ответа

5. **Проверить логирование:**
   - Проверка записей в логах
   - Проверка sanitizeParams

---

## Технические требования

- **Совместимость:** Должен реализовывать `ApiClientInterface`
- **Формат ответов:** Должен быть совместим с форматом CRest
- **Обработка ошибок:** Должна быть аналогична Bitrix24Client
- **Логирование:** Должно быть идентично Bitrix24Client

---

## Критерии приёмки

- [ ] Файл `Bitrix24SdkClient.php` создан
- [ ] Класс реализует `ApiClientInterface`
- [ ] Метод `initializeWithInstallerToken()` работает
- [ ] Метод `initializeWithUserToken()` работает
- [ ] Метод `initializeWithWebhook()` работает
- [ ] Метод `call()` работает и возвращает правильный формат
- [ ] Метод `callBatch()` работает и возвращает правильный формат
- [ ] Обработка ошибок работает корректно
- [ ] Логирование работает корректно
- [ ] Тестовый скрипт подтверждает работоспособность
- [ ] Код соответствует стандартам PSR-12

---

## Тестирование

### 1. Тест инициализации
```php
$client = new Bitrix24SdkClient($logger);
$client->initializeWithInstallerToken();
// Должен инициализироваться без ошибок
```

### 2. Тест простого вызова
```php
$result = $client->call('user.current', []);
// Должен вернуть массив с ключом 'result'
```

### 3. Тест batch-запроса
```php
$commands = [
    'user' => ['method' => 'user.current', 'params' => []],
    'profile' => ['method' => 'profile', 'params' => []]
];
$result = $client->callBatch($commands);
// Должен вернуть массив с результатами
```

### 4. Тест обработки ошибок
```php
$result = $client->call('nonexistent.method', []);
// Должно выбросить Bitrix24ApiException
```

---

## Примеры кода

### Инициализация с токеном установщика
```php
$client = new Bitrix24SdkClient($logger);
$client->initializeWithInstallerToken();
```

### Инициализация с токеном пользователя
```php
$client = new Bitrix24SdkClient($logger);
$client->initializeWithUserToken($authId, $domain);
```

### Вызов API
```php
$result = $client->call('user.current', []);
$user = $result['result'];
```

---

## Troubleshooting (Решение проблем)

### Проблема 1: Ошибка "Access token or domain not found"
**Симптомы:**
```
Bitrix24ApiException: Access token or domain not found
```

**Решение:**
1. Проверить существование `settings.json`
2. Проверить формат файла (валидный JSON)
3. Проверить наличие полей `access_token` и `domain`
4. Проверить права доступа к файлу

### Проблема 2: Ошибка при создании Credentials
**Симптомы:**
```
Failed to initialize SDK client: Invalid credentials
```

**Решение:**
1. Проверить формат токена (не должен быть пустым)
2. Проверить формат домена (должен быть без протокола)
3. Проверить версию SDK (может быть несовместимость)
4. Проверить логи для деталей ошибки

### Проблема 3: Ошибка формата ответа
**Симптомы:**
```
Call to undefined method getResponseData()
```

**Решение:**
1. Проверить версию SDK (может быть старая версия)
2. Проверить документацию SDK для правильного использования
3. Обновить SDK: `composer update bitrix24/b24phpsdk`

### Проблема 4: Batch-запросы не работают
**Симптомы:**
```
API batch call failed
```

**Решение:**
1. Проверить формат команд (должен быть массив с ключами)
2. Проверить параметр `halt` (0 или 1)
3. Проверить документацию SDK для batch-запросов
4. Проверить логи для деталей ошибки

## Риски и митигация

### Риск 1: Несовместимость формата ответов
**Митигация:** 
- Тщательное тестирование, сравнение с CRest форматом
- Преобразование ответов SDK в формат CRest
- Проверка всех полей ответа (result, total, next)

### Риск 2: Проблемы с инициализацией
**Митигация:** 
- Детальное тестирование всех способов инициализации
- Валидация всех параметров перед использованием
- Обработка ошибок с детальным логированием
- Автоматическая инициализация при необходимости

### Риск 3: Ошибки в обработке исключений
**Митигация:** 
- Тестирование различных сценариев ошибок
- Преобразование исключений SDK в Bitrix24ApiException
- Сохранение оригинального исключения в цепочке
- Детальное логирование всех ошибок

---

## Дополнительные детали реализации

### Важные замечания

**1. Формат ответов SDK:**
- SDK возвращает объект `Response` с методом `getResponseData()`
- `getResponseData()` возвращает объект с методами `getResult()`, `getTotal()`, `getNext()`
- Нужно преобразовать в массив для совместимости с CRest

**2. Обработка ошибок:**
- SDK использует `BaseException` для всех ошибок
- Нужно преобразовать в `Bitrix24ApiException`
- Сохранять оригинальное исключение в цепочке для отладки

**3. Логирование:**
- Всегда использовать `sanitizeParams()` перед логированием
- Логировать время выполнения для мониторинга производительности
- Логировать успешные операции для аудита

**4. Валидация:**
- Всегда валидировать входные параметры
- Очищать домен от протокола и слешей
- Проверять формат URL для вебхуков

## История правок

- **2025-12-20 19:48 (UTC+3, Брест):** Создана задача на создание Bitrix24SdkClient
- **2025-12-20 20:25 (UTC+3, Брест):** Добавлены детали валидации, обработки ошибок, troubleshooting секция, детали реализации методов

---

**Связанные документы:**
- [TASK-000-crest-to-b24phpsdk-overview.md](TASK-000-crest-to-b24phpsdk-overview.md) — обзор миграции
- [DOCS/REFACTOR/crest-to-b24phpsdk-migration.md](../../crest-to-b24phpsdk-migration.md) — детальный план

---

**Версия документа:** 1.1  
**Последнее обновление:** 2025-12-20 20:25 (UTC+3, Брест)

