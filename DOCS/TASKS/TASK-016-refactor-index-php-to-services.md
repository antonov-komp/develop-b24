# TASK-016: Рефакторинг index.php — разделение на сервисы и слои

**Дата создания:** 2025-12-29 20:16 (UTC+3, Брест)  
**Статус:** Завершена  
**Приоритет:** Высокий  
**Исполнитель:** Bitrix24 Программист (Vanilla JS) / Full-Stack разработчик

---

## Описание

Переписать `index.php` как основную точку входа в Bitrix24 REST приложение, разделив всю логику на сервисы и слои. Упростить сам `index.php`, оставив только минимальную логику инициализации и вызов основного сервиса.

**Цель:** 
- Упростить `index.php` до минимума (50-100 строк вместо 590)
- Вынести всю бизнес-логику в отдельные сервисы
- Обеспечить модульность и переиспользуемость кода
- Улучшить тестируемость и поддерживаемость

**Ключевой принцип:** `index.php` должен быть тонким контроллером, который только:
- Инициализирует окружение
- Вызывает основной сервис обработки запроса
- Обрабатывает критические ошибки

---

## Контекст

Текущий `index.php` (590 строк) содержит:
1. **Инициализацию окружения** (строки 18-30)
2. **Логирование начала работы** (строки 38-49)
3. **Получение и нормализацию маршрута** (строки 52-58)
4. **Проверку конфигурации внешнего доступа** (строки 61-74)
5. **Проверку включения приложения** (строки 77-112)
6. **Проверку авторизации Bitrix24** (строки 114-191)
7. **Получение данных пользователя** (строки 193-222)
8. **Загрузку Vue.js приложения** (строки 224-226)
9. **Вспомогательные функции:**
   - `getUserDataWithDepartments()` (строки 245-323)
   - `buildAuthInfo()` (строки 325-519)
   - `validateVueAppData()` (строки 521-560)
   - `handleFatalError()` (строки 562-589)

**Проблемы текущей реализации:**
- Слишком много логики в одном файле (590 строк)
- Функции в глобальной области видимости (нарушение ООП принципов)
- Сложная логика проверки авторизации и режимов доступа
- Смешение ответственности (конфигурация, авторизация, данные пользователя, загрузка Vue.js)
- Сложно тестировать отдельные части
- Сложно поддерживать и расширять

**Связи с другими задачами:**
- TASK-013: Рефакторинг и улучшение логики главной страницы (index.php) — частично выполнена, но логика осталась в index.php
- Использует существующие сервисы: `AuthService`, `UserService`, `ConfigService`, `VueAppService`, `LoggerService`, `Bitrix24ApiService`

---

## Модули и компоненты

### Файлы для рефакторинга:

- `APP-B24/index.php` — главная точка входа (основной файл для упрощения)

### Новые сервисы для создания:

1. **`APP-B24/src/Services/IndexPageService.php`** — основной сервис обработки запроса к index.php
   - Метод `handle()` — главный метод обработки запроса
   - Координирует работу всех других сервисов
   - Возвращает результат обработки (данные для Vue.js или редирект)

2. **`APP-B24/src/Services/RouteService.php`** — сервис работы с маршрутами
   - Метод `getRoute()` — получение и нормализация маршрута из запроса
   - Метод `normalizeRoute()` — нормализация маршрута (убирает лишние слеши)
   - **Переиспользование:** Логику можно взять из `api/index.php` (строки 25-61), адаптировав для index.php

3. **`APP-B24/src/Services/AuthInfoBuilderService.php`** — сервис построения информации об авторизации
   - Метод `build()` — построение authInfo (вынести логику из `buildAuthInfo()`)
   - Обработка всех режимов доступа:
     - Режим 1: Только Bitrix24 (external_access=false)
     - Режим 2: Везде (external_access=true, block_bitrix24_iframe=false)
     - Режим 3: Только внешний с токеном админа (external_access=true, block_bitrix24_iframe=true)

4. **`APP-B24/src/Services/UserDataService.php`** — сервис работы с данными пользователя
   - Метод `getUserDataWithDepartments()` — получение данных пользователя с отделами и фото (вынести из функции)
   - Метод `getUserPhotoUrl()` — получение URL фото пользователя
   - Метод `formatUserData()` — форматирование данных пользователя для Vue.js
   - **Альтернатива:** Рассмотреть расширение `UserService` вместо создания нового сервиса, так как `UserService` уже содержит `getUserDepartments()` и `getUserFullName()`

