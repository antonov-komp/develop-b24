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
      output: {
        // Именование файлов для кеширования
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]',
      },
    },
    // Минификация для production
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true, // Удаление console.log в production
      },
    },
    // Размер предупреждений
    chunkSizeWarningLimit: 1000,
  },
  server: {
    port: 5173,
    host: '0.0.0.0', // Доступ с других устройств
    proxy: {
      '/api': {
        target: 'http://localhost',
        changeOrigin: true,
        secure: false,
        rewrite: (path) => path.replace(/^\/api/, '/APP-B24/api'),
      },
    },
    // Hot Module Replacement
    hmr: {
      overlay: true,
    },
  },
  // Оптимизация зависимостей
  optimizeDeps: {
    include: ['vue', 'pinia', 'axios'],
  },
});
```

**Детали конфигурации:**

1. **Alias `@`:** Позволяет использовать `@/components/` вместо `../../components/`
2. **Output naming:** Хеши в именах файлов для кеширования
3. **Terser:** Минификация с удалением console.log в production
4. **Proxy:** Проксирование API запросов на PHP backend
5. **HMR:** Hot Module Replacement для быстрой разработки

**Создать `frontend/index.html` (точка входа):**

```html
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitrix24 Приложение</title>
</head>
<body>
    <div id="app"></div>
    <script type="module" src="/src/main.js"></script>
</body>
</html>
```

**Важно:** В production этот файл будет собран в `public/dist/index.html`

---

## Этап 2: Создание API endpoints

### 2.1. Структура API

**Примечание:** API endpoints используют `Bitrix24SdkClient` (после миграции с CRest на b24phpsdk).

**Важные детали:**
- Все API endpoints должны быть защищены авторизацией
- Параметры `AUTH_ID` и `DOMAIN` обязательны для всех запросов
- Формат ответа: `{ success: true, data: {...} }` или `{ error: 'message' }`
- HTTP статус коды: 200 (успех), 401 (не авторизован), 403 (запрещено), 404 (не найдено), 500 (ошибка сервера)

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

// Получение окружения
$appEnv = getenv('APP_ENV') ?: 'production';

// Получение маршрута
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Удаляем префикс /APP-B24/api если есть
$path = preg_replace('#^/APP-B24/api#', '', $path);
$path = preg_replace('#^/api#', '', $path);
$segments = array_filter(explode('/', trim($path, '/')));
$segments = array_values($segments);

// Маршрутизация
$route = $segments[0] ?? 'index';
$method = $_SERVER['REQUEST_METHOD'];

// Логирование запроса (для отладки)
if ($appEnv === 'development') {
    error_log("API Request: {$method} /api/{$route}");
}

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
    
    // Логирование ошибки
    error_log("API Error: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'error' => $appEnv === 'development' ? $e->getMessage() : 'Internal server error',
        'trace' => $appEnv === 'development' ? $e->getTraceAsString() : null,
        'file' => $appEnv === 'development' ? $e->getFile() : null,
        'line' => $appEnv === 'development' ? $e->getLine() : null
    ]);
}
```

**Детали обработки ошибок:**
- В development режиме показываем полную информацию об ошибке
- В production режиме показываем только общее сообщение
- Все ошибки логируются для диагностики

### 2.2. API для пользователей

**Создать `api/routes/user.php`:**

