# REST API для Vue.js фронтенда

**Версия:** 3.0.0  
**Базовый URL:** `/APP-B24/api/`

---

## Endpoints

### Пользователи

- **GET** `/api/user/current` - Получение текущего пользователя
  - Параметры: `AUTH_ID`, `DOMAIN` (query или POST)
  - Ответ: `{ success: true, data: { user: {...}, isAdmin: bool, departments: [...] } }`

### Отделы

- **GET** `/api/departments` - Получение списка всех отделов
  - Параметры: `AUTH_ID`, `DOMAIN` (query или POST)
  - Ответ: `{ success: true, data: { departments: [...] } }`

### Управление правами доступа

- **GET** `/api/access-control` - Получение конфигурации прав доступа (только для администраторов)
- **POST** `/api/access-control/departments` - Добавление отдела
- **POST** `/api/access-control/users` - Добавление пользователя
- **DELETE** `/api/access-control/departments/{id}` - Удаление отдела
- **DELETE** `/api/access-control/users/{id}` - Удаление пользователя

### Анализ токена

- **GET** `/api/token-analysis` - Анализ текущего токена (только для администраторов)

---

## Формат ответа

### Успешный ответ:
```json
{
  "success": true,
  "data": { ... }
}
```

### Ошибка:
```json
{
  "success": false,
  "error": "Error code",
  "message": "Error message"
}
```

---

## HTTP статус коды

- `200` - Успех
- `400` - Неверный запрос
- `401` - Не авторизован
- `403` - Доступ запрещен
- `404` - Не найдено
- `405` - Метод не разрешен
- `500` - Ошибка сервера

---

**Документация:** См. `/DOCS/REFACTOR/migration-plan.md`
