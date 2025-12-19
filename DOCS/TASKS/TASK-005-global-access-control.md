# TASK-005: Глобальное управление правами доступа в приложении Bitrix24

**Дата создания:** 2025-12-19 14:39 (UTC+3, Брест)  
**Статус:** Новая  
**Приоритет:** Высокий  
**Исполнитель:** Bitrix24 Программист (Vanilla JS)

## Описание

Создать страницу управления глобальными правами доступа в приложение Bitrix24. Страница должна позволять администраторам Bitrix24 настраивать, кто имеет доступ к приложению:
- Дать права на всю структуру отдела (выбор из списка всех отделов)
- Дать права на конкретного сотрудника (выбор из списка всех пользователей)

**Глобальный принцип работы:**
- Все администраторы Bitrix24 видят стартовую страницу (`index.php`) априори
- Все администраторы видят страницу анализа токена (`token-analysis.php`)
- Все администраторы могут редактировать доступ в приложение через новую страницу управления правами
- Если в настройках доступа нет записей (отделов или сотрудников), пользователь получает страницу неуспеха (`failure.php`)
- Если пользователь не является администратором и не имеет прав доступа (нет в списке отделов/сотрудников), он получает страницу неуспеха

## Контекст

В текущей реализации приложение проверяет только авторизацию Bitrix24 через `auth-check.php`, но не проверяет, имеет ли конкретный пользователь право на доступ к приложению. 

**Требования:**
- Администраторы Bitrix24 всегда имеют доступ (глобально)
- Обычные пользователи получают доступ только если:
  - Они находятся в отделе, которому предоставлен доступ
  - ИЛИ они индивидуально добавлены в список разрешённых пользователей
- Если в настройках доступа нет записей (пустой список отделов и пользователей), все не-администраторы получают страницу неуспеха
- Настройки доступа хранятся в JSON-файле и управляются через веб-интерфейс

## Модули и компоненты

- `APP-B24/access-control.php` — страница управления правами доступа (новая)
- `APP-B24/access-config.json` — файл конфигурации прав доступа (новый)
- `APP-B24/auth-check.php` — обновление: добавление проверки прав доступа
- `APP-B24/index.php` — обновление: добавление проверки прав доступа перед отображением интерфейса
- `APP-B24/logs/access-control-*.log` — логи управления правами доступа
- `APP-B24/logs/access-check-*.log` — логи проверки прав доступа

## Зависимости

- Использует параметры запроса от Bitrix24 (`AUTH_ID`, `DOMAIN`)
- Требует наличия `settings.json` с настройками приложения
- Использует библиотеку `crest.php` для работы с API
- Использует `auth-check.php` для проверки авторизации
- Зависит от логики определения администратора (из TASK-002, TASK-003)

## Ступенчатые подзадачи

### 1. Создание структуры файла конфигурации прав доступа

**Файл:** `APP-B24/access-config.json`

**Структура JSON:**
```json
{
  "access_control": {
    "enabled": true,
    "departments": [
      {
        "id": 1,
        "name": "Отдел продаж",
        "added_at": "2025-12-19 14:39:00",
        "added_by": {
          "id": 123,
          "name": "Иван Иванов"
        }
      }
    ],
    "users": [
      {
        "id": 456,
        "name": "Петр Петров",
        "email": "petr@example.com",
        "added_at": "2025-12-19 14:39:00",
        "added_by": {
          "id": 123,
          "name": "Иван Иванов"
        }
      }
    ],
    "last_updated": "2025-12-19 14:39:00",
    "updated_by": {
      "id": 123,
      "name": "Иван Иванов"
    }
  }
}
```

**Принцип работы:**
- Если `enabled: false` — проверка прав доступа отключена (все авторизованные пользователи имеют доступ)
- Если `enabled: true` — проверка прав доступа включена:
  - Администраторы всегда имеют доступ
  - Обычные пользователи имеют доступ только если:
    - Их отдел в списке `departments`
    - ИЛИ их ID в списке `users`
- Если `departments` и `users` пустые массивы — все не-администраторы получают страницу неуспеха

### 2. Создание страницы управления правами доступа

**Файл:** `APP-B24/access-control.php`

**Функциональность:**
1. **Проверка прав администратора:**
   - Страница доступна только администраторам Bitrix24
   - Использовать логику проверки администратора из `token-analysis.php`