```php
<?php
/**
 * API для работы с пользователями
 * 
 * Endpoint: GET /api/user
 * Параметры: AUTH_ID, DOMAIN (query или POST)
 * 
 * Документация: https://context7.com/bitrix24/rest/user.current
 */

use App\Services\UserService;
use App\Services\Bitrix24ApiService;
use App\Services\AuthService;

// Получение параметров авторизации из разных источников
$authId = $_GET['AUTH_ID'] 
    ?? $_POST['AUTH_ID'] 
    ?? json_decode(file_get_contents('php://input'), true)['AUTH_ID'] 
    ?? null;
    
$domain = $_GET['DOMAIN'] 
    ?? $_POST['DOMAIN'] 
    ?? json_decode(file_get_contents('php://input'), true)['DOMAIN'] 
    ?? null;

// Валидация параметров
if (!$authId || !$domain) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Unauthorized',
        'message' => 'AUTH_ID and DOMAIN are required'
    ]);
    exit;
}

// Проверка формата токена (базовая валидация)
if (strlen($authId) < 10) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Invalid token format',
        'message' => 'AUTH_ID is too short'
    ]);
    exit;
}

// Проверка авторизации через AuthService
try {
    if (!$authService->checkAuth(true)) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Forbidden',
            'message' => 'Invalid authorization'
        ]);
        exit;
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Authorization check failed',
        'message' => $e->getMessage()
    ]);
    exit;
}

switch ($method) {
    case 'GET':
        try {
            // Получение текущего пользователя
            $user = $userService->getCurrentUser($authId, $domain);
            
            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'User not found',
                    'message' => 'Unable to get current user'
                ]);
                exit;
            }
            
            $isAdmin = $userService->isAdmin($user, $authId, $domain);
            $departments = $userService->getUserDepartments($user);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'isAdmin' => $isAdmin,
                    'departments' => $departments
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to get user',
                'message' => $e->getMessage()
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'error' => 'Method not allowed',
            'message' => "Method {$method} is not supported for this endpoint"
        ]);
        break;
}
```

**Детали реализации:**
- Обработка ошибок на каждом этапе
- Валидация данных перед использованием
- JSON_UNESCAPED_UNICODE для корректного отображения кириллицы
- JSON_PRETTY_PRINT для читаемости (можно убрать в production)

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

// Создание приложения
const app = createApp(App);

// Настройка Pinia (управление состоянием)
const pinia = createPinia();
app.use(pinia);

// Настройка роутера
app.use(router);

// Глобальная обработка ошибок
app.config.errorHandler = (err, instance, info) => {
  console.error('Vue error:', err, info);
  
  // Логирование ошибки (можно отправить на сервер)
  if (typeof BX !== 'undefined' && BX.ajax) {
    BX.ajax({
      url: '/api/log-error',
      method: 'POST',
      data: {
        error: err.message,
        info: info,
        component: instance?.$options?.name || 'unknown'
      }
    });
  }
};

// Монтирование приложения
app.mount('#app');
```

**Детали настройки:**
- `errorHandler` — глобальная обработка ошибок Vue.js
- Логирование ошибок на сервер для диагностики
- Интеграция с Bitrix24 BX.ajax для отправки логов

**Создать `frontend/src/router/index.js`:**

```javascript
import { createRouter, createWebHistory } from 'vue-router';
import IndexPage from '../components/IndexPage.vue';
import AccessControlPage from '../components/AccessControlPage.vue';
import TokenAnalysisPage from '../components/TokenAnalysisPage.vue';

const routes = [
  {
    path: '/',
    name: 'index',
    component: IndexPage,
    meta: {
      title: 'Главная страница',
      requiresAuth: true
    }
  },
  {
    path: '/access-control',
    name: 'access-control',
    component: AccessControlPage,
    meta: {
      title: 'Управление правами доступа',
      requiresAuth: true,
      requiresAdmin: true
    }
  },
  {
    path: '/token-analysis',
    name: 'token-analysis',
    component: TokenAnalysisPage,
    meta: {
      title: 'Анализ токена',
      requiresAuth: true,
      requiresAdmin: true
    }
  }
];

const router = createRouter({
  history: createWebHistory('/APP-B24/'), // Базовый путь для приложения
  routes
});

// Навигационные хуки
router.beforeEach((to, from, next) => {
  // Проверка авторизации
  if (to.meta.requiresAuth) {
    const urlParams = new URLSearchParams(window.location.search);
    const authId = urlParams.get('AUTH_ID');
    const domain = urlParams.get('DOMAIN');
    
    if (!authId || !domain) {
      // Редирект на страницу ошибки или главную
      next({ name: 'index' });
      return;
    }
  }
  
  // Проверка прав администратора
  if (to.meta.requiresAdmin) {
    // Проверка через store (будет реализовано в stores)
    // Пока пропускаем
  }
  
  next();
});

