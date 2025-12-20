# Структура API

**Дата создания:** 2025-12-19 11:52 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Описание структуры API REST приложения Bitrix24

---

## Общая концепция

Приложение использует **Bitrix24 REST API** для работы с данными. Все запросы выполняются через официальный SDK **b24phpsdk** через клиент `Bitrix24SdkClient`.

---

## Точка входа

### Основной файл
- **Путь:** `APP-B24/index.php`
- **Назначение:** Точка входа для выполнения API-запросов

### Библиотека b24phpsdk
- **Клиент:** `App\Clients\Bitrix24SdkClient`
- **Метод:** `$client->call($method, $params)`
- **Документация:** https://github.com/bitrix24/b24phpsdk

---

## Структура API-запросов

### Формат запроса
```php
use App\Clients\Bitrix24SdkClient;
use App\Services\LoggerService;

$logger = new LoggerService();
$client = new Bitrix24SdkClient($logger);
$client->initializeWithInstallerToken();

$result = $client->call(
    'method_name',           // Метод Bitrix24 REST API
    [                        // Параметры запроса
        'filter' => [...],
        'select' => [...],
        'order' => [...]
    ]
);
```

### Формат ответа
```php
[
    'result' => [...],       // Данные результата
    'total' => 100,          // Общее количество (если применимо)
    'next' => 50            // Следующая страница (если применимо)
]
```

### Обработка ошибок
```php
if (isset($result['error'])) {
    // Обработка ошибки
    // $result['error'] — код ошибки
    // $result['error_description'] — описание ошибки
}
```

---

## Основные методы API

### Пользователи
- **`user.current`** — получение данных текущего пользователя
  - Документация: https://context7.com/bitrix24/rest/user.current
  - Использование: Получение данных пользователя через токен из `AUTH_ID`
- **`user.get`** — получение данных пользователя по ID
  - Документация: https://context7.com/bitrix24/rest/user.get
  - Использование: Получение дополнительных полей пользователя (например, `ADMIN`)
- **`user.admin`** — проверка, является ли пользователь администратором
  - Документация: https://context7.com/bitrix24/rest/user.admin
  - Использование: Альтернативная проверка статуса администратора

### Отделы
- **`department.get`** — получение данных отдела по ID или списка всех отделов
  - Документация: https://context7.com/bitrix24/rest/department.get
  - Использование: Получение списка отделов для управления правами доступа

### Профиль пользователя (устаревший метод)
- **Метод:** `profile`
- **Документация:** https://context7.com/bitrix24/rest/profile
- **Примечание:** Возвращает данные владельца токена, не текущего пользователя
- **Рекомендация:** Использовать `user.current` с токеном текущего пользователя

### Лиды (Leads)
- **Список:** `crm.lead.list`
- **Получение:** `crm.lead.get`
- **Создание:** `crm.lead.add`
- **Обновление:** `crm.lead.update`
- **Удаление:** `crm.lead.delete`
- **Документация:** https://context7.com/bitrix24/rest/crm.lead.list

### Сделки (Deals)
- **Список:** `crm.deal.list`
- **Получение:** `crm.deal.get`
- **Создание:** `crm.deal.add`
- **Обновление:** `crm.deal.update`
- **Документация:** https://context7.com/bitrix24/rest/crm.deal.list

### Контакты (Contacts)
- **Список:** `crm.contact.list`
- **Получение:** `crm.contact.get`
- **Создание:** `crm.contact.add`
- **Обновление:** `crm.contact.update`
- **Документация:** https://context7.com/bitrix24/rest/crm.contact.list

### Компании (Companies)
- **Список:** `crm.company.list`
- **Получение:** `crm.company.get`
- **Создание:** `crm.company.add`
- **Обновление:** `crm.company.update`
- **Документация:** https://context7.com/bitrix24/rest/crm.company.list

---

## Кастомные API endpoints (если требуются)

### Структура
```
/api/
├── leads/          # Работа с лидами
├── deals/          # Работа со сделками
├── contacts/       # Работа с контактами
└── companies/      # Работа с компаниями
```

### Формат ответа
```json
{
    "success": true,
    "data": {...},
    "message": "Операция выполнена успешно"
}
```

---

## Аутентификация

### Вебхук (Webhook)
- **Тип:** Входящий вебхук
- **Настройка:** В Bitrix24 → Разработчикам → Другое → Входящий вебхук
- **Использование:** `C_REST_WEB_HOOK_URL` в `settings.php`

### OAuth 2.0 (Application)
- **Тип:** Приложение Bitrix24
- **Настройка:** `C_REST_CLIENT_ID` и `C_REST_CLIENT_SECRET` в `settings.php`
- **Использование:** Автоматическая авторизация через CRest

---

## Ограничения API

### Лимиты запросов
- **Облачная версия:** Ограничения согласно тарифу Bitrix24
- **Рекомендация:** Использовать кеширование и батч-операции

### Таймауты
- **По умолчанию:** 30 секунд
- **Настройка:** Через параметры CRest

---

## Логирование API-запросов

### Расположение логов
- **Путь:** `APP-B24/logs/`
- **Формат:** `YYYY-MM-DD/HH/[timestamp]_[type]_[id]log.json`

### Типы логов
- `emptySetting` — отсутствие настроек
- `apiCall` — вызов API (если включено логирование)
- `error` — ошибки API

---

## Использование API в приложении

### Получение данных текущего пользователя
Вместо использования `CRest::call('profile')` или `CRest::call('user.current')`, которые возвращают данные владельца токена, приложение использует прямой HTTP-запрос к Bitrix24 REST API с токеном текущего пользователя из параметра `AUTH_ID`:

```php
// Получение токена текущего пользователя
$authId = $_REQUEST['AUTH_ID'] ?? null;
$domain = $_REQUEST['DOMAIN'] ?? null;

// Прямой HTTP-запрос к Bitrix24 REST API
$url = 'https://' . $domain . '/rest/user.current.json';
$params = http_build_query(['auth' => $authId]);
// ... выполнение запроса через cURL
```

### Проверка прав доступа
Приложение проверяет доступность методов API для анализа прав доступа токена:
- `crm.lead.list` — проверка прав на чтение лидов
- `crm.deal.list` — проверка прав на чтение сделок
- `crm.contact.list` — проверка прав на чтение контактов
- `department.get` — проверка прав на чтение отделов
- `user.get` — проверка прав на чтение пользователей

---

## История правок

- **2025-12-19 11:52 (UTC+3, Брест):** Создан документ с описанием структуры API
- **2025-12-20 18:00 (UTC+3, Брест):** Обновлено описание методов API с учетом реализованного функционала


