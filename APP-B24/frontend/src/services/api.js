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
    const params = new URLSearchParams(window.location.search);
    // Bitrix24 может передавать APP_SID вместо AUTH_ID
    const authId = params.get('AUTH_ID') || params.get('APP_SID');
    const domain = params.get('DOMAIN');
    
    if (authId && domain) {
      config.params = {
        ...config.params,
        AUTH_ID: authId, // Всегда используем AUTH_ID в параметрах API
        DOMAIN: domain,
      };
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

