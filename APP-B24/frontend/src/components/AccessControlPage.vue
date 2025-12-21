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
            <div class="section-title-row">
              <h2>Отделы с доступом</h2>
              <span v-if="store.enabledDepartments.length > 0" class="badge-count">
                {{ store.enabledDepartments.length }}
              </span>
            </div>
            <transition-group name="list-item" tag="div" v-if="store.enabledDepartments.length > 0" class="list">
              <div 
                v-for="dept in store.enabledDepartments" 
                :key="dept.id"
                class="list-item"
              >
                <div class="list-item-content">
                  <span class="list-item-name">{{ dept.name }}</span>
                  <span class="list-item-id">ID: {{ dept.id }}</span>
                  <span v-if="dept.added_at" class="list-item-meta">
                    Добавлен: {{ formatDate(dept.added_at) }}
                  </span>
                </div>
                <button 
                  @click="removeDepartment(dept.id)"
                  :disabled="saving"
                  class="btn btn-danger btn-sm"
                  :class="{ 'loading': saving && removingDeptId === dept.id }"
                >
                  <span v-if="!(saving && removingDeptId === dept.id)">🗑️ Удалить</span>
                  <span v-else><span class="spinner">⟳</span> Удаление...</span>
                </button>
              </div>
            </transition-group>
            <div v-else class="empty-state">
              <p class="empty">Нет отделов с доступом</p>
              <p class="empty-hint">Добавьте отделы из списка ниже</p>
            </div>
            
            <!-- Multi-select для отделов -->
            <div class="multi-select-section">
              <div class="section-header">
                <label class="multi-select-label">Выберите отделы для добавления:</label>
                <div class="section-stats">
                  <span class="stat-item">
                    Всего: {{ store.availableDepartments.length }}
                  </span>
                  <span class="stat-item">
                    Доступно: {{ filteredDepartments.length }}
                  </span>
                  <span class="stat-item" v-if="selectedDepartments.length > 0">
                    Выбрано: <strong>{{ selectedDepartments.length }}</strong>
                  </span>
                </div>
              </div>
              
              <!-- Поиск отделов -->
              <div class="search-container">
                <div class="search-input-wrapper">
                  <span class="search-icon">🔍</span>
                  <input 
                    v-model="departmentSearch" 
                    type="text" 
                    placeholder="Поиск отделов по названию или ID..."
                    class="search-input"
                    :disabled="saving || store.loadingDepartments"
                    @input="filterDepartments"
                  />
                  <button 
                    v-if="departmentSearch"
                    @click="departmentSearch = ''"
                    class="clear-search-btn"
                    title="Очистить поиск"
                  >
                    ✕
                  </button>
                </div>
                <button 
                  @click="loadDepartments"
                  :disabled="saving || store.loadingDepartments"
                  class="btn btn-secondary refresh-btn"
                  title="Обновить список отделов"
                >
                  <span v-if="!store.loadingDepartments">🔄</span>
                  <span v-else class="spinner">⟳</span>
                </button>
              </div>
              
              <!-- Индикатор загрузки -->
              <div v-if="store.loadingDepartments" class="loading-indicator">
                <span class="spinner">⟳</span> Загрузка отделов из Bitrix24...
              </div>
              
              <!-- Быстрые действия -->
              <div v-if="!store.loadingDepartments && filteredDepartments.length > 0" class="quick-actions">
                <button 
                  @click="selectAllDepartments"
                  :disabled="saving || filteredDepartments.length === 0"
                  class="btn btn-link"
                  v-if="selectedDepartments.length < filteredDepartments.length"
                >
                  Выбрать все ({{ filteredDepartments.length }})
                </button>
                <button 
                  @click="selectedDepartments = []"
                  :disabled="saving || selectedDepartments.length === 0"
                  class="btn btn-link"
                  v-if="selectedDepartments.length > 0"
                >
                  Снять выбор
                </button>
              </div>
              
              <!-- Multi-select список -->
              <div class="multi-select-wrapper">
                <select 
                  v-model="selectedDepartments" 
                  multiple 
                  class="multi-select"
                  :disabled="saving || store.loadingDepartments"
                  size="8"
                >
                  <option 
                    v-for="dept in filteredDepartments" 
                    :key="dept.id" 
                    :value="dept.id"
                  >
                    {{ dept.name }} <span class="option-id">(ID: {{ dept.id }})</span>
                  </option>
                </select>
                <div v-if="filteredDepartments.length === 0 && !store.loadingDepartments" class="empty-select">
                  <p v-if="departmentSearch">Ничего не найдено по запросу "{{ departmentSearch }}"</p>
                  <p v-else>Все отделы уже добавлены</p>
                </div>
              </div>
              
              <div class="multi-select-info">
                <div v-if="selectedDepartments.length > 0" class="selected-preview">
                  <strong>Выбрано отделов: {{ selectedDepartments.length }}</strong>
                  <div class="selected-items">
                    <span 
                      v-for="deptId in selectedDepartments.slice(0, 3)" 
                      :key="deptId"
                      class="selected-badge"
                    >
                      {{ getDepartmentName(deptId) }}
                      <button 
                        @click="removeFromSelection('department', deptId)"
                        class="remove-badge-btn"
                        title="Убрать из выбора"
                      >×</button>
                    </span>
                    <span v-if="selectedDepartments.length > 3" class="selected-more">
                      +{{ selectedDepartments.length - 3 }} ещё
                    </span>
                  </div>
                </div>
                <span v-else class="hint">
                  💡 Используйте Ctrl/Cmd + клик для множественного выбора или кнопку "Выбрать все"
                </span>
              </div>
              
              <button 
                @click="addSelectedDepartments"
                :disabled="saving || selectedDepartments.length === 0 || store.loadingDepartments"
                class="btn btn-primary add-btn"
                :class="{ 'loading': saving }"
              >
                <span v-if="!saving">
                  ➕ Добавить выбранные отделы ({{ selectedDepartments.length }})
                </span>
                <span v-else>
                  <span class="spinner">⟳</span> Добавление...
                </span>
              </button>
            </div>
          </div>
          
          <!-- Пользователи с доступом -->
          <div class="users-section">
            <div class="section-title-row">
              <h2>Пользователи с доступом</h2>
              <span v-if="store.enabledUsers.length > 0" class="badge-count">
                {{ store.enabledUsers.length }}
              </span>
            </div>
            <transition-group name="list-item" tag="div" v-if="store.enabledUsers.length > 0" class="list">
              <div 
                v-for="user in store.enabledUsers" 
                :key="user.id"
                class="list-item"
              >
                <div class="list-item-content">
                  <span class="list-item-name">{{ user.name }}</span>
                  <span class="list-item-id">ID: {{ user.id }}</span>
                  <span v-if="user.email" class="list-item-email">📧 {{ user.email }}</span>
                  <span v-if="user.added_at" class="list-item-meta">
                    Добавлен: {{ formatDate(user.added_at) }}
                  </span>
                </div>
                <button 
                  @click="removeUser(user.id)"
                  :disabled="saving"
                  class="btn btn-danger btn-sm"
                  :class="{ 'loading': saving && removingUserId === user.id }"
                >
                  <span v-if="!(saving && removingUserId === user.id)">🗑️ Удалить</span>
                  <span v-else><span class="spinner">⟳</span> Удаление...</span>
                </button>
              </div>
            </transition-group>
            <div v-else class="empty-state">
              <p class="empty">Нет пользователей с доступом</p>
              <p class="empty-hint">Добавьте пользователей из списка ниже</p>
            </div>
            
            <!-- Multi-select для пользователей -->
            <div class="multi-select-section">
              <div class="section-header">
                <label class="multi-select-label">Выберите пользователей для добавления:</label>
                <div class="section-stats">
                  <span class="stat-item">
                    Всего: {{ store.availableUsers.length }}
                  </span>
                  <span class="stat-item">
                    Доступно: {{ filteredUsers.length }}
                  </span>
                  <span class="stat-item" v-if="selectedUsers.length > 0">
                    Выбрано: <strong>{{ selectedUsers.length }}</strong>
                  </span>
                </div>
              </div>
              
              <!-- Поиск пользователей -->
              <div class="search-container">
                <div class="search-input-wrapper">
                  <span class="search-icon">🔍</span>
                  <input 
                    v-model="userSearch" 
                    type="text" 
                    placeholder="Поиск пользователей по имени, email или ID..."
                    class="search-input"
                    :disabled="saving || store.loadingUsers"
                    @input="filterUsers"
                    @keyup.enter="loadUsers"
                  />
                  <button 
                    v-if="userSearch"
                    @click="userSearch = ''"
                    class="clear-search-btn"
                    title="Очистить поиск"
                  >
                    ✕
                  </button>
                </div>
                <button 
                  @click="loadUsers"
                  :disabled="saving || store.loadingUsers"
                  class="btn btn-secondary refresh-btn"
                  title="Обновить список пользователей"
                >
                  <span v-if="!store.loadingUsers">🔄</span>
                  <span v-else class="spinner">⟳</span>
                </button>
              </div>
              
              <!-- Индикатор загрузки -->
              <div v-if="store.loadingUsers" class="loading-indicator">
                <span class="spinner">⟳</span> Загрузка пользователей из Bitrix24...
              </div>
              
              <!-- Быстрые действия -->
              <div v-if="!store.loadingUsers && filteredUsers.length > 0" class="quick-actions">
                <button 
                  @click="selectAllUsers"
                  :disabled="saving || filteredUsers.length === 0"
                  class="btn btn-link"
                  v-if="selectedUsers.length < filteredUsers.length"
                >
                  Выбрать все ({{ filteredUsers.length }})
                </button>
                <button 
                  @click="selectedUsers = []"
                  :disabled="saving || selectedUsers.length === 0"
                  class="btn btn-link"
                  v-if="selectedUsers.length > 0"
                >
                  Снять выбор
                </button>
              </div>
              
              <!-- Multi-select список -->
              <div class="multi-select-wrapper">
                <select 
                  v-model="selectedUsers" 
                  multiple 
                  class="multi-select"
                  :disabled="saving || store.loadingUsers"
                  size="8"
                >
                  <option 
                    v-for="user in filteredUsers" 
                    :key="user.id" 
                    :value="user.id"
                  >
                    {{ user.name }} <span class="option-id">(ID: {{ user.id }})</span>
                    <span v-if="user.email" class="option-email"> - {{ user.email }}</span>
                  </option>
                </select>
                <div v-if="filteredUsers.length === 0 && !store.loadingUsers" class="empty-select">
                  <p v-if="userSearch">Ничего не найдено по запросу "{{ userSearch }}"</p>
                  <p v-else>Все пользователи уже добавлены</p>
                </div>
              </div>
              
              <div class="multi-select-info">
                <div v-if="selectedUsers.length > 0" class="selected-preview">
                  <strong>Выбрано пользователей: {{ selectedUsers.length }}</strong>
                  <div class="selected-items">
                    <span 
                      v-for="userId in selectedUsers.slice(0, 3)" 
                      :key="userId"
                      class="selected-badge"
                    >
                      {{ getUserName(userId) }}
                      <button 
                        @click="removeFromSelection('user', userId)"
                        class="remove-badge-btn"
                        title="Убрать из выбора"
                      >×</button>
                    </span>
                    <span v-if="selectedUsers.length > 3" class="selected-more">
                      +{{ selectedUsers.length - 3 }} ещё
                    </span>
                  </div>
                </div>
                <span v-else class="hint">
                  💡 Используйте Ctrl/Cmd + клик для множественного выбора или кнопку "Выбрать все"
                </span>
              </div>
              
              <button 
                @click="addSelectedUsers"
                :disabled="saving || selectedUsers.length === 0 || store.loadingUsers"
                class="btn btn-primary add-btn"
                :class="{ 'loading': saving }"
              >
                <span v-if="!saving">
                  ➕ Добавить выбранных пользователей ({{ selectedUsers.length }})
                </span>
                <span v-else>
                  <span class="spinner">⟳</span> Добавление...
                </span>
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