5. **`APP-B24/src/Services/ConfigValidatorService.php`** — сервис валидации конфигурации
   - Метод `validateIndexPageConfig()` — валидация конфигурации главной страницы
   - Метод `checkAppEnabled()` — проверка, включено ли приложение
   - Метод `renderConfigErrorPage()` — отображение страницы ошибки конфигурации
   - **Переиспользование:** 
     - Использовать `ConfigService::getIndexPageConfig()` для получения конфигурации
     - Использовать шаблон `templates/config-error.php` для отображения ошибки

6. **`APP-B24/src/Services/ErrorHandlerService.php`** — сервис обработки ошибок
   - Метод `handleFatalError()` — обработка фатальных ошибок (вынести из функции `handleFatalError()`)
   - Метод `renderErrorPage()` — отображение страницы ошибки (универсальный метод)
   - Метод `renderConfigErrorPage()` — отображение страницы ошибки конфигурации (использует шаблон)
   - Метод `logError()` — логирование ошибок
   - **Переиспользование:**
     - Использовать `VueAppService::renderErrorPage()` как основу для универсального метода
     - Использовать шаблон `templates/config-error.php` для ошибок конфигурации
     - Вынести логику из `handleFatalError()` в index.php

7. **`APP-B24/src/Services/AccessModeService.php`** — сервис определения режима доступа
   - Метод `determineAccessMode()` — определение режима доступа на основе конфигурации и запроса
   - Метод `checkExternalAccess()` — проверка внешнего доступа
   - Метод `checkBitrix24IframeBlocked()` — проверка блокировки Bitrix24 iframe
   - Метод `hasUserTokenInRequest()` — проверка наличия токена пользователя в запросе

### Обновление существующих сервисов:

- `APP-B24/src/Services/AuthService.php` — может потребоваться добавление методов для работы с режимами доступа
- `APP-B24/src/Services/ConfigService.php` — уже содержит методы для работы с конфигурацией, используется как есть
- `APP-B24/src/Services/UserService.php` — **рассмотреть расширение** методами для работы с отделами и фото пользователя (альтернатива созданию `UserDataService`)
- `APP-B24/src/Services/VueAppService.php` — уже содержит `renderErrorPage()`, можно переиспользовать логику

### Переиспользование существующих компонентов:

- **`templates/config-error.php`** — шаблон для ошибки конфигурации, использовать в `ConfigValidatorService` и `ErrorHandlerService`
- **`src/Helpers/DomainResolver.php`** — хелпер для получения домена, можно использовать в `AuthInfoBuilderService`
- **Логика маршрутизации из `api/index.php`** — адаптировать для `RouteService`

### Технический долг (отметить, но не исправлять в этой задаче):

- **`src/Helpers/AdminChecker.php`** — дублирует функционал `UserService::isAdmin()`. Рассмотреть удаление или объединение в будущем.

---

## Анализ существующего кода и переиспользование

### Найденные компоненты для переиспользования:

1. **Логика маршрутизации в `api/index.php` (строки 25-61):**
   - Уже реализована логика получения маршрута из `$_GET['route']` и `$_SERVER['REQUEST_URI']`
   - Обработка сегментов маршрута
   - Можно адаптировать для `RouteService`, упростив для index.php (не нужны subRoute и segments)

2. **Шаблон ошибки конфигурации `templates/config-error.php`:**
   - Готовый шаблон с красивым дизайном
   - Принимает параметры `message` и `last_updated`
   - Можно использовать в `ConfigValidatorService::renderConfigErrorPage()`

3. **Метод `VueAppService::renderErrorPage()` (строки 370-407):**
   - Уже реализована логика отображения страницы ошибки
   - Поддержка development/production режимов
   - Можно переиспользовать в `ErrorHandlerService` или вынести в общий метод

4. **`UserService` уже содержит полезные методы:**
   - `getUserDepartments()` — получение отделов пользователя
   - `getUserFullName()` — получение полного имени
   - `isAdmin()` — проверка администратора
   - **Рекомендация:** Рассмотреть расширение `UserService` методами `getUserDataWithDepartments()` и `getUserPhotoUrl()` вместо создания отдельного `UserDataService`

5. **`ConfigService::getIndexPageConfig()`:**
   - Уже возвращает конфигурацию с полями `enabled`, `external_access`, `block_bitrix24_iframe`, `message`, `last_updated`
   - Можно использовать напрямую в `ConfigValidatorService`

6. **`DomainResolver` хелпер:**
   - Уже реализована логика получения домена из запроса или настроек
   - Можно использовать в `AuthInfoBuilderService` для получения домена

### Технический долг (не исправлять в этой задаче):

1. **Дублирование функционала `AdminChecker` и `UserService::isAdmin()`:**
   - `AdminChecker::check()` дублирует логику `UserService::isAdmin()`
   - **Рекомендация:** В будущей задаче рассмотреть удаление `AdminChecker` и использование только `UserService::isAdmin()`

