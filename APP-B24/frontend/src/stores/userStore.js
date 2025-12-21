import { defineStore } from 'pinia';
import apiClient from '@/services/api';

export const useUserStore = defineStore('user', {
  state: () => ({
    currentUser: null,
    isAdmin: false,
    departments: [],
    loading: false,
    error: null,
  }),

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
  },

  actions: {
    async fetchCurrentUser() {
      this.loading = true;
      this.error = null;
      
      console.log('UserStore: Starting fetchCurrentUser...');
      
      try {
        // Получаем параметры из URL
        const params = new URLSearchParams(window.location.search);
        const domain = params.get('DOMAIN');
        
        // Пытаемся получить правильный AUTH_ID через BX24.getAuth()
        // APP_SID не работает для API вызовов, нужен auth_token из BX24.getAuth()
        let authId = params.get('AUTH_ID');
        
        // Если AUTH_ID нет, но есть BX24 API, получаем токен асинхронно
        if (!authId && typeof BX24 !== 'undefined' && BX24.getAuth) {
          console.log('UserStore: Getting auth token via BX24.getAuth()...');
          try {
            // Получаем токен через Promise
            const auth = await new Promise((resolve, reject) => {
              BX24.getAuth(function(authData) {
                if (authData && authData.auth_token) {
                  // Сохраняем токен в sessionStorage для последующих запросов
                  sessionStorage.setItem('bitrix24_auth', JSON.stringify(authData));
                  resolve(authData);
                } else {
                  reject(new Error('Failed to get auth token from BX24'));
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
            // Fallback на APP_SID (но он не будет работать)
            authId = params.get('APP_SID');
            if (authId) {
              console.warn('UserStore: Using APP_SID as fallback - this may not work');
            }
          }
        } else if (!authId) {
          // Fallback на APP_SID если нет BX24 API
          authId = params.get('APP_SID');
          if (authId) {
            console.warn('UserStore: Using APP_SID as fallback - this may not work for API calls');
          }
        }
        
        console.log('UserStore: Request params:', {
          AUTH_ID: authId ? 'present' : 'missing',
          DOMAIN: domain ? 'present' : 'missing'
        });
        
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
        this.isAdmin = response.data.data.isAdmin || false;
        this.departments = response.data.data.departments || [];
        this.error = null; // Очищаем ошибку при успехе
        
        console.log('UserStore: User data loaded:', {
          userId: this.currentUser?.ID,
          name: this.currentUser?.NAME,
          isAdmin: this.isAdmin,
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
        
        // Сохраняем детальную информацию об ошибке
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

