# TASK-003: Анализ токена, его владельца и прав доступа в REST-приложении Bitrix24

**Дата создания:** 2025-12-19 13:42 (UTC+3, Брест)  
**Статус:** Завершена  
**Приоритет:** Средний  
**Исполнитель:** Bitrix24 Программист (Vanilla JS)

## Описание

Создать вторую страницу в REST-приложении Bitrix24 для детального анализа токена авторизации. Страница должна анализировать:
- Сам токен (AUTH_ID из параметров запроса)
- Владельца токена (данные пользователя, которому принадлежит токен)
- Права доступа токена (какие методы API доступны, статус администратора)

Результат анализа должен быть представлен в формате JSON с возможностью копирования для дальнейшего использования или отладки.

**Дополнительно реализовано:**
- Защита от доступа не-администраторов (страница доступна только администраторам)
- Безопасная передача токена через POST-запросы (токен не виден в URL)
- Плавные анимации переходов для улучшения UX

## Контекст

В REST-приложениях Bitrix24 важно понимать:
- Какой токен используется (токен текущего пользователя или токен установщика)
- Кому принадлежит токен (данные владельца токена)
- Какие права имеет токен (может ли он выполнять определенные операции, является ли владелец администратором)

Эта информация необходима для:
- Отладки проблем с правами доступа
- Понимания, от имени какого пользователя выполняются запросы
- Проверки доступности методов API
- Документирования прав доступа приложения

## Модули и компоненты

- `APP-B24/token-analysis.php` — страница анализа токена (новая)
- `APP-B24/logs/token-analysis-*.log` — логи анализа токена
- Использование существующих функций из `index.php`:
  - `getCurrentUserData()` — получение данных пользователя по токену
  - Логика получения домена портала

## Зависимости

- Использует параметры запроса от Bitrix24 (`AUTH_ID`, `DOMAIN`)
- Требует наличия `settings.json` с настройками приложения
- Использует библиотеку `crest.php` для fallback-запросов
- Использует `auth-check.php` для защиты от прямого доступа
- Зависит от логики определения домена портала (из TASK-002)

## Ступенчатые подзадачи

### 1. Создание структуры страницы анализа токена

**Файл:** `APP-B24/token-analysis.php`

**Шаги:**
1. Подключить `auth-check.php` для защиты от прямого доступа
2. Подключить `crest.php` для работы с API
3. Получить токен из параметров запроса (`$_REQUEST['AUTH_ID']`)
4. Получить домен портала (использовать логику из `index.php`)
5. Создать функцию `analyzeToken()` для комплексного анализа токена

### 2. Реализация анализа токена

**Функция:** `analyzeToken($authId, $domain)`

**Что анализировать:**

#### 2.1. Базовые данные токена
- Наличие токена в запросе
- Длина токена
- Первые и последние символы токена (для идентификации, без полного раскрытия)
- Тип токена (текущего пользователя или установщика)

#### 2.2. Данные владельца токена
- ID пользователя
- ФИО пользователя
- Email
- Фото пользователя (URL)
- Часовой пояс
- Отдел пользователя (ID и название, если доступно)
- Дата создания аккаунта (если доступно)

**Методы API:**
- `user.current` — получение данных текущего пользователя
  - Документация: https://context7.com/bitrix24/rest/user.current
- `user.get` — получение данных пользователя по ID (если нужны дополнительные поля)
  - Документация: https://context7.com/bitrix24/rest/user.get

#### 2.3. Права доступа токена

**Проверка статуса администратора:**
- Поле `ADMIN` в данных пользователя
- Метод `user.admin` — проверка, является ли пользователь администратором
  - Документация: https://context7.com/bitrix24/rest/user.admin

**Проверка доступности методов API:**
- Попытка вызвать различные методы API для проверки прав доступа
- Методы для проверки:
  - `crm.lead.list` — проверка прав на чтение лидов
    - Документация: https://context7.com/bitrix24/rest/crm.lead.list
  - `crm.deal.list` — проверка прав на чтение сделок
    - Документация: https://context7.com/bitrix24/rest/crm.deal.list
  - `crm.contact.list` — проверка прав на чтение контактов
    - Документация: https://context7.com/bitrix24/rest/crm.contact.list
  - `department.get` — проверка прав на чтение отделов
    - Документация: https://context7.com/bitrix24/rest/department.get
  - `user.get` — проверка прав на чтение пользователей
    - Документация: https://context7.com/bitrix24/rest/user.get

