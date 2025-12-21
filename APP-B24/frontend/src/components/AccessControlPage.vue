<template>
  <div class="access-control-page">
    <div class="container">
      <div class="card">
        <div class="page-header">
          <button @click="goBack" class="back-button">← Назад</button>
          <h1>Управление правами доступа</h1>
        </div>
        
        <div v-if="store.loading" class="loading">
          Загрузка данных...
        </div>
        
        <div v-else-if="store.error" class="error">
          {{ store.error }}
        </div>
        
        <div v-else>
          <!-- Переключатель включения/выключения -->
          <div class="toggle-section">
            <label class="toggle-label">
              <input 
                type="checkbox" 
                v-model="enabled" 
                :disabled="saving || loading"
                class="toggle-input"
              />
              <span class="toggle-slider" :class="{ 'disabled': saving || loading }"></span>
              <span class="toggle-text">
                Включить проверку прав доступа
                <span v-if="saving" class="saving-indicator">Сохранение...</span>
              </span>
            </label>
            <div v-if="toggleError" class="error-message">
              {{ toggleError }}
            </div>
          </div>
          
          <!-- Отделы с доступом -->
          <div class="departments-section">
            <h2>Отделы с доступом</h2>
            <transition-group name="list-item" tag="div" v-if="store.enabledDepartments.length > 0" class="list">
              <div 
                v-for="dept in store.enabledDepartments" 
                :key="dept.id"
                class="list-item"
              >
                <span>{{ dept.name }} (ID: {{ dept.id }})</span>
                <button 
                  @click="removeDepartment(dept.id)"
                  :disabled="saving"
                  class="btn btn-danger"
                  :class="{ 'loading': saving && removingDeptId === dept.id }"
                >
                  <span v-if="!(saving && removingDeptId === dept.id)">Удалить</span>
                  <span v-else>Удаление...</span>
                </button>
              </div>
            </transition-group>
            <p v-else class="empty">Нет отделов с доступом</p>
            
            <div class="add-form">
              <input 
                v-model="newDepartmentId" 
                type="number" 
                placeholder="ID отдела"
                :disabled="saving"
                @keyup.enter="addDepartment"
                class="form-input"
              />
              <input 
                v-model="newDepartmentName" 
                type="text" 
                placeholder="Название отдела"
                :disabled="saving"
                @keyup.enter="addDepartment"
                class="form-input"
              />
              <button 
                @click="addDepartment"
                :disabled="saving || !newDepartmentId || !newDepartmentName"
                class="btn btn-primary"
                :class="{ 'loading': saving }"
                @keyup.enter="addDepartment"
              >
                <span v-if="!saving">Добавить отдел</span>
                <span v-else>Добавление...</span>
              </button>
            </div>
          </div>
          
          <!-- Пользователи с доступом -->
          <div class="users-section">
            <h2>Пользователи с доступом</h2>
            <transition-group name="list-item" tag="div" v-if="store.enabledUsers.length > 0" class="list">
              <div 
                v-for="user in store.enabledUsers" 
                :key="user.id"
                class="list-item"
              >
                <span>{{ user.name }} (ID: {{ user.id }})</span>
                <button 
                  @click="removeUser(user.id)"
                  :disabled="saving"
                  class="btn btn-danger"
                  :class="{ 'loading': saving && removingUserId === user.id }"
                >
                  <span v-if="!(saving && removingUserId === user.id)">Удалить</span>
                  <span v-else>Удаление...</span>
                </button>
              </div>
            </transition-group>
            <p v-else class="empty">Нет пользователей с доступом</p>
            
            <div class="add-form">
              <input 
                v-model="newUserId" 
                type="number" 
                placeholder="ID пользователя"
                :disabled="saving"
                @keyup.enter="addUser"
                class="form-input"
              />
              <input 
                v-model="newUserName" 
                type="text" 
                placeholder="Имя пользователя"
                :disabled="saving"
                @keyup.enter="addUser"
                class="form-input"
              />
              <input 
                v-model="newUserEmail" 
                type="email" 
                placeholder="Email (опционально)"
                :disabled="saving"
                @keyup.enter="addUser"
                class="form-input"
              />
              <button 
                @click="addUser"
                :disabled="saving || !newUserId || !newUserName"
                class="btn btn-primary"
                :class="{ 'loading': saving }"
                @keyup.enter="addUser"
              >
                <span v-if="!saving">Добавить пользователя</span>
                <span v-else>Добавление...</span>
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
import { useAccessControlStore } from '@/stores/accessControlStore';
import { showSuccess, showError } from '@/utils/bitrix24';

const router = useRouter();
const store = useAccessControlStore();
const saving = ref(false);
const loading = ref(false);
const toggleError = ref(null);
const removingDeptId = ref(null);
const removingUserId = ref(null);

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

const enabled = computed({
  get: () => store.isEnabled,
  set: async (value) => {
    saving.value = true;
    toggleError.value = null;
    
    try {
      await store.toggleEnabled(value);
      showSuccess(value ? 'Проверка прав доступа включена' : 'Проверка прав доступа выключена');
    } catch (err) {
      console.error('Ошибка переключения:', err);
      toggleError.value = err.message || 'Ошибка переключения проверки';
      showError(toggleError.value);
      // Откатываем значение
      await store.fetchConfig();
    } finally {
      saving.value = false;
    }
  }
});

