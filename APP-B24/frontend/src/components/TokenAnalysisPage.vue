<template>
  <div class="token-analysis-page">
    <div class="container">
      <div class="card">
        <div class="page-header">
          <button @click="goBack" class="back-button">← Назад</button>
          <h1>Анализ токена</h1>
        </div>
        
        <div v-if="loading" class="loading">
          Загрузка данных...
        </div>
        
        <div v-else-if="error" class="error">
          {{ error }}
        </div>
        
        <div v-else-if="analysis" class="analysis">
          <!-- Информация о пользователе -->
          <div class="section">
            <h2>Информация о пользователе</h2>
            <div class="info-grid">
              <div><strong>ID:</strong> {{ analysis.user?.ID }}</div>
              <div><strong>Имя:</strong> {{ userFullName }}</div>
              <div><strong>Email:</strong> {{ analysis.user?.EMAIL || 'не указан' }}</div>
              <div><strong>Статус:</strong> 
                <span :class="{ 'admin': analysis.isAdmin }">
                  {{ analysis.isAdmin ? 'Администратор' : 'Пользователь' }}
                </span>
              </div>
            </div>
          </div>
          
          <!-- Права доступа -->
          <div class="section">
            <h2>Права доступа</h2>
            <div class="info-grid">
              <div><strong>Имеет доступ:</strong> 
                <span :class="{ 'yes': analysis.hasAccess, 'no': !analysis.hasAccess }">
                  {{ analysis.hasAccess ? 'Да' : 'Нет' }}
                </span>
              </div>
              <div><strong>Является администратором:</strong> 
                <span :class="{ 'yes': analysis.isAdmin, 'no': !analysis.isAdmin }">
                  {{ analysis.isAdmin ? 'Да' : 'Нет' }}
                </span>
              </div>
            </div>
          </div>
          
          <!-- Отделы -->
          <div v-if="analysis.departments && analysis.departments.length > 0" class="section">
            <h2>Отделы пользователя</h2>
            <ul>
              <li v-for="dept in analysis.departments" :key="dept">
                ID: {{ dept }}
              </li>
            </ul>
          </div>
          
          <!-- Конфигурация доступа -->
          <div class="section">
            <h2>Конфигурация доступа</h2>
            <div class="config-info">
              <p><strong>Проверка включена:</strong> 
                {{ analysis.accessConfig?.access_control?.enabled ? 'Да' : 'Нет' }}
              </p>
              <p><strong>Отделов с доступом:</strong> 
                {{ analysis.accessConfig?.access_control?.departments?.length || 0 }}
              </p>
              <p><strong>Пользователей с доступом:</strong> 
                {{ analysis.accessConfig?.access_control?.users?.length || 0 }}
              </p>
            </div>
          </div>
          
          <!-- JSON вывод -->
          <div class="section">
            <h2>Полные данные (JSON)</h2>
            <div class="json-output">
              <pre>{{ jsonOutput }}</pre>
              <button @click="copyToClipboard" class="btn btn-primary">
                Копировать JSON
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { bitrix24Api } from '@/services/bitrix24Api';

const router = useRouter();
const loading = ref(true);
const error = ref(null);
const analysis = ref(null);

const goBack = () => {
  const params = new URLSearchParams(window.location.search);
  const authId = params.get('AUTH_ID') || params.get('APP_SID');
  const domain = params.get('DOMAIN');
  
  if (authId && domain) {
    router.push({
      path: '/',
      query: {
        AUTH_ID: authId,
        DOMAIN: domain,
        ...Object.fromEntries(params.entries())
      }
    });
  } else {
    router.push('/');
  }
};

const userFullName = computed(() => {
  if (!analysis.value?.user) return '';
  const name = analysis.value.user.NAME || '';
  const lastName = analysis.value.user.LAST_NAME || '';
  return `${name} ${lastName}`.trim() || 'Пользователь';
});

const jsonOutput = computed(() => {
  if (!analysis.value) return '';
  return JSON.stringify(analysis.value, null, 2);
});

onMounted(async () => {
  try {
    loading.value = true;
    error.value = null;
    const response = await bitrix24Api.analyzeToken();
    
    if (response.success && response.data) {
      analysis.value = response.data;
    } else {
      throw new Error(response.message || 'Failed to analyze token');
    }
  } catch (err) {
    error.value = err.response?.data?.message || err.message || 'Ошибка анализа токена';
    console.error('Ошибка анализа токена:', err);
  } finally {
    loading.value = false;
  }
});

function copyToClipboard() {
  navigator.clipboard.writeText(jsonOutput.value).then(() => {
    alert('JSON скопирован в буфер обмена');
  }).catch(err => {
    console.error('Ошибка копирования:', err);
  });
}
</script>

<style scoped>
.token-analysis-page {
  min-height: 100vh;
  padding: 20px;
}

.page-header {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}

.page-header h1 {
  margin: 0;
  flex: 1;
}

.back-button {
  padding: 8px 16px;
  background: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  display: inline-block;
}

.back-button:hover {
  background: #545b62;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.section {
  margin: 25px 0;
  padding: 20px;
  background: var(--bg-secondary);
  border-radius: 6px;
}

.section h2 {
  margin-bottom: 15px;
  color: var(--primary-color);
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 10px;
}

.info-grid div {
  padding: 8px;
}

.admin {
  color: var(--success-color);
  font-weight: 600;
}

.yes {
  color: var(--success-color);
  font-weight: 600;
}

.no {
  color: var(--error-color);
  font-weight: 600;
}

.config-info p {
  margin: 10px 0;
}

.json-output {
  position: relative;
}

.json-output pre {
  background: #1e1e1e;
  color: #d4d4d4;
  padding: 15px;
  border-radius: 6px;
  overflow-x: auto;
  font-size: 13px;
  line-height: 1.5;
  max-height: 500px;
  overflow-y: auto;
}

.json-output button {
  margin-top: 10px;
}
</style>