**Обработка ошибок:**
- Если метод возвращает ошибку `insufficient_scope` — нет прав на метод
- Если метод возвращает ошибку `ERROR_METHOD_NOT_FOUND` — метод недоступен
- Если метод возвращает успешный ответ — права есть

### 3. Формирование результата анализа

**Структура JSON-ответа:**

```json
{
  "analysis_timestamp": "2025-12-19 13:42:00",
  "token_info": {
    "exists": true,
    "length": 32,
    "preview": "abc...xyz",
    "type": "current_user" | "installer"
  },
  "token_owner": {
    "id": 123,
    "name": "Иван",
    "last_name": "Иванов",
    "full_name": "Иван Иванов",
    "email": "ivan@example.com",
    "photo": "https://...",
    "time_zone": "Europe/Minsk",
    "department": {
      "id": 1,
      "name": "Отдел продаж" | null
    },
    "account_created": "2024-01-01 00:00:00" | null
  },
  "permissions": {
    "is_admin": true | false,
    "admin_check_method": "ADMIN_field" | "user.admin_method",
    "api_methods": {
      "crm.lead.list": {
        "accessible": true | false,
        "error": "error_code" | null,
        "error_description": "description" | null
      },
      "crm.deal.list": {
        "accessible": true | false,
        "error": "error_code" | null,
        "error_description": "description" | null
      },
      "crm.contact.list": {
        "accessible": true | false,
        "error": "error_code" | null,
        "error_description": "description" | null
      },
      "department.get": {
        "accessible": true | false,
        "error": "error_code" | null,
        "error_description": "description" | null
      },
      "user.get": {
        "accessible": true | false,
        "error": "error_code" | null,
        "error_description": "description" | null
      }
    }
  },
  "portal_info": {
    "domain": "develop.bitrix24.by",
    "domain_source": "request_params" | "client_endpoint" | "settings"
  },
  "errors": []
}
```

### 4. Отображение результата

**Варианты отображения:**

#### Вариант 1: JSON в текстовом поле с кнопкой копирования
- Текстовое поле `<textarea>` с JSON-результатом
- Кнопка "Копировать JSON" для копирования в буфер обмена
- Красивое форматирование JSON (с отступами)

#### Вариант 2: JSON в блоке с кнопкой копирования
- Блок `<pre>` с JSON-результатом
- Кнопка "Копировать" для копирования в буфер обмена
- Стилизация для читаемости

**Рекомендация:** Использовать Вариант 1 (textarea) для удобства копирования.

### 5. Логирование анализа

**Файл:** `APP-B24/logs/token-analysis-YYYY-MM-DD.log`

**Что логировать:**
- Время анализа
- Наличие токена
- Домен портала
- Результаты проверки прав доступа
- Ошибки при проверке методов API
- Общее время выполнения анализа

### 6. Обработка ошибок

**Типы ошибок:**
- Отсутствие токена в запросе
- Неверный формат токена
- Ошибка получения данных пользователя
- Ошибки при проверке прав доступа
- Ошибки сети (cURL)

**Обработка:**
- Все ошибки должны быть включены в JSON-ответ в поле `errors`
- Логирование всех ошибок
- Продолжение анализа даже при частичных ошибках (если возможно)

## API-методы Bitrix24

### user.current
**Описание:** Получение данных текущего пользователя  
**Документация:** https://context7.com/bitrix24/rest/user.current  
**Использование:** Получение данных владельца токена

### user.get
**Описание:** Получение данных пользователя по ID  
**Документация:** https://context7.com/bitrix24/rest/user.get  
**Использование:** Получение дополнительных полей пользователя (если нужны)

### user.admin
**Описание:** Проверка, является ли пользователь администратором  
**Документация:** https://context7.com/bitrix24/rest/user.admin  
**Использование:** Проверка прав администратора

### crm.lead.list
**Описание:** Получение списка лидов  
**Документация:** https://context7.com/bitrix24/rest/crm.lead.list  
**Использование:** Проверка прав на чтение лидов

### crm.deal.list
**Описание:** Получение списка сделок  
**Документация:** https://context7.com/bitrix24/rest/crm.deal.list  
**Использование:** Проверка прав на чтение сделок

### crm.contact.list
**Описание:** Получение списка контактов  
**Документация:** https://context7.com/bitrix24/rest/crm.contact.list  
**Использование:** Проверка прав на чтение контактов