2. **Отображение текущих настроек:**
   - Список отделов с доступом (ID, название, дата добавления, кто добавил)
   - Список пользователей с доступом (ID, ФИО, email, дата добавления, кто добавил)
   - Кнопка "Включить/выключить проверку прав доступа"

3. **Добавление отдела:**
   - Выпадающий список всех отделов портала (получение через API)
   - Кнопка "Добавить отдел"
   - При добавлении сохранять: ID отдела, название, дату, кто добавил

4. **Добавление пользователя:**
   - Поиск пользователей (поиск по имени, email)
   - Выпадающий список найденных пользователей
   - Кнопка "Добавить пользователя"
   - При добавлении сохранять: ID пользователя, ФИО, email, дату, кто добавил

5. **Удаление отдела/пользователя:**
   - Кнопка "Удалить" рядом с каждым отделом/пользователем
   - Подтверждение удаления

6. **Сохранение изменений:**
   - Все изменения сохраняются в `access-config.json`
   - Логирование всех операций (добавление, удаление, включение/выключение)

### 3. Реализация получения списка отделов

**Функция:** `getAllDepartments($authId, $domain)`

**Метод API:** `department.get` (без параметра ID) или `department.list`
- Документация: https://context7.com/bitrix24/rest/department.get
- Альтернатива: https://context7.com/bitrix24/rest/department.list (если доступен)

**Особенности:**
- Получение всех отделов портала
- Обработка ошибок прав доступа (если токен не имеет прав)
- Формирование списка для выпадающего списка (ID и название)

### 4. Реализация получения списка пользователей

**Функция:** `getAllUsers($authId, $domain)`

**Метод API:** `user.get` (без параметра ID)
- Документация: https://context7.com/bitrix24/rest/user.get

**Особенности:**
- Получение всех пользователей портала
- Фильтрация по имени/email (для поиска)
- Формирование списка для выпадающего списка (ID, ФИО, email)

### 5. Реализация проверки прав доступа

**Функция:** `checkUserAccess($userId, $userDepartments, $authId, $domain)`

**Логика проверки:**
1. Проверка статуса администратора (если администратор — доступ разрешён)
2. Чтение `access-config.json`
3. Если `enabled: false` — доступ разрешён
4. Если `enabled: true`:
   - Проверка, есть ли отдел пользователя в списке `departments`
   - Проверка, есть ли ID пользователя в списке `users`
   - Если хотя бы одно условие выполнено — доступ разрешён
   - Если оба условия не выполнены — доступ запрещён
5. Если `departments` и `users` пустые — доступ запрещён (кроме администраторов)

**Возвращаемое значение:**
- `true` — доступ разрешён
- `false` — доступ запрещён

### 6. Интеграция проверки прав доступа в auth-check.php

**Обновление функции:** `checkBitrix24Auth()`

**Добавить после проверки авторизации:**
```php
// Если авторизация прошла успешно — проверяем права доступа
if ($authResult) {
    // Получаем данные текущего пользователя
    $currentUserAuthId = $_REQUEST['AUTH_ID'] ?? null;
    $portalDomain = $_REQUEST['DOMAIN'] ?? null;
    
    if ($currentUserAuthId && $portalDomain) {
        // Получаем данные пользователя
        $userResult = getCurrentUserData($currentUserAuthId, $portalDomain);
        
        if (!isset($userResult['error']) && isset($userResult['result'])) {
            $user = $userResult['result'];
            $userId = $user['ID'] ?? null;
            $userDepartments = $user['UF_DEPARTMENT'] ?? [];
            
            // Проверяем права доступа
            $hasAccess = checkUserAccess($userId, $userDepartments, $currentUserAuthId, $portalDomain);
            
            if (!$hasAccess) {
                // Доступ запрещён — редирект на failure.php
                redirectToFailure();
                return false;
            }
        }
    }
}
```

**ВАЖНО:** Проверка прав доступа должна выполняться только для не-администраторов. Администраторы всегда имеют доступ.

### 7. Интеграция проверки прав доступа в index.php