2. **Логика получения маршрута дублируется:**
   - В `index.php` (строки 52-58) — простая нормализация
   - В `api/index.php` (строки 25-61) — более сложная логика с сегментами
   - **Решение:** Создать единый `RouteService`, который будет использоваться в обоих местах

### Рекомендации по реализации:

1. **RouteService:**
   - Взять логику из `api/index.php`, упростив для index.php (не нужны subRoute и segments)
   - Сделать универсальным для использования в обоих местах

2. **UserDataService vs расширение UserService:**
   - **Вариант 1:** Создать отдельный `UserDataService` для работы с данными пользователя (отделы, фото, форматирование)
   - **Вариант 2:** Расширить `UserService` методами `getUserDataWithDepartments()` и `getUserPhotoUrl()`
   - **Рекомендация:** Вариант 2, так как `UserService` уже содержит связанные методы

3. **ErrorHandlerService:**
   - Объединить логику из `handleFatalError()` (index.php) и `VueAppService::renderErrorPage()`
   - Использовать шаблон `templates/config-error.php` для ошибок конфигурации
   - Создать универсальный метод `renderErrorPage()` с поддержкой разных типов ошибок

4. **ConfigValidatorService:**
   - Использовать `ConfigService::getIndexPageConfig()` для получения конфигурации
   - Использовать шаблон `templates/config-error.php` для отображения ошибки
   - Минимизировать дублирование логики

---

## Зависимости

### От других задач:
- TASK-013: Рефакторинг и улучшение логики главной страницы (index.php) — частично выполнена, но логика осталась в index.php

### От модулей:
- Использует существующие сервисы:
  - `AuthService` — проверка авторизации Bitrix24
  - `UserService` — работа с пользователями
  - `ConfigService` — работа с конфигурацией
  - `VueAppService` — загрузка Vue.js приложения
  - `LoggerService` — логирование
  - `Bitrix24ApiService` — работа с Bitrix24 REST API
  - `AccessControlService` — проверка прав доступа

### Зависимости новых задач:
- Будущие задачи по расширению функциональности смогут использовать новые сервисы
- Упростит добавление новых режимов доступа
- Упростит тестирование отдельных компонентов

---

## Ступенчатые подзадачи

### Этап 1: Анализ и проектирование

1. **Детальный анализ текущего index.php:**
   - Выделить все логические блоки
   - Определить зависимости между блоками
   - Определить входные и выходные данные каждого блока
   - Создать диаграмму потока данных

2. **Проектирование структуры сервисов:**
   - Определить ответственность каждого сервиса
   - Определить интерфейсы и методы сервисов
   - Определить зависимости между сервисами
   - Создать диаграмму зависимостей

3. **Создание технического задания:**
   - Описать структуру каждого сервиса
   - Описать методы и их параметры
   - Описать обработку ошибок
   - Описать логирование

### Этап 2: Создание сервисов (по порядку зависимостей)

4. **Создание RouteService:**
   ```bash
   # Вручную: APP-B24/src/Services/RouteService.php
   ```
   - **Переиспользование:** Адаптировать логику из `api/index.php` (строки 25-61), упростив для index.php
   - Реализовать метод `getRoute()` — получение маршрута из `$_GET['route']` или `$_SERVER['REQUEST_URI']`
   - Реализовать метод `normalizeRoute()` — нормализация маршрута (убирает лишние слеши)
   - Добавить логирование
   - Написать unit-тесты

5. **Создание ConfigValidatorService:**
   ```bash
   # Вручную: APP-B24/src/Services/ConfigValidatorService.php
   ```
   - **Переиспользование:** Использовать `ConfigService::getIndexPageConfig()` для получения конфигурации
   - Реализовать метод `validateIndexPageConfig()` — валидация конфигурации (обёртка над `ConfigService::getIndexPageConfig()`)
   - Реализовать метод `checkAppEnabled()` — проверка включения приложения
   - Реализовать метод `renderConfigErrorPage()` — отображение страницы ошибки конфигурации
   - **Переиспользование:** Использовать шаблон `templates/config-error.php` для отображения ошибки
   - Добавить логирование
   - Написать unit-тесты

6. **Создание AccessModeService:**
   ```bash
   php artisan make:service AccessModeService
   ```
   - Реализовать метод `determineAccessMode()` — определение режима доступа
   - Реализовать метод `checkExternalAccess()` — проверка внешнего доступа
   - Реализовать метод `checkBitrix24IframeBlocked()` — проверка блокировки Bitrix24 iframe
   - Использовать `ConfigService` для получения конфигурации
   - Добавить логирование
   - Написать unit-тесты

