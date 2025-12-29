<template>
  <div class="index-page">
    <div class="container">
      <div class="card">
        <h1>Добро пожаловать!</h1>
        
        <div v-if="userStore.loading" class="loading">
          Загрузка данных...
        </div>
        
        <div v-else-if="userStore.error" class="error">
          <strong>Ошибка:</strong> {{ userStore.error }}
          <br><small>Проверьте консоль для деталей</small>
        </div>
        
        <div v-else-if="!userStore.isAuthenticated && !userStore.externalAccessEnabled" class="no-auth">
          <div class="warning-message">
            <h2>⚠️ Авторизация не выполнена</h2>
            <p>Для работы приложения необходима авторизация через Bitrix24.</p>
            <p v-if="isDevMode" class="dev-info">
              <strong>Development режим:</strong> Приложение открыто напрямую в браузере, а не через iframe Bitrix24.
              <br>Для корректной работы откройте приложение через Bitrix24.
            </p>
            <p v-else>
              Пожалуйста, откройте приложение через Bitrix24.
            </p>
            <div class="auth-params-info" v-if="isDevMode">
              <h3>Параметры авторизации:</h3>
              <ul>
                <li>AUTH_ID: {{ hasAuthId ? 'присутствует' : 'отсутствует' }}</li>
                <li>DOMAIN: {{ hasDomain ? 'присутствует' : 'отсутствует' }}</li>
              </ul>
            </div>
          </div>
        </div>
        
        <div v-else-if="!userStore.isAuthenticated && userStore.externalAccessEnabled" class="no-auth external-access">
          <div class="info-message">
            <h2>ℹ️ Внешний доступ включен</h2>
            <p>Приложение работает в режиме внешнего доступа без авторизации Bitrix24.</p>
            <p v-if="isDevMode" class="dev-info">
              <strong>Development режим:</strong> Приложение открыто напрямую в браузере.
              <br>Для полной функциональности откройте приложение через Bitrix24.
            </p>
            <div class="auth-params-info" v-if="isDevMode">
              <h3>Параметры авторизации:</h3>
              <ul>
                <li>AUTH_ID: {{ hasAuthId ? 'присутствует' : 'отсутствует' }}</li>
                <li>DOMAIN: {{ hasDomain ? 'присутствует' : 'отсутствует' }}</li>
              </ul>
            </div>
          </div>
        </div>
        
        <div v-else-if="user" class="user-info">
          <div class="user-header">
            <div v-if="user.PERSONAL_PHOTO" class="user-photo">
              <img :src="user.PERSONAL_PHOTO" :alt="userFullName" />
            </div>
            <div class="user-details">
              <h2>{{ userFullName }}</h2>
              <p class="user-id">ID пользователя: #{{ user.ID }}</p>
              <p class="user-status" :class="{ 'admin': isAdmin }">
                Статус: {{ isAdmin ? 'Администратор на портале' : 'Пользователь' }}
              </p>
            </div>
          </div>
          
          <div class="user-data">
            <p><strong>Email:</strong> {{ user.EMAIL || 'не указан' }}</p>
            
            <div v-if="departments.length > 0" class="departments">
              <strong>Отдел:</strong>
              <ul>
                <li v-for="dept in departments" :key="dept.id">
                  {{ dept.name }} (ID: {{ dept.id }})
                </li>
              </ul>
            </div>
            <p v-else><strong>Отдел:</strong> не указан</p>
            
            <p v-if="user.TIME_ZONE"><strong>Часовой пояс:</strong> {{ user.TIME_ZONE }}</p>
            
            <p><strong>Домен портала:</strong> {{ domain }}</p>
          </div>
          
          <div class="status-message success">
            <p>✓ Приложение успешно авторизовано и готово к работе</p>
            <p v-if="isCurrentUserToken" class="token-info">
              ✓ Используется токен текущего пользователя
            </p>
            <p v-else class="token-info warning">
              ⚠️ Используется токен установщика (владельца приложения). Токен текущего пользователя не найден в параметрах запроса.
            </p>
          </div>
          
          <div class="auth-info-section">
            <h3>Информация о текущей авторизации</h3>
            <div class="auth-info">
              <p><strong>Статус авторизации:</strong> 
                <span :class="authStatusClass">{{ authStatusText }}</span>
              </p>
              <p v-if="userStore.externalAccessEnabled">
                <strong>Внешний доступ:</strong> 
                <span class="status-enabled">Включен</span>
              </p>
              <p v-else>
                <strong>Внешний доступ:</strong> 
                <span class="status-disabled">Выключен</span>
              </p>
            </div>
          </div>
          
          <div v-if="isAdmin" class="admin-actions">
            <h3>Функционал администратора</h3>
            <div class="admin-buttons">
              <button 
                @click="goToTokenAnalysis" 
                class="admin-btn btn-primary"
              >
                🔍 Проверка токена
              </button>
              <button 
                @click="goToAccessControl" 
                class="admin-btn btn-secondary"
              >
                ⚙️ Администрирование
              </button>
            </div>
          </div>
        </div>
        
        <!-- Fallback: если ничего не подошло -->
        <div v-else class="no-auth fallback">
          <div class="info-message">
            <h2>ℹ️ Информация</h2>
            <p>Состояние приложения:</p>
            <ul>
              <li>Авторизован: {{ userStore.isAuthenticated ? 'да' : 'нет' }}</li>
              <li>Внешний доступ: {{ userStore.externalAccessEnabled ? 'включен' : 'выключен' }}</li>
              <li>Пользователь: {{ user ? 'загружен' : 'не загружен' }}</li>
              <li>Загрузка: {{ userStore.loading ? 'в процессе' : 'завершена' }}</li>
              <li>Ошибка: {{ userStore.error || 'нет' }}</li>
            </ul>
            <p v-if="isDevMode" class="dev-info">
              <strong>Development режим:</strong> Это fallback блок. Проверьте логи в консоли.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/stores/userStore';