**Добавить после проверки конфигурации:**
```php
// Проверка прав доступа (если включена)
$accessConfig = getAccessConfig();
if ($accessConfig['enabled']) {
    // Получаем данные текущего пользователя
    $currentUserAuthId = $_REQUEST['AUTH_ID'] ?? null;
    $portalDomain = $_REQUEST['DOMAIN'] ?? null;
    
    if ($currentUserAuthId && $portalDomain) {
        // Получаем данные пользователя
        $userResult = getCurrentUserData($currentUserAuthId, $portalDomain);
        
        if (!isset($userResult['error']) && isset($userResult['result'])) {
            $user = $userResult['result'];
            $userId = $user['ID'] ?? null;
            $userDepartments = $user['UF_DEPARTMENT'] ?? [];
            
            // Проверяем, является ли пользователь администратором
            $isAdmin = checkIsAdmin($user, $currentUserAuthId, $portalDomain);
            
            // Если не администратор — проверяем права доступа
            if (!$isAdmin) {
                $hasAccess = checkUserAccess($userId, $userDepartments, $currentUserAuthId, $portalDomain);
                
                if (!$hasAccess) {
                    // Доступ запрещён — редирект на failure.php
                    logConfigCheck('ACCESS DENIED: User does not have access rights');
                    redirectToFailure();
                    exit;
                }
            }
        }
    }
}
```

### 8. Создание функций для работы с конфигурацией доступа

**Функции:**
- `getAccessConfig()` — чтение `access-config.json`
- `saveAccessConfig($config)` — сохранение `access-config.json`
- `addDepartmentToAccess($departmentId, $departmentName, $addedBy)` — добавление отдела
- `removeDepartmentFromAccess($departmentId)` — удаление отдела
- `addUserToAccess($userId, $userName, $userEmail, $addedBy)` — добавление пользователя
- `removeUserFromAccess($userId)` — удаление пользователя
- `toggleAccessControl($enabled, $updatedBy)` — включение/выключение проверки прав доступа

### 9. Реализация интерфейса страницы управления правами

**Элементы интерфейса:**
1. **Заголовок:** "Управление правами доступа"
2. **Переключатель:** "Включить проверку прав доступа" (checkbox)
3. **Секция "Отделы с доступом":**
   - Таблица с отделами (ID, название, дата добавления, кто добавил, кнопка удаления)
   - Выпадающий список всех отделов
   - Кнопка "Добавить отдел"
4. **Секция "Пользователи с доступом":**
   - Таблица с пользователями (ID, ФИО, email, дата добавления, кто добавил, кнопка удаления)
   - Поле поиска пользователей
   - Выпадающий список найденных пользователей
   - Кнопка "Добавить пользователя"
5. **Кнопка "Сохранить изменения"**
6. **Кнопка "Назад на главную"**

**Стилизация:**
- Использовать стили, аналогичные `token-analysis.php`
- Плавные анимации переходов
- Адаптивный дизайн

### 10. Логирование операций

**Файл:** `APP-B24/logs/access-control-YYYY-MM-DD.log`

**Что логировать:**
- Время операции
- Тип операции (добавление отдела, удаление пользователя, включение/выключение проверки)
- Данные операции (ID отдела/пользователя, название/ФИО)
- Кто выполнил операцию (ID и ФИО администратора)
- Результат операции (успех/ошибка)

**Файл:** `APP-B24/logs/access-check-YYYY-MM-DD.log`

**Что логировать:**
- Время проверки
- ID пользователя
- Отделы пользователя
- Результат проверки (доступ разрешён/запрещён)
- Причина (администратор, отдел в списке, пользователь в списке, нет прав)

## API-методы Bitrix24

### department.get / department.list
**Описание:** Получение списка всех отделов портала  
**Документация:** 
- https://context7.com/bitrix24/rest/department.get
- https://context7.com/bitrix24/rest/department.list (если доступен)

**Использование:**
- Получение списка всех отделов для выпадающего списка
- Получение названия отдела по ID

**Пример:**
```php
// Получение всех отделов
$result = CRest::call('department.get', []); // Без параметра ID
// Или
$result = CRest::call('department.list', []); // Если метод доступен
```

### user.get
**Описание:** Получение списка всех пользователей портала  
**Документация:** https://context7.com/bitrix24/rest/user.get

**Использование:**
- Получение списка всех пользователей для выпадающего списка
- Поиск пользователей по имени/email

**Пример:**
```php
// Получение всех пользователей
$result = CRest::call('user.get', []); // Без параметра ID

// Поиск пользователей
$result = CRest::call('user.get', [
    'filter' => [
        'NAME' => '%Иван%', // Поиск по имени
        'EMAIL' => '%example.com%' // Поиск по email
    ]
]);
```

