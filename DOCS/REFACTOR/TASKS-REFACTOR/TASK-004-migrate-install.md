# TASK-004: Миграция установки приложения

**Дата создания:** 2025-12-20 19:48 (UTC+3, Брест)  
**Статус:** Новая  
**Приоритет:** Высокий  
**Исполнитель:** Bitrix24 Программист (Vanilla JS)

---

## Описание

Обновление процесса установки приложения для работы без CRest. Замена `CRest::installApp()` на прямую обработку событий установки и сохранение настроек в `settings.json`.

---

## Контекст

**Часть задачи:** TASK-000 (Миграция CRest → b24phpsdk)  
**Зависит от:** TASK-001 (Установка b24phpsdk)  
**Зависимости от этой задачи:** TASK-005 (Обновление bootstrap)

**Цель:** Убрать зависимость от CRest в процессе установки приложения.

---

## Модули и компоненты

**Обновляемые файлы:**
- `APP-B24/install.php` — основной файл установки
- `APP-B24/templates/install.php` — шаблон страницы установки

**Используемые файлы:**
- `APP-B24/settings.json` — файл для сохранения настроек

---

## Зависимости

**От каких задач зависит:**
- TASK-001 (Установка b24phpsdk) — для понимания структуры SDK

**Какие задачи зависят от этой:**
- TASK-005 (Обновление bootstrap) — может использовать настройки из установки

---

## Ступенчатые подзадачи

### 1. Анализ текущего install.php

1. **Открыть `APP-B24/install.php`**

2. **Изучить текущую реализацию:**
   - Как используется `CRest::installApp()`
   - Какие события обрабатываются
   - Как сохраняются настройки
   - Какие параметры принимаются

3. **Понять логику установки:**
   - Событие `ONAPPINSTALL`
   - Событие `PLACEMENT=DEFAULT`
   - Формат данных в `$_REQUEST`

### 2. Удаление зависимости от CRest

1. **Найти `require_once(__DIR__ . '/crest.php');`**

2. **Удалить строку**

3. **Найти `CRest::installApp()`**

4. **Удалить вызов**

### 3. Реализация установки без CRest

1. **Обработка события ONAPPINSTALL:**
   ```php
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
       
       file_put_contents(__DIR__ . '/settings.json', 
           json_encode($settings, JSON_PRETTY_PRINT));
       
       echo json_encode(['rest_only' => true, 'install' => true]);
       exit;
   }
   ```

2. **Обработка события PLACEMENT:**
   ```php
   if ($_REQUEST['PLACEMENT'] == 'DEFAULT') {
       $settings = [
           'access_token' => htmlspecialchars($_REQUEST['AUTH_ID']),
           'expires_in' => htmlspecialchars($_REQUEST['AUTH_EXPIRES']),
           'application_token' => htmlspecialchars($_REQUEST['APP_SID']),
           'refresh_token' => htmlspecialchars($_REQUEST['REFRESH_ID']),
           'domain' => htmlspecialchars($_REQUEST['DOMAIN']),
           'client_endpoint' => 'https://' . htmlspecialchars($_REQUEST['DOMAIN']) . '/rest/',
       ];
       
       file_put_contents(__DIR__ . '/settings.json', 
           json_encode($settings, JSON_PRETTY_PRINT));
       
       echo json_encode(['rest_only' => false, 'install' => true]);
       exit;
   }
   ```

3. **Добавить обработку ошибок с детальной валидацией:**
   ```php
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
           
           // Установка прав доступа
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
   ```

4. **Добавить логирование:**
   - Логирование успешной установки с деталями
   - Логирование ошибок с контекстом
   - Использование LoggerService если доступен

### 4. Обновление шаблона установки

1. **Открыть `APP-B24/templates/install.php`**

2. **Найти упоминания CRest:**
   - В комментариях
   - В коде (если есть)

3. **Обновить комментарии:**
   - Удалить упоминания CRest
   - Обновить описание процесса установки

4. **Проверить отображение:**
   - Убедиться, что шаблон работает корректно
   - Проверить отображение сообщений

### 5. Тестирование установки

1. **Создать тестовый скрипт `APP-B24/test-install.php`**

2. **Протестировать установку через ONAPPINSTALL:**
   - Симулировать событие
   - Проверить сохранение настроек
   - Проверить формат ответа

3. **Протестировать установку через PLACEMENT:**
   - Симулировать событие
   - Проверить сохранение настроек
   - Проверить формат ответа

4. **Проверить файл settings.json:**
   - Правильность структуры
   - Корректность данных
   - Права доступа

---

## Технические требования

- **Формат settings.json:** Должен быть совместим с текущим форматом
- **Обработка ошибок:** Должна быть корректной
- **Логирование:** Должно работать
- **Безопасность:** Валидация всех входных данных

---

## Критерии приёмки