export default router;
```

**Детали роутера:**
- `createWebHistory` — HTML5 history mode (чистые URL)
- Базовый путь `/APP-B24/` для работы внутри Bitrix24
- Навигационные хуки для проверки авторизации
- Мета-данные маршрутов для контроля доступа

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
import { ref, computed, onMounted } from 'vue';
import { useUserStore } from '../stores/userStore';
import UserCard from './common/UserCard.vue';
import { useBitrix24 } from '../utils/bitrix24';

const userStore = useUserStore();
const { showNotification } = useBitrix24();

// Состояние компонента
const loading = ref(true);
const error = ref(null);

// Computed свойства из store
const user = computed(() => userStore.currentUser);
const isAdmin = computed(() => userStore.isAdmin);
const loadingState = computed(() => userStore.loading);

// Инициализация параметров авторизации из URL
onMounted(async () => {
  try {
    // Получение параметров из URL
    const urlParams = new URLSearchParams(window.location.search);
    const authId = urlParams.get('AUTH_ID');
    const domain = urlParams.get('DOMAIN');
    
    if (!authId || !domain) {
      throw new Error('AUTH_ID и DOMAIN обязательны для работы приложения');
    }
    
    // Установка параметров в store
    userStore.setAuthParams(authId, domain);
    
    // Загрузка данных пользователя
    await userStore.fetchCurrentUser();
    
    // Уведомление об успешной загрузке (опционально)
    if (import.meta.env.DEV) {
      console.log('User loaded:', user.value);
    }
  } catch (e) {
    error.value = e.message;
    showNotification('Ошибка загрузки данных: ' + e.message, 'error');
    console.error('Error in IndexPage:', e);
  } finally {
    loading.value = false;
  }
});
</script>
```

**Детали реализации компонента:**
- Использование `computed` для реактивных данных из store
- Обработка ошибок с уведомлениями
- Валидация параметров авторизации
- Логирование в development режиме
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

### 3.4. Pinia Stores (управление состоянием)

**Создать `frontend/src/stores/userStore.js`:**

```javascript
import { defineStore } from 'pinia';
import { userApi } from '../services/bitrix24Api';

/**
 * Store для управления данными пользователя
 * 
 * Использует Pinia для централизованного управления состоянием
 */
export const useUserStore = defineStore('user', {
  state: () => ({
    currentUser: null,
    isAdmin: false,
    departments: [],
    authId: null,
    domain: null,
    loading: false,
    error: null
  }),
  
  getters: {
    /**
     * Полное имя пользователя
     */
    fullName: (state) => {
      if (!state.currentUser) return '';
      const parts = [
        state.currentUser.LAST_NAME,
        state.currentUser.NAME,
        state.currentUser.SECOND_NAME
      ].filter(Boolean);
      return parts.join(' ') || state.currentUser.NAME || 'Не указано';
    },
    
    /**
     * Email пользователя
     */
    email: (state) => {
      return state.currentUser?.EMAIL || '';
    },
    
    /**
     * Фото пользователя
     */
    photo: (state) => {
      return state.currentUser?.PERSONAL_PHOTO || null;
    }
  },
  
  actions: {
    /**
     * Установка параметров авторизации
     * 
     * @param {string} authId Токен авторизации
     * @param {string} domain Домен портала
     */
    setAuthParams(authId, domain) {
      this.authId = authId;
      this.domain = domain;
    },
    
    /**
     * Загрузка данных текущего пользователя
     * 
     * @throws {Error} При ошибке загрузки
     */
    async fetchCurrentUser() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await userApi.getCurrentUser();
        
        if (response.success && response.data) {
          this.currentUser = response.data.user;
          this.isAdmin = response.data.isAdmin || false;
          this.departments = response.data.departments || [];
        } else {
          throw new Error(response.error || 'Failed to fetch user');
        }
      } catch (error) {
        this.error = error.message;
        console.error('Error fetching user:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    /**
     * Очистка данных пользователя
     */
    clearUser() {
      this.currentUser = null;
      this.isAdmin = false;
      this.departments = [];
      this.error = null;
    }
  }
});
```

**Создать `frontend/src/stores/accessControlStore.js`:**

