# TASK-005: Обновление bootstrap и DI

**Дата создания:** 2025-12-20 19:48 (UTC+3, Брест)  
**Статус:** Новая  
**Приоритет:** Критический  
**Исполнитель:** Bitrix24 Программист (Vanilla JS)

---

## Описание

Обновление `bootstrap.php` и всех контроллеров для использования нового `Bitrix24SdkClient` вместо `Bitrix24Client`. Удаление всех `require_once('crest.php')` из проекта.

---

## Контекст

**Часть задачи:** TASK-000 (Миграция CRest → b24phpsdk)  
**Зависит от:** TASK-002, TASK-003, TASK-004  
**Зависимости от этой задачи:** TASK-006 (Тестирование миграции)

**Цель:** Завершить миграцию, обновив все места, где используется старый клиент.

---

## Модули и компоненты

**Обновляемые файлы:**
- `APP-B24/src/bootstrap.php` — инициализация сервисов
- `APP-B24/src/Controllers/IndexController.php`
- `APP-B24/src/Controllers/AccessControlController.php`
- `APP-B24/src/Controllers/TokenAnalysisController.php`
- `APP-B24/src/Services/AuthService.php`
- Все файлы с `require_once('crest.php')`

---

## Зависимости

**От каких задач зависит:**
- TASK-002 (Создание Bitrix24SdkClient)
- TASK-003 (Обновление Bitrix24ApiService)
- TASK-004 (Миграция установки)

**Какие задачи зависят от этой:**
- TASK-006 (Тестирование миграции)

---

## Ступенчатые подзадачи

### 1. Поиск всех использований CRest

1. **Найти все файлы с `require_once('crest.php')`:**
   ```bash
   grep -r "require_once.*crest.php" APP-B24/
   ```

2. **Найти все файлы с `CRest::`:**
   ```bash
   grep -r "CRest::" APP-B24/
   ```

3. **Составить список файлов для обновления**

### 2. Обновление bootstrap.php

1. **Открыть `APP-B24/src/bootstrap.php`**

