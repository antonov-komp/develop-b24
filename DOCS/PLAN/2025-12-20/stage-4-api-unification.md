# Этап 4: Унификация работы с API

**Дата создания:** 2025-12-20 20:30 (UTC+3, Брест)  
**Версия:** 1.0  
**Статус:** План  
**Приоритет:** Высокий  
**Оценка времени:** 1-2 дня

---

## Цель этапа

Создать единый клиент для Bitrix24 API, унифицировать обработку ошибок и логирование всех API-запросов.

**Результат:** Единая точка входа для всех API-запросов, улучшенная обработка ошибок, возможность добавления кеширования и других оптимизаций.

---

## Зависимости

**Требуется завершение:** Этап 3 (Разделение логики и представления)

**Используемые компоненты:**
- Библиотека CRest
- Все сервисы из предыдущих этапов

---

## Задачи этапа

### Задача 4.1: Создание структуры директорий для клиентов

**Действия:**
1. Создать директорию `APP-B24/src/Clients/`
2. Убедиться, что директория доступна для записи

**Проверка:**
```bash
mkdir -p /var/www/backend.antonov-mark.ru/APP-B24/src/Clients
chmod 775 /var/www/backend.antonov-mark.ru/APP-B24/src/Clients
```

**Критерий:** Директория создана и доступна для записи.

---

### Задача 4.2: Создание интерфейса `ApiClientInterface`

**Файл:** `APP-B24/src/Clients/ApiClientInterface.php`

**Назначение:** Интерфейс для клиентов API, позволяющий заменять реализацию.

**Методы:**
- `call(string $method, array $params = []): array` — вызов метода API
- `callBatch(array $commands, int $halt = 0): array` — батч-запросы

**Пример реализации:**
```php
<?php

namespace App\Clients;

/**
 * Интерфейс для клиентов Bitrix24 API
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
interface ApiClientInterface
{
    /**
     * Вызов метода Bitrix24 REST API
     * 
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @return array Ответ от API
     */
    public function call(string $method, array $params = []): array;
    
    /**
     * Батч-запросы к Bitrix24 REST API
     * 
     * @param array $commands Массив команд для выполнения
     * @param int $halt Остановка при ошибке (0 или 1)
     * @return array Ответ от API
     */
    public function callBatch(array $commands, int $halt = 0): array;
}
```

**Критерий:** Интерфейс создан, определены все необходимые методы.

---

### Задача 4.3: Создание класса `Bitrix24Client`

**Файл:** `APP-B24/src/Clients/Bitrix24Client.php`

**Назначение:** Единый клиент для работы с Bitrix24 REST API, обертка над CRest.

**Методы:**
- `call(string $method, array $params = []): array` — вызов метода API
- `callBatch(array $commands, int $halt = 0): array` — батч-запросы
- `refreshToken(): bool` — обновление токена
- `getSettings(): array` — получение настроек приложения

**Источники кода:**
- Библиотека CRest (`crest.php`)
- Логика работы с токенами из `crest.php`

