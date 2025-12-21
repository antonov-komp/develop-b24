import { defineStore } from 'pinia';
import apiClient from '@/services/api';

export const useAccessControlStore = defineStore('accessControl', {
  state: () => ({
    config: null,
    departments: [],
    users: [],
    loading: false,
    error: null,
  }),

  getters: {
    isEnabled: (state) => {
      return state.config?.access_control?.enabled ?? false;
    },
    
    enabledDepartments: (state) => {
      return state.config?.access_control?.departments ?? [];
    },
    
    enabledUsers: (state) => {
      return state.config?.access_control?.users ?? [];
    },
  },

  actions: {
    async fetchConfig() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await apiClient.get('/access-control');
        
        if (response.data.success && response.data.data) {
          this.config = response.data.data;
          this.departments = response.data.data.access_control?.departments ?? [];
          this.users = response.data.data.access_control?.users ?? [];
        } else {
          throw new Error(response.data.message || 'Failed to get access control config');
        }
      } catch (error) {
        this.error = error.response?.data?.message || error.message || 'Ошибка загрузки конфигурации';
        throw error;
      } finally {
        this.loading = false;
      }
    },
    
    async addDepartment(departmentId, departmentName) {
      try {
        const response = await apiClient.post('/access-control/departments', {
          department_id: departmentId,
          department_name: departmentName,
        });
        
        if (response.data.success) {
          await this.fetchConfig(); // Обновляем конфигурацию
          return { success: true };
        } else {
          throw new Error(response.data.message || 'Failed to add department');
        }
      } catch (error) {
        this.error = error.response?.data?.message || error.message || 'Ошибка добавления отдела';
        throw error;
      }
    },
    
    async addUser(userId, userName, userEmail = null) {
      try {
        const response = await apiClient.post('/access-control/users', {
          user_id: userId,
          user_name: userName,
          user_email: userEmail,
        });
        
        if (response.data.success) {
          await this.fetchConfig(); // Обновляем конфигурацию
          return { success: true };
        } else {
          throw new Error(response.data.message || 'Failed to add user');
        }
      } catch (error) {
        this.error = error.response?.data?.message || error.message || 'Ошибка добавления пользователя';
        throw error;
      }
    },
    
    async removeDepartment(departmentId) {
      try {
        const response = await apiClient.delete(`/access-control/departments/${departmentId}`);
        
        if (response.data.success) {
          await this.fetchConfig(); // Обновляем конфигурацию
          return { success: true };
        } else {
          throw new Error(response.data.message || 'Failed to remove department');
        }
      } catch (error) {
        this.error = error.response?.data?.message || error.message || 'Ошибка удаления отдела';
        throw error;
      }
    },
    
    async removeUser(userId) {
      try {
        const response = await apiClient.delete(`/access-control/users/${userId}`);
        
        if (response.data.success) {
          await this.fetchConfig(); // Обновляем конфигурацию
          return { success: true };
        } else {
          throw new Error(response.data.message || 'Failed to remove user');
        }
      } catch (error) {
        this.error = error.response?.data?.message || error.message || 'Ошибка удаления пользователя';
        throw error;
      }
    },
  },
});






