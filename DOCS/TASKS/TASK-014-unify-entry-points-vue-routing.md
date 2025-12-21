# TASK-014: Унификация точек входа и улучшение роутинга в Vue.js

**Дата создания:** 2025-12-21 20:05 (UTC+3, Брест)  
**Статус:** Новая  
**Приоритет:** Средний  
**Исполнитель:** Bitrix24 Программист (Vue.js)

---

## Описание

Упрощение архитектуры приложения путем унификации PHP точек входа и улучшения роутинга в Vue.js. Создание единой минимальной PHP точки входа (`index.php`) с передачей управления роутингом полностью в Vue.js приложение.

**Цель:** 
- Упростить структуру проекта (одна PHP точка входа вместо трех)
- Улучшить роутинг в Vue.js для обработки всех маршрутов
- Устранить дублирование кода в PHP точках входа
- Обеспечить единообразную работу всех маршрутов через Vue Router
- **Создать отдельный сервис VueAppService** для управления загрузкой Vue.js приложения

**Ключевой принцип:** PHP отвечает только за проверку безопасности и загрузку Vue.js. Все остальное (роутинг, отображение, навигация) — в Vue.js.

**Архитектурное улучшение:** Преобразование функции `loadVueApp()` в полноценный сервис `VueAppService` с использованием Dependency Injection, улучшенным логированием через LoggerService и обработкой ошибок через исключения.

---

## Контекст

**Текущая ситуация:**
- Существует три PHP точки входа: `index.php`, `token-analysis.php`, `access-control.php`
- Каждая точка входа делает одно и то же: проверка авторизации + загрузка Vue.js
- Различается только начальный маршрут для Vue.js
- Дублирование кода в трех файлах

**Проблемы:**
- Дублирование логики проверки авторизации
- Дублирование логики загрузки Vue.js
- Сложность поддержки (изменения нужно делать в трех местах)
- Неоптимальная структура проекта

**Решение:**
- Создать единую PHP точку входа `index.php`
- Передать управление роутингом полностью в Vue.js
- Настроить `.htaccess` для редиректов старых URL на новую структуру
- Улучшить обработку маршрутов в Vue Router

**Связь с другими задачами:**
- Зависит от: TASK-013 (рефакторинг index.php)
- Основано на: DOCS/ANALYSIS/2025-12-21-migrate-all-to-vue-analysis.md

---

## Модули и компоненты

### Файлы для изменения:

**PHP (минимальные изменения):**
- `APP-B24/index.php` — упрощение до единой точки входа
- `APP-B24/src/helpers/loadVueApp.php` — **рефакторинг в сервис VueAppService** (или сохранение как обёртка для обратной совместимости)
- `APP-B24/token-analysis.php` — удаление (перенос логики в Vue.js)
- `APP-B24/access-control.php` — удаление (перенос логики в Vue.js)
- `APP-B24/.htaccess` — добавление правил редиректа
- `APP-B24/src/bootstrap.php` — добавление инициализации VueAppService

**Vue.js (основная работа):**
- `APP-B24/frontend/src/router/index.js` — улучшение роутинга
- `APP-B24/frontend/src/components/IndexPage.vue` — проверка работы
- `APP-B24/frontend/src/components/TokenAnalysisPage.vue` — проверка работы
- `APP-B24/frontend/src/components/AccessControlPage.vue` — проверка работы
- `APP-B24/frontend/src/App.vue` — проверка инициализации

### Новые файлы для создания:

**PHP (обязательно):**
- `APP-B24/src/Services/VueAppService.php` — **новый сервис для загрузки Vue.js приложения**
  - Замена функции `loadVueApp()` на полноценный сервис
  - Dependency Injection через конструктор
  - Использование LoggerService для логирования
  - Улучшенная обработка ошибок
  - Методы для различных сценариев загрузки

**PHP (опционально):**
- `APP-B24/src/helpers/loadEntryPoint.php` — единая функция для загрузки точки входа (опционально, можно использовать существующую `loadVueApp.php` или новый сервис)

**Vue.js:**
- Нет новых файлов (используем существующие компоненты)

---

## Зависимости

**От каких модулей зависит:**
- `APP-B24/src/bootstrap.php` — инициализация сервисов
- `APP-B24/src/Services/AuthService.php` — проверка авторизации
- `APP-B24/src/Services/LoggerService.php` — логирование (для нового VueAppService)
- `APP-B24/src/helpers/loadVueApp.php` — загрузка Vue.js приложения (старая функция, будет заменена на сервис)
- `APP-B24/frontend/src/router/index.js` — роутинг Vue.js

**Новые зависимости:**
- `APP-B24/src/Services/VueAppService.php` — новый сервис (зависит от LoggerService)

**Какие задачи зависят от этой:**
- Будущие задачи по добавлению новых маршрутов (упростится добавление)

**Интеграция:**
- Работа с Bitrix24 BX.* API для получения токена
- Интеграция с PHP через параметры URL (`AUTH_ID`, `DOMAIN`)

---

## Ступенчатые подзадачи

### Этап 1: Анализ и подготовка

1. **Изучение текущей структуры:**
   - Проанализировать все три PHP точки входа (`index.php`, `token-analysis.php`, `access-control.php`)
   - Выявить общую логику и различия
   - Изучить текущий роутинг в Vue.js (`frontend/src/router/index.js`)
   - Проверить работу всех маршрутов в текущей версии

2. **Изучение документации:**
   - Прочитать `DOCS/ANALYSIS/2025-12-21-migrate-all-to-vue-analysis.md`
   - Изучить формат задачи TASK-013 для понимания подхода
   - Проверить требования к безопасности (проверка авторизации на сервере)