// Multi-select для отделов
const selectedDepartments = ref([]);
const departmentSearch = ref('');
const filteredDepartments = computed(() => {
  if (!departmentSearch.value) {
    // Исключаем уже добавленные отделы
    const enabledIds = new Set(store.enabledDepartments.map(d => d.id));
    return store.availableDepartments.filter(dept => !enabledIds.has(dept.id));
  }
  
  const searchLower = departmentSearch.value.toLowerCase();
  const enabledIds = new Set(store.enabledDepartments.map(d => d.id));
  return store.availableDepartments.filter(dept => 
    !enabledIds.has(dept.id) && 
    (dept.name.toLowerCase().includes(searchLower) || 
     dept.id.toString().includes(searchLower))
  );
});

// Multi-select для пользователей
const selectedUsers = ref([]);
const userSearch = ref('');
const filteredUsers = computed(() => {
  if (!userSearch.value) {
    // Исключаем уже добавленных пользователей
    const enabledIds = new Set(store.enabledUsers.map(u => u.id));
    return store.availableUsers.filter(user => !enabledIds.has(user.id));
  }
  
  const searchLower = userSearch.value.toLowerCase();
  const enabledIds = new Set(store.enabledUsers.map(u => u.id));
  return store.availableUsers.filter(user => 
    !enabledIds.has(user.id) && 
    (user.name.toLowerCase().includes(searchLower) || 
     user.id.toString().includes(searchLower) ||
     (user.email && user.email.toLowerCase().includes(searchLower)))
  );
});

