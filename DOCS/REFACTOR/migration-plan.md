# План миграции на Vue.js

**Дата создания:** 2025-12-20 19:38 (UTC+3, Брест)  
**Версия:** 1.0  
**Описание:** Пошаговый план миграции REST-приложения Bitrix24 на Vue.js с использованием Vite

---

## Общая концепция миграции

### Принципы

1. **Постепенная миграция:** Поэтапный переход без остановки работы приложения
2. **Обратная совместимость:** Старые страницы продолжают работать во время миграции
3. **Модульность:** Компонентный подход в Vue.js
4. **API-first:** Сначала создаем API endpoints, затем Vue.js компоненты

### Технологический стек (после миграции)

**Backend:**
- PHP 8.4+ (без изменений)
- **b24phpsdk** (официальный Bitrix24 PHP SDK) — замена CRest
- REST API endpoints для Vue.js

**Frontend:**
- Vue.js 3.x (Composition API)
- Vite (сборщик)
- TypeScript (опционально, для лучшей типизации)
- CSS3 / SCSS (для стилей)

**Инструменты:**
- npm/yarn (управление зависимостями)
- Vite (сборка и разработка)
- ESLint (линтинг)
- Prettier (форматирование)

---

## Этап 0: Миграция бэкенда на b24phpsdk

**⚠️ ВАЖНО:** Перед началом миграции на Vue.js необходимо выполнить миграцию бэкенда с CRest на b24phpsdk.

**Подробный план:** См. [crest-to-b24phpsdk-migration.md](crest-to-b24phpsdk-migration.md)

### Краткое описание этапа

1. **Установка b24phpsdk:**
   ```bash
   composer require bitrix24/b24phpsdk
   ```

2. **Создание нового Bitrix24SdkClient:**
   - Замена `Bitrix24Client` на `Bitrix24SdkClient`
   - Использование официального SDK вместо CRest

3. **Обновление сервисов:**
   - Обновление `Bitrix24ApiService`
   - Обновление `bootstrap.php`
   - Обновление `install.php`

4. **Тестирование:**
   - Проверка всех API вызовов
   - Проверка batch-запросов
   - Проверка работы с токенами

---

## Этап 1: Подготовка окружения

### 1.1. Настройка структуры проекта

**Создать структуру для Vue.js:**

```
APP-B24/
├── frontend/                      # Новый фронтенд на Vue.js
│   ├── src/
│   │   ├── main.js               # Точка входа Vue.js
│   │   ├── App.vue               # Корневой компонент
│   │   ├── components/            # Vue.js компоненты
│   │   │   ├── IndexPage.vue      # Главная страница
│   │   │   ├── AccessControlPage.vue # Управление правами
│   │   │   ├── TokenAnalysisPage.vue  # Анализ токена
│   │   │   └── common/            # Общие компоненты
│   │   │       ├── UserCard.vue
│   │   │       ├── DepartmentList.vue
│   │   │       └── UserList.vue
│   │   ├── services/             # Сервисы для API
│   │   │   ├── api.js            # Базовый API клиент
│   │   │   ├── bitrix24Api.js    # Bitrix24 API
│   │   │   └── authService.js     # Авторизация
│   │   ├── stores/               # Состояние (Pinia/Vuex)
│   │   │   ├── userStore.js
│   │   │   └── accessControlStore.js
│   │   ├── utils/                # Утилиты
│   │   │   ├── helpers.js
│   │   │   └── validators.js
│   │   └── assets/               # Статические ресурсы
│   │       ├── css/
│   │       └── images/
│   ├── public/                   # Публичные файлы
│   ├── package.json              # Зависимости npm
│   ├── vite.config.js            # Конфигурация Vite
│   └── .env                      # Переменные окружения
├── api/                          # API endpoints для Vue.js
│   ├── index.php                 # Точка входа API
│   ├── routes/
│   │   ├── user.php              # API для пользователей
│   │   ├── access-control.php    # API для управления правами
│   │   └── token-analysis.php     # API для анализа токена
│   └── middleware/
│       └── auth.php               # Middleware авторизации
└── ... (существующие файлы)
```

### 1.2. Установка зависимостей

**Создать `package.json`:**

```json
{
  "name": "bitrix24-app-frontend",
  "version": "3.0.0",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "vue": "^3.4.0",
    "pinia": "^2.1.0",
    "axios": "^1.6.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.0",
    "vite": "^5.0.0",
    "eslint": "^8.56.0",
    "prettier": "^3.1.0"
  }
}
```

**Установить зависимости:**
```bash
cd APP-B24/frontend
npm install
```

### 1.3. Настройка Vite