**Пример реализации:**
```php
<?php

namespace App\Clients;

require_once(__DIR__ . '/../../crest.php');

use App\Services\LoggerService;
use App\Clients\ApiClientInterface;

/**
 * Клиент для работы с Bitrix24 REST API
 * 
 * Обертка над библиотекой CRest с унифицированной обработкой ошибок
 * Документация: https://context7.com/bitrix24/rest/
 */
class Bitrix24Client implements ApiClientInterface
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
     * @throws Bitrix24ApiException При ошибке API
     */
    public function call(string $method, array $params = []): array
    {
        $this->logger->log('Bitrix24 API call', [
            'method' => $method,
            'params' => $this->sanitizeParams($params)
        ], 'info');
        
        try {
            $result = CRest::call($method, $params);
            
            if (isset($result['error'])) {
                $this->handleApiError($result, $method);
            }
            
            $this->logger->log('Bitrix24 API success', [
                'method' => $method
            ], 'info');
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Bitrix24 API exception', [
                'method' => $method,
                'exception' => $e->getMessage()
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
     */
    public function callBatch(array $commands, int $halt = 0): array
    {
        $this->logger->log('Bitrix24 API batch call', [
            'commands_count' => count($commands),
            'halt' => $halt
        ], 'info');
        
        try {
            $result = CRest::callBatch($commands, $halt);
            
            if (isset($result['error'])) {
                $this->handleApiError($result, 'batch');
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->logError('Bitrix24 API batch exception', [
                'exception' => $e->getMessage()
            ]);
            
            throw new Bitrix24ApiException(
                "API batch call failed",
                $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Обновление токена авторизации
     * 
     * @return bool true если токен обновлен успешно
     */
    public function refreshToken(): bool
    {
        // Логика обновления токена из CRest
        // Метод GetNewAuth() в CRest
        return true;
    }
    
    /**
     * Получение настроек приложения
     * 
     * @return array Настройки приложения
     */
    public function getSettings(): array
    {
        // Логика получения настроек из CRest
        // Метод getAppSettings() в CRest
        return [];
    }
    
    /**
     * Обработка ошибок API
     * 
     * @param array $result Результат запроса с ошибкой
     * @param string $method Метод API
     * @throws Bitrix24ApiException
     */
    protected function handleApiError(array $result, string $method): void
    {
        $error = $result['error'] ?? 'unknown_error';
        $errorDescription = $result['error_description'] ?? 'No description';
        
        $this->logger->logError('Bitrix24 API error', [
            'method' => $method,
            'error' => $error,
            'error_description' => $errorDescription
        ]);
        
        // Маппинг ошибок API на исключения
        $errorMap = [
            'expired_token' => 'Токен истек, требуется обновление',
            'invalid_token' => 'Невалидный токен, требуется переустановка приложения',
            'invalid_grant' => 'Ошибка авторизации, проверьте настройки',
            'QUERY_LIMIT_EXCEEDED' => 'Превышен лимит запросов, попробуйте позже',
            'ERROR_METHOD_NOT_FOUND' => 'Метод API не найден',
            'NO_AUTH_FOUND' => 'Ошибка авторизации в Bitrix24',
            'INTERNAL_SERVER_ERROR' => 'Внутренняя ошибка сервера Bitrix24'
        ];
        
        $message = $errorMap[$error] ?? $errorDescription;
        
        throw new Bitrix24ApiException(
            "API error: {$message}",
            0,
            null,
            $error,
            $errorDescription
        );
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
        if (isset($sanitized['auth'])) {
            $sanitized['auth'] = substr($sanitized['auth'], 0, 10) . '...';
        }
        
        return $sanitized;
    }
}
```

**Критерий:** Класс создан, все методы работают корректно, обработка ошибок унифицирована.

---

### Задача 4.4: Создание классов исключений

**Файлы:**
- `APP-B24/src/Exceptions/Bitrix24ApiException.php` — ошибки API
- `APP-B24/src/Exceptions/AccessDeniedException.php` — ошибки доступа
- `APP-B24/src/Exceptions/ConfigException.php` — ошибки конфигурации

**Пример реализации `Bitrix24ApiException`:**
```php
<?php

namespace App\Exceptions;

/**
 * Исключение для ошибок Bitrix24 API
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class Bitrix24ApiException extends \Exception
{
    protected ?string $apiError;
    protected ?string $apiErrorDescription;
    
    public function __construct(
        string $message = "",
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $apiError = null,
        ?string $apiErrorDescription = null
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->apiError = $apiError;
        $this->apiErrorDescription = $apiErrorDescription;
    }
    
    /**
     * Получение кода ошибки API
     * 
     * @return string|null Код ошибки API
     */
    public function getApiError(): ?string
    {
        return $this->apiError;
    }
    
    /**
     * Получение описания ошибки API
     * 
     * @return string|null Описание ошибки API
     */
    public function getApiErrorDescription(): ?string
    {
        return $this->apiErrorDescription;
    }
}
```

**Критерий:** Все классы исключений созданы, наследуются от `\Exception`.

---

### Задача 4.5: Обновление `Bitrix24ApiService` для использования `Bitrix24Client`

**Файл:** `APP-B24/src/Services/Bitrix24ApiService.php`

**Изменения:**
1. Заменить прямые вызовы `CRest::call()` на использование `Bitrix24Client`
2. Использовать исключения вместо проверки `isset($result['error'])`
3. Улучшить обработку ошибок

