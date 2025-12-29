/**
 * Конфигурация логирования
 * 
 * Загружает конфигурацию из app_data (переданную из PHP)
 * Использует значения по умолчанию, если конфигурация не задана
 */

/**
 * Получение конфигурации логирования
 * 
 * @returns {object} Конфигурация логирования
 */
export function getLoggingConfig() {
  // Значения по умолчанию
  const defaultConfig = {
    enabled: true,
    default_level: 'info',
    layers: {
      INIT: { enabled: true, level: 'info' },
      ROUTER: { enabled: true, level: 'debug' },
      VUE_LIFECYCLE: { enabled: true, level: 'info' },
      USER_STORE: { enabled: true, level: 'debug' },
      ACCESS_CONTROL: { enabled: true, level: 'debug' },
      API: { enabled: true, level: 'debug' },
      BITRIX24: { enabled: true, level: 'info' },
      ERROR: { enabled: true, level: 'error' }
    }
  };
  
  // Проверяем режим (development/production)
  const isDevelopment = import.meta.env.DEV || import.meta.env.MODE === 'development';
  
  // В production режиме по умолчанию только ERROR логи включены
  if (!isDevelopment) {
    defaultConfig.layers = {
      INIT: { enabled: false, level: 'info' },
      ROUTER: { enabled: false, level: 'debug' },
      VUE_LIFECYCLE: { enabled: false, level: 'info' },
      USER_STORE: { enabled: false, level: 'debug' },
      ACCESS_CONTROL: { enabled: false, level: 'debug' },
      API: { enabled: false, level: 'debug' },
      BITRIX24: { enabled: false, level: 'info' },
      ERROR: { enabled: true, level: 'error' }
    };
  }
  
  // Пытаемся получить конфигурацию из app_data
  try {
    const appDataStr = sessionStorage.getItem('app_data');
    if (appDataStr) {
      const appData = JSON.parse(appDataStr);
      // Проверяем loggingConfig в app_data (переданный из PHP)
      if (appData.loggingConfig && typeof appData.loggingConfig === 'object') {
        // Объединяем конфигурацию из app_data с значениями по умолчанию
        const config = {
          enabled: appData.loggingConfig.enabled !== undefined 
            ? appData.loggingConfig.enabled 
            : defaultConfig.enabled,
          default_level: appData.loggingConfig.default_level || defaultConfig.default_level,
          layers: { ...defaultConfig.layers }
        };
        
        // Объединяем настройки слоёв
        if (appData.loggingConfig.layers) {
          Object.keys(appData.loggingConfig.layers).forEach(layer => {
            if (config.layers[layer]) {
              config.layers[layer] = {
                ...config.layers[layer],
                ...appData.loggingConfig.layers[layer]
              };
            }
          });
        }
        
        return config;
      }
    }
  } catch (e) {
    // Игнорируем ошибки парсинга, используем значения по умолчанию
    console.warn('Failed to load logging config from app_data, using defaults', e);
  }
  
  // Возвращаем значения по умолчанию
  return defaultConfig;
}

