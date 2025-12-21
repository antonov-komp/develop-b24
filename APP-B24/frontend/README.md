# Frontend на Vue.js

**Версия:** 3.0.0  
**Технологии:** Vue.js 3, Vite, Pinia, Axios

---

## Установка

```bash
cd frontend
npm install
```

## Разработка

```bash
npm run dev
```

Приложение будет доступно на `http://localhost:5173`

## Сборка для production

```bash
npm run build
```

Собранные файлы будут в `public/dist/`

## Структура проекта

```
frontend/
├── src/
│   ├── components/      # Vue компоненты
│   ├── services/        # API сервисы
│   ├── stores/          # Pinia stores
│   ├── utils/           # Утилиты
│   ├── assets/          # Статические ресурсы
│   ├── App.vue          # Корневой компонент
│   └── main.js          # Точка входа
├── public/              # Публичные файлы
├── package.json         # Зависимости
└── vite.config.js       # Конфигурация Vite
```

## API Endpoints

API endpoints находятся в `/APP-B24/api/`

- `GET /api/user/current` - Получение текущего пользователя
- `GET /api/departments` - Список отделов
- `GET /api/users` - Список пользователей

---

**Документация:** См. `/DOCS/REFACTOR/migration-plan.md`





