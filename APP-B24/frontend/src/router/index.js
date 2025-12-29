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

// Счётчик попыток редиректа для предотвращения бесконечного цикла
let redirectAttempts = 0;
const MAX_REDIRECT_ATTEMPTS = 3;

// Навигационные хуки
router.beforeEach((to, from, next) => {
  console.log('Router beforeEach:', { to: to.path, from: from.path, fullPath: to.fullPath, query: to.query });
  
  // Если маршрут содержит index.php, редиректим на главную
  if (to.path === '/index.php' || to.path.includes('index.php')) {
    console.log('Router: Redirecting from index.php to /');
    redirectAttempts = 0; // Сбрасываем счётчик при нормальном редиректе
    next({ path: '/', query: to.query, replace: true });
    return;
  }
  
  // Сохраняем параметры авторизации при навигации
  // Если параметры есть в текущем URL, но их нет в целевом маршруте, добавляем их
  const currentParams = new URLSearchParams(window.location.search);
  const authId = currentParams.get('AUTH_ID') || currentParams.get('APP_SID');
  const domain = currentParams.get('DOMAIN');
  
  // Если параметры есть в текущем URL, но их нет в query целевого маршрута, добавляем их
  if (authId && domain && (!to.query.AUTH_ID && !to.query.APP_SID) && !to.query.DOMAIN) {
    console.log('Router: Adding auth params to route query');
    redirectAttempts = 0; // Сбрасываем счётчик при успешном добавлении параметров
    next({
      path: to.path,
      query: {
        ...to.query,
        AUTH_ID: authId,
        DOMAIN: domain,
        // Сохраняем другие параметры из текущего URL
        ...Object.fromEntries(currentParams.entries())
      },
      replace: false
    });
    return;
  }
  
  // Проверка авторизации
  if (to.meta.requiresAuth) {
    // Проверяем параметры в query маршрута или в текущем URL
    const routeAuthId = to.query.AUTH_ID || to.query.APP_SID || authId;
    const routeDomain = to.query.DOMAIN || domain;
    
    if (!routeAuthId || !routeDomain) {
      // Если уже на главной странице и нет параметров - это бесконечный цикл
      if (to.name === 'index' && to.path === '/') {
        console.error('Router: Infinite redirect detected! Missing AUTH_ID or DOMAIN. Already on index page.');
        redirectAttempts = 0; // Сбрасываем счётчик
        
        // Разрешаем доступ без авторизации, чтобы предотвратить бесконечный цикл
        console.warn('Router: Allowing access without auth to prevent infinite loop');
        next();
        return;
      }
      
      // Проверяем количество попыток редиректа
      if (redirectAttempts >= MAX_REDIRECT_ATTEMPTS) {
        // Превышен лимит попыток - это бесконечный цикл
        console.error('Router: Infinite redirect detected! Missing AUTH_ID or DOMAIN. Attempts:', redirectAttempts);
        redirectAttempts = 0; // Сбрасываем счётчик
        
        // Разрешаем доступ без авторизации, чтобы предотвратить бесконечный цикл
        console.warn('Router: Allowing access without auth to prevent infinite loop (max attempts reached)');
        next();
        return;
      }
      
      // Увеличиваем счётчик попыток
      redirectAttempts++;
      console.warn('Router: Missing AUTH_ID or DOMAIN, redirecting to index (attempt', redirectAttempts, 'of', MAX_REDIRECT_ATTEMPTS + ')');
      
      // Редирект на главную страницу с сохранением параметров, если они есть
      const redirectQuery = authId && domain ? { AUTH_ID: authId, DOMAIN: domain } : {};
      next({ name: 'index', query: redirectQuery });
      return;
    }
    
    // Если авторизация успешна, сбрасываем счётчик
    redirectAttempts = 0;
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

