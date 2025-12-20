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

3. **Добавить обработку ошибок:**
   - Валидация входных данных
   - Проверка прав на запись файла
   - Обработка ошибок JSON

4. **Добавить логирование:**
   - Логирование успешной установки
   - Логирование ошибок

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

## Риски и митигация

### Риск 1: Несовместимость формата settings.json
**Митигация:** Проверка совместимости с текущим форматом, тестирование

### Риск 2: Проблемы с правами доступа
**Митигация:** Проверка прав на запись файла, обработка ошибок

### Риск 3: Потеря данных при установке
**Митигация:** Резервное копирование перед изменениями, валидация данных

---

## История правок

- **2025-12-20 19:48 (UTC+3, Брест):** Создана задача на миграцию установки

---

**Связанные документы:**
- [TASK-000-crest-to-b24phpsdk-overview.md](TASK-000-crest-to-b24phpsdk-overview.md) — обзор миграции

---

**Версия документа:** 1.0  
**Последнее обновление:** 2025-12-20 19:48 (UTC+3, Брест)