7. **Создание ErrorHandlerService:**
   ```bash
   # Вручную: APP-B24/src/Services/ErrorHandlerService.php
   ```
   - **Переиспользование:** 
     - Вынести логику из функции `handleFatalError()` в index.php (строки 573-589)
     - Адаптировать логику из `VueAppService::renderErrorPage()` (строки 370-407)
     - Использовать шаблон `templates/config-error.php` для ошибок конфигурации
   - Реализовать метод `handleFatalError()` — обработка фатальных ошибок
   - Реализовать метод `renderErrorPage()` — универсальный метод отображения страницы ошибки
   - Реализовать метод `renderConfigErrorPage()` — отображение страницы ошибки конфигурации (использует шаблон)
   - Реализовать метод `logError()` — логирование ошибок
   - Использовать `LoggerService` для логирования
   - Добавить поддержку development/production режимов
   - Написать unit-тесты

8. **Создание UserDataService (или расширение UserService):**
   ```bash
   # Вариант 1: Вручную: APP-B24/src/Services/UserDataService.php
   # Вариант 2: Расширить APP-B24/src/Services/UserService.php
   ```
   - **Рекомендация:** Рассмотреть расширение `UserService` вместо создания нового сервиса
   - **Переиспользование:** `UserService` уже содержит `getUserDepartments()` и `getUserFullName()`
   - Реализовать метод `getUserDataWithDepartments()` — получение данных пользователя с отделами (вынести из функции в index.php, строки 245-323)
   - Реализовать метод `getUserPhotoUrl()` — получение URL фото пользователя
   - Реализовать метод `formatUserData()` — форматирование данных пользователя для Vue.js
   - Использовать `UserService` и `Bitrix24ApiService` для получения данных
   - Добавить логирование
   - Написать unit-тесты

9. **Создание AuthInfoBuilderService:**
   ```bash
   # Вручную: APP-B24/src/Services/AuthInfoBuilderService.php
   ```
   - **Переиспользование:** Вынести логику из функции `buildAuthInfo()` в index.php (строки 325-519)
   - Реализовать метод `build()` — построение authInfo
   - Обработать все режимы доступа:
     - Режим 1: Только Bitrix24
     - Режим 2: Везде
     - Режим 3: Только внешний с токеном админа
   - Использовать `AuthService`, `UserService`, `UserDataService` (или расширенный `UserService`), `ConfigService`
   - **Переиспользование:** Использовать `DomainResolver` для получения домена
   - Добавить логирование
   - Написать unit-тесты

10. **Создание IndexPageService:**
    ```bash
    # Вручную: APP-B24/src/Services/IndexPageService.php
    ```
    - Реализовать метод `handle()` — главный метод обработки запроса
    - Координировать работу всех сервисов:
      1. Получение маршрута через `RouteService`
      2. Валидация конфигурации через `ConfigValidatorService`
      3. Определение режима доступа через `AccessModeService`
      4. Проверка авторизации через `AuthService`
      5. Построение authInfo через `AuthInfoBuilderService`
      6. Загрузка Vue.js через `VueAppService`
    - Обработка ошибок через `ErrorHandlerService`
    - **Переиспользование:** Использовать существующие сервисы из bootstrap.php
    - Добавить логирование
    - Написать unit-тесты

### Этап 3: Рефакторинг index.php

11. **Упрощение index.php:**
    - Удалить всю бизнес-логику
    - Оставить только:
      - Инициализацию окружения (error_reporting, display_errors)
      - Подключение bootstrap.php
      - Вызов `IndexPageService::handle()`
      - Обработку критических ошибок через try-catch
    - Целевой размер: 50-100 строк

12. **Удаление глобальных функций:**
    - Удалить функцию `getUserDataWithDepartments()` (перенесена в `UserDataService`)
    - Удалить функцию `buildAuthInfo()` (перенесена в `AuthInfoBuilderService`)
    - Удалить функцию `validateVueAppData()` (перенесена в `IndexPageService` или `AuthInfoBuilderService`)
    - Удалить функцию `handleFatalError()` (перенесена в `ErrorHandlerService`)

13. **Обновление bootstrap.php:**
    - Добавить инициализацию новых сервисов:
      - `RouteService`
      - `ConfigValidatorService`
      - `AccessModeService`
      - `ErrorHandlerService`
      - `UserDataService`
      - `AuthInfoBuilderService`
      - `IndexPageService`

### Этап 4: Тестирование и проверка

14. **Unit-тестирование сервисов:**
    - Написать тесты для каждого нового сервиса
    - Проверить все режимы доступа
    - Проверить обработку ошибок
    - Проверить логирование