**Создать `vite.config.js`:**

```javascript
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  build: {
    outDir: '../public/dist',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
      },
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost',
        changeOrigin: true,
      },
    },
  },
});
```

---

## Этап 2: Создание API endpoints

### 2.1. Структура API

**Примечание:** API endpoints используют `Bitrix24SdkClient` (после миграции с CRest на b24phpsdk).

**Создать базовый API endpoint (`api/index.php`):**

```php
<?php
/**
 * REST API для Vue.js фронтенда
 * 
 * Все запросы проходят через этот файл
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Обработка preflight запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once(__DIR__ . '/../../src/bootstrap.php');

// Получение маршрута
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);
$segments = array_filter(explode('/', $path));
$segments = array_values($segments);

// Маршрутизация
$route = $segments[0] ?? 'index';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($route) {
        case 'user':
            require_once(__DIR__ . '/routes/user.php');
            break;
        case 'access-control':
            require_once(__DIR__ . '/routes/access-control.php');
            break;
        case 'token-analysis':
            require_once(__DIR__ . '/routes/token-analysis.php');
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Route not found']);
            break;
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $appEnv === 'development' ? $e->getTraceAsString() : null
    ]);
}
```

### 2.2. API для пользователей

**Создать `api/routes/user.php`:**

```php
<?php
/**
 * API для работы с пользователями
 */

use App\Services\UserService;
use App\Services\Bitrix24ApiService;
use App\Services\AuthService;

// Проверка авторизации
$authId = $_GET['AUTH_ID'] ?? $_POST['AUTH_ID'] ?? null;
$domain = $_GET['DOMAIN'] ?? $_POST['DOMAIN'] ?? null;

if (!$authId || !$domain) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Проверка авторизации через AuthService
if (!$authService->checkAuth(true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

switch ($method) {
    case 'GET':
        // Получение текущего пользователя
        $user = $userService->getCurrentUser($authId, $domain);
        $isAdmin = $userService->isAdmin($user, $authId, $domain);
        $departments = $userService->getUserDepartments($user);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'user' => $user,
                'isAdmin' => $isAdmin,
                'departments' => $departments
            ]
        ]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
```

### 2.3. API для управления правами доступа

**Создать `api/routes/access-control.php`:**

```php
<?php
/**
 * API для управления правами доступа
 */

// Проверка прав администратора
$user = $userService->getCurrentUser($authId, $domain);
if (!$userService->isAdmin($user, $authId, $domain)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Admin access required']);
    exit;
}

switch ($method) {
    case 'GET':
        // Получение конфигурации прав доступа
        $config = $configService->getAccessConfig();
        $allDepartments = $apiService->getAllDepartments($authId, $domain);
        $allUsers = $apiService->getAllUsers($authId, $domain);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'config' => $config,
                'allDepartments' => $allDepartments,
                'allUsers' => $allUsers
            ]
        ]);
        break;
        
    case 'POST':
        // Добавление отдела/пользователя
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? null;
        
        if ($action === 'add-department') {
            $result = $accessControlService->addDepartment(
                $data['departmentId'],
                $data['departmentName'],
                $user
            );
        } elseif ($action === 'add-user') {
            $result = $accessControlService->addUser(
                $data['userId'],
                $data['userName'],
                $data['userEmail'],
                $user
            );
        } elseif ($action === 'toggle-enabled') {
            $result = $accessControlService->toggleEnabled($data['enabled'], $user);
        }
        
        echo json_encode(['success' => true, 'data' => $result]);
        break;
        
    case 'DELETE':
        // Удаление отдела/пользователя
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? null;
        
        if ($action === 'remove-department') {
            $result = $accessControlService->removeDepartment($data['departmentId'], $user);
        } elseif ($action === 'remove-user') {
            $result = $accessControlService->removeUser($data['userId'], $user);
        }
        
        echo json_encode(['success' => true, 'data' => $result]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
```

### 2.4. API для анализа токена

**Создать `api/routes/token-analysis.php`:**

```php
<?php
/**
 * API для анализа токена
 */

// Проверка прав администратора
$user = $userService->getCurrentUser($authId, $domain);
if (!$userService->isAdmin($user, $authId, $domain)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Admin access required']);
    exit;
}

switch ($method) {
    case 'GET':
        // Анализ токена
        $analysis = $tokenAnalysisController->analyze($authId, $domain);
        
        echo json_encode([
            'success' => true,
            'data' => $analysis
        ]);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
```

---

## Этап 3: Создание Vue.js компонентов

### 3.1. Настройка Vue.js приложения

**Создать `frontend/src/main.js`:**