onMounted(async () => {
  loading.value = true;
  try {
    await store.fetchConfig();
    // Загружаем списки отделов и пользователей
    await Promise.all([
      loadDepartments(),
      loadUsers()
    ]);
  } catch (err) {
    console.error('Ошибка загрузки конфигурации:', err);
    showError(err.message || 'Ошибка загрузки конфигурации');
  } finally {
    loading.value = false;
  }
});

async function loadDepartments() {
  try {
    await store.fetchDepartments();
  } catch (err) {
    console.error('Ошибка загрузки отделов:', err);
    showError(err.message || 'Ошибка загрузки списка отделов');
  }
}

async function loadUsers() {
  try {
    await store.fetchUsers(userSearch.value || null);
  } catch (err) {
    console.error('Ошибка загрузки пользователей:', err);
    showError(err.message || 'Ошибка загрузки списка пользователей');
  }
}

function filterDepartments() {
  // Фильтрация происходит через computed свойство
}

function filterUsers() {
  // Фильтрация происходит через computed свойство
  // При изменении поиска можно перезагрузить список с сервера
  if (userSearch.value.length >= 3) {
    loadUsers();
  } else if (userSearch.value.length === 0) {
    loadUsers();
  }
}

function selectAllDepartments() {
  selectedDepartments.value = filteredDepartments.value.map(d => d.id);
}