const newDepartmentId = ref('');
const newDepartmentName = ref('');
const newUserId = ref('');
const newUserName = ref('');
const newUserEmail = ref('');

onMounted(async () => {
  loading.value = true;
  try {
    await store.fetchConfig();
  } catch (err) {
    console.error('Ошибка загрузки конфигурации:', err);
    showError(err.message || 'Ошибка загрузки конфигурации');
  } finally {
    loading.value = false;
  }
});

async function addDepartment() {
  if (!newDepartmentId.value || !newDepartmentName.value) return;
  
  saving.value = true;
  try {
    await store.addDepartment(
      parseInt(newDepartmentId.value),
      newDepartmentName.value
    );
    newDepartmentId.value = '';
    newDepartmentName.value = '';
    showSuccess('Отдел успешно добавлен');
  } catch (err) {
    console.error('Ошибка добавления отдела:', err);
    showError(err.message || 'Ошибка добавления отдела');
  } finally {
    saving.value = false;
  }
}

async function removeDepartment(id) {
  if (!confirm('Вы уверены, что хотите удалить этот отдел из списка доступа?')) {
    return;
  }
  
  removingDeptId.value = id;
  saving.value = true;
  
  try {
    await store.removeDepartment(id);
    showSuccess('Отдел успешно удален');
  } catch (err) {
    console.error('Ошибка удаления отдела:', err);
    showError(err.message || 'Ошибка удаления отдела');
  } finally {
    saving.value = false;
    removingDeptId.value = null;
  }
}

async function addUser() {
  if (!newUserId.value || !newUserName.value) return;
  
  saving.value = true;
  try {
    await store.addUser(
      parseInt(newUserId.value),
      newUserName.value,
      newUserEmail.value || null
    );
    newUserId.value = '';
    newUserName.value = '';
    newUserEmail.value = '';
    showSuccess('Пользователь успешно добавлен');
  } catch (err) {
    console.error('Ошибка добавления пользователя:', err);
    showError(err.message || 'Ошибка добавления пользователя');
  } finally {
    saving.value = false;
  }
}

async function removeUser(id) {
  if (!confirm('Вы уверены, что хотите удалить этого пользователя из списка доступа?')) {
    return;
  }
  
  removingUserId.value = id;
  saving.value = true;
  
  try {
    await store.removeUser(id);
    showSuccess('Пользователь успешно удален');
  } catch (err) {
    console.error('Ошибка удаления пользователя:', err);
    showError(err.message || 'Ошибка удаления пользователя');
  } finally {
    saving.value = false;
    removingUserId.value = null;
  }
}
</script>

<style scoped>
.access-control-page {
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

.toggle-section {
  margin: 20px 0;
  padding: 15px;
  background: var(--bg-secondary);
  border-radius: 6px;
}

.toggle-section label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.departments-section,
.users-section {
  margin: 30px 0;
  padding: 20px;
  border: 1px solid var(--border-color);
  border-radius: 6px;
}

.departments-section h2,
.users-section h2 {
  margin-bottom: 15px;
  color: var(--primary-color);
}

.list {
  margin: 15px 0;
}

.list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 15px;
  margin: 5px 0;
  background: var(--bg-secondary);
  border-radius: 4px;
}

.btn-danger {
  background: var(--error-color);
  color: white;
}

.btn-danger:hover {
  background: #dc2626;
}

.add-form {
  display: flex;
  gap: 10px;
  margin-top: 15px;
  flex-wrap: wrap;
}

.add-form input {
  flex: 1;
  min-width: 150px;
  padding: 8px 12px;
  border: 1px solid var(--border-color);
  border-radius: 4px;
}

.empty {
  color: var(--text-secondary);
  font-style: italic;
  margin: 15px 0;
}

/* Анимации для списка */
.list-item-enter-active,
.list-item-leave-active {
  transition: all 0.3s ease;
}

.list-item-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.list-item-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* Индикатор загрузки */
.loading {
  opacity: 0.6;
  cursor: wait;
}

.saving-indicator {
  color: var(--primary-color);
  font-size: 0.9em;
  margin-left: 10px;
}

/* Улучшенный toggle */
.toggle-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
}

.toggle-input {
  display: none;
}

.toggle-slider {
  position: relative;
  width: 50px;
  height: 24px;
  background-color: #ccc;
  border-radius: 24px;
  transition: background-color 0.3s;
}

.toggle-slider::before {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background-color: white;
  top: 2px;
  left: 2px;
  transition: transform 0.3s;
}

.toggle-input:checked + .toggle-slider {
  background-color: var(--primary-color, #007bff);
}

.toggle-input:checked + .toggle-slider::before {
  transform: translateX(26px);
}

.toggle-slider.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.error-message {
  margin-top: 10px;
  padding: 10px;
  background-color: #f8d7da;
  color: #721c24;
  border-radius: 4px;
  font-size: 0.9em;
}

.form-input {
  flex: 1;
  min-width: 150px;
  padding: 8px 12px;
  border: 1px solid var(--border-color);
  border-radius: 4px;
}
</style>