```javascript
import { defineStore } from 'pinia';
import { accessControlApi } from '../services/bitrix24Api';

/**
 * Store для управления правами доступа
 * 
 * Требует прав администратора
 */
export const useAccessControlStore = defineStore('accessControl', {
  state: () => ({
    config: {
      enabled: false,
      departments: [],
      users: []
    },
    allDepartments: [],
    allUsers: [],
    loading: false,
    error: null
  }),
  
  actions: {
    /**
     * Загрузка конфигурации прав доступа
     */
    async fetchConfig() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await accessControlApi.getConfig();
        
        if (response.success && response.data) {
          this.config = response.data.config?.access_control || this.config;
          this.allDepartments = response.data.allDepartments || [];
          this.allUsers = response.data.allUsers || [];
        } else {
          throw new Error(response.error || 'Failed to fetch config');
        }
      } catch (error) {
        this.error = error.message;
        console.error('Error fetching access control config:', error);
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    /**
     * Включение/выключение проверки прав доступа
     * 
     * @param {boolean} enabled Включить проверку
     */
    async toggleEnabled(enabled) {
      try {
        const response = await accessControlApi.toggleEnabled(enabled);
        
        if (response.success) {
          this.config.enabled = enabled;
        } else {
          throw new Error(response.error || 'Failed to toggle enabled');
        }
      } catch (error) {
        this.error = error.message;
        throw error;
      }
    },
    
    /**
     * Добавление отдела
     * 
     * @param {number} departmentId ID отдела
     * @param {string} departmentName Название отдела
     */
    async addDepartment(departmentId, departmentName) {
      try {
        const response = await accessControlApi.addDepartment(departmentId, departmentName);
        
        if (response.success) {
          // Обновляем конфигурацию
          await this.fetchConfig();
        } else {
          throw new Error(response.error || 'Failed to add department');
        }
      } catch (error) {
        this.error = error.message;
        throw error;
      }
    },
    
    /**
     * Удаление отдела
     * 
     * @param {number} departmentId ID отдела
     */
    async removeDepartment(departmentId) {
      try {
        const response = await accessControlApi.removeDepartment(departmentId);
        
        if (response.success) {
          // Обновляем конфигурацию
          await this.fetchConfig();
        } else {
          throw new Error(response.error || 'Failed to remove department');
        }
      } catch (error) {
        this.error = error.message;
        throw error;
      }
    },
    
    /**
     * Добавление пользователя
     * 
     * @param {number} userId ID пользователя
     * @param {string} userName Имя пользователя
     * @param {string} userEmail Email пользователя
     */
    async addUser(userId, userName, userEmail) {
      try {
        const response = await accessControlApi.addUser(userId, userName, userEmail);
        
        if (response.success) {
          await this.fetchConfig();
        } else {
          throw new Error(response.error || 'Failed to add user');
        }
      } catch (error) {
        this.error = error.message;
        throw error;
      }
    },
    
    /**
     * Удаление пользователя
     * 
     * @param {number} userId ID пользователя
     */
    async removeUser(userId) {
      try {
        const response = await accessControlApi.removeUser(userId);
        
        if (response.success) {
          await this.fetchConfig();
        } else {
          throw new Error(response.error || 'Failed to remove user');
        }
      } catch (error) {
        this.error = error.message;
        throw error;
      }
    }
  }
});
```

**Детали реализации stores:**
- Использование Pinia для централизованного состояния
- Getters для вычисляемых значений
- Actions для асинхронных операций
- Обработка ошибок в каждом action
- Автоматическое обновление конфигурации после изменений

### 3.5. Сервисы для API

**Создать `frontend/src/services/api.js`:**

