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
          
          <!-- Детальный анализ токена -->
          <div v-if="analysis.tokenAnalysis" class="section">
            <h2>Детальный анализ токена авторизации</h2>
            <div class="info-grid">
              <div><strong>Тип токена:</strong> {{ getTokenTypeLabel(analysis.tokenAnalysis.type) }}</div>
              <div><strong>Длина токена:</strong> {{ analysis.tokenAnalysis.auth_id_length }} символов</div>
              <div><strong>Preview токена:</strong> <code>{{ analysis.tokenAnalysis.auth_id_preview }}</code></div>
              <div><strong>Домен:</strong> {{ analysis.tokenAnalysis.domain }}</div>
              <div v-if="analysis.tokenAnalysis.domain_region">
                <strong>Регион домена:</strong> 
                <span class="region-badge">{{ analysis.tokenAnalysis.domain_region.toUpperCase() }}</span>
              </div>
              <div v-if="analysis.tokenAnalysis.has_refresh_token">
                <strong>Refresh токен:</strong> 
                <span class="yes">Есть</span>
                <span v-if="analysis.tokenAnalysis.refresh_token_preview" class="token-preview">
                  ({{ analysis.tokenAnalysis.refresh_token_preview }})
                </span>
              </div>
              <div v-else>
                <strong>Refresh токен:</strong> <span class="no">Нет</span>
              </div>
              <div v-if="analysis.tokenAnalysis.expires_at">
                <strong>Истекает:</strong> {{ analysis.tokenAnalysis.expires_at }}
              </div>
              <div v-if="analysis.tokenAnalysis.is_expired !== null">
                <strong>Статус:</strong> 
                <span :class="{ 'yes': !analysis.tokenAnalysis.is_expired, 'no': analysis.tokenAnalysis.is_expired }">
                  {{ analysis.tokenAnalysis.is_expired ? 'Истёк' : 'Действителен' }}
                </span>
              </div>
              <div v-if="analysis.tokenAnalysis.time_until_expiry_formatted">
                <strong>Осталось времени:</strong> 
                <span class="time-remaining">{{ analysis.tokenAnalysis.time_until_expiry_formatted }}</span>
              </div>
            </div>
          </div>
          
          <!-- Настройки из settings.json (Local) -->
          <div v-if="analysis.localSettings" class="section">
            <h2>Настройки приложения (Local settings.json)</h2>
            <div class="info-grid">
              <div v-if="analysis.localSettings.domain">
                <strong>Домен (Local):</strong> {{ analysis.localSettings.domain }}
              </div>
              <div v-if="analysis.localSettings.client_endpoint">
                <strong>Client Endpoint:</strong> {{ analysis.localSettings.client_endpoint }}
              </div>
              <div>
                <strong>Access Token (Local):</strong> 
                <span :class="{ 'yes': analysis.localSettings.has_access_token, 'no': !analysis.localSettings.has_access_token }">
                  {{ analysis.localSettings.has_access_token ? 'Есть' : 'Нет' }}
                </span>
                <span v-if="analysis.localSettings.access_token_preview" class="token-preview">
                  ({{ analysis.localSettings.access_token_preview }})
                </span>
              </div>
              <div>
                <strong>Refresh Token (Local):</strong> 
                <span :class="{ 'yes': analysis.localSettings.has_refresh_token, 'no': !analysis.localSettings.has_refresh_token }">
                  {{ analysis.localSettings.has_refresh_token ? 'Есть' : 'Нет' }}
                </span>
                <span v-if="analysis.localSettings.refresh_token_preview" class="token-preview">
                  ({{ analysis.localSettings.refresh_token_preview }})
                </span>
              </div>
              <div>
                <strong>Client ID (Local):</strong> 
                <span :class="{ 'yes': analysis.localSettings.has_client_id, 'no': !analysis.localSettings.has_client_id }">
                  {{ analysis.localSettings.has_client_id ? 'Есть' : 'Нет' }}
                </span>
                <span v-if="analysis.localSettings.client_id_preview" class="token-preview">
                  ({{ analysis.localSettings.client_id_preview }})
                </span>
              </div>
              <div>
                <strong>Client Secret (Local):</strong> 
                <span :class="{ 'yes': analysis.localSettings.has_client_secret, 'no': !analysis.localSettings.has_client_secret }">
                  {{ analysis.localSettings.has_client_secret ? 'Есть' : 'Нет' }}
                </span>
              </div>
              <div>
                <strong>Application Token (Local):</strong> 
                <span :class="{ 'yes': analysis.localSettings.has_application_token, 'no': !analysis.localSettings.has_application_token }">
                  {{ analysis.localSettings.has_application_token ? 'Есть' : 'Нет' }}
                </span>
                <span v-if="analysis.localSettings.application_token_preview" class="token-preview">
                  ({{ analysis.localSettings.application_token_preview }})
                </span>
              </div>
              <div v-if="analysis.localSettings.scope">
                <strong>Scope:</strong> {{ analysis.localSettings.scope }}
              </div>
              <div v-if="analysis.localSettings.expires_in">
                <strong>Expires In:</strong> {{ analysis.localSettings.expires_in }} секунд
              </div>
              <div v-if="analysis.localSettings.last_updated">
                <strong>Последнее обновление:</strong> {{ analysis.localSettings.last_updated }}
              </div>
            </div>
          </div>
          
          <!-- Права доступа и доступные методы -->
          <div v-if="analysis.permissions" class="section">
            <h2>Права доступа и доступные методы API</h2>
            
            <!-- Текущие права доступа (scope) -->
            <div v-if="analysis.permissions.current_scope" class="permissions-section">
              <h3>Текущие права доступа (Scope)</h3>
              <div class="scope-list">
                <span 
                  v-for="scope in analysis.permissions.current_scope" 
                  :key="scope" 
                  class="scope-badge"
                >
                  {{ scope }}
                </span>
              </div>
              <p class="scope-count">
                Всего прав: {{ analysis.permissions.current_scope.length }}
              </p>
            </div>
            
            <!-- Все возможные права -->
            <div v-if="analysis.permissions.all_available_scope" class="permissions-section">
              <h3>Все возможные права доступа</h3>
              <div class="scope-list">
                <span 
                  v-for="scope in analysis.permissions.all_available_scope" 
                  :key="scope" 
                  class="scope-badge scope-available"
                >
                  {{ scope }}
                </span>
              </div>
              <p class="scope-count">
                Всего возможных прав: {{ analysis.permissions.all_available_scope.length }}
              </p>
            </div>
            
            <!-- Доступные методы -->
            <div v-if="analysis.permissions.available_methods_count !== null" class="permissions-section">
              <h3>Доступные методы API</h3>
              <p class="methods-count">
                <strong>Всего доступно методов:</strong> {{ analysis.permissions.available_methods_count }}
              </p>
              <div v-if="analysis.permissions.available_methods_preview && analysis.permissions.available_methods_preview.length > 0" class="methods-preview">
                <p><strong>Примеры доступных методов (первые 20):</strong></p>
                <ul class="methods-list">
                  <li v-for="method in analysis.permissions.available_methods_preview" :key="method">
                    <code>{{ method }}</code>
                  </li>
                </ul>
              </div>
            </div>
            
            <!-- Проверка ключевых методов -->
            <div v-if="analysis.permissions.tested_methods && Object.keys(analysis.permissions.tested_methods).length > 0" class="permissions-section">
              <h3>Проверка доступности ключевых методов</h3>
              <div class="methods-check-grid">
                <div 
                  v-for="(result, method) in analysis.permissions.tested_methods" 
                  :key="method"
                  class="method-check-item"
                >
                  <div class="method-name">
                    <code>{{ method }}</code>
                  </div>
                  <div class="method-status">
                    <span 
                      v-if="result.is_existing && result.is_available" 
                      class="status-badge status-available"
                    >
                      ✓ Доступен
                    </span>
                    <span 
                      v-else-if="result.is_existing && !result.is_available" 
                      class="status-badge status-restricted"
                    >
                      ⚠ Ограничен
                    </span>
                    <span 
                      v-else 
                      class="status-badge status-unavailable"
                    >
                      ✗ Недоступен
                    </span>
                  </div>
                  <div v-if="result.error" class="method-error">
                    <small>{{ result.error }}</small>
                  </div>
                </div>
              </div>
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

