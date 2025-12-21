# Placement — методы работы с встройками в интерфейс Bitrix24

**Дата создания:** 2025-12-20 19:09 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Документация по методам работы с placements (встройками в интерфейс)

---

## Обзор

**Placement** — механизм встраивания приложений в интерфейс Bitrix24. Позволяет создавать кастомные вкладки, кнопки и виджеты.

**Документация:**
- **REST API:** https://context7.com/bitrix24/rest/placement/
- **UI Kit:** https://apidocs.bitrix24.ru/sdk/ui.html

---

## Основные методы

### Placements
- `placement.bind` — регистрация placement
- `placement.unbind` — отвязка placement
- `placement.list` — получение списка placements

---

## Типы placements

- `CRM_LEAD_DETAIL_TAB` — вкладка в карточке лида
- `CRM_DEAL_DETAIL_TAB` — вкладка в карточке сделки
- `CRM_CONTACT_DETAIL_TAB` — вкладка в карточке контакта
- `CRM_COMPANY_DETAIL_TAB` — вкладка в карточке компании

---

## История правок

- 2025-12-20 19:09 (UTC+3, Брест): Создана документация по методам работы с placements







