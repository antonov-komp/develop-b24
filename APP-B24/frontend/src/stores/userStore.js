import { defineStore } from 'pinia';
import apiClient from '@/services/api';

export const useUserStore = defineStore('user', {
  state: () => {
    // Получаем данные из sessionStorage при инициализации (переданные из index.php)
    let initialData = null;
    try {
      const appDataStr = sessionStorage.getItem('app_data');
      if (appDataStr) {
        initialData = JSON.parse(appDataStr);
      }
    } catch (e) {
      console.warn('UserStore: Failed to parse app_data from sessionStorage', e);
    }
    
    // Инициализируем состояние из данных, переданных из PHP
    const authInfo = initialData?.authInfo || {};
    const user = authInfo.user || null;
    
    return {
      currentUser: user ? {
        ID: user.id,
        NAME: user.name,
        LAST_NAME: user.last_name,
        EMAIL: user.email,
        ADMIN: user.admin
      } : null,
      isAdmin: authInfo.is_admin || false,
      isAuthenticated: authInfo.is_authenticated || false,
      departments: [],
      loading: false,
      error: null,
      externalAccessEnabled: initialData?.externalAccessEnabled || false,
    };
  },

  getters: {
    isAdminUser: (state) => {
      return state.isAdmin || (state.currentUser && (state.currentUser.ADMIN === 'Y' || state.currentUser.IS_ADMIN === true));
    },
    
    userFullName: (state) => {
      if (!state.currentUser) return '';
      const name = state.currentUser.NAME || '';
      const lastName = state.currentUser.LAST_NAME || '';
      return `${name} ${lastName}`.trim() || 'Пользователь';
    },
    
    authStatus: (state) => {
      if (state.externalAccessEnabled && !state.isAuthenticated) {
        return 'external_access';
      }
      return state.isAuthenticated ? 'authenticated' : 'not_authenticated';
    },
  },

  actions: {
    async fetchCurrentUser() {
      this.loading = true;
      this.error = null;
      
      console.log('UserStore: Starting fetchCurrentUser...');
      
      try {
        // Получаем параметры из URL
        const params = new URLSearchParams(window.location.search);
        let domain = params.get('DOMAIN');
        
        // Пытаемся получить правильный AUTH_ID
        // APP_SID не работает для API вызовов, нужен auth_token
        let authId = params.get('AUTH_ID');
        
        // Если токена нет в URL, проверяем данные из PHP (app_data)
        if (!authId || !domain) {
          try {
            const appDataStr = sessionStorage.getItem('app_data');
            if (appDataStr) {
              const appData = JSON.parse(appDataStr);
              if (appData.authInfo && appData.authInfo.auth_id && appData.authInfo.domain) {
                authId = appData.authInfo.auth_id;
                domain = appData.authInfo.domain;
                console.log('UserStore: Using token from app_data (PHP)', {
                  token_length: authId ? authId.length : 0,
                  domain: domain
                });
              }
            }
          } catch (e) {
            console.warn('UserStore: Failed to parse app_data', e);
          }
        }
        
        // Проверка: если внешний доступ включен и нет токена, не делаем запрос
        if (this.externalAccessEnabled && !authId && !domain) {
          // Проверяем sessionStorage на наличие токена
          const storedAuth = sessionStorage.getItem('bitrix24_auth');
          if (!storedAuth) {
            console.log('UserStore: External access enabled, but no auth token. Skipping API request.');
            this.loading = false;
            this.isAuthenticated = false;
            this.currentUser = null;
            return; // Не делаем запрос, просто выходим
          }
        }
        
        // Логируем доступность BX24 API
        console.log('UserStore: BX24 API check:', {
          hasBX: typeof BX !== 'undefined',
          hasBX24: typeof BX24 !== 'undefined' && BX24 !== null,
          hasBX24GetAuth: typeof BX24 !== 'undefined' && BX24 !== null && typeof BX24.getAuth === 'function',
          hasStoredAuth: !!sessionStorage.getItem('bitrix24_auth')
        });
        
        // Если AUTH_ID нет, пытаемся получить токен
        if (!authId) {
          // Сначала проверяем sessionStorage
          const storedAuth = sessionStorage.getItem('bitrix24_auth');
          if (storedAuth) {
            try {
              const auth = JSON.parse(storedAuth);
              authId = auth.auth_token;
              console.log('UserStore: Using stored auth token from sessionStorage', {
                auth_token_length: authId ? authId.length : 0
              });
            } catch (e) {
              console.warn('UserStore: Failed to parse stored auth token', e);
            }
          }
          
          // Если токена нет в sessionStorage, пытаемся получить через BX24.getAuth()
          // Но сначала проверяем, может быть токен уже был передан из PHP
          if (!authId) {
            // Пытаемся инициализировать BX24, если он доступен, но еще не инициализирован
            if (typeof BX24 !== 'undefined' && BX24 !== null && typeof BX24.init === 'function') {
              console.log('UserStore: Initializing BX24...');
              try {
                await new Promise((resolve, reject) => {
                  const timeout = setTimeout(() => {
                    reject(new Error('BX24.init() timeout'));
                  }, 3000);
                  
                  // Проверяем BX24 перед вызовом
                  if (typeof BX24 === 'undefined' || BX24 === null || typeof BX24.init !== 'function') {
                    clearTimeout(timeout);
                    reject(new Error('BX24.init is not available'));
                    return;
                  }
                  
                  BX24.init(function() {
                    clearTimeout(timeout);
                    console.log('UserStore: BX24 initialized');
                    resolve();
                  });
                });
              } catch (e) {
                console.warn('UserStore: Failed to initialize BX24', e);
              }
            } else {
              console.warn('UserStore: BX24.init() is not available (BX24 is null or undefined)');
            }
            
            // Теперь пытаемся получить токен через BX24.getAuth()
            // Проверяем наличие BX24 перед каждым вызовом
            if (typeof BX24 !== 'undefined' && BX24 !== null && typeof BX24.getAuth === 'function') {
              console.log('UserStore: Getting auth token via BX24.getAuth()...');
              try {
                // Получаем токен через Promise с меньшим таймаутом
                const auth = await new Promise((resolve, reject) => {
                  // Таймаут на случай, если BX24.getAuth не ответит
                  const timeout = setTimeout(() => {
                    reject(new Error('BX24.getAuth() timeout'));
                  }, 3000);
                  
                  // Проверяем BX24 перед вызовом
                  if (typeof BX24 === 'undefined' || BX24 === null || typeof BX24.getAuth !== 'function') {
                    clearTimeout(timeout);
                    reject(new Error('BX24.getAuth is not available'));
                    return;
                  }
                  
                  BX24.getAuth(function(authData) {
                    clearTimeout(timeout);
                    if (authData && authData.auth_token) {
                      // Сохраняем токен в sessionStorage для последующих запросов
                      sessionStorage.setItem('bitrix24_auth', JSON.stringify(authData));
                      resolve(authData);
                    } else {
                      reject(new Error('Failed to get auth token from BX24: no auth_token in response'));
                    }
                  });
                });
                
                authId = auth.auth_token;
                console.log('UserStore: Got auth token from BX24.getAuth()', {
                  auth_token_length: authId ? authId.length : 0,
                  domain: auth.domain || domain
                });
              } catch (e) {
                console.warn('UserStore: Failed to get auth token from BX24.getAuth()', e);
                // Не используем APP_SID как fallback, так как он не работает для API
              }
            } else {
              console.warn('UserStore: BX24.getAuth() is not available (BX24 is null or undefined)');
            }
            
            // Если все еще нет токена, используем APP_SID (но он не будет работать)
            if (!authId) {
              authId = params.get('APP_SID');
              if (authId) {
                let reason = 'unknown';
                if (typeof BX24 === 'undefined') {
                  reason = 'BX24 is undefined';
                } else if (BX24 === null) {
                  reason = 'BX24 is null';
                } else if (typeof BX24.getAuth !== 'function') {
                  reason = 'BX24.getAuth is not a function';
                } else {
                  reason = 'BX24.getAuth() timeout';
                }
                console.warn('UserStore: Using APP_SID as fallback - this may not work for API calls', {
                  reason: reason
                });
              }
            }
          }
        }
        
        console.log('UserStore: Request params:', {
          AUTH_ID: authId ? 'present' : 'missing',
          DOMAIN: domain ? 'present' : 'missing'
        });
        
        // Проверка: если внешний доступ включен и нет токена после всех попыток, не делаем запрос
        if (this.externalAccessEnabled && (!authId || !domain)) {
          console.log('UserStore: External access enabled, but no auth token after all attempts. Skipping API request.');
          this.loading = false;
          this.isAuthenticated = false;
          this.currentUser = null;
          return; // Не делаем запрос, просто выходим
        }
        
        // Если нет токена и внешний доступ не включен, это ошибка
        if (!authId || !domain) {
          throw new Error('AUTH_ID and DOMAIN are required for API requests');
        }
        
        // Если получили токен через BX24.getAuth(), добавляем его в параметры запроса
        const requestConfig = {};
        if (authId && domain) {
          requestConfig.params = {
            AUTH_ID: authId,
            DOMAIN: domain
          };
        }
        
        const response = await apiClient.get('/user/current', requestConfig);
        
        console.log('UserStore: API response:', {
          success: response.data.success,
          hasData: !!response.data.data,
          hasUser: !!response.data.data?.user,
          fullResponse: response.data
        });
        
        // Проверяем success: false даже при HTTP 200
        if (!response.data.success) {
          const errorMessage = response.data.message || response.data.error || 'Failed to get user data';
          const errorDetails = {
            message: errorMessage,
            error: response.data.error,
            debug: response.data.debug,
            possible_reasons: response.data.possible_reasons,
            suggestions: response.data.suggestions
          };
          
          console.error('UserStore: API returned success: false', errorDetails);
          
          // Сохраняем детальную информацию об ошибке
          this.error = errorMessage;
          
          // Показываем уведомление с детальной информацией
          if (typeof BX !== 'undefined' && BX.UI && BX.UI.Notification) {
            let notificationMessage = errorMessage;
            
            // Добавляем первую причину, если есть
            if (response.data.possible_reasons && response.data.possible_reasons.length > 0) {
              notificationMessage += '\n' + response.data.possible_reasons[0];
            }
            
            BX.UI.Notification.Center.notify({
              content: notificationMessage,
              autoHideDelay: 8000,
              type: 'error'
            });
          }
          
          // Бросаем ошибку с детальной информацией
          const error = new Error(errorMessage);
          error.response = {
            data: errorDetails,
            status: 200, // HTTP 200, но success: false
            statusText: 'OK'
          };
          throw error;
        }
        
        // Проверяем наличие данных
        if (!response.data.data || !response.data.data.user) {
          throw new Error('User data is missing in response');
        }
        
        // Сохраняем данные пользователя
        this.currentUser = response.data.data.user;
        // Сохраняем isAdmin из API, если он передан, иначе сохраняем текущее значение
        // Это важно, так как значение может быть установлено из PHP при инициализации
        if (response.data.data.hasOwnProperty('isAdmin')) {
          this.isAdmin = response.data.data.isAdmin;
        }
        // Если isAdmin не передан в ответе, но есть в currentUser.ADMIN, используем его
        if (!response.data.data.hasOwnProperty('isAdmin') && this.currentUser) {
          const userAdmin = this.currentUser.ADMIN === 'Y' || this.currentUser.ADMIN === 'y' || 
                          this.currentUser.ADMIN === 1 || this.currentUser.ADMIN === true ||
                          this.currentUser.IS_ADMIN === 'Y' || this.currentUser.IS_ADMIN === true;
          if (userAdmin) {
            this.isAdmin = true;
          }
        }
        this.departments = response.data.data.departments || [];
        this.error = null; // Очищаем ошибку при успехе
        
        console.log('UserStore: User data loaded:', {
          userId: this.currentUser?.ID,
          name: this.currentUser?.NAME,
          isAdmin: this.isAdmin,
          isAdminFromAPI: response.data.data.isAdmin,
          userAdminField: this.currentUser?.ADMIN,
          departmentsCount: this.departments.length
        });
        
      } catch (error) {
        console.error('UserStore: Error fetching user:', error);
        console.error('UserStore: Error details:', {
          message: error.message,
          response: error.response?.data,
          status: error.response?.status,
          statusText: error.response?.statusText,
          fullError: error
        });
        
        // Если ошибка 401 (Unauthorized) - это нормальная ситуация отсутствия авторизации
        // Не устанавливаем error, чтобы показать блок "no-auth" вместо ошибки
        if (error.response?.status === 401) {
          console.log('UserStore: 401 Unauthorized - это нормально, авторизация отсутствует');
          this.error = null; // Не показываем ошибку, покажем блок "no-auth"
          this.isAuthenticated = false;
          return; // Не бросаем ошибку, просто выходим
        }
        
        // Для других ошибок сохраняем детальную информацию
        this.error = error.response?.data?.message || 
                    error.response?.data?.error || 
                    error.message || 
                    'Ошибка загрузки пользователя';
        
        // Показываем уведомление, если еще не показали
        if (typeof BX !== 'undefined' && BX.UI && BX.UI.Notification && !error.response?.data) {
          BX.UI.Notification.Center.notify({
            content: this.error,
            autoHideDelay: 5000,
            type: 'error'
          });
        }
        
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});

