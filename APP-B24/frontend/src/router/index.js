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
  // Если путь содержит /APP-B24/, используем его
  if (path.includes('/APP-B24/')) {
    const match = path.match(/^(\/APP-B24\/)/);
    return match ? match[1] : '/APP-B24/';
  }
  // Иначе используем дефолтный путь
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
  // Проверка авторизации
  if (to.meta.requiresAuth) {
    const urlParams = new URLSearchParams(window.location.search);
    const authId = urlParams.get('AUTH_ID');
    const domain = urlParams.get('DOMAIN');
    
    if (!authId || !domain) {
      // Редирект на главную страницу
      next({ name: 'index' });
      return;
    }
  }
  
  // Проверка прав администратора (будет реализовано через store)
  if (to.meta.requiresAdmin) {
    // Пока пропускаем, проверка будет в компонентах
  }
  
  next();
});

export default router;