### Этап 2: Упрощение PHP точек входа

3. **Создание единой функции загрузки (опционально):**
   - Создать `APP-B24/src/helpers/loadEntryPoint.php` (или использовать существующую `loadVueApp.php`)
   - Функция должна принимать маршрут как параметр
   - Функция должна проверять авторизацию и загружать Vue.js

4. **Упрощение `index.php`:**
   - Оставить только минимальную логику:
     - Проверка авторизации
     - Получение маршрута из URL или query параметра
     - Загрузка Vue.js приложения с маршрутом
   - Убрать дублирование кода

5. **Удаление `token-analysis.php` и `access-control.php`:**
   - Удалить файлы после проверки, что роутинг работает через Vue.js
   - Обновить документацию, если есть ссылки на эти файлы

### Этап 4: Настройка .htaccess для редиректов

8. **Добавление правил редиректа:**
   - Настроить редирект `/token-analysis` → `/index.php?route=/token-analysis`
   - Настроить редирект `/access-control` → `/index.php?route=/access-control`
   - Сохранить query параметры (`AUTH_ID`, `DOMAIN`) при редиректе
   - Протестировать работу редиректов

**Пример правил для .htaccess:**
```apache
# Редирект старых URL на новую структуру
RewriteEngine On
RewriteBase /APP-B24/

# Редирект /token-analysis на index.php с маршрутом
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^token-analysis$ index.php?route=/token-analysis [L,QSA]

# Редирект /access-control на index.php с маршрутом
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^access-control$ index.php?route=/access-control [L,QSA]
```

### Этап 5: Улучшение роутинга в Vue.js

9. **Обновление Vue Router:**
   - Убедиться, что все маршруты определены в `frontend/src/router/index.js`:
     - `/` — главная страница (IndexPage)
     - `/token-analysis` — анализ токена (TokenAnalysisPage)
     - `/access-control` — управление правами (AccessControlPage)
   - Проверить, что метаданные маршрутов корректны (`requiresAuth`, `requiresAdmin`)

10. **Улучшение обработки начального маршрута:**
   - Обновить `loadVueApp.php` для передачи маршрута из PHP в Vue.js
   - Убедиться, что Vue Router корректно обрабатывает начальный маршрут из `route` параметра
   - Проверить сохранение query параметров (`AUTH_ID`, `DOMAIN`) при навигации

11. **Улучшение навигационных хуков:**
   - Проверить работу `router.beforeEach()` для проверки авторизации
   - Убедиться, что параметры авторизации сохраняются при навигации
   - Проверить обработку отсутствующих параметров авторизации

### Этап 6: Интеграция и тестирование

12. **Интеграция с PHP:**
    - Проверить передачу маршрута из PHP в Vue.js
    - Убедиться, что токен авторизации передается корректно
    - Проверить работу в iframe Bitrix24

13. **Тестирование всех маршрутов:**
    - Протестировать переход на главную страницу (`/`)
    - Протестировать переход на анализ токена (`/token-analysis`)
    - Протестировать переход на управление правами (`/access-control`)
    - Проверить работу старых URL (через редиректы)
    - Проверить сохранение параметров авторизации при навигации

14. **Тестирование безопасности:**
    - Проверить, что без авторизации нельзя получить доступ
    - Проверить, что проверка прав администратора работает
    - Проверить логирование попыток доступа

### Этап 7: Документация и финализация

15. **Обновление документации:**
    - Обновить `APP-B24/README.md` с новой структурой
    - Обновить `DOCS/ARCHITECTURE/application-architecture.md`
    - Добавить описание новой структуры в документацию

16. **Проверка обратной совместимости:**
    - Убедиться, что старые URL работают через редиректы
    - Проверить работу закладок пользователей
    - Проверить работу внешних ссылок

---

## API-методы Bitrix24

**Не используются напрямую в этой задаче**, но используются через существующие сервисы:

- `user.current` — получение текущего пользователя (через `UserService`)
- `user.get` — проверка статуса администратора (через `UserService`)
- `department.get` — получение отделов (через `Bitrix24ApiService`)

**Документация:** https://context7.com/bitrix24/rest/

---

## Технические требования

### Vue.js

- **Версия:** Vue.js 3.x (Composition API)
- **Роутинг:** Vue Router 4.x
- **Совместимость:** Работа в iframe Bitrix24
- **Браузеры:** Современные браузеры (Chrome, Firefox, Safari, Edge)

### PHP

- **Версия:** PHP 8.3+
- **Зависимости:** Существующие сервисы (AuthService, UserService, ConfigService)
- **Совместимость:** Работа с существующей архитектурой

### Веб-сервер

- **Apache:** Поддержка mod_rewrite для .htaccess
- **Nginx:** Настройка редиректов (если используется)

### Безопасность

- ✅ Проверка авторизации на сервере (PHP) — **обязательно**
- ✅ Защита от прямого доступа — **обязательно**
- ✅ Валидация параметров авторизации — **обязательно**
- ✅ Логирование попыток доступа — **обязательно**

---

## Критерии приёмки

### Функциональные требования

- [ ] Все три маршрута работают через единую точку входа `index.php`
- [ ] Старые URL (`/token-analysis`, `/access-control`) работают через редиректы
- [ ] Роутинг в Vue.js обрабатывает все маршруты корректно
- [ ] Параметры авторизации (`AUTH_ID`, `DOMAIN`) сохраняются при навигации
- [ ] Проверка авторизации работает на сервере (PHP)
- [ ] Проверка прав администратора работает корректно
- [ ] Все компоненты Vue.js загружаются и отображаются правильно

