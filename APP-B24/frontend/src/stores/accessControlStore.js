import { defineStore } from 'pinia';
import apiClient from '@/services/api';
import Logger from '@/utils/logger';

export const useAccessControlStore = defineStore('accessControl', {
  state: () => ({
    config: null,
    departments: [],
    users: [],
    loading: false,
    error: null,
    // Новые поля для динамических списков
    availableDepartments: [], // Список всех отделов из Bitrix24
    availableUsers: [], // Список всех пользователей из Bitrix24
    loadingDepartments: false,
    loadingUsers: false,
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
    
    async toggleEnabled(enabled) {
      try {
        const response = await apiClient.post('/access-control/toggle', {
          enabled: enabled,
        });
        
        if (response.data.success) {
          await this.fetchConfig(); // Обновляем конфигурацию
          return { success: true };
        } else {
          throw new Error(response.data.message || 'Failed to toggle access control');
        }
      } catch (error) {
        this.error = error.response?.data?.message || error.message || 'Ошибка переключения проверки';
        throw error;
      }
    },
    
    async fetchDepartments() {
      this.loadingDepartments = true;
      try {
        const response = await apiClient.get('/departments');
        if (response.data.success && response.data.data) {
          this.availableDepartments = response.data.data.departments || [];
        } else {
          throw new Error(response.data.message || 'Failed to get departments');
        }
      } catch (error) {
        Logger.error('ERROR', 'Ошибка загрузки отделов', error);
        this.availableDepartments = [];
        this.error = error.response?.data?.message || error.message || 'Ошибка загрузки отделов';
        throw error;
      } finally {
        this.loadingDepartments = false;
      }
    },
    
    async fetchUsers(search = null) {
      this.loadingUsers = true;
      try {
        const params = search ? { search } : {};
        const response = await apiClient.get('/users', { params });
        if (response.data.success && response.data.data) {
          this.availableUsers = response.data.data.users || [];
        } else {
          throw new Error(response.data.message || 'Failed to get users');
        }
      } catch (error) {
        Logger.error('ERROR', 'Ошибка загрузки пользователей', error);
        this.availableUsers = [];
        this.error = error.response?.data?.message || error.message || 'Ошибка загрузки пользователей';
        throw error;
      } finally {
        this.loadingUsers = false;
      }
    },
    
    async addDepartments(departments) {
      try {
        const response = await apiClient.post('/access-control/departments/bulk', {
          departments: departments,
        });
        
        if (response.data.success) {
          await this.fetchConfig(); // Обновляем конфигурацию
          return { 
            success: true, 
            added: response.data.data?.added || 0,
            skipped: response.data.data?.skipped || 0
          };
        } else {
          throw new Error(response.data.message || 'Failed to add departments');
        }
      } catch (error) {
        this.error = error.response?.data?.message || error.message || 'Ошибка добавления отделов';
        throw error;
      }
    },
    
    async addUsers(users) {
      try {
        const response = await apiClient.post('/access-control/users/bulk', {
          users: users,
        });
        
        if (response.data.success) {
          await this.fetchConfig(); // Обновляем конфигурацию
          return { 
            success: true, 
            added: response.data.data?.added || 0,
            skipped: response.data.data?.skipped || 0
          };
        } else {
          throw new Error(response.data.message || 'Failed to add users');
        }
      } catch (error) {
        this.error = error.response?.data?.message || error.message || 'Ошибка добавления пользователей';
        throw error;
      }
    },
  },
});