### user.current
**Описание:** Получение данных текущего пользователя  
**Документация:** https://context7.com/bitrix24/rest/user.current

**Использование:**
- Получение данных пользователя для проверки прав доступа
- Получение отделов пользователя (поле `UF_DEPARTMENT`)

### user.admin
**Описание:** Проверка, является ли пользователь администратором  
**Документация:** https://context7.com/bitrix24/rest/user.admin

**Использование:**
- Проверка статуса администратора перед проверкой прав доступа
- Администраторы всегда имеют доступ

## Технические требования

### Безопасность
- Страница управления правами доступна только администраторам
- Все изменения логируются с указанием, кто их выполнил
- Валидация всех входящих данных (ID отдела/пользователя)
- Защита от прямого доступа к `access-config.json` (через `.htaccess` или проверку в PHP)

### Производительность
- Кеширование списка отделов и пользователей (если возможно)
- Асинхронная загрузка данных (через AJAX, если используется JavaScript)
- Оптимизация запросов к API (батч-запросы, если возможно)

### Форматирование JSON
- JSON должен быть отформатирован с отступами (pretty print)
- Использовать `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE` для вывода

### Обработка ошибок
- Обработка ошибок при чтении/записи `access-config.json`
- Обработка ошибок при запросах к API (нет прав, метод не найден)
- Fallback-механизмы (если файл конфигурации повреждён, использовать значения по умолчанию)

## Критерии приёмки

- [ ] Страница `access-control.php` создана и защищена от доступа не-администраторов
- [ ] Файл `access-config.json` создан с правильной структурой
- [ ] Получение списка всех отделов работает корректно
- [ ] Получение списка всех пользователей работает корректно
- [ ] Добавление отдела в список доступа работает
- [ ] Удаление отдела из списка доступа работает
- [ ] Добавление пользователя в список доступа работает
- [ ] Удаление пользователя из списка доступа работает
- [ ] Включение/выключение проверки прав доступа работает
- [ ] Проверка прав доступа интегрирована в `auth-check.php`
- [ ] Проверка прав доступа интегрирована в `index.php`
- [ ] Администраторы всегда имеют доступ (независимо от настроек)
- [ ] Если `enabled: false` — все авторизованные пользователи имеют доступ
- [ ] Если `enabled: true` и списки пустые — все не-администраторы получают страницу неуспеха
- [ ] Если пользователь в списке отделов — доступ разрешён
- [ ] Если пользователь в списке пользователей — доступ разрешён
- [ ] Если пользователь не в списках и не администратор — доступ запрещён (страница неуспеха)
- [ ] Логирование всех операций работает корректно
- [ ] Обработка ошибок реализована
- [ ] Интерфейс страницы управления правами соответствует дизайну приложения

## Тестирование

### Тест 1: Доступ администратора
**Шаги:**
1. Открыть приложение от имени администратора
2. Проверить, что доступ разрешён (отображается главная страница)
3. Открыть страницу управления правами доступа
4. Проверить, что страница открывается без ошибок

**Ожидаемый результат:**
- Администратор имеет доступ независимо от настроек прав доступа
- Страница управления правами доступна

### Тест 2: Доступ обычного пользователя (без настроек)
**Шаги:**
1. Убедиться, что `access-config.json` содержит пустые списки отделов и пользователей
2. Убедиться, что `enabled: true`
3. Открыть приложение от имени обычного пользователя (не администратора)
4. Проверить, что отображается страница неуспеха (`failure.php`)

**Ожидаемый результат:**
- Пользователь получает страницу неуспеха
- В логах записано: "ACCESS DENIED: User does not have access rights"

### Тест 3: Доступ пользователя из разрешённого отдела
**Шаги:**
1. Добавить отдел в список доступа через страницу управления правами
2. Убедиться, что `enabled: true`
3. Открыть приложение от имени пользователя из этого отдела
4. Проверить, что доступ разрешён (отображается главная страница)

**Ожидаемый результат:**
- Пользователь имеет доступ
- В логах записано: "ACCESS GRANTED: User department in access list"

### Тест 4: Доступ индивидуально добавленного пользователя
**Шаги:**
1. Добавить пользователя в список доступа через страницу управления правами
2. Убедиться, что `enabled: true`
3. Открыть приложение от имени этого пользователя
4. Проверить, что доступ разрешён (отображается главная страница)