### Технические требования

- [ ] **Создан сервис `VueAppService`** (`APP-B24/src/Services/VueAppService.php`):
  - [ ] Метод `load(string $route, ?array $appData)` — основной метод загрузки
  - [ ] Метод `checkVueAppExists(): bool` — проверка существования файлов
  - [ ] Метод `getVueAppPath(): string` — получение пути к Vue.js приложению
  - [ ] Метод `buildAuthScript(...): string` — построение скрипта авторизации
  - [ ] Метод `buildAppDataScript(?array $appData): string` — построение скрипта данных
  - [ ] Метод `buildNavigationScript(string $route, string $query): string` — построение скрипта навигации
  - [ ] Метод `renderErrorPage(string $message, string $details): void` — отображение ошибок
- [ ] Сервис использует Dependency Injection (LoggerService через конструктор)
- [ ] Обновлен `bootstrap.php` для инициализации VueAppService
- [ ] Обновлен `loadVueApp.php` как обёртка для обратной совместимости
- [ ] Удалены файлы `token-analysis.php` и `access-control.php`
- [ ] `index.php` упрощен до минимальной логики и использует VueAppService
- [ ] Настроены редиректы в `.htaccess`
- [ ] Vue Router обрабатывает все маршруты
- [ ] Нет дублирования кода в PHP
- [ ] Код соответствует стандартам проекта (PSR-12)
- [ ] Логирование работает через LoggerService (не error_log())
- [ ] Обработка ошибок через исключения (не die())

### Безопасность

- [ ] Проверка авторизации выполняется на сервере (нельзя обойти)
- [ ] Защита от прямого доступа работает
- [ ] Логирование попыток доступа работает
- [ ] Валидация параметров авторизации работает

### Тестирование

- [ ] Протестированы все маршруты в браузере
- [ ] Протестирована работа в iframe Bitrix24
- [ ] Протестированы редиректы старых URL
- [ ] Протестирована проверка авторизации
- [ ] Протестирована проверка прав администратора
- [ ] Протестирована навигация между страницами

### Документация

- [ ] Обновлен `README.md` с новой структурой
- [ ] Обновлена архитектурная документация
- [ ] Добавлены комментарии в код
- [ ] Обновлена история правок в задаче

---

## Детали создания VueAppService

### Назначение сервиса

**VueAppService** — это отдельный сервис для управления загрузкой Vue.js приложения. Он заменяет функцию `loadVueApp()` и предоставляет:

1. ✅ **Единый интерфейс** для загрузки Vue.js приложения
2. ✅ **Dependency Injection** через конструктор (LoggerService)
3. ✅ **Улучшенное логирование** через LoggerService
4. ✅ **Обработка ошибок** через исключения вместо die()
5. ✅ **Тестируемость** — можно мокировать зависимости
6. ✅ **Расширяемость** — легко добавлять новые методы

### Архитектура сервиса

**Структура класса:**
```
App\Services\VueAppService
├── __construct(LoggerService $logger) — инициализация
├── load(string $route, ?array $appData) — основной метод загрузки
├── checkVueAppExists(): bool — проверка существования файлов
├── getVueAppPath(): string — получение пути к Vue.js приложению
├── buildAuthScript(...): string — построение скрипта авторизации Bitrix24
├── buildAppDataScript(?array $appData): string — построение скрипта передачи данных
├── buildNavigationScript(string $route, string $query): string — построение скрипта навигации
└── renderErrorPage(string $message, string $details): void — отображение страницы ошибки
```

**Зависимости:**
- `LoggerService` — для логирования (через конструктор)
- Не зависит от глобальных переменных
- Получает параметры через методы, а не напрямую из `$_POST`, `$_GET`

### Преимущества сервиса

**По сравнению с функцией `loadVueApp()`:**

1. ✅ **Тестируемость:**
   - Можно мокировать LoggerService в тестах
   - Можно тестировать методы отдельно
   - Легко создавать unit-тесты для каждого метода

2. ✅ **Расширяемость:**
   - Легко добавлять новые методы (например, `loadWithCache()`, `loadWithCustomConfig()`)
   - Можно создавать подклассы для кастомизации поведения
   - Можно добавлять интерфейсы для разных типов загрузки

3. ✅ **Поддерживаемость:**
   - Четкая структура класса с явными методами
   - Явные зависимости через конструктор (не скрытые глобальные переменные)
   - Единая точка для изменений (один класс вместо функции)

4. ✅ **Логирование:**
   - Использует LoggerService вместо `error_log()`
   - Структурированные логи с контекстом
   - Легко отслеживать проблемы через логи

5. ✅ **Обработка ошибок:**
   - Использует исключения вместо `die()`
   - Можно обрабатывать ошибки на уровне вызывающего кода
   - Более гибкая обработка различных типов ошибок

### Методы сервиса

#### 1. `load(string $route = '/', ?array $appData = null): void`

**Основной метод для загрузки Vue.js приложения.**

**Параметры:**
- `$route` — начальный маршрут для Vue Router (по умолчанию `/`)
- `$appData` — данные для передачи в Vue.js (опционально)

**Логика:**
1. Проверка существования файлов Vue.js
2. Чтение `index.html`
3. Получение параметров авторизации из `$_POST`/`$_GET`
4. Построение скриптов (авторизация, данные, навигация)
5. Вставка скриптов в HTML
6. Вывод HTML и завершение выполнения

