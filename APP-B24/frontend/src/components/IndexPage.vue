<template>
  <div class="index-page">
    <div class="container">
      <div class="card">
        <h1>Добро пожаловать!</h1>
        
        <div v-if="userStore.loading" class="loading">
          Загрузка данных...
        </div>
        
        <div v-else-if="userStore.error" class="error">
          {{ userStore.error }}
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
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useUserStore } from '@/stores/userStore';
import { showSuccess, showError } from '@/utils/bitrix24';

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
  return params.has('AUTH_ID') && params.has('DOMAIN');
});

// Получение домена из URL или данных пользователя
const domain = computed(() => {
  const params = new URLSearchParams(window.location.search);
  return params.get('DOMAIN') || 'не указан';
});

onMounted(async () => {
  try {
    await userStore.fetchCurrentUser();
    showSuccess('Данные пользователя загружены');
  } catch (err) {
    console.error('Ошибка загрузки пользователя:', err);
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
</style>