```javascript
import axios from 'axios';

// Создание экземпляра axios с базовой конфигурацией
const api = axios.create({
  baseURL: '/APP-B24/api', // Базовый URL для API
  timeout: 30000, // Таймаут 30 секунд
  headers: {
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  },
});

// Interceptor для добавления параметров авторизации
api.interceptors.request.use((config) => {
  // Получение параметров из URL (для работы внутри Bitrix24)
  const urlParams = new URLSearchParams(window.location.search);
  const authId = urlParams.get('AUTH_ID');
  const domain = urlParams.get('DOMAIN');
  
  // Добавление параметров к запросу
  if (authId && domain) {
    config.params = {
      ...config.params,
      AUTH_ID: authId,
      DOMAIN: domain,
    };
  }
  
  // Логирование запроса (только в development)
  if (import.meta.env.DEV) {
    console.log('API Request:', config.method?.toUpperCase(), config.url, config.params);
  }
  
  return config;
}, (error) => {
  // Обработка ошибок запроса
  console.error('API Request Error:', error);
  return Promise.reject(error);
});

// Interceptor для обработки ответов
api.interceptors.response.use(
  (response) => {
    // Успешный ответ
    if (import.meta.env.DEV) {
      console.log('API Response:', response.config.url, response.data);
    }
    return response;
  },
  (error) => {
    // Обработка ошибок ответа
    console.error('API Response Error:', error);
    
    // Обработка различных типов ошибок
    if (error.response) {
      // Сервер вернул ошибку
      const status = error.response.status;
      const data = error.response.data;
      
      switch (status) {
        case 401:
          console.error('Unauthorized - проверьте AUTH_ID и DOMAIN');
          break;
        case 403:
          console.error('Forbidden - недостаточно прав доступа');
          break;
        case 404:
          console.error('Not Found - endpoint не найден');
          break;
        case 500:
          console.error('Server Error - ошибка на сервере');
          break;
      }
      
      // Можно показать уведомление через Bitrix24 UI
      if (typeof BX !== 'undefined' && BX.UI && BX.UI.Notification) {
        BX.UI.Notification.Center.notify({
          content: data.error || 'Произошла ошибка',
          autoHideDelay: 5000
        });
      }
    } else if (error.request) {
      // Запрос был отправлен, но ответа не получено
      console.error('Network Error - нет ответа от сервера');
    } else {
      // Ошибка при настройке запроса
      console.error('Request Setup Error:', error.message);
    }
    
    return Promise.reject(error);
  }
);

export default api;
```

**Детали реализации:**
- Таймаут 30 секунд для долгих запросов
- Автоматическое добавление параметров авторизации
- Логирование в development режиме
- Обработка различных типов ошибок
- Интеграция с Bitrix24 UI для уведомлений

**Создать `frontend/src/services/bitrix24Api.js`:**

```javascript
import api from './api';

/**
 * API для работы с пользователями
 * 
 * Endpoint: GET /api/user
 * Документация: https://context7.com/bitrix24/rest/user.current
 */
export const userApi = {
  /**
   * Получение текущего пользователя
   * 
   * @returns {Promise<Object>} Данные пользователя
   * @throws {Error} При ошибке запроса
   */
  getCurrentUser: async () => {
    const response = await api.get('/user');
    return response.data;
  }
};

/**
 * API для управления правами доступа
 * 
 * Endpoint: /api/access-control
 * Требует прав администратора
 */
export const accessControlApi = {
  /**
   * Получение конфигурации прав доступа
   */
  getConfig: async () => {
    const response = await api.get('/access-control');
    return response.data;
  },
  
  /**
   * Добавление отдела
   * 
   * @param {number} departmentId ID отдела
   * @param {string} departmentName Название отдела
   */
  addDepartment: async (departmentId, departmentName) => {
    const response = await api.post('/access-control', {
      action: 'add-department',
      departmentId,
      departmentName,
    });
    return response.data;
  },
  
  /**
   * Удаление отдела
   * 
   * @param {number} departmentId ID отдела
   */
  removeDepartment: async (departmentId) => {
    const response = await api.delete('/access-control', {
      data: { action: 'remove-department', departmentId },
    });
    return response.data;
  },
  
  /**
   * Добавление пользователя
   * 
   * @param {number} userId ID пользователя
   * @param {string} userName Имя пользователя
   * @param {string} userEmail Email пользователя
   */
  addUser: async (userId, userName, userEmail) => {
    const response = await api.post('/access-control', {
      action: 'add-user',
      userId,
      userName,
      userEmail,
    });
    return response.data;
  },
  
  /**
   * Удаление пользователя
   * 
   * @param {number} userId ID пользователя
   */
  removeUser: async (userId) => {
    const response = await api.delete('/access-control', {
      data: { action: 'remove-user', userId },
    });
    return response.data;
  },
  
  /**
   * Включение/выключение проверки прав доступа
   * 
   * @param {boolean} enabled Включить проверку
   */
  toggleEnabled: async (enabled) => {
    const response = await api.post('/access-control', {
      action: 'toggle-enabled',
      enabled,
    });
    return response.data;
  }
};

/**
 * API для анализа токена
 * 
 * Endpoint: GET /api/token-analysis
 * Требует прав администратора
 */
export const tokenAnalysisApi = {
  /**
   * Анализ токена и прав доступа
   * 
   * @returns {Promise<Object>} Результат анализа
   */
  analyze: async () => {
    const response = await api.get('/token-analysis');
    return response.data;
  }
};
```