2. **Проверить наличие автозагрузки Composer:**
   ```php
   // Должно быть в начале файла (после открывающего тега PHP):
   require_once(__DIR__ . '/../vendor/autoload.php');
   ```
   
   **Если нет — добавить в самое начало файла (после `<?php`):**
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
   ```

3. **Найти создание Bitrix24Client:**
   ```php
   // Было:
   $bitrix24Client = new App\Clients\Bitrix24Client($logger);
   ```

4. **Заменить на Bitrix24SdkClient с обработкой ошибок:**
   ```php
   // Стало:
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
   ```

5. **Обновить require_once для клиентов:**
   ```php
   // Было:
   require_once(__DIR__ . '/Clients/Bitrix24Client.php');
   
   // Стало:
   require_once(__DIR__ . '/Clients/Bitrix24SdkClient.php'); // Новый клиент
   // Bitrix24Client.php можно оставить для обратной совместимости (пока)
   ```

6. **Удалить `require_once('crest.php')` (если есть):**
   ```php
   // Удалить все строки вида:
   require_once(__DIR__ . '/../../crest.php');
   require_once(__DIR__ . '/../crest.php');
   require_once('crest.php');
   ```

7. **Проверить порядок инициализации:**
   - Автозагрузка Composer должна быть первой
   - Инициализация клиента должна быть до создания сервисов
   - Обработка ошибок позволяет работать даже если установка не завершена

### 3. Обновление контроллеров

1. **IndexController:**
   - Удалить `require_once('crest.php')`
   - Проверить, что использует сервисы (не напрямую клиент)

2. **AccessControlController:**
   - Удалить `require_once('crest.php')`
   - Проверить использование сервисов

3. **TokenAnalysisController:**
   - Удалить `require_once('crest.php')`
   - Проверить использование сервисов

### 4. Обновление AuthService

1. **Открыть `APP-B24/src/Services/AuthService.php`**

2. **Удалить `require_once('crest.php')`**

3. **Проверить использование сервисов:**
   - Должен использовать Bitrix24ApiService
   - Не должен напрямую использовать клиент

### 5. Удаление require_once из всех файлов

1. **Для каждого файла из списка:**
   - Удалить `require_once(__DIR__ . '/../../crest.php');`
   - Удалить `require_once(__DIR__ . '/../crest.php');`
   - Удалить другие варианты

2. **Проверить, что файлы работают:**
   - Запустить тесты
   - Проверить логи на ошибки

### 6. Обновление точек входа

1. **index.php:**
   - Проверить, что использует bootstrap
   - Убедиться, что нет прямых вызовов CRest

2. **access-control.php:**
   - Проверить использование контроллеров
   - Убедиться, что нет прямых вызовов CRest

3. **token-analysis.php:**
   - Проверить использование контроллеров
   - Убедиться, что нет прямых вызовов CRest

### 7. Финальная проверка

1. **Повторный поиск CRest:**
   ```bash
   grep -r "CRest" APP-B24/src/
   # Должен вернуть пустой результат (или только в комментариях)
   ```
   
   **Если найдены использования:**
   - Проверить, не остались ли прямые вызовы `CRest::call()`
   - Заменить на использование сервисов через `Bitrix24ApiService`

2. **Повторный поиск require_once crest:**
   ```bash
   grep -r "crest.php" APP-B24/
   # Должен вернуть пустой результат
   ```
   
   **Если найдены:**
   - Удалить все `require_once('crest.php')`
   - Проверить, что файлы работают без CRest

3. **Проверка загрузки классов:**
   ```bash
   php -r "
   require 'APP-B24/src/bootstrap.php';
   echo 'Bitrix24SdkClient: ' . (class_exists('App\Clients\Bitrix24SdkClient') ? 'OK' : 'FAIL') . PHP_EOL;
   echo 'Bitrix24ApiService: ' . (class_exists('App\Services\Bitrix24ApiService') ? 'OK' : 'FAIL') . PHP_EOL;
   "
   ```

4. **Проверка работоспособности:**
   - Запустить все страницы
   - Проверить логи на ошибки
   - Проверить, что API вызовы работают
   - Проверить, что нет ошибок инициализации

5. **Проверка зависимостей:**
   ```bash
   # Проверить, что все сервисы инициализируются правильно
   php -r "
   require 'APP-B24/src/bootstrap.php';
   echo 'LoggerService: ' . (isset(\$logger) ? 'OK' : 'FAIL') . PHP_EOL;
   echo 'Bitrix24Client: ' . (isset(\$bitrix24Client) ? 'OK' : 'FAIL') . PHP_EOL;
   echo 'ApiService: ' . (isset(\$apiService) ? 'OK' : 'FAIL') . PHP_EOL;
   "
   ```

---

## Технические требования

- **Совместимость:** Все должно работать как раньше
- **Зависимости:** Правильная инициализация всех сервисов
- **Очистка:** Полное удаление зависимостей от CRest

---

## Критерии приёмки

- [ ] bootstrap.php обновлен для использования Bitrix24SdkClient
- [ ] Bitrix24SdkClient инициализируется с токеном установщика
- [ ] Все контроллеры обновлены (удалены require_once crest.php)
- [ ] AuthService обновлен (удален require_once crest.php)
- [ ] Все require_once('crest.php') удалены из проекта
- [ ] Все прямые вызовы CRest удалены
- [ ] Поиск CRest не находит использований (кроме комментариев)
- [ ] Все страницы работают корректно
- [ ] Логи не содержат ошибок
- [ ] Код соответствует стандартам PSR-12

---

## Тестирование

### 1. Проверка поиска CRest
```bash
grep -r "CRest" APP-B24/src/
# Должен вернуть пустой результат
```

### 2. Проверка поиска require_once crest
```bash
grep -r "crest.php" APP-B24/
# Должен вернуть пустой результат
```

### 3. Тест главной страницы
```bash
# Открыть index.php в браузере
# Должна работать без ошибок
```

### 4. Тест управления правами
```bash
# Открыть access-control.php в браузере
# Должна работать без ошибок
```

### 5. Тест анализа токена
```bash
# Открыть token-analysis.php в браузере
# Должна работать без ошибок
```

---

## Troubleshooting (Решение проблем)

### Проблема 1: Ошибка "Class not found" после обновления
**Симптомы:**
```
Fatal error: Class 'App\Clients\Bitrix24SdkClient' not found
```

**Решение:**
1. Проверить, что файл `Bitrix24SdkClient.php` существует
2. Проверить, что `require_once` добавлен в bootstrap.php
3. Проверить namespace в файле клиента
4. Выполнить `composer dump-autoload` (если используется автозагрузка)

### Проблема 2: Ошибка инициализации SDK клиента
**Симптомы:**
```
Failed to initialize SDK client: Access token or domain not found
```

**Решение:**
1. Проверить существование `settings.json`
2. Проверить формат файла (валидный JSON)
3. Проверить наличие полей `access_token` и `domain`
4. Проверить права доступа к файлу
5. В development режиме ошибка будет показана, в production - только логирование

### Проблема 3: Страницы не работают после обновления
**Симптомы:**
```
Страница не загружается, ошибки в логах
```

**Решение:**
1. Проверить логи на ошибки: `APP-B24/logs/*.log`
2. Проверить, что все `require_once('crest.php')` удалены
3. Проверить, что контроллеры используют сервисы (не напрямую клиент)
4. Проверить порядок инициализации в bootstrap.php

### Проблема 4: Ошибки автозагрузки Composer
**Симптомы:**
```
Class 'Bitrix24\SDK\Core\ApiClient' not found
```

**Решение:**
1. Проверить, что `vendor/autoload.php` подключен в bootstrap.php
2. Проверить, что автозагрузка идет первой (до других require)
3. Проверить установку SDK: `composer show bitrix24/b24phpsdk`
4. Выполнить `composer dump-autoload`

## Риски и митигация

### Риск 1: Пропущенные использования CRest
**Митигация:** 
- Тщательный поиск через grep всех использований
- Проверка всех файлов в проекте
- Автоматизированная проверка через скрипт

### Риск 2: Проблемы с инициализацией
**Митигация:** 
- Тестирование всех страниц после обновления
- Проверка логов на ошибки
- Обработка ошибок в bootstrap.php с логированием
- Продолжение работы даже при ошибках инициализации (для случаев незавершенной установки)

### Риск 3: Нарушение зависимостей
**Митигация:** 
- Проверка порядка инициализации (LoggerService → ConfigService → Client → Services)
- Тестирование после каждого изменения
- Проверка зависимостей через скрипт

---

## История правок

- **2025-12-20 19:48 (UTC+3, Брест):** Создана задача на обновление bootstrap

---

**Связанные документы:**
- [TASK-000-crest-to-b24phpsdk-overview.md](TASK-000-crest-to-b24phpsdk-overview.md) — обзор миграции
- [TASK-002-create-sdk-client.md](TASK-002-create-sdk-client.md) — создание SDK клиента
- [TASK-003-update-api-service.md](TASK-003-update-api-service.md) — обновление API сервиса

---

**Версия документа:** 1.0  
**Последнее обновление:** 2025-12-20 19:48 (UTC+3, Брест)

