<template>
  <div class="index-page">
    <div class="container">
      <div class="card">
        <h1>Добро пожаловать!</h1>
        
        <!-- Отладочная информация -->
        <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 4px; font-size: 12px;">
          <strong>Debug:</strong><br>
          Loading: {{ userStore.loading }}<br>
          Error: {{ userStore.error || 'none' }}<br>
          User: {{ user ? 'loaded' : 'not loaded' }}<br>
          User ID: {{ user?.ID || 'N/A' }}
        </div>
        
        <div v-if="userStore.loading" class="loading">
          Загрузка данных...
        </div>
        
        <div v-else-if="userStore.error" class="error">
          <strong>Ошибка:</strong> {{ userStore.error }}
          <br><small>Проверьте консоль для деталей</small>
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
          
          <!-- Информация о текущей авторизации -->
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
          
          <!-- Кнопки для администраторов -->
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/stores/userStore';
import { showSuccess, showError } from '@/utils/bitrix24';

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

// Проверка, используется ли токен текущего пользователя
const isCurrentUserToken = computed(() => {
  const params = new URLSearchParams(window.location.search);
  // Bitrix24 может передавать APP_SID вместо AUTH_ID
  return (params.has('AUTH_ID') || params.has('APP_SID')) && params.has('DOMAIN');
});

// Получение домена из URL или данных пользователя
const domain = computed(() => {
  const params = new URLSearchParams(window.location.search);
  return params.get('DOMAIN') || 'не указан';
});

// Информация о статусе авторизации
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

// Навигация для администраторов
const goToTokenAnalysis = () => {
  router.push('/token-analysis');
};

const goToAccessControl = () => {
  router.push('/access-control');
};

onMounted(async () => {
  console.log('IndexPage mounted, fetching user data...');
  try {
    await userStore.fetchCurrentUser();
    console.log('User data loaded:', userStore.currentUser);
    if (userStore.currentUser) {
      showSuccess('Данные пользователя загружены');
    }
  } catch (err) {
    console.error('Ошибка загрузки пользователя:', err);
    console.error('Error details:', {
      message: err.message,
      response: err.response?.data,
      status: err.response?.status
    });
    showError(err.message || 'Ошибка загрузки данных пользователя');
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