**Обработка ошибок:**
- Если файлы не найдены → вызывает `renderErrorPage()`
- Если ошибка чтения → выбрасывает исключение

#### 2. `checkVueAppExists(): bool`

**Проверка существования файлов Vue.js.**

**Возвращает:** `true` если файлы существуют, `false` иначе

**Использование:** Внутренний метод, вызывается из `load()`

#### 3. `getVueAppPath(): string`

**Получение пути к файлу `index.html` Vue.js приложения.**

**Возвращает:** Абсолютный путь к файлу

**Использование:** Для проверки существования и чтения файла

#### 4. `buildAuthScript(?string $authId, ?string $domain, ?string $refreshId = null, ?int $authExpires = null): string`

**Построение JavaScript скрипта для авторизации Bitrix24.**

**Параметры:**
- `$authId` — токен авторизации (AUTH_ID)
- `$domain` — домен портала Bitrix24
- `$refreshId` — refresh токен (опционально)
- `$authExpires` — время истечения токена (опционально)

**Возвращает:** JavaScript код для вставки в `<head>`

**Функционал:**
- Подключение BX24 SDK
- Сохранение токена в `sessionStorage`
- Инициализация BX24 SDK для получения токена (если не передан)

#### 5. `buildAppDataScript(?array $appData): string`

**Построение JavaScript скрипта для передачи данных в Vue.js.**

**Параметры:**
- `$appData` — данные для передачи (массив или null)

**Возвращает:** JavaScript код для вставки в `<head>` или пустую строку

**Функционал:**
- Валидация данных перед кодированием
- JSON-кодирование данных
- Сохранение в `sessionStorage` и `window.__APP_DATA__`

**Валидация:**
- Проверка, что `$appData` является массивом
- Логирование ошибок валидации

#### 6. `buildNavigationScript(string $route, string $queryString = ''): string`

**Построение JavaScript скрипта для навигации Vue Router.**

**Параметры:**
- `$route` — начальный маршрут
- `$queryString` — query строка (опционально)

**Возвращает:** JavaScript код для вставки перед `</body>` или пустую строку

**Функционал:**
- Изменение URL через `history.replaceState()`
- Попытка навигации через Vue Router после загрузки
- Обработка случаев, когда роутер еще не готов

**Особенности:**
- Не создает скрипт, если маршрут `/` (главная страница)
- Убирает параметр `route` из query строки

#### 7. `renderErrorPage(string $message, string $details = ''): void`

**Отображение страницы ошибки.**

**Параметры:**
- `$message` — основное сообщение об ошибке
- `$details` — детали ошибки (опционально)

**Логика:**
- Логирование ошибки через LoggerService
- Установка HTTP статуса 503
- Отображение HTML страницы с ошибкой
- В development режиме — детальная информация
- В production режиме — простое сообщение

### Миграция с функции на сервис

**Шаг 1: Создание сервиса**
- Создать файл `APP-B24/src/Services/VueAppService.php`
- Перенести логику из `loadVueApp.php` в методы сервиса
- Добавить Dependency Injection через конструктор

**Шаг 2: Обновление bootstrap.php**
- Добавить инициализацию VueAppService после LoggerService
- Создать глобальную переменную `$vueAppService` для обратной совместимости

**Шаг 3: Создание обёртки**
- Обновить `loadVueApp.php` для использования VueAppService
- Сохранить старую сигнатуру функции для обратной совместимости
- Функция должна использовать глобальную переменную `$vueAppService`

**Шаг 4: Обновление вызовов**
- Обновить `index.php` для использования сервиса напрямую (или через обёртку)
- Проверить все места, где используется `loadVueApp()`

**Шаг 5: Тестирование**
- Протестировать работу через сервис
- Протестировать обратную совместимость через обёртку
- Проверить логирование

**Шаг 6: (Опционально) Удаление обёртки**
- После полной миграции можно удалить обёртку `loadVueApp.php`
- Все вызовы должны использовать сервис напрямую

### Интеграция с существующими сервисами

**VueAppService использует:**
- `LoggerService` — для логирования всех операций

**VueAppService не зависит от:**
- `AuthService` — проверка авторизации выполняется до вызова сервиса
- `UserService` — получение данных пользователя выполняется до вызова сервиса
- `ConfigService` — конфигурация проверяется до вызова сервиса

**Принцип:** VueAppService отвечает только за загрузку Vue.js приложения, не за бизнес-логику.

---

## Примеры кода

### 1. Новый сервис VueAppService