15. **Интеграционное тестирование:**
    - Проверить работу index.php с новыми сервисами
    - Проверить все сценарии доступа:
      - Только Bitrix24 (external_access=false)
      - Везде (external_access=true, block_bitrix24_iframe=false)
      - Только внешний (external_access=true, block_bitrix24_iframe=true)
    - Проверить обработку ошибок
    - Проверить загрузку Vue.js приложения

16. **Проверка логирования:**
    - Проверить, что все ключевые шаги логируются
    - Проверить формат логов
    - Проверить уровень логирования (info, warning, error)

### Этап 5: Документация и финализация

17. **Обновление документации:**
    - Обновить `DOCS/ARCHITECTURE/application-architecture.md` с описанием новых сервисов
    - Создать диаграмму зависимостей сервисов
    - Обновить комментарии в коде

18. **Проверка соответствия стандартам:**
    - Проверить соответствие PSR-12
    - Проверить именование классов и методов
    - Проверить документацию методов (PHPDoc)

---

## API-методы Bitrix24

Используются существующие методы через `Bitrix24ApiService`:

- `user.current` — получение текущего пользователя
  - Документация: https://context7.com/bitrix24/rest/user.current
- `department.get` — получение данных отдела
  - Документация: https://context7.com/bitrix24/rest/department.get
- `user.get` — получение пользователя по ID
  - Документация: https://context7.com/bitrix24/rest/user.get

---

## Технические требования

### Версии технологий:
- PHP 8.4+
- Bitrix24 REST API (через b24phpsdk)

### Ограничения и особенности:
- Сохранить обратную совместимость с существующим кодом
- Не нарушать работу существующих интеграций
- Сохранить все режимы доступа:
  - Только Bitrix24 (external_access=false)
  - Везде (external_access=true, block_bitrix24_iframe=false)
  - Только внешний (external_access=true, block_bitrix24_iframe=true)

### Требования к производительности:
- Не должно быть деградации производительности
- Использовать кеширование где возможно
- Минимизировать количество вызовов API Bitrix24

### Требования к безопасности:
- Сохранить все проверки авторизации
- Сохранить все проверки прав доступа
- Не допустить утечки данных

---

## Критерии приёмки

### Функциональные требования:
- [ ] `index.php` упрощён до 50-100 строк
- [ ] Вся бизнес-логика вынесена в сервисы
- [ ] Все глобальные функции удалены
- [ ] Все режимы доступа работают корректно:
  - [ ] Только Bitrix24 (external_access=false)
  - [ ] Везде (external_access=true, block_bitrix24_iframe=false)
  - [ ] Только внешний (external_access=true, block_bitrix24_iframe=true)
- [ ] Проверка авторизации работает корректно
- [ ] Получение данных пользователя работает корректно
- [ ] Загрузка Vue.js приложения работает корректно
- [ ] Обработка ошибок работает корректно

### Технические требования:
- [ ] Все новые сервисы созданы и протестированы
- [ ] Unit-тесты написаны для всех сервисов
- [ ] Интеграционные тесты написаны
- [ ] Код соответствует стандартам PSR-12
- [ ] Все методы документированы (PHPDoc)
- [ ] Логирование добавлено для всех ключевых шагов
- [ ] Нет деградации производительности

### Качество кода:
- [ ] Нет дублирования кода
- [ ] Принцип единственной ответственности соблюдён
- [ ] Зависимости инжектируются через конструктор
- [ ] Код легко тестировать
- [ ] Код легко поддерживать и расширять
- [ ] Переиспользованы существующие компоненты (шаблоны, хелперы, методы сервисов)
- [ ] Избежано создание велосипедов (дублирование существующего функционала)

### Документация:
- [ ] Архитектура обновлена
- [ ] Диаграммы зависимостей созданы
- [ ] Комментарии в коде актуальны

---

## Примеры кода

### Упрощённый index.php (целевой вариант):

```php
<?php
/**
 * Главная страница приложения Bitrix24
 * 
 * ВАЖНО: PHP не генерирует UI. Вся визуальная часть на Vue.js.
 * PHP только:
 * - Проверяет авторизацию
 * - Получает данные
 * - Передаёт данные в Vue.js
 * - Загружает Vue.js приложение
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */

use App\Services\IndexPageService;
use App\Services\ErrorHandlerService;

// Определение окружения (development/production)
$appEnv = getenv('APP_ENV') ?: 'production';

// Условное включение отладочных настроек
if ($appEnv === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php-errors.log');
}

try {
    // Инициализация окружения
    require_once(__DIR__ . '/src/bootstrap.php');
    
    // Обработка запроса через основной сервис
    $indexPageService = new IndexPageService(
        $routeService,
        $configValidatorService,
        $accessModeService,
        $authService,
        $authInfoBuilderService,
        $vueAppService,
        $errorHandlerService,
        $logger
    );
    
    $indexPageService->handle();
    
} catch (\Throwable $e) {
    // Единая точка обработки критических ошибок
    $errorHandlerService = new ErrorHandlerService($logger);
    $errorHandlerService->handleFatalError($e, $appEnv);
}
```