### department.get
**Описание:** Получение данных отдела по ID  
**Документация:** https://context7.com/bitrix24/rest/department.get  
**Использование:** Проверка прав на чтение отделов и получение названия отдела

## Технические требования

### Безопасность
- Страница должна быть защищена от прямого доступа (через `auth-check.php`)
- Токен не должен полностью отображаться в интерфейсе (только preview)
- Логи не должны содержать полный токен (только preview)

### Производительность
- Анализ должен выполняться быстро (не более 5 секунд)
- Проверка методов API должна быть асинхронной или последовательной (на усмотрение разработчика)
- Кеширование результатов не требуется (каждый раз свежий анализ)

### Форматирование JSON
- JSON должен быть отформатирован с отступами (pretty print)
- Использовать `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE` для вывода

### Копирование в буфер обмена
- Использовать JavaScript для копирования
- Поддержка современных браузеров (Clipboard API)
- Fallback для старых браузеров (через `document.execCommand('copy')`)

## Критерии приёмки

- [x] Страница `token-analysis.php` создана и защищена от прямого доступа
- [x] Анализ токена выполняется корректно (базовые данные токена)
- [x] Данные владельца токена получаются и отображаются в JSON
- [x] Проверка статуса администратора работает (через поле `ADMIN` и метод `user.admin`)
- [x] Проверка прав доступа к методам API работает (минимум 5 методов)
- [x] Результат анализа отображается в формате JSON
- [x] JSON можно скопировать в буфер обмена (кнопка "Копировать")
- [x] JSON отформатирован с отступами (pretty print)
- [x] Обработка ошибок реализована (все ошибки в поле `errors`)
- [x] Логирование добавлено для диагностики
- [x] Токен не полностью раскрывается в интерфейсе (только preview)
- [x] Страница работает как из Bitrix24, так и при прямом доступе (с валидным токеном)
- [x] Страница доступна только администраторам портала
- [x] Ссылка на страницу скрыта для обычных пользователей
- [x] Токен передаётся через POST-запросы (безопасно)
- [x] Добавлены плавные анимации переходов

## Тестирование

### Тест 1: Анализ токена текущего пользователя
**Шаги:**
1. Открыть приложение в Bitrix24 (от имени обычного пользователя)
2. Перейти на страницу анализа токена
3. Проверить, что JSON содержит данные текущего пользователя
4. Проверить, что права доступа проверены корректно

**Ожидаемый результат:**
- JSON содержит данные текущего пользователя
- Поле `token_info.type` = `"current_user"`
- Права доступа проверены для всех методов

### Тест 2: Анализ токена администратора
**Шаги:**
1. Открыть приложение в Bitrix24 (от имени администратора)
2. Перейти на страницу анализа токена
3. Проверить, что `permissions.is_admin` = `true`

**Ожидаемый результат:**
- `permissions.is_admin` = `true`
- Большинство методов API доступны

### Тест 3: Копирование JSON
**Шаги:**
1. Открыть страницу анализа токена
2. Нажать кнопку "Копировать JSON"
3. Вставить в текстовый редактор

**Ожидаемый результат:**
- JSON скопирован в буфер обмена
- Вставленный JSON корректен и отформатирован

### Тест 4: Проверка логов
**Файлы для проверки:**
- `APP-B24/logs/token-analysis-YYYY-MM-DD.log`

**Ожидаемое содержимое:**
- Время анализа
- Наличие токена
- Результаты проверки прав доступа
- Ошибки (если есть)

### Тест 5: Обработка ошибок
**Шаги:**
1. Открыть страницу анализа токена без токена (если возможно)
2. Проверить, что ошибки обработаны корректно

**Ожидаемый результат:**
- Ошибки включены в поле `errors` JSON-ответа
- Страница не падает с фатальной ошибкой

## Примеры реализации

### Пример 1: Функция анализа токена