import { showSuccess, showError } from '@/utils/bitrix24';
import Logger from '@/utils/logger';

const router = useRouter();
const userStore = useUserStore();

const user = computed(() => userStore.currentUser);
const isAdmin = computed(() => userStore.isAdminUser);
const departments = computed(() => userStore.departments || []);

const userFullName = computed(() => {
  if (!user.value) return '';
  const name = user.value.NAME || '';
  const lastName = user.value.LAST_NAME || '';
  return `${name} ${lastName}`.trim() || 'Пользователь';
});

const isCurrentUserToken = computed(() => {
  const params = new URLSearchParams(window.location.search);
  return (params.has('AUTH_ID') || params.has('APP_SID')) && params.has('DOMAIN');
});

const domain = computed(() => {
  const params = new URLSearchParams(window.location.search);
  return params.get('DOMAIN') || 'не указан';
});

const hasAuthId = computed(() => {
  const params = new URLSearchParams(window.location.search);
  return params.has('AUTH_ID') || params.has('APP_SID');
});

const hasDomain = computed(() => {
  const params = new URLSearchParams(window.location.search);
  return params.has('DOMAIN');
});

const isDevMode = computed(() => {
  return import.meta.env.DEV;
});

const authStatusClass = computed(() => {
  if (userStore.externalAccessEnabled && !userStore.isAuthenticated) {
    return 'status-external';
  }
  return userStore.isAuthenticated ? 'status-authenticated' : 'status-not-authenticated';
});

const authStatusText = computed(() => {
  if (userStore.externalAccessEnabled && !userStore.isAuthenticated) {
    return 'Внешний доступ (без авторизации Bitrix24)';
  }
  return userStore.isAuthenticated ? 'Авторизован' : 'Не авторизован';
});

const goToTokenAnalysis = () => {
  const params = new URLSearchParams(window.location.search);
  const authId = params.get('AUTH_ID') || params.get('APP_SID');
  const domain = params.get('DOMAIN');
  
  if (authId && domain) {
    router.push({
      path: '/token-analysis',
      query: {
        AUTH_ID: authId,
        DOMAIN: domain,
        ...Object.fromEntries(params.entries())
      }
    });
  } else {
    router.push('/token-analysis');
  }
};

const goToAccessControl = () => {
  const params = new URLSearchParams(window.location.search);
  const authId = params.get('AUTH_ID') || params.get('APP_SID');
  const domain = params.get('DOMAIN');
  
  if (authId && domain) {
    router.push({
      path: '/access-control',
      query: {
        AUTH_ID: authId,
        DOMAIN: domain,
        ...Object.fromEntries(params.entries())
      }
    });
  } else {
    router.push('/access-control');
  }
};