function selectAllUsers() {
  selectedUsers.value = filteredUsers.value.map(u => u.id);
}

function removeFromSelection(type, id) {
  if (type === 'department') {
    selectedDepartments.value = selectedDepartments.value.filter(dId => dId !== id);
  } else if (type === 'user') {
    selectedUsers.value = selectedUsers.value.filter(uId => uId !== id);
  }
}

function getDepartmentName(id) {
  const dept = store.availableDepartments.find(d => d.id === id);
  return dept ? dept.name : `ID: ${id}`;
}

function getUserName(id) {
  const user = store.availableUsers.find(u => u.id === id);
  return user ? user.name : `ID: ${id}`;
}

function formatDate(dateString) {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (e) {
    return dateString;
  }
}

async function addSelectedDepartments() {
  if (selectedDepartments.value.length === 0) return;
  
  saving.value = true;
  try {
    // Получаем данные выбранных отделов
    const departmentsToAdd = selectedDepartments.value.map(deptId => {
      const dept = store.availableDepartments.find(d => d.id === deptId);
      return dept ? { id: dept.id, name: dept.name } : null;
    }).filter(Boolean);
    
    if (departmentsToAdd.length === 0) {
      showError('Не удалось найти данные выбранных отделов');
      return;
    }
    
    const result = await store.addDepartments(departmentsToAdd);
    
    if (result.success) {
      selectedDepartments.value = [];
      departmentSearch.value = '';
      showSuccess(`Добавлено отделов: ${result.added}${result.skipped > 0 ? `, пропущено: ${result.skipped}` : ''}`);
    }
  } catch (err) {
    console.error('Ошибка добавления отделов:', err);
    showError(err.message || 'Ошибка добавления отделов');
  } finally {
    saving.value = false;
  }
}