```php
/**
 * Комплексный анализ токена Bitrix24
 * 
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @return array Результат анализа в формате массива
 */
function analyzeToken($authId, $domain) {
    $analysis = [
        'analysis_timestamp' => date('Y-m-d H:i:s'),
        'token_info' => [],
        'token_owner' => [],
        'permissions' => [
            'is_admin' => false,
            'admin_check_method' => null,
            'api_methods' => []
        ],
        'portal_info' => [
            'domain' => $domain,
            'domain_source' => 'request_params' // или другой источник
        ],
        'errors' => []
    ];
    
    // Анализ токена
    if (empty($authId)) {
        $analysis['errors'][] = 'Токен не найден в параметрах запроса';
        return $analysis;
    }
    
    $analysis['token_info'] = [
        'exists' => true,
        'length' => strlen($authId),
        'preview' => substr($authId, 0, 4) . '...' . substr($authId, -4),
        'type' => 'current_user' // или 'installer'
    ];
    
    // Получение данных владельца токена
    $userResult = getCurrentUserData($authId, $domain);
    
    if (isset($userResult['error'])) {
        $analysis['errors'][] = 'Ошибка получения данных пользователя: ' . ($userResult['error_description'] ?? $userResult['error']);
    } else {
        $user = $userResult['result'] ?? null;
        
        if ($user) {
            $analysis['token_owner'] = [
                'id' => $user['ID'] ?? null,
                'name' => $user['NAME'] ?? null,
                'last_name' => $user['LAST_NAME'] ?? null,
                'full_name' => trim(($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? '')),
                'email' => $user['EMAIL'] ?? null,
                'photo' => $user['PERSONAL_PHOTO'] ?? null,
                'time_zone' => $user['TIME_ZONE'] ?? null,
                'department' => [
                    'id' => $user['UF_DEPARTMENT'][0] ?? null,
                    'name' => null // Получим позже через department.get
                ]
            ];
            
            // Проверка статуса администратора
            if (isset($user['ADMIN'])) {
                $analysis['permissions']['is_admin'] = (
                    $user['ADMIN'] === 'Y' || 
                    $user['ADMIN'] == 1 || 
                    $user['ADMIN'] === true
                );
                $analysis['permissions']['admin_check_method'] = 'ADMIN_field';
            } else {
                // Проверка через метод user.admin
                $adminCheckResult = checkAdminStatus($authId, $domain);
                if ($adminCheckResult !== null) {
                    $analysis['permissions']['is_admin'] = $adminCheckResult;
                    $analysis['permissions']['admin_check_method'] = 'user.admin_method';
                }
            }
        }
    }
    
    // Проверка прав доступа к методам API
    $methodsToCheck = [
        'crm.lead.list',
        'crm.deal.list',
        'crm.contact.list',
        'department.get',
        'user.get'
    ];
    
    foreach ($methodsToCheck as $method) {
        $checkResult = checkApiMethodAccess($method, $authId, $domain);
        $analysis['permissions']['api_methods'][$method] = $checkResult;
    }
    
    return $analysis;
}
```

### Пример 2: Функция проверки доступа к методу API

```php
/**
 * Проверка доступа к методу API
 * 
 * @param string $method Название метода API (например, 'crm.lead.list')
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @return array Результат проверки
 */
function checkApiMethodAccess($method, $authId, $domain) {
    $url = 'https://' . $domain . '/rest/' . $method . '.json';
    
    // Формируем минимальный запрос для проверки доступа
    $params = http_build_query([
        'auth' => $authId,
        'filter' => ['ID' => 0], // Несуществующий ID для минимального запроса
        'select' => ['ID'], // Минимальный набор полей
        'limit' => 1 // Минимум данных
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Таймаут 5 секунд
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return [
            'accessible' => false,
            'error' => 'curl_error',
            'error_description' => $curlError
        ];
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['error'])) {
        return [
            'accessible' => false,
            'error' => $result['error'],
            'error_description' => $result['error_description'] ?? 'Unknown error'
        ];
    }
    
    // Если нет ошибки, значит метод доступен
    return [
        'accessible' => true,
        'error' => null,
        'error_description' => null
    ];
}
```

### Пример 3: HTML-разметка страницы

```html
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анализ токена - Bitrix24 Приложение</title>
    <style>
        /* Стили для страницы анализа токена */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        .json-container {
            position: relative;
        }
        textarea {
            width: 100%;
            min-height: 500px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }
        .copy-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .copy-button:hover {
            background: #5568d3;
        }
        .copy-button:active {
            background: #4457c2;
        }
        .success-message {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Анализ токена Bitrix24</h1>
        <div class="json-container">
            <textarea id="json-output" readonly><?= htmlspecialchars(json_encode($analysisResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>
            <button class="copy-button" onclick="copyJson()">Копировать JSON</button>
        </div>
    </div>
    
    <div class="success-message" id="success-message">
        JSON скопирован в буфер обмена!
    </div>
    
    <script>
        function copyJson() {
            const textarea = document.getElementById('json-output');
            textarea.select();
            textarea.setSelectionRange(0, 99999); // Для мобильных устройств
            
            try {
                document.execCommand('copy');
                showSuccessMessage();
            } catch (err) {
                // Fallback для современных браузеров
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(textarea.value).then(function() {
                        showSuccessMessage();
                    });
                } else {
                    alert('Не удалось скопировать. Пожалуйста, скопируйте вручную.');
                }
            }
        }
        
        function showSuccessMessage() {
            const message = document.getElementById('success-message');
            message.style.display = 'block';
            setTimeout(function() {
                message.style.display = 'none';
            }, 2000);
        }
    </script>
</body>
</html>
```

