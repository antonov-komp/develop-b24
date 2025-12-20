<template>
  <div class="access-control-page">
    <div class="container">
      <div class="card">
        <h1>Управление правами доступа</h1>
        
        <div v-if="store.loading" class="loading">
          Загрузка данных...
        </div>
        
        <div v-else-if="store.error" class="error">
          {{ store.error }}
        </div>
        
        <div v-else>
          <!-- Переключатель включения/выключения -->
          <div class="toggle-section">
            <label>
              <input 
                type="checkbox" 
                v-model="enabled" 
                @change="toggleEnabled"
                :disabled="saving"
              />
              Включить проверку прав доступа
            </label>
          </div>
          
          <!-- Отделы с доступом -->
          <div class="departments-section">
            <h2>Отделы с доступом</h2>
            <div v-if="store.enabledDepartments.length > 0" class="list">
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
                >
                  Удалить
                </button>
              </div>
            </div>
            <p v-else class="empty">Нет отделов с доступом</p>
            
            <div class="add-form">
              <input 
                v-model="newDepartmentId" 
                type="number" 
                placeholder="ID отдела"
                :disabled="saving"
              />
              <input 
                v-model="newDepartmentName" 
                type="text" 
                placeholder="Название отдела"
                :disabled="saving"
              />
              <button 
                @click="addDepartment"
                :disabled="saving || !newDepartmentId || !newDepartmentName"
                class="btn btn-primary"
              >
                Добавить отдел
              </button>
            </div>
          </div>
          
          <!-- Пользователи с доступом -->
          <div class="users-section">
            <h2>Пользователи с доступом</h2>
            <div v-if="store.enabledUsers.length > 0" class="list">
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
                >
                  Удалить
                </button>
              </div>
            </div>
            <p v-else class="empty">Нет пользователей с доступом</p>
            
            <div class="add-form">
              <input 
                v-model="newUserId" 
                type="number" 
                placeholder="ID пользователя"
                :disabled="saving"
              />
              <input 
                v-model="newUserName" 
                type="text" 
                placeholder="Имя пользователя"
                :disabled="saving"
              />
              <input 
                v-model="newUserEmail" 
                type="email" 
                placeholder="Email (опционально)"
                :disabled="saving"
              />
              <button 
                @click="addUser"
                :disabled="saving || !newUserId || !newUserName"
                class="btn btn-primary"
              >
                Добавить пользователя
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
import { useAccessControlStore } from '@/stores/accessControlStore';
import { showSuccess, showError } from '@/utils/bitrix24';

const store = useAccessControlStore();
const saving = ref(false);

const enabled = computed({
  get: () => store.isEnabled,
  set: (value) => {
    // Будет реализовано через API
  }
});

const newDepartmentId = ref('');
const newDepartmentName = ref('');
const newUserId = ref('');
const newUserName = ref('');
const newUserEmail = ref('');

onMounted(async () => {
  try {
    await store.fetchConfig();
  } catch (err) {
    console.error('Ошибка загрузки конфигурации:', err);
  }
});

async function toggleEnabled() {
  // TODO: Реализовать через API
  console.log('Toggle enabled:', enabled.value);
}

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
  if (!confirm('Удалить отдел из списка доступа?')) return;
  
  saving.value = true;
  try {
    await store.removeDepartment(id);
    showSuccess('Отдел успешно удален');
  } catch (err) {
    console.error('Ошибка удаления отдела:', err);
    showError(err.message || 'Ошибка удаления отдела');
  } finally {
    saving.value = false;
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
  if (!confirm('Удалить пользователя из списка доступа?')) return;
  
  saving.value = true;
  try {
    await store.removeUser(id);
    showSuccess('Пользователь успешно удален');
  } catch (err) {
    console.error('Ошибка удаления пользователя:', err);
    showError(err.message || 'Ошибка удаления пользователя');
  } finally {
    saving.value = false;
  }
}
</script>

<style scoped>
.access-control-page {
  min-height: 100vh;
  padding: 20px;
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
</style>