```javascript
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

app.mount('#app');
```

**Создать `frontend/src/App.vue`:**

```vue
<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useUserStore } from './stores/userStore';

const userStore = useUserStore();

onMounted(() => {
  // Инициализация приложения
  const urlParams = new URLSearchParams(window.location.search);
  const authId = urlParams.get('AUTH_ID');
  const domain = urlParams.get('DOMAIN');
  
  if (authId && domain) {
    userStore.setAuthParams(authId, domain);
    userStore.fetchCurrentUser();
  }
});
</script>

<style>
/* Глобальные стили */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
```

### 3.2. Компонент главной страницы

**Создать `frontend/src/components/IndexPage.vue`:**

```vue
<template>
  <div class="index-page">
    <div v-if="loading" class="loading">Загрузка...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    <div v-else class="welcome-container">
      <h1>Добро пожаловать!</h1>
      <UserCard :user="user" :isAdmin="isAdmin" />
      <div v-if="isAdmin" class="admin-actions">
        <router-link to="/access-control">Управление правами</router-link>
        <router-link to="/token-analysis">Анализ токена</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useUserStore } from '../stores/userStore';
import UserCard from './common/UserCard.vue';

const userStore = useUserStore();
const loading = ref(true);
const error = ref(null);

const user = computed(() => userStore.currentUser);
const isAdmin = computed(() => userStore.isAdmin);

onMounted(async () => {
  try {
    await userStore.fetchCurrentUser();
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
});
</script>
```

### 3.3. Компонент управления правами

**Создать `frontend/src/components/AccessControlPage.vue`:**

```vue
<template>
  <div class="access-control-page">
    <h1>Управление правами доступа</h1>
    
    <div class="toggle-section">
      <label>
        <input 
          type="checkbox" 
          v-model="config.enabled"
          @change="toggleEnabled"
        />
        Включить проверку прав доступа
      </label>
    </div>
    
    <DepartmentList 
      :departments="config.departments"
      :allDepartments="allDepartments"
      @add="addDepartment"
      @remove="removeDepartment"
    />
    
    <UserList 
      :users="config.users"
      :allUsers="allUsers"
      @add="addUser"
      @remove="removeUser"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAccessControlStore } from '../stores/accessControlStore';
import DepartmentList from './common/DepartmentList.vue';
import UserList from './common/UserList.vue';

const accessControlStore = useAccessControlStore();
const config = ref({});
const allDepartments = ref([]);
const allUsers = ref([]);

onMounted(async () => {
  await accessControlStore.fetchConfig();
  config.value = accessControlStore.config;
  allDepartments.value = accessControlStore.allDepartments;
  allUsers.value = accessControlStore.allUsers;
});

const toggleEnabled = async () => {
  await accessControlStore.toggleEnabled(config.value.enabled);
};

const addDepartment = async (departmentId, departmentName) => {
  await accessControlStore.addDepartment(departmentId, departmentName);
  config.value = accessControlStore.config;
};

const removeDepartment = async (departmentId) => {
  await accessControlStore.removeDepartment(departmentId);
  config.value = accessControlStore.config;
};

const addUser = async (userId, userName, userEmail) => {
  await accessControlStore.addUser(userId, userName, userEmail);
  config.value = accessControlStore.config;
};

const removeUser = async (userId) => {
  await accessControlStore.removeUser(userId);
  config.value = accessControlStore.config;
};
</script>
```

### 3.4. Сервисы для API

**Создать `frontend/src/services/api.js`:**

```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
  },
});

// Добавление параметров авторизации к каждому запросу
api.interceptors.request.use((config) => {
  const urlParams = new URLSearchParams(window.location.search);
  const authId = urlParams.get('AUTH_ID');
  const domain = urlParams.get('DOMAIN');
  
  if (authId && domain) {
    config.params = {
      ...config.params,
      AUTH_ID: authId,
      DOMAIN: domain,
    };
  }
  
  return config;
});

export default api;
```

**Создать `frontend/src/services/bitrix24Api.js`:**

```javascript
import api from './api';

export const userApi = {
  getCurrentUser: () => api.get('/user'),
};

export const accessControlApi = {
  getConfig: () => api.get('/access-control'),
  addDepartment: (departmentId, departmentName) => 
    api.post('/access-control', {
      action: 'add-department',
      departmentId,
      departmentName,
    }),
  removeDepartment: (departmentId) =>
    api.delete('/access-control', {
      data: { action: 'remove-department', departmentId },
    }),
  addUser: (userId, userName, userEmail) =>
    api.post('/access-control', {
      action: 'add-user',
      userId,
      userName,
      userEmail,
    }),
  removeUser: (userId) =>
    api.delete('/access-control', {
      data: { action: 'remove-user', userId },
    }),
  toggleEnabled: (enabled) =>
    api.post('/access-control', {
      action: 'toggle-enabled',
      enabled,
    }),
};

export const tokenAnalysisApi = {
  analyze: () => api.get('/token-analysis'),
};
```

