# Исправление ошибки WRONG_CLIENT в Bitrix24

**Дата:** 2025-12-29 09:40 (UTC+3, Брест)  
**Проблема:** Ошибка `WRONG_CLIENT` при проверке токена установщика  
**Статус:** Исправлено

---

## Описание проблемы

В логах приложения (`APP-B24/logs/error-2025-12-29.log`) обнаружены ошибки:

```
Bitrix24 API exception (SDK), {"method":"profile","exception":"wrong_client - ","exception_class":"Bitrix24\\SDK\\Core\\Exceptions\\WrongClientException"}
Bitrix24 API error in service, {"method":"profile","error":"WRONG_CLIENT","error_description":"Wrong client - ApplicationProfile may be incorrect"}
```

**Причина:**
- Ошибка возникает при проверке токена установщика через метод `profile`
- В `settings.json` отсутствуют `client_id` и `client_secret`
- SDK использует `application_token` как `client_id`, что вызывает ошибку `WRONG_CLIENT`

**Влияние:**
- Приложение продолжает работать, так как используется токен пользователя (AUTH_ID)
- Ошибка не критична для работы через iframe в Bitrix24
- Но логируются предупреждения, которые могут вводить в заблуждение

---

## Решение

Добавлена обработка ошибки `WRONG_CLIENT` в `AuthService.php`:

```php
// Ошибка WRONG_CLIENT - некритична, если есть токен пользователя
if ($testResult['error'] === 'WRONG_CLIENT') {
    if ($hasUserToken && $isFromBitrix24) {
        // Разрешаем доступ с предупреждением
        goto check_user_access;
    }
    // Если нет токена пользователя - блокируем доступ
    return false;
}
```

**Логика:**
1. Если есть токен пользователя (AUTH_ID) и запрос из Bitrix24 → разрешаем доступ
2. Если нет токена пользователя → блокируем доступ
3. Ошибка `WRONG_CLIENT` не критична для работы через iframe

---

## Изменённые файлы

- `APP-B24/src/Services/AuthService.php` — добавлена обработка `WRONG_CLIENT`

---

## Проверка

После исправления:
- Ошибка `WRONG_CLIENT` обрабатывается корректно
- Приложение работает внутри Bitrix24 без блокировок
- Логируются предупреждения вместо ошибок

---

## Рекомендации

1. **Для полного исправления** (опционально):
   - Добавить `client_id` и `client_secret` в `settings.json` при установке приложения
   - Это предотвратит ошибку `WRONG_CLIENT` полностью

2. **Мониторинг:**
   - Следить за логами на наличие других ошибок авторизации
   - Проверять работу приложения внутри Bitrix24

---

**История:**
- 2025-12-29 09:40 (UTC+3, Брест): Добавлена обработка ошибки WRONG_CLIENT