## История правок

- **2025-12-19 13:42 (UTC+3, Брест):** Создана задача
- **2025-12-19 17:00 (UTC+3, Брест):** Задача выполнена. Создана страница `token-analysis.php` с полным функционалом анализа токена, проверки прав доступа и отображения результатов в JSON-формате с возможностью копирования.
- **2025-12-19 18:00 (UTC+3, Брест):** Добавлена защита от доступа не-администраторов:
  - Проверка прав администратора в начале страницы `token-analysis.php`
  - Скрыта ссылка на страницу анализа токена для обычных пользователей в `index.php`
  - Добавлена страница ошибки доступа с информативным сообщением
  - Логирование попыток доступа не-администраторов в `token-analysis-access-denied-YYYY-MM-DD.log`
- **2025-12-19 18:30 (UTC+3, Брест):** Улучшена безопасность передачи токена:
  - Все ссылки переведены с GET на POST-запросы
  - Токен передаётся через скрытые поля формы вместо URL-параметров
  - Токен не виден в адресной строке браузера
  - Сохранена обратная совместимость с GET-запросами
- **2025-12-19 19:00 (UTC+3, Брест):** Добавлены плавные анимации переходов:
  - Анимация появления контента на главной странице (`index.php`)
  - Анимация появления контента на странице анализа токена (`token-analysis.php`)
  - Анимация появления страницы ошибки доступа
  - Последовательное появление элементов с задержками
  - Hover-эффекты для интерактивных элементов

---

## Дополнительные улучшения (вне основной задачи)

### Безопасность
1. **Защита от доступа не-администраторов:**
   - Страница `token-analysis.php` доступна только администраторам портала
   - Проверка статуса администратора выполняется через поле `ADMIN` или метод `user.admin`
   - Обычные пользователи видят страницу с ошибкой доступа
   - Все попытки доступа не-администраторов логируются

2. **Безопасная передача токена:**
   - Токен передаётся через POST-запросы вместо GET
   - Токен не отображается в URL браузера
   - Токен не попадает в историю браузера, логи сервера и рефереры
   - Сохранена обратная совместимость с GET-запросами

### UX/UI улучшения
1. **Плавные анимации:**
   - Плавное появление фона страницы (fadeIn)
   - Анимация появления контейнера снизу вверх (slideUpFadeIn)
   - Последовательное появление элементов с задержками
   - Анимация масштабирования для фото пользователя (scaleIn)
   - Анимация появления слева для кнопки "Назад" (fadeInLeft)
   - Hover-эффекты для всех интерактивных элементов

2. **Улучшенная навигация:**
   - Кнопка "Назад" на странице анализа токена
   - Кнопка "Вернуться на главную" на странице ошибки доступа
   - Все переходы используют POST-запросы для безопасности

### Технические детали реализации

#### Функции анализа токена:
- `analyzeToken($authId, $domain)` — комплексный анализ токена
- `checkAdminStatus($authId, $domain)` — проверка статуса администратора
- `checkApiMethodAccess($method, $authId, $domain)` — проверка прав доступа к методам API
- `getCurrentUserData($authId, $domain)` — получение данных пользователя
- `getDepartmentData($departmentId, $authId, $domain)` — получение данных отдела

#### Проверяемые методы API:
- `crm.lead.list` — проверка прав на чтение лидов
- `crm.deal.list` — проверка прав на чтение сделок
- `crm.contact.list` — проверка прав на чтение контактов
- `department.get` — проверка прав на чтение отделов
- `user.get` — проверка прав на чтение пользователей

#### Логирование:
- `token-analysis-YYYY-MM-DD.log` — логи анализа токена
- `token-analysis-access-denied-YYYY-MM-DD.log` — логи попыток доступа не-администраторов

---

**Статус:** Завершена  
**Дата создания:** 2025-12-19 13:42 (UTC+3, Брест)  
**Дата завершения:** 2025-12-19 19:00 (UTC+3, Брест)

