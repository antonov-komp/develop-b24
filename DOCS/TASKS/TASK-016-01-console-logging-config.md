# TASK-016-01: Система управления логированием в консоль с конфигурацией

**Дата создания:** 2025-12-29 21:30 (UTC+3, Брест)  
**Статус:** Завершена  
**Приоритет:** Средний  
**Исполнитель:** Bitrix24 Программист (Vue.js) / Full-Stack разработчик

---

## Описание

Создать систему управления логированием в консоль браузера с возможностью включения/выключения логов по слоям через конфигурацию. Все существующие `console.log`, `console.warn`, `console.error` должны быть заменены на централизованную систему логирования с поддержкой категорий.

**Цель:**
- Организовать логи по смысловым слоям
- Добавить возможность управления логированием через конфигурацию
- Упростить отладку и мониторинг приложения
- Уменьшить шум в консоли в production режиме

**Ключевой принцип:** Все логи должны проходить через единую систему, которая проверяет конфигурацию перед выводом.

---

## Контекст

### Текущая ситуация

В приложении используется множество `console.log` в разных местах:
- **9 файлов** с логированием в консоль
- **Разрозненные логи** без категоризации
- **Нет централизованного управления** логированием
- **Сложно отключать логи** для production

### Анализ существующих логов

Из консоли браузера видно следующие категории логов:

1. **Инициализация (INIT):**
   - `Auth token from PHP saved to sessionStorage`
   - `Bitrix24 SDK initialized successfully`
   - `App data from PHP saved`

2. **Роутинг (ROUTER):**
   - `Router: Determining base path from: ...`
   - `Router: Base path determined: ...`
   - `Router beforeEach: ...`
   - `Router afterEach: ...`
   - `Router: External access enabled: ...`
   - `Router: Navigation allowed to ...`

3. **Vue App Lifecycle (VUE_LIFECYCLE):**
   - `Vue app mounting...`
   - `Vue app mounted successfully`
   - `App.vue mounted`
   - `IndexPage mounted, fetching user data...`

4. **UserStore (USER_STORE):**
   - `UserStore: Starting fetchCurrentUser...`
   - `UserStore: User data already available in app_data, using it`
   - `UserStore: app_data structure: ...`
   - `UserStore: Departments loaded from app_data: ...`
   - `User data loaded: ...`
   - `Admin status after fetch: ...`
   - `Auth status after fetch: ...`

5. **Bitrix24 API (BITRIX24):**
   - `Bitrix24 BX.* API доступен`
   - `URL params: ...`

6. **API запросы (API):**
   - `API Response: ...` (в development режиме)

7. **Access Control (ACCESS_CONTROL):**
   - Логи из `accessControlStore.js` (не видны в примере, но есть в коде)

8. **Ошибки (ERROR):**
   - `Vue error: ...`
   - `Ошибка загрузки пользователя: ...`

### Файлы с логированием

1. `frontend/src/main.js` - инициализация Vue app
2. `frontend/src/App.vue` - lifecycle компонента
3. `frontend/src/router/index.js` - роутинг
4. `frontend/src/stores/userStore.js` - UserStore
5. `frontend/src/stores/accessControlStore.js` - AccessControl store
6. `frontend/src/components/IndexPage.vue` - главная страница
7. `frontend/src/components/AccessControlPage.vue` - страница управления доступом
8. `frontend/src/components/TokenAnalysisPage.vue` - анализ токена
9. `frontend/src/services/api.js` - API клиент

---

## Модули и компоненты

### Новые файлы для создания:

1. **`frontend/src/utils/logger.js`** — утилита для логирования
   - Класс `Logger` с методами для каждого типа лога
   - Поддержка категорий (слоёв)
   - Проверка конфигурации перед выводом
   - Поддержка уровней логирования (debug, info, warn, error)

2. **`frontend/src/config/logging.js`** — конфигурация логирования
   - Загрузка конфигурации из `config.json` (через PHP)
   - Значения по умолчанию
   - Поддержка development/production режимов

### Обновление существующих файлов:

1. **`config.json`** — добавить секцию `logging`
2. **`index.php`** — передать конфигурацию логирования в Vue.js через `app_data`
3. Все файлы с `console.log` — заменить на использование `Logger`

---

## Структура конфигурации логирования

### Формат в `config.json`:

```json
{
  "index_page": {
    "enabled": true,
    "external_access": false,
    "block_bitrix24_iframe": false,
    "message": "...",
    "last_updated": "...",
    "updated_by": "..."
  },
  "logging": {
    "enabled": true,
    "default_level": "info",
    "layers": {
      "INIT": {
        "enabled": true,
        "level": "info"
      },
      "ROUTER": {
        "enabled": true,
        "level": "debug"
      },
      "VUE_LIFECYCLE": {
        "enabled": true,
        "level": "info"
      },
      "USER_STORE": {
        "enabled": true,
        "level": "debug"
      },
      "ACCESS_CONTROL": {
        "enabled": true,
        "level": "debug"
      },
      "API": {
        "enabled": true,
        "level": "debug"
      },
      "BITRIX24": {
        "enabled": true,
        "level": "info"
      },
      "ERROR": {
        "enabled": true,
        "level": "error"
      }
    }
  }
}
```

### Уровни логирования:

- **`debug`** — отладочная информация (самый детальный)
- **`info`** — информационные сообщения
- **`warn`** — предупреждения
- **`error`** — ошибки

### Слои логирования:

1. **`INIT`** — Инициализация (SDK, токены, app_data)
2. **`ROUTER`** — Роутинг (beforeEach, afterEach, navigation)
3. **`VUE_LIFECYCLE`** — Vue app lifecycle (mounting, mounted, components)
4. **`USER_STORE`** — UserStore (fetchCurrentUser, departments, auth)
5. **`ACCESS_CONTROL`** — AccessControl (права доступа)
6. **`API`** — API запросы (requests, responses)
7. **`BITRIX24`** — Bitrix24 API (BX.*, SDK)
8. **`ERROR`** — Ошибки (errors, warnings)

---

## Зависимости

### От других задач:
- TASK-016: Рефакторинг index.php — использует `ConfigService` для получения конфигурации

### От модулей:
- Использует `ConfigService` для получения конфигурации логирования
- Использует `app_data` для передачи конфигурации в Vue.js

---

## Ступенчатые подзадачи

### Этап 1: Создание системы логирования

1. **Создать `frontend/src/utils/logger.js`:**
   ```bash
   # Вручную: frontend/src/utils/logger.js
   ```
   - Реализовать класс `Logger`
   - Методы: `debug()`, `info()`, `warn()`, `error()`
   - Поддержка категорий (слоёв)
   - Проверка конфигурации перед выводом
   - Поддержка форматирования объектов

2. **Создать `frontend/src/config/logging.js`:**
   ```bash
   # Вручную: frontend/src/config/logging.js
   ```
   - Загрузка конфигурации из `app_data`
   - Значения по умолчанию
   - Поддержка development/production режимов

### Этап 2: Обновление конфигурации

3. **Обновить `config.json`:**
   - Добавить секцию `logging` с настройками по умолчанию
   - Все слои включены в development, только ERROR в production

4. **Обновить `ConfigService.php`:**
   - Добавить метод `getLoggingConfig()` для получения конфигурации логирования
   - Возвращать значения по умолчанию, если конфигурация не задана

5. **Обновить `IndexPageService.php`:**
   - Добавить конфигурацию логирования в `app_data` для передачи в Vue.js

### Этап 3: Рефакторинг существующих логов

6. **Обновить `frontend/src/main.js`:**
   - Заменить `console.log` на `Logger.info('VUE_LIFECYCLE', ...)`
   - Заменить `console.error` на `Logger.error('ERROR', ...)`

7. **Обновить `frontend/src/App.vue`:**
   - Заменить все `console.log` на использование `Logger`

8. **Обновить `frontend/src/router/index.js`:**
   - Заменить все `console.log` на `Logger.debug('ROUTER', ...)`