onMounted(async () => {
  Logger.info('VUE_LIFECYCLE', 'IndexPage mounted, fetching user data...');
  Logger.debug('VUE_LIFECYCLE', 'Initial store state', {
    isAdmin: userStore.isAdmin,
    isAdminUser: userStore.isAdminUser,
    currentUser: userStore.currentUser,
    isAuthenticated: userStore.isAuthenticated,
    externalAccessEnabled: userStore.externalAccessEnabled,
    loading: userStore.loading,
    error: userStore.error
  });
  try {
    await userStore.fetchCurrentUser();
    Logger.debug('VUE_LIFECYCLE', 'User data loaded', userStore.currentUser);
    Logger.debug('VUE_LIFECYCLE', 'Admin status after fetch', {
      isAdmin: userStore.isAdmin,
      isAdminUser: userStore.isAdminUser,
      userAdminField: userStore.currentUser?.ADMIN,
      userIsAdminField: userStore.currentUser?.IS_ADMIN
    });
    Logger.debug('VUE_LIFECYCLE', 'Auth status after fetch', {
      isAuthenticated: userStore.isAuthenticated,
      externalAccessEnabled: userStore.externalAccessEnabled,
      hasUser: !!userStore.currentUser,
      loading: userStore.loading,
      error: userStore.error
    });
  } catch (err) {
    Logger.error('ERROR', 'Ошибка загрузки пользователя', err);
    Logger.error('ERROR', 'Error details', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status
    });
    // Не показываем ошибку, если это 401 - это нормально
    if (err.response?.status !== 401) {
      showError(err.message || 'Ошибка загрузки данных пользователя');
    }
  }
});
</script>

<style scoped>
.index-page {
  min-height: 100vh;
  padding: 20px;
}

.loading {
  text-align: center;
  padding: 40px;
  color: var(--text-secondary);
}

.error {
  color: var(--error-color);
  padding: 20px;
  background: #fee;
  border-radius: 6px;
}

.user-header {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-bottom: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--border-color);
}

.user-photo {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}

.user-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-details h2 {
  margin: 0 0 8px 0;
  color: var(--primary-color);
}

.user-id {
  margin: 5px 0;
  color: var(--text-secondary);
  font-size: 14px;
}

.user-status {
  margin: 5px 0;
  font-weight: 500;
}

.user-status.admin {
  color: var(--success-color);
}

.user-data {
  margin: 20px 0;
}

.user-data p {
  margin: 10px 0;
  color: var(--text-primary);
}

.departments {
  margin: 10px 0;
}

.departments ul {
  list-style: none;
  padding-left: 20px;
  margin: 5px 0;
}

.departments li {
  margin: 5px 0;
  color: var(--text-secondary);
}

.status-message {
  margin-top: 20px;
  padding: 15px;
  border-radius: 6px;
}

.status-message.success {
  background: #f0fdf4;
  border: 1px solid var(--success-color);
  color: #166534;
}

.status-message p {
  margin: 5px 0;
}

.token-info {
  font-size: 14px;
  margin-top: 10px;
}

.token-info.warning {
  color: var(--warning-color);
}

.auth-info-section {
  margin-top: 30px;
  padding: 20px;
  background: #f9fafb;
  border-radius: 6px;
  border: 1px solid var(--border-color);
}

.auth-info-section h3 {
  margin: 0 0 15px 0;
  color: var(--primary-color);
  font-size: 18px;
}

.auth-info p {
  margin: 10px 0;
  color: var(--text-primary);
}

.status-authenticated {
  color: var(--success-color);
  font-weight: 600;
}

.status-not-authenticated {
  color: var(--error-color);
  font-weight: 600;
}

.status-external {
  color: var(--warning-color);
  font-weight: 600;
}

.status-enabled {
  color: var(--success-color);
  font-weight: 500;
}

.status-disabled {
  color: var(--text-secondary);
  font-weight: 500;
}

.admin-actions {
  margin-top: 30px;
  padding: 20px;
  background: #f0f9ff;
  border-radius: 6px;
  border: 1px solid var(--primary-color);
}

.admin-actions h3 {
  margin: 0 0 15px 0;
  color: var(--primary-color);
  font-size: 18px;
}

.admin-buttons {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.admin-btn {
  padding: 12px 24px;
  border: none;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  display: inline-block;
}

.admin-btn.btn-primary {
  background: var(--primary-color);
  color: white;
}

.admin-btn.btn-primary:hover {
  background: #0056b3;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.admin-btn.btn-secondary {
  background: #6c757d;
  color: white;
}

.admin-btn.btn-secondary:hover {
  background: #545b62;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>

