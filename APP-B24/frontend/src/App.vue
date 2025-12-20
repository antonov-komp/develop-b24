<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { isInBitrix24, getBitrix24Data } from '@/utils/bitrix24';

const router = useRouter();

onMounted(() => {
  console.log('App.vue mounted', {
    currentRoute: router.currentRoute.value,
    location: window.location.href,
    search: window.location.search
  });
  
  // Проверка, что мы внутри Bitrix24 iframe
  if (isInBitrix24()) {
    console.log('Bitrix24 BX.* API доступен');
    
    // Получение данных из Bitrix24
    const bitrixData = getBitrix24Data();
    if (bitrixData) {
      console.log('Bitrix24 данные:', bitrixData);
    }
    
    // Инициализация Bitrix24 UI (если нужно)
    if (typeof BX !== 'undefined' && BX.ready) {
      BX.ready(() => {
        console.log('Bitrix24 готов');
      });
    }
  } else {
    console.warn('Приложение запущено вне Bitrix24 iframe');
  }
  
  // Инициализация роутера
  // Если нет параметров авторизации, редирект на главную
  const params = new URLSearchParams(window.location.search);
  console.log('URL params:', {
    AUTH_ID: params.get('AUTH_ID') ? 'present' : 'missing',
    DOMAIN: params.get('DOMAIN') ? 'present' : 'missing'
  });
  
  if (!params.has('AUTH_ID') || !params.has('DOMAIN')) {
    // В development режиме можно разрешить доступ без параметров
    if (import.meta.env.DEV) {
      console.warn('Development mode: AUTH_ID and DOMAIN not found');
    } else {
      console.error('Missing AUTH_ID or DOMAIN in production mode');
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