### Пример IndexPageService::handle():

```php
<?php

namespace App\Services;

/**
 * Основной сервис обработки запроса к index.php
 * 
 * Координирует работу всех сервисов для обработки запроса
 * Документация: https://context7.com/bitrix24/rest/
 */
class IndexPageService
{
    protected RouteService $routeService;
    protected ConfigValidatorService $configValidatorService;
    protected AccessModeService $accessModeService;
    protected AuthService $authService;
    protected AuthInfoBuilderService $authInfoBuilderService;
    protected VueAppService $vueAppService;
    protected ErrorHandlerService $errorHandlerService;
    protected LoggerService $logger;
    
    public function __construct(
        RouteService $routeService,
        ConfigValidatorService $configValidatorService,
        AccessModeService $accessModeService,
        AuthService $authService,
        AuthInfoBuilderService $authInfoBuilderService,
        VueAppService $vueAppService,
        ErrorHandlerService $errorHandlerService,
        LoggerService $logger
    ) {
        $this->routeService = $routeService;
        $this->configValidatorService = $configValidatorService;
        $this->accessModeService = $accessModeService;
        $this->authService = $authService;
        $this->authInfoBuilderService = $authInfoBuilderService;
        $this->vueAppService = $vueAppService;
        $this->errorHandlerService = $errorHandlerService;
        $this->logger = $logger;
    }
    
    /**
     * Обработка запроса к index.php
     * 
     * @return void
     * @throws \Exception При критических ошибках
     */
    public function handle(): void
    {
        // 1. Получение маршрута
        $route = $this->routeService->getRoute();
        
        // 2. Валидация конфигурации
        $configValidation = $this->configValidatorService->validateIndexPageConfig();
        if (!$configValidation['enabled']) {
            $this->configValidatorService->getConfigErrorPage($configValidation);
            exit;
        }
        
        // 3. Определение режима доступа
        $accessMode = $this->accessModeService->determineAccessMode($configValidation);
        
        // 4. Проверка авторизации
        $authResult = $this->checkAuthorization($accessMode);
        
        // 5. Построение данных для Vue.js (только для главной страницы)
        $vueAppData = null;
        if ($route === '/') {
            $vueAppData = $this->buildVueAppData($authResult, $accessMode);
        }
        
        // 6. Загрузка Vue.js приложения
        $this->vueAppService->load($route, $vueAppData);
    }
    
    /**
     * Проверка авторизации в зависимости от режима доступа
     * 
     * @param array $accessMode Режим доступа
     * @return bool Результат проверки авторизации
     */
    protected function checkAuthorization(array $accessMode): bool
    {
        // Логика проверки авторизации в зависимости от режима
        // Использует AuthService и AccessModeService
        // ...
    }
    
    /**
     * Построение данных для Vue.js
     * 
     * @param bool $authResult Результат проверки авторизации
     * @param array $accessMode Режим доступа
     * @return array Данные для Vue.js
     */
    protected function buildVueAppData(bool $authResult, array $accessMode): array
    {
        $authInfo = $this->authInfoBuilderService->build($authResult, $accessMode);
        
        return [
            'authInfo' => $authInfo,
            'externalAccessEnabled' => $accessMode['external_access_enabled']
        ];
    }
}
```

### Пример RouteService (переиспользование логики из api/index.php):

```php
<?php

namespace App\Services;

/**
 * Сервис работы с маршрутами
 * 
 * Переиспользует и адаптирует логику из api/index.php
 * Документация: https://context7.com/bitrix24/rest/
 */
class RouteService
{
    protected LoggerService $logger;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Получение маршрута из запроса
     * 
     * Адаптировано из api/index.php (строки 25-61)
     * 
     * @return string Нормализованный маршрут
     */
    public function getRoute(): string
    {
        // Пробуем получить маршрут из query параметров
        $route = $_GET['route'] ?? null;
        
        // Если маршрут не в query, пытаемся извлечь из REQUEST_URI
        if (!$route) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $path = parse_url($requestUri, PHP_URL_PATH) ?: '';
            
            // Удаляем префиксы для index.php
            $path = preg_replace('#^/APP-B24/index\.php#', '', $path);
            $path = preg_replace('#^/APP-B24#', '', $path);
            $path = preg_replace('#^/index\.php#', '', $path);
            
            $segments = array_filter(explode('/', trim($path, '/')));
            $route = $segments[0] ?? '/';
        }
        
        // Нормализация маршрута
        return $this->normalizeRoute($route);
    }
    
    /**
     * Нормализация маршрута
     * 
     * @param string $route Маршрут для нормализации
     * @return string Нормализованный маршрут
     */
    public function normalizeRoute(string $route): string
    {
        $route = '/' . trim($route, '/');
        if ($route === '//') {
            $route = '/';
        }
        
        $this->logger->log('RouteService: Route normalized', [
            'original' => $route,
            'normalized' => $route
        ], 'debug');
        
        return $route;
    }
}
```