---

## Этап 4: Интеграция с Bitrix24

### 4.1. Интеграция с BX.* API

**Создать `frontend/src/utils/bitrix24.js`:**

```javascript
/**
 * Утилиты для работы с Bitrix24 BX.* API
 */

export const useBitrix24 = () => {
  const isBitrix24 = typeof BX !== 'undefined';
  
  const showNotification = (message, type = 'info') => {
    if (isBitrix24 && BX.UI && BX.UI.Notification) {
      BX.UI.Notification.Center.notify({
        content: message,
        autoHideDelay: 5000,
      });
    } else {
      alert(message);
    }
  };
  
  const showPopup = (content, options = {}) => {
    if (isBitrix24 && BX.PopupWindow) {
      return new BX.PopupWindow('custom-popup', null, {
        content,
        width: options.width || 600,
        height: options.height || 400,
        ...options,
      });
    }
  };
  
  const ajax = (url, data = {}) => {
    if (isBitrix24 && BX.ajax) {
      return new Promise((resolve, reject) => {
        BX.ajax({
          url,
          method: 'POST',
          dataType: 'json',
          data,
          onsuccess: (response) => {
            if (response.error) {
              reject(new Error(response.error_description || response.error));
            } else {
              resolve(response);
            }
          },
          onfailure: () => {
            reject(new Error('Request failed'));
          },
        });
      });
    } else {
      // Fallback на fetch
      return fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      }).then((res) => res.json());
    }
  };
  
  return {
    isBitrix24,
    showNotification,
    showPopup,
    ajax,
  };
};
```

---

## Этап 5: Сборка и деплой

### 5.1. Настройка production сборки

**Обновить `vite.config.js` для production:**

```javascript
export default defineConfig({
  // ... существующая конфигурация
  build: {
    outDir: '../public/dist',
    emptyOutDir: true,
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
      },
    },
    rollupOptions: {
      output: {
        manualChunks: {
          'vue-vendor': ['vue', 'pinia'],
        },
      },
    },
  },
});
```

### 5.2. Сборка для production

```bash
cd APP-B24/frontend
npm run build
```

### 5.3. Интеграция с PHP

**Обновить `index.php` для загрузки Vue.js приложения:**

```php
<?php
// ... существующий код проверки авторизации

// Если запрос из Bitrix24 - показываем Vue.js приложение
if ($isFromBitrix24) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bitrix24 Приложение</title>
        <script type="module" src="/public/dist/assets/main.js"></script>
        <link rel="stylesheet" href="/public/dist/assets/main.css">
    </head>
    <body>
        <div id="app"></div>
    </body>
    </html>
    <?php
    exit;
}

// Fallback на старую версию (для обратной совместимости)
// ... существующий код
```

---

## Чек-лист миграции

### Этап 1: Подготовка
- [ ] Создана структура `frontend/`
- [ ] Установлены зависимости (npm install)
- [ ] Настроен Vite
- [ ] Настроен ESLint и Prettier

### Этап 2: API
- [ ] Создан базовый API endpoint
- [ ] Реализован API для пользователей
- [ ] Реализован API для управления правами
- [ ] Реализован API для анализа токена
- [ ] Протестированы все API endpoints

### Этап 3: Vue.js компоненты
- [ ] Создано Vue.js приложение
- [ ] Реализован компонент главной страницы
- [ ] Реализован компонент управления правами
- [ ] Реализован компонент анализа токена
- [ ] Созданы общие компоненты (UserCard, DepartmentList, UserList)
- [ ] Настроен роутинг (Vue Router)

### Этап 4: Интеграция
- [ ] Интегрирован с Bitrix24 BX.* API
- [ ] Настроены уведомления через Bitrix24 UI
- [ ] Протестирована работа внутри Bitrix24 iframe

### Этап 5: Деплой
- [ ] Настроена production сборка
- [ ] Собран фронтенд (npm run build)
- [ ] Интегрирован с PHP (обновлен index.php)
- [ ] Протестировано на production сервере

---

## История правок

- **2025-12-20 19:38 (UTC+3, Брест):** Создан план миграции на Vue.js

---

**Версия документа:** 1.0  
**Последнее обновление:** 2025-12-20 19:38 (UTC+3, Брест)