**Ожидаемый результат:**
- Пользователь имеет доступ
- В логах записано: "ACCESS GRANTED: User ID in access list"

### Тест 5: Управление правами доступа
**Шаги:**
1. Открыть страницу управления правами от имени администратора
2. Добавить отдел в список доступа
3. Добавить пользователя в список доступа
4. Проверить, что изменения сохранились в `access-config.json`
5. Удалить отдел из списка доступа
6. Удалить пользователя из списка доступа
7. Проверить, что изменения сохранились

**Ожидаемый результат:**
- Все изменения сохраняются в `access-config.json`
- В логах записаны все операции
- Интерфейс обновляется после сохранения

### Тест 6: Выключение проверки прав доступа
**Шаги:**
1. Установить `enabled: false` через страницу управления правами
2. Открыть приложение от имени обычного пользователя (не администратора)
3. Проверить, что доступ разрешён (отображается главная страница)

**Ожидаемый результат:**
- Все авторизованные пользователи имеют доступ
- Проверка прав доступа не выполняется

## Примеры реализации

### Пример 1: Функция проверки прав доступа

```php
/**
 * Проверка прав доступа пользователя к приложению
 * 
 * @param int $userId ID пользователя
 * @param array $userDepartments Массив ID отделов пользователя
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @return bool true если доступ разрешён, false если запрещён
 */
function checkUserAccess($userId, $userDepartments, $authId, $domain) {
    // Проверка статуса администратора
    $isAdmin = checkIsAdmin($authId, $domain);
    if ($isAdmin) {
        // Администраторы всегда имеют доступ
        logAccessCheck($userId, $userDepartments, 'granted', 'admin');
        return true;
    }
    
    // Чтение конфигурации прав доступа
    $accessConfig = getAccessConfig();
    
    // Если проверка отключена — доступ разрешён
    if (!isset($accessConfig['access_control']['enabled']) || !$accessConfig['access_control']['enabled']) {
        logAccessCheck($userId, $userDepartments, 'granted', 'check_disabled');
        return true;
    }
    
    $departments = $accessConfig['access_control']['departments'] ?? [];
    $users = $accessConfig['access_control']['users'] ?? [];
    
    // Если списки пустые — доступ запрещён
    if (empty($departments) && empty($users)) {
        logAccessCheck($userId, $userDepartments, 'denied', 'no_access_rules');
        return false;
    }
    
    // Проверка, есть ли отдел пользователя в списке
    foreach ($userDepartments as $deptId) {
        foreach ($departments as $dept) {
            if (isset($dept['id']) && $dept['id'] == $deptId) {
                logAccessCheck($userId, $userDepartments, 'granted', 'department_in_list');
                return true;
            }
        }
    }
    
    // Проверка, есть ли пользователь в списке
    foreach ($users as $user) {
        if (isset($user['id']) && $user['id'] == $userId) {
            logAccessCheck($userId, $userDepartments, 'granted', 'user_in_list');
            return true;
        }
    }
    
    // Доступ запрещён
    logAccessCheck($userId, $userDepartments, 'denied', 'not_in_lists');
    return false;
}
```

### Пример 2: Функция получения всех отделов

```php
/**
 * Получение списка всех отделов портала
 * 
 * Метод: department.get (без параметра ID) или department.list
 * Документация: https://context7.com/bitrix24/rest/department.get
 * 
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @return array Массив отделов [['id' => 1, 'name' => 'Отдел продаж'], ...]
 */
function getAllDepartments($authId, $domain) {
    // Пробуем метод department.get без параметра ID
    $url = 'https://' . $domain . '/rest/department.get.json';
    $params = http_build_query(['auth' => $authId]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError || $httpCode !== 200) {
        // Пробуем через CRest (токен установщика)
        $result = CRest::call('department.get', []);
        
        if (isset($result['error'])) {
            // Если и это не работает, пробуем department.list
            $result = CRest::call('department.list', []);
        }
    } else {
        $result = json_decode($response, true);
    }
    
    if (isset($result['error']) || !isset($result['result'])) {
        return [];
    }
    
    $departments = [];
    $resultData = $result['result'];
    
    // Обработка разных вариантов структуры ответа
    if (is_array($resultData)) {
        foreach ($resultData as $dept) {
            if (is_array($dept) && isset($dept['ID'])) {
                $departments[] = [
                    'id' => (int)$dept['ID'],
                    'name' => $dept['NAME'] ?? 'Без названия'
                ];
            }
        }
    }
    
    return $departments;
}
```

