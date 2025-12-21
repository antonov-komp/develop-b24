import axios from 'axios';

// Базовый URL API
// Используем отдельные файлы для каждого endpoint (обход проблемы с nginx роутингом)
const API_BASE_URL = '/APP-B24/api';

// Создание экземпляра axios
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Перехватчик запросов
apiClient.interceptors.request.use(
  (config) => {
    // Извлекаем маршрут из URL
    // URL формат: /user/current
    // Преобразуем в: /user.php?action=current
    const url = config.url || '';
    const urlParts = url.split('/').filter(part => part);
    
    // Если URL содержит сегменты, используем первый как имя файла
    if (urlParts.length > 0) {
      const route = urlParts[0];
      const action = urlParts[1] || null;
      
      // Устанавливаем URL на имя файла PHP
      config.url = `/${route}.php`;
      
      // Добавляем action в query параметры, если есть
      if (action) {
        config.params = {
          ...config.params,
          action: action
        };
      }
    }
    
    // Добавление параметров из Bitrix24, если доступны
    // Сначала проверяем sessionStorage (приоритет - токен из BX24.getAuth())
    let authId = null;
    let domain = null;
    
    // Пытаемся получить токен из sessionStorage (самый надежный способ)
    const storedAuth = sessionStorage.getItem('bitrix24_auth');
    if (storedAuth) {
      try {
        const auth = JSON.parse(storedAuth);
        authId = auth.auth_token;
        domain = auth.domain;
        console.log('API: Using stored auth token from sessionStorage', {
          has_auth_token: !!authId,
          has_domain: !!domain
        });
      } catch (e) {
        console.warn('API: Failed to parse stored auth token', e);
      }
    }
    
    // Если токена нет в sessionStorage, проверяем URL параметры
    if (!authId || !domain) {
      const params = new URLSearchParams(window.location.search);
      
      // Получаем DOMAIN из URL
      if (!domain) {
        domain = params.get('DOMAIN');
      }
      
      // Пытаемся получить AUTH_ID из URL (но это может быть APP_SID, который не работает)
      if (!authId) {
        authId = params.get('AUTH_ID');
      }
      
      // Если AUTH_ID нет, но есть APP_SID - используем его с предупреждением
      if (!authId) {
        authId = params.get('APP_SID');
        if (authId) {
          console.warn('API: Using APP_SID from URL - this may not work for API calls');
        }
      }
    }
    
    // Если все еще нет параметров, проверяем router.query (если доступен Vue Router)
    if ((!authId || !domain) && typeof window !== 'undefined' && window.__VUE_ROUTER__) {
      try {
        // Пытаемся получить router из глобального контекста
        // Это fallback, если router не доступен напрямую
        const currentRoute = window.location.pathname;
        const queryString = window.location.search;
        if (queryString) {
          const urlParams = new URLSearchParams(queryString);
          if (!domain) {
            domain = urlParams.get('DOMAIN');
          }
          if (!authId) {
            authId = urlParams.get('AUTH_ID') || urlParams.get('APP_SID');
          }
        }
      } catch (e) {
        console.warn('API: Failed to get params from router', e);
      }
    }
    
    // Добавляем параметры в запрос, если они есть
    if (authId && domain) {
      config.params = {
        ...config.params,
        AUTH_ID: authId,
        DOMAIN: domain,
      };
      console.log('API: Auth params added to request', {
        has_auth_id: !!authId,
        auth_id_length: authId ? authId.length : 0,
        domain: domain
      });
    } else {
      console.warn('API: Missing AUTH_ID or DOMAIN', {
        has_auth_id: !!authId,
        has_domain: !!domain,
        url_search: window.location.search
      });
    }
    
    console.log('API Request:', config.method?.toUpperCase(), config.baseURL + config.url, config.params);
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Перехватчик ответов
apiClient.interceptors.response.use(
  (response) => {
    // Логирование в development режиме
    if (import.meta.env.DEV) {
      console.log('API Response:', response.config.url, response.data);
    }
    
    // Проверяем success: false даже при HTTP 200
    // Это позволяет обрабатывать бизнес-ошибки как ошибки
    if (response.data && response.data.success === false) {
      const error = new Error(response.data.message || response.data.error || 'Request failed');
      error.response = {
        data: response.data,
        status: response.status,
        statusText: response.statusText,
        headers: response.headers,
        config: response.config
      };
      return Promise.reject(error);
    }
    
    return response;
  },
  (error) => {
    // Обработка ошибок
    if (error.response) {
      // Сервер вернул ошибку
      const { status, data } = error.response;
      
      // Показываем уведомление через Bitrix24 UI
      if (typeof BX !== 'undefined' && BX.UI && BX.UI.Notification) {
        let message = data.message || data.error || 'Произошла ошибка';
        
        switch (status) {
          case 401:
            message = 'Ошибка авторизации. Проверьте AUTH_ID и DOMAIN';
            break;
          case 403:
            message = 'Доступ запрещен. Недостаточно прав доступа';
            break;
          case 404:
            message = 'Endpoint не найден';
            break;
          case 500:
            message = 'Ошибка сервера. Попробуйте позже';
            break;
        }
        
        BX.UI.Notification.Center.notify({
          content: message,
          autoHideDelay: 5000,
          type: 'error'
        });
      }
      
      if (status === 401) {
        console.error('Ошибка авторизации');
      } else if (status === 403) {
        console.error('Доступ запрещен');
      } else if (status >= 500) {
        console.error('Ошибка сервера:', data);
      }
    } else if (error.request) {
      // Запрос отправлен, но ответа нет
      console.error('Нет ответа от сервера');
      
      if (typeof BX !== 'undefined' && BX.UI && BX.UI.Notification) {
        BX.UI.Notification.Center.notify({
          content: 'Нет ответа от сервера. Проверьте подключение',
          autoHideDelay: 5000,
          type: 'error'
        });
      }
    } else {
      // Ошибка при настройке запроса
      console.error('Ошибка запроса:', error.message);
    }
    
    return Promise.reject(error);
  }
);

export default apiClient;