```php
<?php

namespace App\Services;

/**
 * Сервис для загрузки Vue.js приложения
 * 
 * Обеспечивает единый интерфейс для загрузки Vue.js приложения
 * с передачей данных авторизации и настройкой начального маршрута
 * Документация: https://context7.com/bitrix24/rest/
 */
class VueAppService
{
    protected LoggerService $logger;
    protected string $vueAppPath;
    protected string $appEnv;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
        $this->appEnv = getenv('APP_ENV') ?: 'production';
        
        // Путь к собранным файлам Vue.js
        $this->vueAppPath = __DIR__ . '/../../public/dist/index.html';
    }
    
    /**
     * Загрузка Vue.js приложения
     * 
     * @param string $route Начальный маршрут для роутера Vue.js
     * @param array|null $appData Данные для передачи в Vue.js приложение
     * @return void
     * @throws \Exception Если файлы Vue.js не найдены или ошибка чтения
     */
    public function load(string $route = '/', ?array $appData = null): void
    {
        // Проверка существования файлов Vue.js
        if (!$this->checkVueAppExists()) {
            $this->renderErrorPage(
                'Vue.js приложение не собрано',
                'Для запуска приложения необходимо собрать фронтенд: cd frontend && npm install && npm run build'
            );
            return;
        }
        
        // Чтение index.html
        $html = file_get_contents($this->vueAppPath);
        if ($html === false) {
            $this->logger->logError('VueAppService: Failed to read index.html', [
                'path' => $this->vueAppPath
            ]);
            throw new \Exception('Failed to read Vue.js application file');
        }
        
        // Получение параметров авторизации
        $authId = $_POST['AUTH_ID'] ?? $_GET['AUTH_ID'] ?? $_GET['APP_SID'] ?? null;
        $refreshId = $_POST['REFRESH_ID'] ?? $_GET['REFRESH_ID'] ?? null;
        $authExpires = isset($_POST['AUTH_EXPIRES']) 
            ? (int)$_POST['AUTH_EXPIRES'] 
            : (isset($_GET['AUTH_EXPIRES']) ? (int)$_GET['AUTH_EXPIRES'] : null);
        $domain = $_POST['DOMAIN'] ?? $_GET['DOMAIN'] ?? null;
        
        // Построение скриптов
        $authScript = $this->buildAuthScript($authId, $domain, $refreshId, $authExpires);
        $appDataScript = $this->buildAppDataScript($appData);
        $navigationScript = $this->buildNavigationScript($route);
        
        // Вставка скриптов в HTML
        $html = str_replace('</head>', $authScript . $appDataScript . '</head>', $html);
        if ($navigationScript) {
            $html = str_replace('</body>', $navigationScript . '</body>', $html);
        }
        
        // Логирование успешной загрузки
        $this->logger->log('Vue.js app loaded successfully', [
            'route' => $route,
            'has_data' => $appData !== null,
            'has_auth' => !empty($authId) && !empty($domain)
        ], 'info');
        
        // Вывод HTML
        echo $html;
        exit;
    }
    
    /**
     * Проверка существования файлов Vue.js
     * 
     * @return bool true если файлы существуют
     */
    public function checkVueAppExists(): bool
    {
        $path = realpath($this->vueAppPath);
        return $path !== false && file_exists($path);
    }
    
    /**
     * Получение пути к Vue.js приложению
     * 
     * @return string Путь к index.html
     */
    public function getVueAppPath(): string
    {
        return $this->vueAppPath;
    }
    
    /**
     * Построение скрипта авторизации Bitrix24
     * 
     * @param string|null $authId Токен авторизации
     * @param string|null $domain Домен портала
     * @param string|null $refreshId Refresh токен
     * @param int|null $authExpires Время истечения токена
     * @return string JavaScript код
     */
    protected function buildAuthScript(
        ?string $authId, 
        ?string $domain, 
        ?string $refreshId = null, 
        ?int $authExpires = null
    ): string {
        $script = '<script src="//api.bitrix24.com/api/v1/"></script>' . "\n";
        $script .= '<script>' . "\n";
        
        if ($authId && $domain) {
            $script .= '        (function() {' . "\n";
            $script .= '            const authData = {' . "\n";
            $script .= '                auth_token: ' . json_encode($authId, JSON_UNESCAPED_UNICODE) . ',' . "\n";
            $script .= '                refresh_token: ' . ($refreshId ? json_encode($refreshId, JSON_UNESCAPED_UNICODE) : 'null') . ',' . "\n";
            $script .= '                expires: ' . ($authExpires ?: 'null') . ',' . "\n";
            $script .= '                domain: ' . json_encode($domain, JSON_UNESCAPED_UNICODE) . "\n";
            $script .= '            };' . "\n";
            $script .= '            sessionStorage.setItem("bitrix24_auth", JSON.stringify(authData));' . "\n";
            $script .= '            console.log("Auth token from PHP saved to sessionStorage");' . "\n";
            $script .= '        })();' . "\n";
        }
        
        $script .= '        if (typeof BX24 !== "undefined" && typeof BX24.init === "function") {' . "\n";
        $script .= '            BX24.init(function() {' . "\n";
        $script .= '                if (!sessionStorage.getItem("bitrix24_auth")) {' . "\n";
        $script .= '                    BX24.getAuth(function(auth) {' . "\n";
        $script .= '                        if (auth && auth.auth_token) {' . "\n";
        $script .= '                            sessionStorage.setItem("bitrix24_auth", JSON.stringify(auth));' . "\n";
        $script .= '                        }' . "\n";
        $script .= '                    });' . "\n";
        $script .= '                }' . "\n";
        $script .= '            });' . "\n";
        $script .= '        }' . "\n";
        $script .= '    </script>' . "\n";
        
        return $script;
    }
    
    /**
     * Построение скрипта передачи данных в Vue.js
     * 
     * @param array|null $appData Данные для передачи
     * @return string JavaScript код
     */
    protected function buildAppDataScript(?array $appData): string
    {
        if ($appData === null || empty($appData)) {
            return '';
        }
        
        // Валидация данных
        if (!is_array($appData)) {
            $this->logger->logError('VueAppService: Invalid appData', [
                'type' => gettype($appData)
            ]);
            return '';
        }
        
        $jsonData = json_encode($appData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($jsonData === false) {
            $this->logger->logError('VueAppService: Failed to encode appData', [
                'error' => json_last_error_msg()
            ]);
            return '';
        }
        
        $script = '        (function() {' . "\n";
        $script .= '            const appData = ' . $jsonData . ';' . "\n";
        $script .= '            sessionStorage.setItem("app_data", JSON.stringify(appData));' . "\n";
        $script .= '            window.__APP_DATA__ = appData;' . "\n";
        $script .= '            console.log("App data from PHP saved");' . "\n";
        $script .= '        })();' . "\n";
        
        return '<script>' . "\n" . $script . '    </script>' . "\n";
    }
    
    /**
     * Построение скрипта навигации для Vue Router
     * 
     * @param string $route Начальный маршрут
     * @param string $queryString Query строка
     * @return string JavaScript код или пустая строка
     */
    protected function buildNavigationScript(string $route, string $queryString = ''): string
    {
        if ($route === '/' || empty($route)) {
            return '';
        }
        
        // Получение query строки из текущего URL
        if (empty($queryString)) {
            $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
        }
        
        // Убираем параметр route из query, так как он уже в маршруте
        if (!empty($queryString)) {
            $params = [];
            parse_str(ltrim($queryString, '?'), $params);
            unset($params['route']);
            $queryString = !empty($params) ? '?' . http_build_query($params) : '';
        }
        
        $script = '        <script>' . "\n";
        $script .= '            (function() {' . "\n";
        $script .= '                const targetRoute = ' . json_encode($route, JSON_UNESCAPED_UNICODE) . ';' . "\n";
        $script .= '                const queryString = ' . json_encode($queryString, JSON_UNESCAPED_UNICODE) . ';' . "\n";
        $script .= '                if (window.history && window.history.pushState) {' . "\n";
        $script .= '                    window.history.replaceState({}, "", targetRoute + queryString);' . "\n";
        $script .= '                }' . "\n";
        $script .= '                function tryRouterNavigation() {' . "\n";
        $script .= '                    const appElement = document.querySelector("#app");' . "\n";
        $script .= '                    if (appElement && appElement.__vue_app__) {' . "\n";
        $script .= '                        const app = appElement.__vue_app__;' . "\n";
        $script .= '                        if (app.config && app.config.globalProperties && app.config.globalProperties.$router) {' . "\n";
        $script .= '                            const router = app.config.globalProperties.$router;' . "\n";
        $script .= '                            router.push(targetRoute).catch(function(err) {' . "\n";
        $script .= '                                if (err.name !== "NavigationDuplicated") {' . "\n";
        $script .= '                                    console.warn("Router navigation:", err);' . "\n";
        $script .= '                                }' . "\n";
        $script .= '                            });' . "\n";
        $script .= '                            return true;' . "\n";
        $script .= '                        }' . "\n";
        $script .= '                    }' . "\n";
        $script .= '                    return false;' . "\n";
        $script .= '                }' . "\n";
        $script .= '                if (document.readyState === "loading") {' . "\n";
        $script .= '                    document.addEventListener("DOMContentLoaded", function() {' . "\n";
        $script .= '                        setTimeout(function() {' . "\n";
        $script .= '                            if (!tryRouterNavigation()) {' . "\n";
        $script .= '                                setTimeout(tryRouterNavigation, 500);' . "\n";
        $script .= '                            }' . "\n";
        $script .= '                        }, 100);' . "\n";
        $script .= '                    });' . "\n";
        $script .= '                } else {' . "\n";
        $script .= '                    setTimeout(function() {' . "\n";
        $script .= '                        if (!tryRouterNavigation()) {' . "\n";
        $script .= '                            setTimeout(tryRouterNavigation, 500);' . "\n";
        $script .= '                        }' . "\n";
        $script .= '                    }, 100);' . "\n";
        $script .= '                }' . "\n";
        $script .= '            })();' . "\n";
        $script .= '        </script>' . "\n";
        
        return $script;
    }
    
    /**
     * Отображение страницы ошибки
     * 
     * @param string $message Основное сообщение
     * @param string $details Детали ошибки
     * @return void
     */
    protected function renderErrorPage(string $message, string $details = ''): void
    {
        $this->logger->logError('VueAppService: ' . $message, [
            'details' => $details,
            'path' => $this->vueAppPath
        ]);
        
        http_response_code(503);
        
        if ($this->appEnv === 'development') {
            echo '<!DOCTYPE html>' . "\n";
            echo '<html>' . "\n";
            echo '<head>' . "\n";
            echo '    <meta charset="UTF-8">' . "\n";
            echo '    <title>Vue.js приложение не собрано</title>' . "\n";
            echo '    <style>' . "\n";
            echo '        body { font-family: Arial, sans-serif; padding: 40px; text-align: center; }' . "\n";
            echo '        h1 { color: #e74c3c; }' . "\n";
            echo '        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }' . "\n";
            echo '    </style>' . "\n";
            echo '</head>' . "\n";
            echo '<body>' . "\n";
            echo '    <h1>' . htmlspecialchars($message) . '</h1>' . "\n";
            if ($details) {
                echo '    <p>' . htmlspecialchars($details) . '</p>' . "\n";
            }
            echo '    <p>Для запуска приложения необходимо собрать фронтенд:</p>' . "\n";
            echo '    <p><code>cd frontend && npm install && npm run build</code></p>' . "\n";
            echo '    <p>Или запустить dev-сервер:</p>' . "\n";
            echo '    <p><code>cd frontend && npm run dev</code></p>' . "\n";
            echo '</body>' . "\n";
            echo '</html>' . "\n";
        } else {
            echo 'Service temporarily unavailable';
        }
        
        exit;
    }
}
```