### Пример ConfigValidatorService (переиспользование ConfigService и шаблона):

```php
<?php

namespace App\Services;

/**
 * Сервис валидации конфигурации
 * 
 * Переиспользует ConfigService::getIndexPageConfig() и шаблон templates/config-error.php
 * Документация: https://context7.com/bitrix24/rest/
 */
class ConfigValidatorService
{
    protected ConfigService $configService;
    protected LoggerService $logger;
    protected string $configErrorTemplatePath;
    
    public function __construct(ConfigService $configService, LoggerService $logger)
    {
        $this->configService = $configService;
        $this->logger = $logger;
        $this->configErrorTemplatePath = __DIR__ . '/../../templates/config-error.php';
    }
    
    /**
     * Валидация конфигурации главной страницы
     * 
     * Переиспользует ConfigService::getIndexPageConfig()
     * 
     * @return array Результат валидации
     */
    public function validateIndexPageConfig(): array
    {
        return $this->configService->getIndexPageConfig();
    }
    
    /**
     * Проверка, включено ли приложение
     * 
     * @return bool true если приложение включено
     */
    public function checkAppEnabled(): bool
    {
        $config = $this->validateIndexPageConfig();
        return $config['enabled'] ?? true;
    }
    
    /**
     * Отображение страницы ошибки конфигурации
     * 
     * Переиспользует шаблон templates/config-error.php
     * 
     * @param array $config Конфигурация с полями message и last_updated
     * @return void
     */
    public function renderConfigErrorPage(array $config): void
    {
        $message = $config['message'] ?? 'Интерфейс приложения временно недоступен. Пожалуйста, попробуйте позже.';
        $lastUpdated = $config['last_updated'] ?? null;
        
        $this->logger->log('ConfigValidatorService: Rendering config error page', [
            'message' => $message,
            'last_updated' => $lastUpdated
        ], 'info');
        
        // Используем существующий шаблон
        if (file_exists($this->configErrorTemplatePath)) {
            $originalGet = $_GET;
            $_GET['message'] = $message;
            if ($lastUpdated) {
                $_GET['last_updated'] = $lastUpdated;
            }
            require_once($this->configErrorTemplatePath);
            $_GET = $originalGet;
        } else {
            // Fallback если шаблон не найден
            http_response_code(503);
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Интерфейс недоступен</title></head><body>';
            echo '<h1>Интерфейс недоступен</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
            if ($lastUpdated) {
                echo '<p><small>Последнее обновление: ' . htmlspecialchars($lastUpdated) . '</small></p>';
            }
            echo '</body></html>';
        }
        exit;
    }
}
```

### Пример AuthInfoBuilderService::build():

```php
<?php

namespace App\Services;

/**
 * Сервис построения информации об авторизации
 * 
 * Обрабатывает все режимы доступа и строит authInfo для Vue.js
 * Документация: https://context7.com/bitrix24/rest/
 */
class AuthInfoBuilderService
{
    protected AuthService $authService;
    protected UserService $userService;
    protected UserDataService $userDataService;
    protected ConfigService $configService;
    protected LoggerService $logger;
    
    public function __construct(
        AuthService $authService,
        UserService $userService,
        UserDataService $userDataService,
        ConfigService $configService,
        LoggerService $logger
    ) {
        $this->authService = $authService;
        $this->userService = $userService;
        $this->userDataService = $userDataService;
        $this->configService = $configService;
        $this->logger = $logger;
    }
    
    /**
     * Построение информации об авторизации
     * 
     * Режимы работы:
     * 1. Только Bitrix24: external_access=false - использует токен пользователя из запроса
     * 2. Везде: external_access=true, block_bitrix24_iframe=false - работает везде, использует токен пользователя если есть
     * 3. Только внешний с токеном админа: external_access=true, block_bitrix24_iframe=true - использует токен админа из settings.json
     * 
     * @param bool $authResult Результат проверки авторизации
     * @param array $accessMode Режим доступа
     * @return array Информация об авторизации
     */
    public function build(bool $authResult, array $accessMode): array
    {
        $authInfo = [
            'is_authenticated' => false,
            'user' => null,
            'is_admin' => false,
            'domain' => null,
            'auth_id' => null
        ];
        
        // Режим 1: Только Bitrix24
        if ($authResult && !$accessMode['external_access_enabled']) {
            return $this->buildBitrix24OnlyMode($authInfo);
        }
        
        // Режим 3: Только внешний с токеном админа
        if ($accessMode['external_access_enabled'] && $accessMode['block_bitrix24_iframe']) {
            return $this->buildExternalOnlyMode($authInfo);
        }
        
        // Режим 2: Везде
        return $this->buildEverywhereMode($authInfo, $authResult);
    }
    
    /**
     * Построение authInfo для режима "Только Bitrix24"
     * 
     * @param array $authInfo Базовая структура authInfo
     * @return array Обновлённая authInfo
     */
    protected function buildBitrix24OnlyMode(array $authInfo): array
    {
        // Логика из режима 1 в buildAuthInfo()
        // ...
    }
    
    /**
     * Построение authInfo для режима "Только внешний"
     * 
     * @param array $authInfo Базовая структура authInfo
     * @return array Обновлённая authInfo
     */
    protected function buildExternalOnlyMode(array $authInfo): array
    {
        // Логика из режима 3 в buildAuthInfo()
        // ...
    }
    
    /**
     * Построение authInfo для режима "Везде"
     * 
     * @param array $authInfo Базовая структура authInfo
     * @param bool $authResult Результат проверки авторизации
     * @return array Обновлённая authInfo
     */
    protected function buildEverywhereMode(array $authInfo, bool $authResult): array
    {
        // Логика из режима 2 в buildAuthInfo()
        // ...
    }
}
```