### Пример 3: Функция получения всех пользователей

```php
/**
 * Получение списка всех пользователей портала
 * 
 * Метод: user.get (без параметра ID)
 * Документация: https://context7.com/bitrix24/rest/user.get
 * 
 * @param string $authId Токен авторизации
 * @param string $domain Домен портала
 * @param string|null $search Поисковый запрос (имя или email)
 * @return array Массив пользователей [['id' => 1, 'name' => 'Иван Иванов', 'email' => 'ivan@example.com'], ...]
 */
function getAllUsers($authId, $domain, $search = null) {
    $url = 'https://' . $domain . '/rest/user.get.json';
    
    $requestParams = ['auth' => $authId];
    
    // Если есть поисковый запрос — добавляем фильтр
    if ($search) {
        $requestParams['filter'] = [
            'NAME' => '%' . $search . '%',
            'EMAIL' => '%' . $search . '%'
        ];
    }
    
    $params = http_build_query($requestParams);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Bitrix24 App PHP');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError || $httpCode !== 200) {
        // Пробуем через CRest (токен установщика)
        $result = CRest::call('user.get', $search ? ['filter' => $requestParams['filter']] : []);
    } else {
        $result = json_decode($response, true);
    }
    
    if (isset($result['error']) || !isset($result['result'])) {
        return [];
    }
    
    $users = [];
    $resultData = $result['result'];
    
    // Обработка структуры ответа
    if (is_array($resultData)) {
        foreach ($resultData as $user) {
            if (is_array($user) && isset($user['ID'])) {
                $users[] = [
                    'id' => (int)$user['ID'],
                    'name' => trim(($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? '')),
                    'email' => $user['EMAIL'] ?? null
                ];
            }
        }
    }
    
    return $users;
}
```

### Пример 4: HTML-разметка страницы управления правами

```html
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление правами доступа - Bitrix24 Приложение</title>
    <style>
        /* Стили аналогичные token-analysis.php */
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
        .section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .section h2 {
            margin-bottom: 15px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.8;
        }
        select, input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Управление правами доступа</h1>
        
        <div class="section">
            <label>
                <input type="checkbox" id="access-enabled" <?= $accessConfig['enabled'] ? 'checked' : '' ?>>
                Включить проверку прав доступа
            </label>
        </div>
        
        <div class="section">
            <h2>Отделы с доступом</h2>
            <table id="departments-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Добавлен</th>
                        <th>Кто добавил</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Заполняется через JavaScript -->
                </tbody>
            </table>
            <select id="department-select">
                <option value="">Выберите отдел</option>
                <!-- Заполняется через JavaScript -->
            </select>
            <button class="btn btn-primary" onclick="addDepartment()">Добавить отдел</button>
        </div>
        
        <div class="section">
            <h2>Пользователи с доступом</h2>
            <table id="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Email</th>
                        <th>Добавлен</th>
                        <th>Кто добавил</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Заполняется через JavaScript -->
                </tbody>
            </table>
            <input type="text" id="user-search" placeholder="Поиск пользователей (имя или email)">
            <select id="user-select">
                <option value="">Выберите пользователя</option>
                <!-- Заполняется через JavaScript -->
            </select>
            <button class="btn btn-primary" onclick="addUser()">Добавить пользователя</button>
        </div>
        
        <div style="margin-top: 30px;">
            <button class="btn btn-primary" onclick="saveChanges()">Сохранить изменения</button>
            <form method="POST" action="index.php" style="display: inline-block; margin-left: 10px;">
                <input type="hidden" name="AUTH_ID" value="<?= htmlspecialchars($_REQUEST['AUTH_ID'] ?? '') ?>">
                <input type="hidden" name="DOMAIN" value="<?= htmlspecialchars($_REQUEST['DOMAIN'] ?? '') ?>">
                <button type="submit" class="btn">Назад на главную</button>
            </form>
        </div>
    </div>
    
    <script>
        // JavaScript для управления правами доступа
        // Загрузка списков отделов и пользователей
        // Обработка добавления/удаления
        // Сохранение изменений
    </script>
</body>
</html>
```

## История правок

- **2025-12-19 14:39 (UTC+3, Брест):** Создана задача

---

**Статус:** Новая  
**Дата создания:** 2025-12-19 14:39 (UTC+3, Брест)

