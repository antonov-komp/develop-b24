import { createRouter, createWebHistory } from 'vue-router';
import IndexPage from '@/components/IndexPage.vue';
import AccessControlPage from '@/components/AccessControlPage.vue';
import TokenAnalysisPage from '@/components/TokenAnalysisPage.vue';

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
    path: '/index.php',
    redirect: '/'
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

// Определяем базовый путь динамически
// Если мы в iframe Bitrix24, используем текущий путь
const getBasePath = () => {
  const path = window.location.pathname;
  console.log('Router: Determining base path from:', path);
  
  // Если путь содержит /APP-B24/, используем его
  if (path.includes('/APP-B24')) {
    // Убираем index.php и другие файлы из пути, оставляем только /APP-B24/
    let basePath = '/APP-B24/';
    
    // Если путь заканчивается на index.php, используем базовый путь
    if (path.endsWith('index.php')) {
      basePath = path.replace(/\/index\.php$/, '') + '/';
    } else if (path.includes('/APP-B24/')) {
      // Извлекаем путь до /APP-B24/
      const match = path.match(/^(\/APP-B24\/)/);
      basePath = match ? match[1] : '/APP-B24/';
    }
    
    console.log('Router: Base path determined:', basePath);
    return basePath;
  }
  // Иначе используем дефолтный путь
  console.log('Router: Using default base path: /APP-B24/');
  return '/APP-B24/';
};

const router = createRouter({
  history: createWebHistory(getBasePath()), // Базовый путь для приложения
  routes,
  // Сохраняем query параметры при навигации
  parseQuery: (query) => {
    const params = new URLSearchParams(query);
    const result = {};
    for (const [key, value] of params.entries()) {
      result[key] = value;
    }
    return result;
  },
  stringifyQuery: (query) => {
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(query)) {
      if (value !== null && value !== undefined) {
        params.set(key, String(value));
      }
    }
    return params.toString();
  }
});

// Навигационные хуки
router.beforeEach((to, from, next) => {
  console.log('Router beforeEach:', { to: to.path, from: from.path, fullPath: to.fullPath });
  
  // Если маршрут содержит index.php, редиректим на главную
  if (to.path === '/index.php' || to.path.includes('index.php')) {
    console.log('Router: Redirecting from index.php to /');
    next({ path: '/', query: to.query, replace: true });
    return;
  }
  
  // Проверка авторизации
  if (to.meta.requiresAuth) {
    const urlParams = new URLSearchParams(window.location.search);
    // Bitrix24 может передавать APP_SID вместо AUTH_ID
    const authId = urlParams.get('AUTH_ID') || urlParams.get('APP_SID');
    const domain = urlParams.get('DOMAIN');
    
    if (!authId || !domain) {
      console.warn('Router: Missing AUTH_ID or DOMAIN, redirecting to index');
      // Редирект на главную страницу
      next({ name: 'index' });
      return;
    }
  }
  
  // Проверка прав администратора (будет реализовано через store)
  if (to.meta.requiresAdmin) {
    // Пока пропускаем, проверка будет в компонентах
  }
  
  console.log('Router: Navigation allowed to', to.path);
  next();
});

// Логирование после навигации
router.afterEach((to, from) => {
  console.log('Router afterEach:', { to: to.path, from: from.path });
});

export default router;

