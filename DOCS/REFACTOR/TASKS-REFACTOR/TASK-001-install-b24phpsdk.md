# TASK-001: Установка и настройка b24phpsdk

**Дата создания:** 2025-12-20 19:48 (UTC+3, Брест)  
**Статус:** Новая  
**Приоритет:** Критический  
**Исполнитель:** Bitrix24 Программист (Vanilla JS)

---

## Описание

Установка официального Bitrix24 PHP SDK (b24phpsdk) через Composer и настройка автозагрузки. Это первый этап миграции с CRest на b24phpsdk.

---

## Контекст

**Часть задачи:** TASK-000 (Миграция CRest → b24phpsdk)  
**Зависит от:** Нет (первая задача)  
**Зависимости от этой задачи:** TASK-002, TASK-003, TASK-004, TASK-005

**Цель:** Подготовить окружение для использования b24phpsdk вместо CRest.

---

## Модули и компоненты

**Создаваемые файлы:**
- `APP-B24/composer.json` — конфигурация Composer
- `APP-B24/composer.lock` — файл блокировки зависимостей (автоматически)
- `APP-B24/vendor/` — установленные зависимости (автоматически)

**Обновляемые файлы:**
- `APP-B24/src/bootstrap.php` — добавление автозагрузки Composer

---

## Зависимости

**От каких задач зависит:**
- Нет (первая задача в цепочке)

**Какие задачи зависят от этой:**
- TASK-002 (Создание Bitrix24SdkClient)
- TASK-003 (Обновление Bitrix24ApiService)
- TASK-004 (Миграция установки)
- TASK-005 (Обновление bootstrap)

---

## Ступенчатые подзадачи

### 1. Проверка наличия Composer

1. **Проверить установлен ли Composer:**
   ```bash
   composer --version
   ```

2. **Если не установлен — установить:**
   ```bash
   # Следовать инструкциям: https://getcomposer.org/download/
   ```

### 2. Создание composer.json

1. **Создать файл `APP-B24/composer.json`:**
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

2. **Проверить синтаксис JSON:**
   ```bash
   composer validate
   ```

### 3. Установка b24phpsdk

1. **Установить зависимости:**
   ```bash
   cd APP-B24
   composer install
   ```
   
   **Важно:** Если возникают ошибки:
   - Проверить версию PHP: `php -v` (должна быть >= 8.4)
   - Обновить Composer: `composer self-update`
   - Очистить кеш: `composer clear-cache`

2. **Проверить установку:**
   ```bash
   composer show bitrix24/b24phpsdk
   ```
   
   **Ожидаемый вывод:**
   ```
   name     : bitrix24/b24phpsdk
   descrip. : Official Bitrix24 PHP SDK
   versions : * 1.0.0
   ```

3. **Проверить версию SDK:**
   ```bash
   composer show bitrix24/b24phpsdk | grep versions
   ```
   
   **Зафиксировать версию в composer.json:**
   ```json
   "require": {
       "bitrix24/b24phpsdk": "^1.0"  // или конкретная версия "1.0.0"
   }
   ```

4. **Проверить создание vendor/autoload.php:**
   ```bash
   ls -la vendor/autoload.php
   ```
   
   **Проверить права доступа:**
   ```bash
   chmod 644 vendor/autoload.php
   ```

5. **Проверить структуру vendor/:**
   ```bash
   ls -la vendor/bitrix24/
   # Должна быть директория b24phpsdk
   ```

### 4. Настройка автозагрузки

1. **Открыть `APP-B24/src/bootstrap.php`**

2. **Добавить в начало файла (после открывающего тега PHP):**
   ```php
   // Автозагрузка Composer
   require_once(__DIR__ . '/../vendor/autoload.php');
   ```

3. **Проверить, что автозагрузка не конфликтует с существующим кодом**

### 5. Проверка загрузки классов

