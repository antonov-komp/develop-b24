# Обновление SSL сертификата для поддоменов

**Дата:** 2025-12-29 18:08 (UTC+3, Брест)  
**Статус:** ✅ **ВЫПОЛНЕНО**

---

## Задача

Обновить SSL сертификат Let's Encrypt:
- ✅ Удалить лишние поддомены из сертификата
- ✅ Добавить `develop.antonov-mark.ru` в сертификат
- ✅ Оставить только нужные поддомены

---

## Удалённые поддомены (лишние)

Следующие поддомены были удалены из сертификата:
- ❌ `back.antonov-mark.ru`
- ❌ `back1.antonov-mark.ru`
- ❌ `chats.antonov-mark.ru`
- ❌ `www.back1.antonov-mark.ru`
- ❌ `www.chats.antonov-mark.ru`

---

## Актуальные поддомены в сертификате

После обновления в сертификате остались только нужные поддомены:

1. ✅ `antonov-mark.ru` (основной домен)
2. ✅ `www.antonov-mark.ru` (www версия)
3. ✅ `backend.antonov-mark.ru` (backend поддомен)
4. ✅ `develop.antonov-mark.ru` (develop поддомен) — **ДОБАВЛЕН**

---

## Выполненные действия

### 1. Обновление сертификата через Certbot

```bash
sudo certbot certonly --nginx --expand \
  -d antonov-mark.ru \
  -d www.antonov-mark.ru \
  -d backend.antonov-mark.ru \
  -d develop.antonov-mark.ru \
  --non-interactive --agree-tos
```

**Результат:**
- Создан новый сертификат: `antonov-mark.ru-0001`
- Путь к сертификату: `/etc/letsencrypt/live/antonov-mark.ru-0001/fullchain.pem`
- Путь к ключу: `/etc/letsencrypt/live/antonov-mark.ru-0001/privkey.pem`

### 2. Автоматическое обновление конфигураций NGINX

Certbot автоматически обновил конфигурации NGINX:
- ✅ `/etc/nginx/sites-enabled/antonov-mark.ru`
- ✅ `/etc/nginx/sites-enabled/develop.antonov-mark.ru`
- ✅ `/etc/nginx/sites-enabled/default` (для backend.antonov-mark.ru)

### 3. Перезагрузка NGINX

```bash
sudo nginx -t
sudo systemctl reload nginx
```

**Результат:** Конфигурация валидна, NGINX перезагружен

---

## Проверка работоспособности

### Проверка сертификата

```bash
sudo certbot certificates
```

**Результат:**
```
Certificate Name: antonov-mark.ru-0001
Domains: antonov-mark.ru backend.antonov-mark.ru develop.antonov-mark.ru www.antonov-mark.ru
Expiry Date: 2026-03-29 14:09:17+00:00 (VALID: 89 days)
```

### Проверка SSL для develop.antonov-mark.ru

```bash
curl -I https://develop.antonov-mark.ru
```

**Результат:**
```
HTTP/2 200
server: nginx/1.24.0 (Ubuntu)
```

✅ **SSL работает корректно!**

### Проверка Subject Alternative Name

```bash
sudo openssl x509 -in /etc/letsencrypt/live/antonov-mark.ru-0001/fullchain.pem -noout -text | grep -A 1 "Subject Alternative Name"
```

**Результат:**
```
X509v3 Subject Alternative Name:
    DNS:antonov-mark.ru, DNS:backend.antonov-mark.ru, DNS:develop.antonov-mark.ru, DNS:www.antonov-mark.ru
```

---

## Статус сертификатов

### Активный сертификат (antonov-mark.ru-0001)

- **Имя:** `antonov-mark.ru-0001`
- **Домены:** 4 поддомена (только нужные)
- **Срок действия:** до 2026-03-29 (89 дней)
- **Статус:** ✅ Активен и используется

### Старый сертификат (antonov-mark.ru)

- **Имя:** `antonov-mark.ru`
- **Домены:** 8 поддоменов (включая лишние)
- **Срок действия:** до 2026-03-12 (72 дня)
- **Статус:** ⚠️ Не используется (оставлен для совместимости)

**Примечание:** Старый сертификат будет автоматически удалён после истечения срока действия.

---

## Конфигурация NGINX

### Обновлённые файлы

Все активные конфигурации NGINX теперь используют новый сертификат:

```nginx
ssl_certificate /etc/letsencrypt/live/antonov-mark.ru-0001/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/antonov-mark.ru-0001/privkey.pem;
```

**Файлы:**
- `/etc/nginx/sites-enabled/antonov-mark.ru`
- `/etc/nginx/sites-enabled/develop.antonov-mark.ru`
- `/etc/nginx/sites-enabled/default` (для backend)

---

## Автоматическое обновление

Certbot настроен на автоматическое обновление сертификата:
- Обновление происходит за 30 дней до истечения срока
- Следующее обновление: ~2026-02-28
- Команда обновления: `certbot renew`

---

## Итоговый результат

✅ **SSL сертификат успешно обновлён:**

1. ✅ Лишние поддомены удалены из сертификата
2. ✅ `develop.antonov-mark.ru` добавлен в сертификат
3. ✅ Конфигурации NGINX автоматически обновлены
4. ✅ NGINX перезагружен и работает корректно
5. ✅ HTTPS работает для всех нужных поддоменов

**Доступные HTTPS поддомены:**
- ✅ `https://antonov-mark.ru`
- ✅ `https://www.antonov-mark.ru`
- ✅ `https://backend.antonov-mark.ru`
- ✅ `https://develop.antonov-mark.ru` — **НОВЫЙ**

---

## Команды для проверки

### Проверка списка сертификатов
```bash
sudo certbot certificates
```

### Проверка доменов в сертификате
```bash
sudo openssl x509 -in /etc/letsencrypt/live/antonov-mark.ru-0001/fullchain.pem -noout -text | grep -A 1 "Subject Alternative Name"
```

### Проверка SSL соединения
```bash
curl -I https://develop.antonov-mark.ru
```

### Проверка конфигурации NGINX
```bash
sudo nginx -t
```

### Перезагрузка NGINX
```bash
sudo systemctl reload nginx
```

---

**История правок:**
- 2025-12-29 18:08 (UTC+3, Брест): Обновлён SSL сертификат, удалены лишние поддомены, добавлен develop.antonov-mark.ru