9. **Обновить `frontend/src/stores/userStore.js`:**
   - Заменить все `console.log` на `Logger.debug('USER_STORE', ...)`
   - Заменить `console.warn` на `Logger.warn('USER_STORE', ...)`

10. **Обновить `frontend/src/stores/accessControlStore.js`:**
    - Заменить все `console.log` на `Logger.debug('ACCESS_CONTROL', ...)`

11. **Обновить `frontend/src/components/IndexPage.vue`:**
    - Заменить все `console.log` на `Logger.info('VUE_LIFECYCLE', ...)`
    - Заменить `console.error` на `Logger.error('ERROR', ...)`

12. **Обновить `frontend/src/components/AccessControlPage.vue`:**
    - Заменить все `console.log` на `Logger.debug('ACCESS_CONTROL', ...)`

13. **Обновить `frontend/src/components/TokenAnalysisPage.vue`:**
    - Заменить все `console.log` на `Logger.debug('BITRIX24', ...)`

14. **Обновить `frontend/src/services/api.js`:**
    - Заменить `console.log` на `Logger.debug('API', ...)`
    - Заменить `console.error` на `Logger.error('API', ...)`

### Этап 4: Тестирование и проверка

15. **Проверить работу логирования:**
    - Все слои работают корректно
    - Конфигурация применяется правильно
    - Логи отключаются при `enabled: false`
    - Уровни логирования работают корректно

16. **Проверить в разных режимах:**
    - Development режим (все логи включены)
    - Production режим (только ERROR)
    - Кастомная конфигурация (включение/выключение отдельных слоёв)

---

## Технические требования

### Версии технологий:
- Vue.js 3.x
- JavaScript ES6+

### Ограничения и особенности:
- Сохранить обратную совместимость (не ломать существующий функционал)
- Логи должны работать даже если конфигурация не загружена (fallback на значения по умолчанию)
- В production режиме по умолчанию только ERROR логи включены

### Требования к производительности:
- Проверка конфигурации должна быть быстрой (кеширование)
- Не должно быть деградации производительности

---

## Критерии приёмки

### Функциональные требования:
- [ ] Создан класс `Logger` с поддержкой категорий
- [ ] Создана конфигурация логирования в `config.json`
- [ ] Конфигурация передаётся в Vue.js через `app_data`
- [ ] Все существующие `console.log` заменены на `Logger`
- [ ] Логи можно включать/выключать по слоям через конфигурацию
- [ ] Уровни логирования работают корректно (debug, info, warn, error)
- [ ] В production режиме по умолчанию только ERROR логи включены

### Технические требования:
- [ ] Код соответствует стандартам ESLint
- [ ] Все методы документированы (JSDoc)
- [ ] Нет деградации производительности
- [ ] Fallback на значения по умолчанию работает корректно

### Качество кода:
- [ ] Нет дублирования кода
- [ ] Код легко поддерживать и расширять
- [ ] Легко добавлять новые слои логирования

### Документация:
- [ ] Описание слоёв логирования в комментариях
- [ ] Примеры использования в коде

---

## Примеры кода

### Пример Logger:

```javascript
// frontend/src/utils/logger.js

import { getLoggingConfig } from '@/config/logging';

class Logger {
  constructor() {
    this.config = getLoggingConfig();
  }
  
  /**
   * Логирование с категорией и уровнем
   * 
   * @param {string} layer Категория (INIT, ROUTER, VUE_LIFECYCLE, etc.)
   * @param {string} level Уровень (debug, info, warn, error)
   * @param {string} message Сообщение
   * @param {object} data Данные для логирования
   */
  log(layer, level, message, data = null) {
    // Проверка, включено ли логирование вообще
    if (!this.config.enabled) {
      return;
    }
    
    // Проверка, включен ли слой
    const layerConfig = this.config.layers[layer];
    if (!layerConfig || !layerConfig.enabled) {
      return;
    }
    
    // Проверка уровня логирования
    const levels = ['debug', 'info', 'warn', 'error'];
    const currentLevelIndex = levels.indexOf(level);
    const configLevelIndex = levels.indexOf(layerConfig.level || this.config.default_level);
    
    if (currentLevelIndex < configLevelIndex) {
      return; // Уровень слишком низкий
    }
    
    // Форматирование сообщения
    const prefix = `[${layer}]`;
    const formattedMessage = `${prefix} ${message}`;
    
    // Вывод в консоль
    const consoleMethod = console[level] || console.log;
    if (data) {
      consoleMethod(formattedMessage, data);
    } else {
      consoleMethod(formattedMessage);
    }
  }
  
  debug(layer, message, data = null) {
    this.log(layer, 'debug', message, data);
  }
  
  info(layer, message, data = null) {
    this.log(layer, 'info', message, data);
  }
  
  warn(layer, message, data = null) {
    this.log(layer, 'warn', message, data);
  }
  
  error(layer, message, data = null) {
    this.log(layer, 'error', message, data);
  }
}

// Экспорт singleton
export default new Logger();
```

