import apiClient from './api';

/**
 * Сервис для работы с Bitrix24 API
 */
export const bitrix24Api = {
  /**
   * Получение текущего пользователя
   */
  async getCurrentUser() {
    const response = await apiClient.get('/user/current');
    return response.data;
  },
  
  /**
   * Получение списка отделов
   */
  async getDepartments() {
    const response = await apiClient.get('/departments');
    return response.data;
  },
  
  /**
   * Получение конфигурации прав доступа
   */
  async getAccessControlConfig() {
    const response = await apiClient.get('/access-control');
    return response.data;
  },
  
  /**
   * Анализ токена
   */
  async analyzeToken() {
    const response = await apiClient.get('/token-analysis');
    return response.data;
  },
};


