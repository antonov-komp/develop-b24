<template>
  <div id="app">
    <!-- Отладочная информация -->
    <div v-if="false" style="background: #ff0; padding: 10px; margin: 10px; border: 2px solid #f00;">
      Router view should be here
    </div>
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { isInBitrix24, getBitrix24Data } from '@/utils/bitrix24';
import Logger from '@/utils/logger';

const router = useRouter();

onMounted(() => {
  Logger.info('VUE_LIFECYCLE', 'App.vue mounted', {
    currentRoute: router.currentRoute.value,
    location: window.location.href,
    search: window.location.search,
    pathname: window.location.pathname
  });
  
  // Проверка, что router-view рендерится
  setTimeout(() => {
    const routerView = document.querySelector('#app router-view, #app > div');
    Logger.debug('ROUTER', 'Router view check', {
      hasRouterView: !!routerView,
      appContent: document.querySelector('#app')?.innerHTML?.substring(0, 200)
    });
  }, 100);
  
  // Проверка, что мы внутри Bitrix24 iframe
  if (isInBitrix24()) {
    Logger.info('BITRIX24', 'Bitrix24 BX.* API доступен');
    
    // Получение данных из Bitrix24
    const bitrixData = getBitrix24Data();
    if (bitrixData) {
      Logger.debug('BITRIX24', 'Bitrix24 данные', bitrixData);
    }
    
    // Инициализация Bitrix24 UI (если нужно)
    if (typeof BX !== 'undefined' && BX.ready) {
      BX.ready(() => {
        Logger.info('BITRIX24', 'Bitrix24 готов');
      });
    }
  } else {
    Logger.warn('BITRIX24', 'Приложение запущено вне Bitrix24 iframe');
  }
  
  // Инициализация роутера
  // Если нет параметров авторизации, редирект на главную
  const params = new URLSearchParams(window.location.search);
  // Bitrix24 может передавать APP_SID вместо AUTH_ID
  const hasAuthId = params.has('AUTH_ID') || params.has('APP_SID');
  const hasDomain = params.has('DOMAIN');
  
  Logger.debug('BITRIX24', 'URL params', {
    AUTH_ID: params.get('AUTH_ID') ? 'present' : (params.get('APP_SID') ? 'present (APP_SID)' : 'missing'),
    DOMAIN: params.get('DOMAIN') ? 'present' : 'missing'
  });
  
  if (!hasAuthId || !hasDomain) {
    // В development режиме можно разрешить доступ без параметров
    if (import.meta.env.DEV) {
      Logger.warn('BITRIX24', 'Development mode: AUTH_ID/APP_SID or DOMAIN not found');
    } else {
      Logger.error('ERROR', 'Missing AUTH_ID/APP_SID or DOMAIN in production mode');
    }
  }
});
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background: #f5f5f5;
  color: #333;
}

#app {
  min-height: 100vh;
}
</style>