---

## Тестирование

### Unit-тестирование:

1. **RouteService:**
   - Тест получения маршрута из `$_GET['route']`
   - Тест получения маршрута из `$_SERVER['REQUEST_URI']`
   - Тест нормализации маршрута

2. **ConfigValidatorService:**
   - Тест валидации конфигурации при включённом приложении
   - Тест валидации конфигурации при выключенном приложении
   - Тест получения страницы ошибки

3. **AccessModeService:**
   - Тест определения режима "Только Bitrix24"
   - Тест определения режима "Везде"
   - Тест определения режима "Только внешний"

4. **AuthInfoBuilderService:**
   - Тест построения authInfo для режима "Только Bitrix24"
   - Тест построения authInfo для режима "Везде"
   - Тест построения authInfo для режима "Только внешний"

5. **UserDataService:**
   - Тест получения данных пользователя с отделами
   - Тест получения URL фото пользователя
   - Тест форматирования данных пользователя

6. **ErrorHandlerService:**
   - Тест обработки фатальных ошибок
   - Тест отображения страницы ошибки
   - Тест логирования ошибок

### Интеграционное тестирование:

1. **Тест работы index.php:**
   - Проверить все режимы доступа
   - Проверить обработку ошибок
   - Проверить загрузку Vue.js приложения

2. **Тест сценариев доступа:**
   - Сценарий 1: Только Bitrix24 (external_access=false)
   - Сценарий 2: Везде (external_access=true, block_bitrix24_iframe=false)
   - Сценарий 3: Только внешний (external_access=true, block_bitrix24_iframe=true)

3. **Тест обработки ошибок:**
   - Тест обработки ошибок конфигурации
   - Тест обработки ошибок авторизации
   - Тест обработки фатальных ошибок

---

## История правок

- **2025-12-29 20:16 (UTC+3, Брест):** Создана задача TASK-016 на рефакторинг index.php с разделением на сервисы и слои
- **2025-12-29 20:30 (UTC+3, Брест):** Добавлен анализ существующего кода и рекомендации по переиспользованию компонентов:
  - Обновлён раздел "Модули и компоненты" с указанием переиспользования
  - Добавлен раздел "Анализ существующего кода и переиспользование"
  - Обновлены подзадачи с указанием источников для переиспользования
  - Добавлены рекомендации по расширению `UserService` вместо создания `UserDataService`
  - Отмечен технический долг (дублирование `AdminChecker` и `UserService::isAdmin()`)
- **2025-12-29 20:48 (UTC+3, Брест):** Задача успешно завершена:
  - `index.php` упрощён с 590 строк до 44 строк
  - Создано 7 новых сервисов: `IndexPageService`, `RouteService`, `ConfigValidatorService`, `AccessModeService`, `ErrorHandlerService`, `AuthInfoBuilderService`, `UserService` (расширен)
  - Вся бизнес-логика вынесена в сервисы с dependency injection
  - Все глобальные функции удалены и перенесены в сервисы
  - Обновлён `bootstrap.php` для инициализации всех новых сервисов
  - Все режимы доступа работают корректно
  - Код соответствует стандартам PSR-12
  - Улучшена модульность, тестируемость и поддерживаемость кода

