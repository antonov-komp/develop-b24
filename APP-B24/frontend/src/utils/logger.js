/**
 * Утилита для логирования в консоль
 * 
 * Поддерживает категории (слои) и уровни логирования
 * Проверяет конфигурацию перед выводом
 */

import { getLoggingConfig } from '@/config/logging';

/**
 * Класс для логирования с поддержкой категорий и уровней
 */
class Logger {
  constructor() {
    this.config = null;
    this.configCache = null;
    this.cacheTimestamp = 0;
    this.cacheTTL = 5000; // Кешируем конфигурацию на 5 секунд
  }
  
  /**
   * Получение конфигурации (с кешированием)
   * 
   * @returns {object} Конфигурация логирования
   */
  getConfig() {
    const now = Date.now();
    
    // Если кеш устарел, обновляем конфигурацию
    if (!this.configCache || (now - this.cacheTimestamp) > this.cacheTTL) {
      this.configCache = getLoggingConfig();
      this.cacheTimestamp = now;
    }
    
    return this.configCache;
  }
  
  /**
   * Логирование с категорией и уровнем
   * 
   * @param {string} layer Категория (INIT, ROUTER, VUE_LIFECYCLE, etc.)
   * @param {string} level Уровень (debug, info, warn, error)
   * @param {string} message Сообщение
   * @param {object|array|null} data Данные для логирования
   */
  log(layer, level, message, data = null) {
    const config = this.getConfig();
    
    // Проверка, включено ли логирование вообще
    if (!config.enabled) {
      return;
    }
    
    // Проверка, включен ли слой
    const layerConfig = config.layers[layer];
    if (!layerConfig || !layerConfig.enabled) {
      return;
    }
    
    // Проверка уровня логирования
    const levels = ['debug', 'info', 'warn', 'error'];
    const currentLevelIndex = levels.indexOf(level);
    const configLevelIndex = levels.indexOf(layerConfig.level || config.default_level);
    
    if (currentLevelIndex < configLevelIndex) {
      return; // Уровень слишком низкий
    }
    
    // Форматирование сообщения
    const prefix = `[${layer}]`;
    const formattedMessage = `${prefix} ${message}`;
    
    // Выбор метода консоли
    const consoleMethod = console[level] || console.log;
    
    // Вывод в консоль
    if (data !== null && data !== undefined) {
      consoleMethod(formattedMessage, data);
    } else {
      consoleMethod(formattedMessage);
    }
  }
  
  /**
   * Логирование уровня debug
   * 
   * @param {string} layer Категория
   * @param {string} message Сообщение
   * @param {object|array|null} data Данные
   */
  debug(layer, message, data = null) {
    this.log(layer, 'debug', message, data);
  }
  
  /**
   * Логирование уровня info
   * 
   * @param {string} layer Категория
   * @param {string} message Сообщение
   * @param {object|array|null} data Данные
   */
  info(layer, message, data = null) {
    this.log(layer, 'info', message, data);
  }
  
  /**
   * Логирование уровня warn
   * 
   * @param {string} layer Категория
   * @param {string} message Сообщение
   * @param {object|array|null} data Данные
   */
  warn(layer, message, data = null) {
    this.log(layer, 'warn', message, data);
  }
  
  /**
   * Логирование уровня error
   * 
   * @param {string} layer Категория
   * @param {string} message Сообщение
   * @param {object|array|null} data Данные
   */
  error(layer, message, data = null) {
    this.log(layer, 'error', message, data);
  }
}

// Создание singleton
const logger = new Logger();

// Экспорт singleton
export default logger;

// Глобальный доступ для использования в генерируемом JavaScript (из PHP)
if (typeof window !== 'undefined') {
  window.__LOGGER__ = logger;
}