1. **Создать тестовый скрипт `APP-B24/test-autoload.php`:**
   ```php
   <?php
   /**
    * Тестовый скрипт для проверки автозагрузки классов
    * 
    * Проверяет загрузку:
    * - Классов b24phpsdk
    * - Классов приложения
    * - Автозагрузку Composer
    */
   
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   
   echo "=== Проверка автозагрузки ===\n\n";
   
   // 1. Проверка автозагрузки Composer
   $autoloadFile = __DIR__ . '/vendor/autoload.php';
   if (!file_exists($autoloadFile)) {
       echo "✗ Файл vendor/autoload.php не найден\n";
       echo "  Выполните: composer install\n";
       exit(1);
   }
   require_once($autoloadFile);
   echo "✓ Автозагрузка Composer подключена\n";
   
   // 2. Проверка загрузки классов SDK
   $sdkClasses = [
       'Bitrix24\SDK\Core\ApiClient',
       'Bitrix24\SDK\Core\Credentials\ApplicationProfile',
       'Bitrix24\SDK\Core\Credentials\Credentials',
       'Bitrix24\SDK\Core\Credentials\WebhookUrl',
       'Bitrix24\SDK\Core\Exceptions\BaseException'
   ];
   
   echo "\n--- Проверка классов SDK ---\n";
   $sdkOk = true;
   foreach ($sdkClasses as $class) {
       if (class_exists($class)) {
           echo "✓ {$class}\n";
       } else {
           echo "✗ {$class} - НЕ НАЙДЕН\n";
           $sdkOk = false;
       }
   }
   
   if (!$sdkOk) {
       echo "\n✗ Ошибка загрузки классов SDK\n";
       echo "  Проверьте установку: composer show bitrix24/b24phpsdk\n";
       exit(1);
   }
   
   // 3. Проверка загрузки классов приложения
   echo "\n--- Проверка классов приложения ---\n";
   require_once(__DIR__ . '/src/bootstrap.php');
   
   $appClasses = [
       'App\Services\LoggerService',
       'App\Services\ConfigService',
       'App\Services\Bitrix24ApiService',
       'App\Clients\ApiClientInterface'
   ];
   
   $appOk = true;
   foreach ($appClasses as $class) {
       if (class_exists($class)) {
           echo "✓ {$class}\n";
       } else {
           echo "✗ {$class} - НЕ НАЙДЕН\n";
           $appOk = false;
       }
   }
   
   if (!$appOk) {
       echo "\n✗ Ошибка загрузки классов приложения\n";
       exit(1);
   }
   
   // 4. Проверка версии SDK (если доступно)
   echo "\n--- Информация о SDK ---\n";
   if (defined('Bitrix24\SDK\Core\ApiClient::VERSION')) {
       $version = Bitrix24\SDK\Core\ApiClient::VERSION;
       echo "Версия SDK: {$version}\n";
   } else {
       echo "Версия SDK: информация недоступна\n";
   }
   
   echo "\n=== Все проверки пройдены успешно ===\n";
   ```

2. **Запустить тест:**
   ```bash
   php APP-B24/test-autoload.php
   ```
   
   **Ожидаемый вывод:**
   ```
   === Проверка автозагрузки ===
   
   ✓ Автозагрузка Composer подключена
   
   --- Проверка классов SDK ---
   ✓ Bitrix24\SDK\Core\ApiClient
   ✓ Bitrix24\SDK\Core\Credentials\ApplicationProfile
   ...
   
   === Все проверки пройдены успешно ===
   ```

3. **Если тест не проходит:**
   - Проверить пути к файлам
   - Проверить права доступа
   - Выполнить `composer dump-autoload`

---

## Технические требования

- **PHP:** 8.4+
- **Composer:** Последняя стабильная версия
- **b24phpsdk:** Версия ^1.0 (или последняя стабильная)
- **Автозагрузка:** PSR-4 стандарт

---

## Критерии приёмки

- [ ] Composer установлен и работает
- [ ] Файл `composer.json` создан с правильной конфигурацией
- [ ] b24phpsdk установлен через `composer install`
- [ ] Файл `vendor/autoload.php` существует
- [ ] Автозагрузка добавлена в `bootstrap.php`
- [ ] Тестовый скрипт подтверждает загрузку классов SDK
- [ ] Тестовый скрипт подтверждает загрузку классов приложения
- [ ] Нет конфликтов с существующим кодом

---

## Тестирование

### 1. Проверка установки Composer
```bash
composer --version
# Должна быть выведена версия Composer
```

