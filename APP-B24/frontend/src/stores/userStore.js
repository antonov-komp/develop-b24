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
        // Логируем параметры запроса
        const params = new URLSearchParams(window.location.search);
        console.log('UserStore: Request params:', {
          AUTH_ID: params.get('AUTH_ID') || params.get('APP_SID') ? 'present' : 'missing',
          DOMAIN: params.get('DOMAIN') ? 'present' : 'missing'
        });
        
        const response = await apiClient.get('/user/current');
        
        console.log('UserStore: API response:', {
          success: response.data.success,
          hasData: !!response.data.data,
          hasUser: !!response.data.data?.user
        });
        
        if (response.data.success && response.data.data) {
          this.currentUser = response.data.data.user;
          // Сохраняем дополнительные данные
          this.isAdmin = response.data.data.isAdmin || false;
          this.departments = response.data.data.departments || [];
          console.log('UserStore: User data loaded:', {
            userId: this.currentUser?.ID,
            name: this.currentUser?.NAME,
            isAdmin: this.isAdmin
          });
        } else {
          throw new Error(response.data.message || 'Failed to get user data');
        }
      } catch (error) {
        console.error('UserStore: Error fetching user:', error);
        console.error('UserStore: Error details:', {
          message: error.message,
          response: error.response?.data,
          status: error.response?.status,
          statusText: error.response?.statusText
        });
        this.error = error.response?.data?.message || error.message || 'Ошибка загрузки пользователя';
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});

