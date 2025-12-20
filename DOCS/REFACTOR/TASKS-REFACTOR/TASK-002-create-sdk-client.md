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
   - Создает Credentials с ApplicationProfile
   - Инициализирует ApiClient

5. **Реализовать метод `initializeWithUserToken()`:**
   - Принимает authId и domain
   - Создает Credentials с ApplicationProfile
   - Инициализирует ApiClient

6. **Реализовать метод `initializeWithWebhook()`:**
   - Принимает webhookUrl
   - Создает Credentials с WebhookUrl
   - Инициализирует ApiClient

7. **Реализовать метод `call()`:**
   - Проверяет инициализацию ApiClient
   - Логирует запрос
   - Вызывает SDK метод
   - Преобразует ответ в формат, совместимый с CRest
   - Обрабатывает ошибки
   - Логирует результат

8. **Реализовать метод `callBatch()`:**
   - Преобразует команды в формат SDK
   - Вызывает SDK batch метод
   - Преобразует ответ
   - Обрабатывает ошибки

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

## Риски и митигация

### Риск 1: Несовместимость формата ответов
**Митигация:** Тщательное тестирование, сравнение с CRest форматом

### Риск 2: Проблемы с инициализацией
**Митигация:** Детальное тестирование всех способов инициализации

### Риск 3: Ошибки в обработке исключений
**Митигация:** Тестирование различных сценариев ошибок

---

## История правок

- **2025-12-20 19:48 (UTC+3, Брест):** Создана задача на создание Bitrix24SdkClient

---

**Связанные документы:**
- [TASK-000-crest-to-b24phpsdk-overview.md](TASK-000-crest-to-b24phpsdk-overview.md) — обзор миграции
- [DOCS/REFACTOR/crest-to-b24phpsdk-migration.md](../../crest-to-b24phpsdk-migration.md) — детальный план

---

**Версия документа:** 1.0  
**Последнее обновление:** 2025-12-20 19:48 (UTC+3, Брест)