### 2. Проверка установки b24phpsdk
```bash
cd APP-B24
composer show bitrix24/b24phpsdk
# Должна быть выведена информация о пакете
```

### 3. Проверка автозагрузки
```bash
php APP-B24/test-autoload.php
# Должны быть выведены сообщения об успешной загрузке
```

### 4. Проверка структуры файлов
```bash
ls -la APP-B24/vendor/autoload.php
# Файл должен существовать
```

---

## Troubleshooting (Решение проблем)

### Проблема 1: Composer не найден
**Симптомы:**
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
chmod +x /usr/local/bin/composer
```

### Проблема 2: Ошибка при установке пакета
**Симптомы:**
```
Your requirements could not be resolved to an installable set of packages.
```

**Решение:**
1. Проверить версию PHP: `php -v` (должна быть >= 8.4)
2. Обновить Composer: `composer self-update`
3. Очистить кеш: `composer clear-cache`
4. Проверить интернет-соединение
5. Попробовать установить конкретную версию: `composer require bitrix24/b24phpsdk:1.0.0`

### Проблема 3: Классы SDK не найдены
**Симптомы:**
```
Class 'Bitrix24\SDK\Core\ApiClient' not found
```

**Решение:**
1. Проверить, что автозагрузка подключена: `require_once('vendor/autoload.php');`
2. Выполнить: `composer dump-autoload`
3. Проверить, что пакет установлен: `composer show bitrix24/b24phpsdk`
4. Проверить пути к файлам в `composer.json`

### Проблема 4: Конфликт версий
**Симптомы:**
```
Conflict with another package
```

**Решение:**
1. Проверить `composer.lock` на конфликты
2. Обновить зависимости: `composer update`
3. Использовать конкретную версию SDK в `composer.json`

## Риски и митигация

### Риск 1: Конфликт версий PHP
**Митигация:** 
- Проверить версию PHP перед установкой: `php -v`
- Обновить PHP при необходимости
- Использовать правильную версию в `composer.json`: `"php": ">=8.4"`

### Риск 2: Проблемы с автозагрузкой
**Митигация:** 
- Использовать тестовый скрипт для проверки
- Проверить пути к файлам в `composer.json`
- Выполнить `composer dump-autoload` после изменений
- Проверить права доступа к файлам

### Риск 3: Конфликты зависимостей
**Митигация:** 
- Проверить `composer.lock` на конфликты
- Использовать конкретные версии пакетов
- Обновлять зависимости постепенно
- Тестировать после каждого обновления

---

## Дополнительные проверки

### Проверка совместимости версий

**Проверить версию SDK:**
```bash
composer show bitrix24/b24phpsdk | grep versions
```

**Зафиксировать версию в composer.json:**
```json
{
    "require": {
        "bitrix24/b24phpsdk": "^1.0"  // или "1.0.0" для конкретной версии
    }
}
```

**Обновление SDK (если нужно):**
```bash
# Проверить доступные обновления
composer outdated bitrix24/b24phpsdk

# Обновить до последней версии в пределах ^1.0
composer update bitrix24/b24phpsdk

# Обновить до конкретной версии
composer require bitrix24/b24phpsdk:1.0.1
```

### Проверка структуры vendor/

**Проверить установленные файлы:**
```bash
ls -la vendor/bitrix24/b24phpsdk/
# Должны быть директории: src/, tests/, composer.json, README.md
```

**Проверить автозагрузку:**
```bash
cat vendor/composer/autoload_psr4.php | grep Bitrix24
# Должен быть путь к SDK классам
```

## История правок

- **2025-12-20 19:48 (UTC+3, Брест):** Создана задача на установку b24phpsdk
- **2025-12-20 20:25 (UTC+3, Брест):** Добавлены детали проверки установки, troubleshooting секция, детали проверки версий

---

**Связанные документы:**
- [TASK-000-crest-to-b24phpsdk-overview.md](TASK-000-crest-to-b24phpsdk-overview.md) — обзор миграции
- [DOCS/REFACTOR/crest-to-b24phpsdk-migration.md](../../crest-to-b24phpsdk-migration.md) — детальный план

---

**Версия документа:** 1.1  
**Последнее обновление:** 2025-12-20 20:25 (UTC+3, Брест)