### 2. Обновление bootstrap.php

```php
// APP-B24/src/bootstrap.php

// ... существующий код инициализации сервисов ...

// Инициализация VueAppService
$vueAppService = new App\Services\VueAppService($logger);

// Для обратной совместимости можно использовать глобальную переменную
// или передавать через dependency injection в будущем
```

### 3. Обновление loadVueApp.php (обёртка для обратной совместимости)

```php
<?php
/**
 * Обёртка для обратной совместимости
 * 
 * Использует VueAppService для загрузки Vue.js приложения
 * Сохраняет старую сигнатуру функции для обратной совместимости
 * 
 * @param string|null $initialRoute Начальный маршрут для роутера Vue.js
 * @param array|null $appData Данные для передачи в Vue.js приложение
 * @return void
 */
function loadVueApp(?string $initialRoute = null, ?array $appData = null): void
{
    global $vueAppService;
    
    // Если сервис не инициализирован, создаём его
    if (!isset($vueAppService)) {
        $logger = new App\Services\LoggerService();
        $vueAppService = new App\Services\VueAppService($logger);
    }
    
    $route = $initialRoute ?? '/';
    $vueAppService->load($route, $appData);
}
```

### 4. Упрощенный `index.php` с использованием сервиса

```php
<?php
/**
 * Единая точка входа для приложения Bitrix24
 * 
 * ВАЖНО: PHP не генерирует UI. Вся визуальная часть на Vue.js.
 * PHP только:
 * - Проверяет авторизацию
 * - Загружает Vue.js приложение с нужным маршрутом через VueAppService
 * 
 * Документация: https://context7.com/bitrix24/rest/
 */

// Определение окружения
$appEnv = getenv('APP_ENV') ?: 'production';

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
    // Подключение и инициализация сервисов
    require_once(__DIR__ . '/src/bootstrap.php');
    
    // Проверка авторизации Bitrix24
    if (!$authService->checkBitrix24Auth()) {
        exit; // Редирект на страницу ошибки уже выполнен
    }
    
    // Получение маршрута из query параметра или URL
    $route = $_GET['route'] ?? '/';
    
    // Нормализация маршрута (убираем лишние слеши)
    $route = '/' . trim($route, '/');
    if ($route === '//') {
        $route = '/';
    }
    
    // Загрузка Vue.js приложения через сервис
    // $vueAppService инициализирован в bootstrap.php
    $vueAppService->load($route);
    
} catch (\Throwable $e) {
    error_log('Fatal error in index.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ошибка</title></head><body>';
    echo '<h1>Ошибка приложения</h1>';
    echo '<p>Произошла ошибка при загрузке страницы.</p>';
    if (ini_get('display_errors')) {
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    echo '</body></html>';
    exit;
}
```

