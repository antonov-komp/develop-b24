import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import './assets/css/main.css';

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
      url: '/APP-B24/api/log-error',
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
console.log('Vue app mounting...', { 
  appElement: document.querySelector('#app'),
  router: router,
  routes: router.getRoutes(),
  currentPath: window.location.pathname
});

try {
  app.mount('#app');
  console.log('Vue app mounted successfully');
  
  // После монтирования проверяем текущий маршрут и исправляем, если нужно
  setTimeout(() => {
    const currentPath = router.currentRoute.value.path;
    console.log('Current route after mount:', currentPath);
    
    // Если маршрут содержит index.php, редиректим на главную
    if (currentPath === '/index.php' || currentPath.includes('index.php')) {
      console.log('Redirecting from index.php to /');
      router.replace({ path: '/', query: router.currentRoute.value.query });
    }
  }, 100);
} catch (error) {
  console.error('Error mounting Vue app:', error);
}