**Пример:**
```php
<?php

namespace App\Services;

use App\Clients\Bitrix24Client;
use App\Exceptions\Bitrix24ApiException;

/**
 * Сервис для работы с Bitrix24 REST API
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */
class Bitrix24ApiService
{
    protected Bitrix24Client $client;
    protected LoggerService $logger;
    
    public function __construct(Bitrix24Client $client, LoggerService $logger)
    {
        $this->client = $client;
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
        try {
            return $this->client->call($method, $params);
        } catch (Bitrix24ApiException $e) {
            $this->logger->logError('Bitrix24 API error in service', [
                'method' => $method,
                'error' => $e->getApiError(),
                'error_description' => $e->getApiErrorDescription()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Получение текущего пользователя
     * 
     * @param string $authId Токен авторизации
     * @param string $domain Домен портала
     * @return array|null Данные пользователя или null
     */
    public function getCurrentUser(string $authId, string $domain): ?array
    {
        try {
            // Используем прямой вызов API через curl для токена текущего пользователя
            // или через Bitrix24Client для токена установщика
            
            $url = 'https://' . $domain . '/rest/user.current.json';
            $requestParams = ['auth' => $authId];
            $params = http_build_query($requestParams);
            
            // ... логика из getCurrentUserData()
            
            return $result['result'] ?? null;
        } catch (\Exception $e) {
            $this->logger->logError('Error getting current user', [
                'exception' => $e->getMessage()
            ]);
            
            return null;
        }
    }
    
    // ... остальные методы
}
```

**Критерий:** `Bitrix24ApiService` использует `Bitrix24Client`, обработка ошибок унифицирована.

---

### Задача 4.6: Обновление всех сервисов для использования исключений

**Файлы для обновления:**
- `UserService.php`
- `AccessControlService.php`
- `AuthService.php`
- Все контроллеры

**Изменения:**
1. Заменить проверки `isset($result['error'])` на обработку исключений
2. Использовать try-catch для обработки ошибок API
3. Логировать все ошибки через `LoggerService`

**Критерий:** Все сервисы используют исключения для обработки ошибок.

---

### Задача 4.7: Унификация логирования API-запросов

**Действия:**
1. Убедиться, что все API-запросы логируются через `Bitrix24Client`
2. Добавить логирование времени выполнения запросов
3. Добавить логирование размера ответов

**Критерий:** Все API-запросы логируются единообразно.

---

## Порядок выполнения

1. **Создать структуру директорий** (Задача 4.1)
2. **Создать интерфейс ApiClientInterface** (Задача 4.2)
3. **Создать классы исключений** (Задача 4.4)
4. **Создать Bitrix24Client** (Задача 4.3)
5. **Обновить Bitrix24ApiService** (Задача 4.5)
6. **Обновить все сервисы** (Задача 4.6)
7. **Унифицировать логирование** (Задача 4.7)

---

## Тестирование

### Тест 1: Bitrix24Client
```php
$client = new Bitrix24Client($logger);
$result = $client->call('profile', []);
// Проверить, что запрос выполнен и результат получен
```

### Тест 2: Обработка ошибок
```php
try {
    $client->call('invalid.method', []);
} catch (Bitrix24ApiException $e) {
    // Проверить, что исключение выброшено корректно
}
```

### Тест 3: Логирование
- Проверить, что все API-запросы логируются
- Проверить, что ошибки логируются корректно

---

## Критерии приёмки этапа

- [ ] Все API-запросы идут через `Bitrix24Client`
- [ ] Обработка ошибок унифицирована через исключения
- [ ] Весь функционал работает идентично до рефакторинга
- [ ] Логирование API-запросов работает корректно
- [ ] Все исключения обрабатываются корректно
- [ ] Код соответствует стандартам PSR-12
- [ ] Все тесты проходят успешно

---

## Риски и митигация

### Риск 1: Потеря функциональности при замене CRest
**Митигация:** `Bitrix24Client` является оберткой над CRest, не заменяет его полностью

### Риск 2: Проблемы с обработкой исключений
**Митигация:** Тщательно тестировать обработку всех типов ошибок API

### Риск 3: Ухудшение производительности
**Митигация:** Минимизировать накладные расходы на логирование, использовать условное логирование

---

## История правок

- **2025-12-20 20:30 (UTC+3, Брест):** Создан детальный план этапа 4

---

**Статус:** План готов к реализации  
**Дата создания:** 2025-12-20 20:30 (UTC+3, Брест)






