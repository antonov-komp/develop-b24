# D7 ORM — методология работы с данными в коробочной версии Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по D7 ORM для работы с данными в коробочной версии Bitrix24

---

## Обзор

**D7 ORM** — объектно-реляционное отображение для работы с данными в коробочной версии Bitrix24. Позволяет работать напрямую с базой данных через Entity-классы.

**Документация:**
- **Официальная:** https://dev.1c-bitrix.ru/api_d7/orm/
- **Примеры:** https://dev.1c-bitrix.ru/api_d7/orm/examples/

---

## Когда использовать

✅ **Используйте D7 ORM, если:**
- Работаете с коробочной версией Bitrix24
- Нужна максимальная производительность
- Создаёте внутренние модули Bitrix24
- Нужна прямая работа с БД без ограничений API

❌ **Не используйте D7 ORM, если:**
- Работаете с облачной версией Bitrix24 (используйте REST API)
- Нужна внешняя интеграция из другого приложения

---

## Основные принципы

### 1. Entity-классы

**Структура Entity-класса:**
```php
<?php

namespace Local\Modules\CustomModule\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\DatetimeField;

class CustomEntityTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_custom_entity';
    }
    
    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            
            (new StringField('NAME'))
                ->configureRequired(true)
                ->configureSize(255),
            
            (new StringField('EMAIL'))
                ->configureSize(255),
            
            (new DatetimeField('CREATED_AT'))
                ->configureRequired(true)
        ];
    }
}
```

### 2. Работа с данными

**Получение записей:**
```php
use Local\Modules\CustomModule\ORM\CustomEntityTable;

// Получение всех записей
$result = CustomEntityTable::getList([
    'filter' => ['>ID' => 0],
    'select' => ['ID', 'NAME', 'EMAIL'],
    'order' => ['ID' => 'DESC'],
    'limit' => 50
]);

while ($row = $result->fetch()) {
    // Обработка записи
    echo $row['NAME'];
}
```

**Создание записи:**
```php
$result = CustomEntityTable::add([
    'NAME' => 'Иван Иванов',
    'EMAIL' => 'ivan@example.com',
    'CREATED_AT' => new \Bitrix\Main\Type\DateTime()
]);

if ($result->isSuccess()) {
    $id = $result->getId();
} else {
    $errors = $result->getErrorMessages();
}
```

**Обновление записи:**
```php
$result = CustomEntityTable::update($id, [
    'NAME' => 'Новое имя',
    'EMAIL' => 'new@example.com'
]);

if ($result->isSuccess()) {
    // Успешно обновлено
} else {
    $errors = $result->getErrorMessages();
}
```

**Удаление записи:**
```php
$result = CustomEntityTable::delete($id);

if ($result->isSuccess()) {
    // Успешно удалено
} else {
    $errors = $result->getErrorMessages();
}
```

### 3. Связи (Relations)

**Определение связи:**
```php
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\ManyToOne;

public static function getMap(): array
{
    return [
        // ... другие поля ...
        
        // Связь один-ко-многим
        (new OneToMany('ITEMS', ItemTable::class, 'ENTITY_ID')),
        
        // Связь многие-к-одному
        (new ManyToOne('STATUS', StatusTable::class, 'STATUS_ID'))
    ];
}
```

**Использование связей:**
```php
// Получение с связанными данными
$result = CustomEntityTable::getList([
    'filter' => ['ID' => 123],
    'select' => ['ID', 'NAME', 'STATUS.NAME', 'ITEMS']
]);

$row = $result->fetch();
$statusName = $row['STATUS_NAME']; // Имя статуса
$items = $row['ITEMS']; // Массив связанных элементов
```

---

## Работа с встроенными таблицами Bitrix24

### CRM: Лиды

```php
use Bitrix\Crm\LeadTable;

$result = LeadTable::getList([
    'filter' => [
        '>CREATED_DATE' => new \Bitrix\Main\Type\DateTime('2025-01-01')
    ],
    'select' => ['ID', 'NAME', 'EMAIL', 'PHONE', 'STATUS_ID'],
    'order' => ['ID' => 'DESC']
]);

while ($lead = $result->fetch()) {
    // Обработка лида
}
```

### CRM: Сделки