async function addSelectedUsers() {
  if (selectedUsers.value.length === 0) return;
  
  saving.value = true;
  try {
    // Получаем данные выбранных пользователей
    const usersToAdd = selectedUsers.value.map(userId => {
      const user = store.availableUsers.find(u => u.id === userId);
      return user ? { id: user.id, name: user.name, email: user.email || null } : null;
    }).filter(Boolean);
    
    if (usersToAdd.length === 0) {
      showError('Не удалось найти данные выбранных пользователей');
      return;
    }
    
    const result = await store.addUsers(usersToAdd);
    
    if (result.success) {
      selectedUsers.value = [];
      userSearch.value = '';
      showSuccess(`Добавлено пользователей: ${result.added}${result.skipped > 0 ? `, пропущено: ${result.skipped}` : ''}`);
    }
  } catch (err) {
    console.error('Ошибка добавления пользователей:', err);
    showError(err.message || 'Ошибка добавления пользователей');
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
  border: 1px solid var(--border-color, #e0e0e0);
  border-radius: 8px;
  background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.section-title-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.departments-section h2,
.users-section h2 {
  margin: 0;
  color: var(--primary-color, #007bff);
  font-size: 20px;
  font-weight: 600;
}

.badge-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 8px;
  background: var(--primary-color, #007bff);
  color: white;
  border-radius: 14px;
  font-size: 0.85em;
  font-weight: 600;
}

.list {
  margin: 15px 0;
}

.list-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  margin: 6px 0;
  background: var(--bg-secondary, #f8f9fa);
  border: 1px solid var(--border-color, #e0e0e0);
  border-radius: 6px;
  transition: all 0.2s;
}

.list-item:hover {
  background: #f0f0f0;
  border-color: var(--primary-color, #007bff);
  transform: translateX(2px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.list-item-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  flex: 1;
}

.list-item-name {
  font-weight: 500;
  color: var(--text-color, #333);
  font-size: 15px;
}

.list-item-id {
  font-size: 0.85em;
  color: var(--text-secondary, #666);
}

.list-item-email {
  font-size: 0.9em;
  color: var(--text-secondary, #666);
}

.list-item-meta {
  font-size: 0.8em;
  color: var(--text-secondary, #999);
  font-style: italic;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.9em;
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

.empty-state {
  padding: 40px 20px;
  text-align: center;
  background: var(--bg-secondary, #f8f9fa);
  border-radius: 6px;
  border: 2px dashed var(--border-color, #ddd);
}

.empty {
  color: var(--text-secondary, #666);
  font-style: italic;
  margin: 10px 0;
  font-size: 16px;
}

.empty-hint {
  color: var(--text-secondary, #999);
  font-size: 0.9em;
  margin-top: 8px;
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

/* Multi-select секция */
.multi-select-section {
  margin-top: 20px;
  padding: 20px;
  background: var(--bg-secondary, #f8f9fa);
  border-radius: 8px;
  border: 1px solid var(--border-color, #e0e0e0);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  flex-wrap: wrap;
  gap: 10px;
}

.multi-select-label {
  display: block;
  font-weight: 600;
  color: var(--text-color, #333);
  font-size: 16px;
}

.section-stats {
  display: flex;
  gap: 15px;
  font-size: 0.9em;
  color: var(--text-secondary, #666);
}

.stat-item {
  padding: 4px 8px;
  background: white;
  border-radius: 4px;
  border: 1px solid var(--border-color, #e0e0e0);
}

.stat-item strong {
  color: var(--primary-color, #007bff);
  font-weight: 600;
}

.search-container {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
}

.search-input-wrapper {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 12px;
  color: var(--text-secondary, #999);
  font-size: 16px;
  pointer-events: none;
}

.search-input {
  flex: 1;
  padding: 10px 12px 10px 40px;
  border: 2px solid var(--border-color, #ddd);
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s;
  background: white;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color, #007bff);
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.search-input:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
}

.clear-search-btn {
  position: absolute;
  right: 8px;
  background: none;
  border: none;
  color: var(--text-secondary, #999);
  cursor: pointer;
  font-size: 18px;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.2s;
}

.clear-search-btn:hover {
  background: var(--bg-secondary, #f0f0f0);
  color: var(--text-color, #333);
}

.refresh-btn {
  padding: 10px 14px;
  min-width: 44px;
  height: 44px;
  background: var(--secondary-color, #6c757d);
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 18px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.refresh-btn:hover:not(:disabled) {
  background: var(--secondary-color-dark, #545b62);
  transform: rotate(180deg);
}

.refresh-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.spinner {
  display: inline-block;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.loading-indicator {
  padding: 12px;
  text-align: center;
  color: var(--text-secondary, #666);
  font-style: italic;
  background: white;
  border-radius: 6px;
  border: 1px dashed var(--border-color, #ddd);
  margin-bottom: 15px;
}

.loading-indicator .spinner {
  margin-right: 8px;
}

.quick-actions {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.btn-link {
  background: none;
  border: none;
  color: var(--primary-color, #007bff);
  cursor: pointer;
  padding: 6px 12px;
  font-size: 0.9em;
  text-decoration: underline;
  transition: color 0.2s;
}

.btn-link:hover:not(:disabled) {
  color: var(--primary-color-dark, #0056b3);
}

.btn-link:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.multi-select-wrapper {
  position: relative;
  margin-bottom: 15px;
}

.multi-select {
  width: 100%;
  min-height: 200px;
  max-height: 300px;
  padding: 8px;
  border: 2px solid var(--border-color, #ddd);
  border-radius: 6px;
  font-size: 14px;
  background: white;
  transition: all 0.2s;
}

.multi-select:focus {
  outline: none;
  border-color: var(--primary-color, #007bff);
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.multi-select:disabled {
  background: #f5f5f5;
  cursor: not-allowed;
  opacity: 0.6;
}

.multi-select option {
  padding: 8px 12px;
  border-bottom: 1px solid #f0f0f0;
  transition: background 0.2s;
}

.multi-select option:hover {
  background: #f8f9fa;
}

.multi-select option:checked {
  background: var(--primary-color, #007bff) linear-gradient(0deg, var(--primary-color, #007bff) 0%, var(--primary-color, #007bff) 100%);
  color: white;
  font-weight: 500;
}

.option-id {
  color: var(--text-secondary, #999);
  font-size: 0.9em;
}

.multi-select option:checked .option-id {
  color: rgba(255, 255, 255, 0.9);
}

.option-email {
  color: var(--text-secondary, #666);
  font-size: 0.9em;
}

.multi-select option:checked .option-email {
  color: rgba(255, 255, 255, 0.9);
}

.empty-select {
  padding: 40px 20px;
  text-align: center;
  color: var(--text-secondary, #999);
  font-style: italic;
  background: white;
  border: 2px dashed var(--border-color, #ddd);
  border-radius: 6px;
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.multi-select-info {
  margin-bottom: 15px;
  font-size: 0.9em;
  color: var(--text-secondary, #666);
}

.selected-preview {
  background: white;
  padding: 12px;
  border-radius: 6px;
  border: 1px solid var(--border-color, #e0e0e0);
}

.selected-preview strong {
  display: block;
  margin-bottom: 8px;
  color: var(--text-color, #333);
}

.selected-items {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}

.selected-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  background: var(--primary-color, #007bff);
  color: white;
  border-radius: 16px;
  font-size: 0.85em;
  font-weight: 500;
}

.remove-badge-btn {
  background: rgba(255, 255, 255, 0.3);
  border: none;
  color: white;
  cursor: pointer;
  font-size: 16px;
  line-height: 1;
  padding: 0;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.remove-badge-btn:hover {
  background: rgba(255, 255, 255, 0.5);
}

.selected-more {
  padding: 4px 10px;
  background: var(--bg-secondary, #f0f0f0);
  color: var(--text-secondary, #666);
  border-radius: 16px;
  font-size: 0.85em;
  font-style: italic;
}

.multi-select-info .hint {
  display: block;
  padding: 10px;
  background: #e7f3ff;
  border-left: 3px solid var(--primary-color, #007bff);
  border-radius: 4px;
  font-style: italic;
  color: var(--text-secondary, #666);
}

.add-btn {
  width: 100%;
  padding: 12px 20px;
  font-size: 16px;
  font-weight: 500;
  border-radius: 6px;
  transition: all 0.2s;
}

.add-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.add-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.add-btn .spinner {
  margin-right: 8px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .section-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .section-stats {
    width: 100%;
    justify-content: space-between;
  }
  
  .search-container {
    flex-direction: column;
  }
  
  .refresh-btn {
    width: 100%;
  }
  
  .multi-select {
    min-height: 150px;
    max-height: 200px;
  }
}
</style>