### Пример использования:

```javascript
// frontend/src/router/index.js

import Logger from '@/utils/logger';

router.beforeEach((to, from, next) => {
  Logger.debug('ROUTER', 'Router beforeEach', { 
    to: to.path, 
    from: from.path, 
    fullPath: to.fullPath, 
    query: to.query 
  });
  
  // ... остальной код
});
```

### Пример конфигурации:

```json
{
  "logging": {
    "enabled": true,
    "default_level": "info",
    "layers": {
      "INIT": { "enabled": true, "level": "info" },
      "ROUTER": { "enabled": false, "level": "debug" },
      "VUE_LIFECYCLE": { "enabled": true, "level": "info" },
      "USER_STORE": { "enabled": false, "level": "debug" },
      "ACCESS_CONTROL": { "enabled": true, "level": "debug" },
      "API": { "enabled": true, "level": "debug" },
      "BITRIX24": { "enabled": true, "level": "info" },
      "ERROR": { "enabled": true, "level": "error" }
    }
  }
}
```

---

## Тестирование

### Unit-тестирование:

1. **Logger:**
   - Тест включения/выключения логов
   - Тест уровней логирования
   - Тест категорий (слоёв)
   - Тест форматирования сообщений

2. **Конфигурация:**
   - Тест загрузки конфигурации из `app_data`
   - Тест значений по умолчанию
   - Тест fallback при отсутствии конфигурации

### Интеграционное тестирование:

1. **Проверка работы в браузере:**
   - Все слои логирования работают
   - Конфигурация применяется корректно
   - Логи отключаются при `enabled: false`
   - Уровни логирования работают

2. **Проверка в разных режимах:**
   - Development (все логи)
   - Production (только ERROR)
   - Кастомная конфигурация

---

## История правок

- **2025-12-29 21:30 (UTC+3, Брест):** Создана задача TASK-016-01 на систему управления логированием в консоль с конфигурацией
- **2025-12-29 20:44 (UTC+3, Брест):** Задача выполнена:
  - Создан Logger утилита с поддержкой категорий и уровней
  - Создана конфигурация логирования с загрузкой из app_data
  - Обновлён config.json с секцией logging
  - Обновлён ConfigService.php с методом getLoggingConfig()
  - Обновлён IndexPageService.php для передачи конфигурации в app_data
  - Рефакторены все файлы frontend (9 файлов) - заменены все console.log на Logger
  - Обновлён VueAppService.php - заменены console.log в генерируемом JavaScript на Logger
  - Все логи организованы по 8 слоям: INIT, ROUTER, VUE_LIFECYCLE, USER_STORE, ACCESS_CONTROL, API, BITRIX24, ERROR
- **2025-12-29 20:48 (UTC+3, Брест):** Задача успешно завершена и закрыта:
  - Система логирования полностью функциональна
  - Все критерии приёмки выполнены
  - Логи можно глобально отключать через `config.json` (установка `logging.enabled: false`)
  - Поддержка включения/выключения логов по слоям работает корректно
  - Уровни логирования (debug, info, warn, error) работают корректно
  - В production режиме по умолчанию только ERROR логи включены