**Детали реализации:**
- JSDoc комментарии для автокомплита
- Async/await для удобной работы с промисами
- Единый формат ответов через api interceptor
- Обработка ошибок на уровне api.js

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

## Этап 5: Настройка сервера и окружения

### 5.1. Настройка .htaccess для API

**Создать или обновить `APP-B24/api/.htaccess`:**

```apache
# Включение mod_rewrite
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Редирект всех запросов на index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>

# Защита конфигурационных файлов
<FilesMatch "\.(json|php)$">
    # Разрешаем доступ только к index.php и routes
    <If "%{REQUEST_URI} =~ m#/api/(index\.php|routes/)#">
        Require all granted
    </If>
    <Else>
        Require all denied
    </Else>
</FilesMatch>

# Настройки для CORS (если нужно)
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"
</IfModule>

# Настройки PHP
<IfModule mod_php.c>
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value max_execution_time 300
    php_value memory_limit 256M
</IfModule>
```

**Для Nginx (если используется):**

```nginx
location /APP-B24/api/ {
    try_files $uri $uri/ /APP-B24/api/index.php?$query_string;
    
    # CORS headers
    add_header Access-Control-Allow-Origin *;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
    add_header Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With";
    
    # PHP обработка
    fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### 5.2. Настройка переменных окружения

**Создать `APP-B24/frontend/.env` (для development):**

```env
# Режим разработки
VITE_APP_ENV=development

# Базовый URL для API
VITE_API_BASE_URL=/APP-B24/api

# Включить отладку
VITE_DEBUG=true
```

**Создать `APP-B24/frontend/.env.production` (для production):**

```env
# Режим продакшена
VITE_APP_ENV=production

# Базовый URL для API
VITE_API_BASE_URL=/APP-B24/api

# Отключить отладку
VITE_DEBUG=false
```

**Использование в коде:**

```javascript
// frontend/src/services/api.js
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/APP-B24/api',
  // ...
});

// Проверка режима
if (import.meta.env.VITE_APP_ENV === 'development') {
  console.log('Development mode');
}
```

### 5.3. Настройка production сборки

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

**Команда сборки:**
```bash
cd APP-B24/frontend
npm run build
```

**Детали сборки:**
- Создается директория `APP-B24/public/dist/`
- JavaScript файлы минифицируются и хешируются
- CSS файлы минифицируются и хешируются
- Создается `index.html` с правильными путями к ресурсам

**Проверка сборки:**
```bash
# Проверка созданных файлов
ls -la APP-B24/public/dist/
ls -la APP-B24/public/dist/assets/

# Проверка размера файлов
du -sh APP-B24/public/dist/assets/*.js
du -sh APP-B24/public/dist/assets/*.css

# Проверка с gzip
gzip -c APP-B24/public/dist/assets/main-*.js | wc -c
```

**Оптимизация для production:**
- Убедиться, что `drop_console: true` в vite.config.js
- Проверить, что tree-shaking работает
- Проверить размер бандла (должен быть < 100 KB gzipped)

**Проблемы при сборке:**
- Если сборка падает с ошибкой - проверить версии зависимостей
- Если файлы не создаются - проверить права доступа к директории
- Если пути неправильные - проверить `base` в vite.config.js

### 5.4. Интеграция с PHP

**Обновить `index.php` для загрузки Vue.js приложения:**

```php
<?php
/**
 * Главная страница приложения Bitrix24
 * 
 * После миграции на Vue.js загружает SPA приложение
 * Fallback на старую версию для обратной совместимости
 */

// Определение окружения
$appEnv = getenv('APP_ENV') ?: 'production';

// Условное включение отладочных настроек
if ($appEnv === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php-errors.log');
}

