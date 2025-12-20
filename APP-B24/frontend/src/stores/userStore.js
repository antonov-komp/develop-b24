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
      
      try {
        const response = await apiClient.get('/user/current');
        
        if (response.data.success && response.data.data) {
          this.currentUser = response.data.data.user;
          // Сохраняем дополнительные данные
          this.isAdmin = response.data.data.isAdmin || false;
          this.departments = response.data.data.departments || [];
        } else {
          throw new Error(response.data.message || 'Failed to get user data');
        }
      } catch (error) {
        this.error = error.response?.data?.message || error.message || 'Ошибка загрузки пользователя';
        throw error;
      } finally {
        this.loading = false;
      }
    },
  },
});