### 2. Правила для `.htaccess`

```apache
# APP-B24/.htaccess

# Включение mod_rewrite
RewriteEngine On
RewriteBase /APP-B24/

# Редирект старых URL на новую структуру
# Сохраняем query параметры (AUTH_ID, DOMAIN и т.д.)

# Редирект /token-analysis на index.php с маршрутом
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^token-analysis$ index.php?route=/token-analysis [L,QSA]

# Редирект /access-control на index.php с маршрутом
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^access-control$ index.php?route=/access-control [L,QSA]

# Защита конфигурационных файлов
<FilesMatch "^(config\.json|settings\.json|access-config\.json)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### 3. Обновление Vue Router (если нужно)

```javascript
// frontend/src/router/index.js

// ... существующий код ...

// Навигационные хуки
router.beforeEach((to, from, next) => {
  console.log('Router beforeEach:', { to: to.path, from: from.path });
  
  // Получение параметров авторизации из URL
  const params = new URLSearchParams(window.location.search);
  const authId = params.get('AUTH_ID') || params.get('APP_SID');
  const domain = params.get('DOMAIN');
  
  // Если параметры есть в URL, но их нет в query маршрута, добавляем их
  if (authId && domain && (!to.query.AUTH_ID && !to.query.APP_SID) && !to.query.DOMAIN) {
    next({
      path: to.path,
      query: {
        ...to.query,
        AUTH_ID: authId,
        DOMAIN: domain,
      },
      replace: false
    });
    return;
  }
  
  // Проверка авторизации
  if (to.meta.requiresAuth) {
    const routeAuthId = to.query.AUTH_ID || to.query.APP_SID || authId;
    const routeDomain = to.query.DOMAIN || domain;
    
    if (!routeAuthId || !routeDomain) {
      console.warn('Router: Missing AUTH_ID or DOMAIN, redirecting to index');
      next({ name: 'index', query: { AUTH_ID: authId, DOMAIN: domain } });
      return;
    }
  }
  
  // Проверка прав администратора (будет реализовано через store)
  if (to.meta.requiresAdmin) {
    // Пока пропускаем, проверка будет в компонентах
  }
  
  next();
});
```

### 4. Обновление `loadVueApp.php` (если нужно)

```php
// APP-B24/src/helpers/loadVueApp.php

// ... существующий код ...