try {
    // Подключение и инициализация сервисов
    require_once(__DIR__ . '/src/bootstrap.php');
    
    // Проверка авторизации через AuthService
    $isFromBitrix24 = $authService->isRequestFromBitrix24();
    $isAuthorized = $authService->checkAuth($isFromBitrix24);
    
    // Если запрос из Bitrix24 и авторизован - показываем Vue.js приложение
    if ($isFromBitrix24 && $isAuthorized) {
        // Проверка существования собранных файлов
        $distDir = __DIR__ . '/public/dist';
        $indexFile = $distDir . '/index.html';
        
        if (file_exists($indexFile)) {
            // Загружаем собранное Vue.js приложение
            $indexContent = file_get_contents($indexFile);
            
            // Заменяем пути к ресурсам на правильные
            // Vite добавляет /assets/, но нам нужно /APP-B24/public/dist/assets/
            $indexContent = str_replace(
                '/assets/',
                '/APP-B24/public/dist/assets/',
                $indexContent
            );
            
            // Заменяем базовый путь для правильной работы роутера
            $indexContent = str_replace(
                '<base href="/">',
                '<base href="/APP-B24/">',
                $indexContent
            );
            
            echo $indexContent;
            exit;
        } else {
            // Если файлы не собраны - показываем сообщение или fallback
            if ($appEnv === 'development') {
                echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Vue.js не собран</title></head><body>';
                echo '<h1>Vue.js приложение не собрано</h1>';
                echo '<p>Выполните: <code>cd frontend && npm run build</code></p>';
                echo '<p>Или запустите dev сервер: <code>npm run dev</code></p>';
                echo '</body></html>';
                exit;
            }
            // В production - fallback на старую версию
        }
    }
    
    // Fallback на старую версию (PHP шаблоны)
    // Это обеспечивает обратную совместимость
    require_once(__DIR__ . '/src/Controllers/BaseController.php');
    require_once(__DIR__ . '/src/Controllers/IndexController.php');
    
    $controller = new App\Controllers\IndexController(
        $logger,
        $configService,
        $apiService,
        $userService,
        $accessControlService,
        $authService,
        $domainResolver,
        $adminChecker
    );
    
    $controller->index();
    
} catch (\Throwable $e) {
    error_log('Fatal error in index.php: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    http_response_code(500);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ошибка</title></head><body>';
    echo '<h1>Ошибка приложения</h1>';
    echo '<p>Произошла ошибка при загрузке страницы.</p>';
    if (ini_get('display_errors')) {
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    echo '</body></html>';
    exit;
}
```

**Детали интеграции:**
- Проверка существования собранных файлов
- Замена путей к ресурсам для правильной загрузки
- Fallback на старую версию при отсутствии сборки
- Сообщение в development режиме, если файлы не собраны
- Обработка ошибок с логированием

**Важно для production:**
- Файлы должны быть собраны: `npm run build`
- Пути к ресурсам должны быть правильными
- Базовый путь должен соответствовать структуре сервера

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

## Troubleshooting (Решение проблем)

### Проблемы с Vite

**Проблема 1: Ошибка "Cannot find module"**
```
Error: Cannot find module '@vitejs/plugin-vue'
```
**Решение:**
```bash
cd APP-B24/frontend
npm install
# Или
yarn install
```

**Проблема 2: Порт 5173 уже занят**
```
Error: Port 5173 is in use
```
**Решение:**
```javascript
// В vite.config.js изменить порт
server: {
  port: 5174, // Или другой свободный порт
}
```

**Проблема 3: HMR не работает**
**Решение:**
- Проверить настройки proxy в vite.config.js
- Убедиться, что сервер запущен на правильном хосте
- Проверить настройки firewall

### Проблемы с API endpoints

**Проблема 1: 404 Not Found при запросах к /api/**
**Решение:**
- Проверить настройки .htaccess (если используется Apache)
- Проверить настройки nginx (если используется Nginx)
- Убедиться, что путь к API правильный: `/APP-B24/api/` или `/api/`

**Проблема 2: CORS ошибки**
```
Access to fetch at 'http://localhost/api/user' from origin 'http://localhost:5173' has been blocked by CORS policy
```
**Решение:**
Добавить в `api/index.php`:
```php
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
```

**Проблема 3: Ошибка "AUTH_ID and DOMAIN are required"**
**Решение:**
- Проверить, что параметры передаются в запросе
- Проверить формат запроса (GET/POST)
- Проверить, что параметры не фильтруются сервером

### Проблемы с Vue.js

**Проблема 1: Компонент не отображается**
**Решение:**
- Проверить, что компонент зарегистрирован в router
- Проверить консоль браузера на ошибки
- Убедиться, что компонент импортирован правильно

**Проблема 2: Ошибка "Cannot read property of undefined"**
**Решение:**
- Добавить проверки на существование данных
- Использовать optional chaining: `user?.name`
- Добавить loading состояния

**Проблема 3: Стили не применяются**
**Решение:**
- Проверить, что стили импортированы в компоненте
- Проверить scoped стили (если используются)
- Убедиться, что CSS файлы включены в сборку

### Проблемы с интеграцией Bitrix24

**Проблема 1: BX не определен**
```
ReferenceError: BX is not defined
```
**Решение:**
- Проверить, что приложение загружается внутри Bitrix24 iframe
- Добавить проверку: `if (typeof BX !== 'undefined')`
- Использовать fallback для внешнего доступа

**Проблема 2: Уведомления не показываются**
**Решение:**
- Проверить, что BX.UI.Notification доступен
- Проверить версию Bitrix24 (может не поддерживаться)
- Использовать альтернативный способ уведомлений

### Проблемы с production сборкой

**Проблема 1: Файлы не найдены после сборки**
**Решение:**
- Проверить путь `outDir` в vite.config.js
- Убедиться, что файлы скопированы в правильную директорию
- Проверить права доступа к файлам

**Проблема 2: Большой размер бандла**
**Решение:**
- Использовать code splitting
- Проверить, что не импортируются неиспользуемые библиотеки
- Использовать tree-shaking

**Проблема 3: Медленная загрузка**
**Решение:**
- Включить gzip сжатие на сервере
- Использовать CDN для статических ресурсов
- Настроить кеширование

---

## Детали тестирования

### Тестирование API endpoints

**Инструменты:**
- Postman
- curl
- JavaScript fetch

**Пример тестирования через curl:**
```bash
# Тест получения пользователя
curl -X GET "http://localhost/APP-B24/api/user?AUTH_ID=your_token&DOMAIN=your_domain.bitrix24.ru" \
  -H "Content-Type: application/json"

# Тест добавления отдела
curl -X POST "http://localhost/APP-B24/api/access-control?AUTH_ID=your_token&DOMAIN=your_domain.bitrix24.ru" \
  -H "Content-Type: application/json" \
  -d '{"action":"add-department","departmentId":1,"departmentName":"Отдел продаж"}'
```

**Пример тестирования через JavaScript:**
```javascript
// В консоли браузера
fetch('/api/user?AUTH_ID=your_token&DOMAIN=your_domain.bitrix24.ru')
  .then(res => res.json())
  .then(data => console.log(data))
  .catch(err => console.error(err));
```

### Тестирование Vue.js компонентов

**Инструменты:**
- Vue DevTools (расширение браузера)
- Консоль браузера
- Network tab в DevTools

**Проверка состояния:**
```javascript
// В консоли браузера (если используется Pinia)
import { useUserStore } from './stores/userStore';
const store = useUserStore();
console.log(store.currentUser);
```

**Проверка API запросов:**
- Открыть Network tab в DevTools
- Фильтровать по XHR/Fetch
- Проверить запросы к /api/
- Проверить статус ответов

### Тестирование производительности

**Инструменты:**
- Chrome DevTools Performance
- Lighthouse
- WebPageTest

**Метрики для проверки:**
- First Contentful Paint (FCP) < 1.8s
- Largest Contentful Paint (LCP) < 2.5s
- Time to Interactive (TTI) < 3.8s
- Total Blocking Time (TBT) < 200ms

**Проверка размера бандла:**
```bash
cd APP-B24/frontend
npm run build
# Проверить размер файлов в public/dist/assets/
```

---

## История правок

- **2025-12-20 19:38 (UTC+3, Брест):** Создан план миграции на Vue.js
- **2025-12-20 20:15 (UTC+3, Брест):** Добавлены детали конфигурации Vite, детали API endpoints, секция Troubleshooting, детали тестирования

---

**Версия документа:** 1.1  
**Последнее обновление:** 2025-12-20 20:15 (UTC+3, Брест)