const getTokenTypeLabel = (type) => {
  const types = {
    'user_token': 'Токен пользователя',
    'installer_token': 'Токен установщика'
  };
  return types[type] || type;
};

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

.token-preview {
  font-size: 12px;
  color: #6c757d;
  font-family: monospace;
}

.region-badge {
  display: inline-block;
  padding: 2px 8px;
  background: var(--primary-color);
  color: white;
  border-radius: 3px;
  font-size: 12px;
  font-weight: 600;
}

.time-remaining {
  color: var(--success-color);
  font-weight: 600;
  font-family: monospace;
}

code {
  background: #f5f5f5;
  padding: 2px 6px;
  border-radius: 3px;
  font-family: monospace;
  font-size: 13px;
}

.permissions-section {
  margin: 20px 0;
  padding: 15px;
  background: #f9f9f9;
  border-radius: 6px;
  border-left: 4px solid var(--primary-color);
}

.permissions-section h3 {
  margin-top: 0;
  margin-bottom: 15px;
  color: var(--primary-color);
  font-size: 16px;
}

.scope-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 10px;
}

.scope-badge {
  display: inline-block;
  padding: 4px 12px;
  background: var(--primary-color);
  color: white;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.scope-badge.scope-available {
  background: #6c757d;
  opacity: 0.7;
}

.scope-count,
.methods-count {
  margin: 10px 0 0 0;
  font-size: 14px;
  color: #666;
}

.methods-preview {
  margin-top: 15px;
}

.methods-list {
  list-style: none;
  padding: 0;
  margin: 10px 0;
  max-height: 200px;
  overflow-y: auto;
  background: white;
  padding: 10px;
  border-radius: 4px;
}

.methods-list li {
  padding: 4px 0;
  border-bottom: 1px solid #eee;
}

.methods-list li:last-child {
  border-bottom: none;
}

.methods-check-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 10px;
  margin-top: 15px;
}

.method-check-item {
  padding: 10px;
  background: white;
  border-radius: 4px;
  border: 1px solid #ddd;
}

.method-name {
  margin-bottom: 8px;
  font-weight: 500;
}

.method-status {
  margin-bottom: 5px;
}

.status-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 3px;
  font-size: 12px;
  font-weight: 600;
}

.status-available {
  background: #d4edda;
  color: #155724;
}

.status-restricted {
  background: #fff3cd;
  color: #856404;
}

.status-unavailable {
  background: #f8d7da;
  color: #721c24;
}

.method-error {
  margin-top: 5px;
  color: #dc3545;
  font-size: 11px;
}
</style>