- [ ] `require_once crest.php` удален из install.php
- [ ] `CRest::installApp()` удален
- [ ] Обработка события ONAPPINSTALL реализована
- [ ] Обработка события PLACEMENT реализована
- [ ] Настройки сохраняются в settings.json
- [ ] Формат settings.json совместим с текущим
- [ ] Обработка ошибок работает корректно
- [ ] Логирование работает корректно
- [ ] Шаблон установки обновлен
- [ ] Тестовый скрипт подтверждает работоспособность
- [ ] Установка работает в реальном Bitrix24

---

## Тестирование

### 1. Тест установки через ONAPPINSTALL
```php
$_REQUEST = [
    'event' => 'ONAPPINSTALL',
    'auth' => [
        'access_token' => 'test_token',
        'expires_in' => 3600,
        'application_token' => 'test_app_token',
        'refresh_token' => 'test_refresh_token',
        'domain' => 'test.bitrix24.ru'
    ]
];
// Должен сохранить настройки и вернуть успешный ответ
```

### 2. Тест установки через PLACEMENT
```php
$_REQUEST = [
    'PLACEMENT' => 'DEFAULT',
    'AUTH_ID' => 'test_auth_id',
    'AUTH_EXPIRES' => '3600',
    'APP_SID' => 'test_app_sid',
    'REFRESH_ID' => 'test_refresh_id',
    'DOMAIN' => 'test.bitrix24.ru'
];
// Должен сохранить настройки и вернуть успешный ответ
```

### 3. Проверка settings.json
```bash
cat APP-B24/settings.json
# Должен содержать правильную структуру данных
```

---

## Troubleshooting (Решение проблем)

### Проблема 1: Ошибка "Settings directory is not writable"
**Симптомы:**
```
Exception: Settings directory is not writable: /path/to/APP-B24
```

**Решение:**
1. Проверить права доступа к директории: `ls -la APP-B24/`
2. Установить права на запись: `chmod 755 APP-B24/`
3. Проверить владельца директории: `ls -ld APP-B24/`
4. Установить правильного владельца: `chown www-data:www-data APP-B24/` (или другого пользователя веб-сервера)

### Проблема 2: Ошибка "Failed to write settings.json"
**Симптомы:**
```
Exception: Failed to write settings.json
```

**Решение:**
1. Проверить права на запись файла
2. Проверить свободное место на диске: `df -h`
3. Проверить, не заблокирован ли файл другим процессом
4. Проверить логи PHP на детали ошибки

### Проблема 3: Неправильный формат settings.json
**Симптомы:**
```
Ошибки при чтении settings.json после установки
```

**Решение:**
1. Проверить валидность JSON: `php -r "json_decode(file_get_contents('settings.json'));"`
2. Проверить кодировку файла (должна быть UTF-8)
3. Проверить использование `JSON_UNESCAPED_UNICODE` при сохранении
4. Проверить структуру данных перед сохранением

### Проблема 4: Ошибка валидации домена
**Симптомы:**
```
Invalid domain format
```

**Решение:**
1. Проверить формат домена (должен быть без протокола)
2. Проверить очистку домена от протокола и слешей
3. Проверить валидацию формата Bitrix24 домена

## Риски и митигация

### Риск 1: Несовместимость формата settings.json
**Митигация:** 
- Проверка совместимости с текущим форматом
- Сохранение всех существующих полей
- Тестирование чтения settings.json после установки
- Резервное копирование перед изменениями

### Риск 2: Проблемы с правами доступа
**Митигация:** 
- Проверка прав на запись файла перед сохранением
- Обработка ошибок с понятными сообщениями
- Логирование ошибок для диагностики
- Установка правильных прав доступа (0600 для файла)

### Риск 3: Потеря данных при установке
**Митигация:** 
- Резервное копирование settings.json перед изменениями
- Валидация всех входных данных
- Проверка структуры данных перед сохранением
- Использование блокировки файла (LOCK_EX) при записи

---

## Дополнительные детали

### Важные замечания

**1. Безопасность:**
- Всегда валидировать входные данные
- Использовать `htmlspecialchars()` для экранирования
- Проверять права доступа перед записью
- Устанавливать правильные права доступа (0600 для settings.json)

**2. Обработка ошибок:**
- Всегда использовать try-catch
- Логировать все ошибки
- Возвращать понятные сообщения об ошибках
- Использовать правильные HTTP статус коды

**3. Формат данных:**
- Сохранять все необходимые поля для совместимости
- Использовать `JSON_PRETTY_PRINT` для читаемости
- Использовать `JSON_UNESCAPED_UNICODE` для кириллицы
- Добавлять метаданные (installed_at, installed_by)

## История правок

- **2025-12-20 19:48 (UTC+3, Брест):** Создана задача на миграцию установки
- **2025-12-20 20:25 (UTC+3, Брест):** Добавлены детали валидации, обработки ошибок, troubleshooting секция

---

**Связанные документы:**
- [TASK-000-crest-to-b24phpsdk-overview.md](TASK-000-crest-to-b24phpsdk-overview.md) — обзор миграции
- [DOCS/REFACTOR/crest-to-b24phpsdk-migration.md](../../crest-to-b24phpsdk-migration.md) — детальный план

---

**Версия документа:** 1.1  
**Последнее обновление:** 2025-12-20 20:25 (UTC+3, Брест)

