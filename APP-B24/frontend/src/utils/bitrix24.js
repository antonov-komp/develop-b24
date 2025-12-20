/**
 * Утилиты для работы с Bitrix24 BX.* API
 */

/**
 * Показ уведомления через Bitrix24 UI
 * 
 * @param {string} message Текст сообщения
 * @param {string} type Тип уведомления: 'success', 'error', 'info', 'warning'
 * @param {number} duration Длительность показа (мс)
 */
export function showNotification(message, type = 'info', duration = 5000) {
  if (typeof BX !== 'undefined' && BX.UI && BX.UI.Notification) {
    BX.UI.Notification.Center.notify({
      content: message,
      autoHideDelay: duration,
      type: type
    });
  } else {
    // Fallback на обычный alert
    alert(message);
  }
}

/**
 * Показ уведомления об успехе
 */
export function showSuccess(message, duration = 3000) {
  showNotification(message, 'success', duration);
}

/**
 * Показ уведомления об ошибке
 */
export function showError(message, duration = 5000) {
  showNotification(message, 'error', duration);
}

/**
 * Показ информационного уведомления
 */
export function showInfo(message, duration = 4000) {
  showNotification(message, 'info', duration);
}

/**
 * Показ предупреждения
 */
export function showWarning(message, duration = 4000) {
  showNotification(message, 'warning', duration);
}

/**
 * Получение параметров из URL (AUTH_ID, DOMAIN и т.д.)
 */
export function getUrlParams() {
  const params = new URLSearchParams(window.location.search);
  return {
    AUTH_ID: params.get('AUTH_ID'),
    DOMAIN: params.get('DOMAIN'),
    APP_SID: params.get('APP_SID'),
    PLACEMENT: params.get('PLACEMENT'),
  };
}

/**
 * Проверка, что мы внутри Bitrix24 iframe
 */
export function isInBitrix24() {
  return typeof BX !== 'undefined' && typeof window.parent !== 'undefined';
}

/**
 * Получение данных из Bitrix24 BX.* API
 */
export function getBitrix24Data() {
  if (typeof BX !== 'undefined' && BX.message) {
    return {
      userId: BX.message('USER_ID'),
      userName: BX.message('USER_NAME'),
      userLastName: BX.message('USER_LAST_NAME'),
    };
  }
  return null;
}