```php
use Bitrix\Crm\DealTable;

$result = DealTable::getList([
    'filter' => ['STAGE_ID' => 'NEW'],
    'select' => ['ID', 'TITLE', 'OPPORTUNITY', 'CURRENCY_ID']
]);
```

### Пользователи

```php
use Bitrix\Main\UserTable;

$result = UserTable::getList([
    'filter' => ['ACTIVE' => 'Y'],
    'select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL']
]);
```

---

## Query Builder

**Сложные запросы:**
```php
use Bitrix\Main\ORM\Query\Query;

$query = new Query(CustomEntityTable::getEntity());
$query
    ->setSelect(['ID', 'NAME', 'EMAIL'])
    ->setFilter(['>ID' => 0])
    ->setOrder(['ID' => 'DESC'])
    ->setLimit(50);

$result = $query->exec();

while ($row = $result->fetch()) {
    // Обработка
}
```

**Подзапросы:**
```php
$subQuery = new Query(CustomEntityTable::getEntity());
$subQuery
    ->setSelect(['ID'])
    ->setFilter(['STATUS' => 'ACTIVE']);

$query = new Query(CustomEntityTable::getEntity());
$query
    ->setSelect(['ID', 'NAME'])
    ->setFilter(['!ID' => $subQuery])
    ->exec();
```

---

## События (Events)

**Обработка событий:**
```php
use Bitrix\Main\ORM\Event;

// Перед добавлением
AddEventHandler('main', 'OnBeforeCustomEntityAdd', function(Event $event) {
    $fields = $event->getParameter('fields');
    
    // Валидация или модификация данных
    if (empty($fields['NAME'])) {
        $event->addError(new \Bitrix\Main\Error('Имя обязательно'));
    }
});

// После добавления
AddEventHandler('main', 'OnAfterCustomEntityAdd', function(Event $event) {
    $id = $event->getParameter('id');
    $fields = $event->getParameter('fields');
    
    // Дополнительная обработка после создания
});
```

---

## Кеширование

**Использование кеша:**
```php
use Bitrix\Main\Data\Cache;

$cache = Cache::createInstance();
$cacheId = 'custom_entity_list';
$cacheDir = '/custom_module/';
$cacheTime = 3600;

if ($cache->startDataCache($cacheTime, $cacheId, $cacheDir)) {
    $result = CustomEntityTable::getList([
        'filter' => ['>ID' => 0],
        'select' => ['ID', 'NAME']
    ]);
    
    $data = [];
    while ($row = $result->fetch()) {
        $data[] = $row;
    }
    
    $cache->endDataCache($data);
} else {
    $data = $cache->getVars();
}
```

---

## Лучшие практики

### 1. Использование индексов

```php
public static function getMap(): array
{
    return [
        // ... поля ...
        
        // Индекс для часто используемых фильтров
        (new Index('idx_created_at', ['CREATED_AT']))
    ];
}
```

### 2. Оптимизация запросов

```php
// ❌ Плохо: выборка всех полей
$result = CustomEntityTable::getList();

// ✅ Хорошо: выборка только нужных полей
$result = CustomEntityTable::getList([
    'select' => ['ID', 'NAME', 'EMAIL']
]);
```

### 3. Использование транзакций

```php
use Bitrix\Main\Application;

$connection = Application::getConnection();
$connection->startTransaction();

try {
    CustomEntityTable::add([...]);
    CustomEntityTable::update($id, [...]);
    
    $connection->commitTransaction();
} catch (\Exception $e) {
    $connection->rollbackTransaction();
    throw $e;
}
```

---

## Сравнение с REST API

| Критерий | D7 ORM | REST API |
|----------|--------|----------|
| Производительность | Высокая (прямая работа с БД) | Средняя (HTTP-запросы) |
| Ограничения | Нет | Есть (лимиты запросов) |
| Доступность | Только коробочная версия | Облачная и коробочная |
| Сложность | Средняя | Низкая |
| Кеширование | Встроенное | Требует реализации |

---

## Ссылки

- **Примеры Entity-классов:** [examples/](./examples/)
- **Лучшие практики:** [best-practices.md](./best-practices.md)
- **Решение проблем:** [troubleshooting.md](./troubleshooting.md)

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по D7 ORM