// Если указан начальный маршрут, добавляем скрипт для навигации
if ($initialRoute && $initialRoute !== '/') {
    // Сохраняем query параметры из текущего URL
    $queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
    
    // Убираем параметр route из query, так как он уже в маршруте
    $queryParams = [];
    parse_str($queryString, $queryParams);
    unset($queryParams['route']);
    $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
    
    $navigationScript = '
    <script>
        (function() {
            const targetRoute = "' . htmlspecialchars($initialRoute, ENT_QUOTES) . '";
            const queryString = "' . htmlspecialchars($queryString, ENT_QUOTES) . '";
            
            // Изменяем URL без перезагрузки страницы
            if (window.history && window.history.pushState) {
                window.history.replaceState({}, "", targetRoute + queryString);
            }
            
            // После загрузки Vue.js роутер автоматически обработает новый URL
            // ... существующий код ...
        })();
    </script>
    ';
    
    $html = str_replace('</body>', $navigationScript . '</body>', $html);
}
```

---

## Тестирование

### 1. Тестирование основных маршрутов

**Тест 1: Главная страница**
```
URL: /APP-B24/index.php?AUTH_ID=...&DOMAIN=...
Ожидаемый результат:
- Загружается Vue.js приложение
- Отображается главная страница (IndexPage)
- Параметры авторизации сохраняются
```

**Тест 2: Анализ токена (новый URL)**
```
URL: /APP-B24/index.php?route=/token-analysis&AUTH_ID=...&DOMAIN=...
Ожидаемый результат:
- Загружается Vue.js приложение
- Отображается страница анализа токена (TokenAnalysisPage)
- Параметры авторизации сохраняются
```

**Тест 3: Управление правами (новый URL)**
```
URL: /APP-B24/index.php?route=/access-control&AUTH_ID=...&DOMAIN=...
Ожидаемый результат:
- Загружается Vue.js приложение
- Отображается страница управления правами (AccessControlPage)
- Параметры авторизации сохраняются
```

### 2. Тестирование редиректов

**Тест 4: Старый URL анализа токена**
```
URL: /APP-B24/token-analysis?AUTH_ID=...&DOMAIN=...
Ожидаемый результат:
- Редирект на /APP-B24/index.php?route=/token-analysis&AUTH_ID=...&DOMAIN=...
- Загружается Vue.js приложение
- Отображается страница анализа токена
```

**Тест 5: Старый URL управления правами**
```
URL: /APP-B24/access-control?AUTH_ID=...&DOMAIN=...
Ожидаемый результат:
- Редирект на /APP-B24/index.php?route=/access-control&AUTH_ID=...&DOMAIN=...
- Загружается Vue.js приложение
- Отображается страница управления правами
```

### 3. Тестирование навигации

**Тест 6: Навигация между страницами**
```
Шаги:
1. Открыть главную страницу
2. Нажать кнопку "Проверка токена"
3. Нажать кнопку "Назад"
4. Нажать кнопку "Администрирование"

Ожидаемый результат:
- Навигация работает корректно
- Параметры авторизации сохраняются при навигации
- URL обновляется без перезагрузки страницы
```

### 4. Тестирование безопасности

**Тест 7: Доступ без авторизации**
```
URL: /APP-B24/index.php
Ожидаемый результат:
- Редирект на страницу ошибки авторизации
- Vue.js приложение не загружается
```

**Тест 8: Доступ к защищенным маршрутам без прав администратора**
```
URL: /APP-B24/index.php?route=/token-analysis&AUTH_ID=...&DOMAIN=...
(Пользователь не является администратором)

Ожидаемый результат:
- Загружается Vue.js приложение
- Отображается сообщение об отсутствии прав доступа
- Или редирект на главную страницу
```

### 5. Тестирование в iframe Bitrix24

**Тест 9: Работа в iframe**
```
Шаги:
1. Открыть приложение в Bitrix24 через iframe
2. Проверить работу всех маршрутов
3. Проверить навигацию между страницами

Ожидаемый результат:
- Все маршруты работают корректно
- Навигация работает без перезагрузки страницы
- Параметры авторизации передаются корректно
```

---

## История правок

- **2025-12-21 20:05 (UTC+3, Брест):** Создана задача TASK-014 на унификацию точек входа и улучшение роутинга в Vue.js
- **2025-12-21 20:05 (UTC+3, Брест):** Добавлены детали создания VueAppService как отдельного сервиса с Dependency Injection и улучшенным логированием
- **2025-12-21 21:30 (UTC+3, Брест):** Задача выполнена:
  - ✅ Создан сервис `VueAppService` с Dependency Injection и улучшенным логированием через LoggerService
  - ✅ Обновлен `bootstrap.php` для инициализации VueAppService
  - ✅ Обновлен `loadVueApp.php` как обёртка для обратной совместимости
  - ✅ Упрощен `index.php` для использования единой точки входа с параметром `route`
  - ✅ Удалены файлы `token-analysis.php` и `access-control.php`
  - ✅ Настроены редиректы в `.htaccess` для старых URL
  - ✅ Обновлен навигационный скрипт в VueAppService для корректной работы с базовым путём `/APP-B24/`

---

## Дополнительные заметки

### Важные моменты

1. **Безопасность:**
   - Проверка авторизации должна оставаться на сервере (PHP)
   - Нельзя полагаться только на клиентскую проверку в Vue.js
   - Все проверки безопасности должны быть в PHP

2. **Обратная совместимость:**
   - Старые URL должны работать через редиректы
   - Закладки пользователей должны продолжать работать
   - Внешние ссылки должны работать

3. **Производительность:**
   - Редиректы должны быть быстрыми
   - Vue.js должен загружаться только после проверки авторизации
   - Кеширование статических файлов должно работать

4. **Логирование:**
   - Все попытки доступа должны логироваться
   - Ошибки должны логироваться с контекстом
   - Успешные загрузки должны логироваться для отладки

### Связанные документы

- `DOCS/ANALYSIS/2025-12-21-migrate-all-to-vue-analysis.md` — анализ возможности переноса на Vue.js
- `DOCS/ANALYSIS/2025-12-21-token-analysis-file-analysis.md` — анализ файла token-analysis.php
- `DOCS/TASKS/TASK-013-refactor-index-page-logic.md` — рефакторинг index.php
- `APP-B24/frontend/INTEGRATION.md` — документация по интеграции Vue.js

