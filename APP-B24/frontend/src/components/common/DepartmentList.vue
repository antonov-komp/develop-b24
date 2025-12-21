<template>
  <div class="department-list">
    <h3 v-if="title">{{ title }}</h3>
    <ul v-if="departments.length > 0">
      <li v-for="dept in departments" :key="dept.id">
        {{ dept.name }} (ID: {{ dept.id }})
      </li>
    </ul>
    <p v-else class="empty">Нет отделов</p>
  </div>
</template>

<script setup>
defineProps({
  departments: {
    type: Array,
    default: () => [],
  },
  title: {
    type: String,
    default: '',
  },
});
</script>

<style scoped>
.department-list {
  margin: 15px 0;
}

.department-list h3 {
  margin-bottom: 10px;
  color: var(--text-primary);
}

.department-list ul {
  list-style: none;
  padding: 0;
}

.department-list li {
  padding: 8px 12px;
  margin: 5px 0;
  background: var(--bg-secondary);
  border-radius: 4px;
  color: var(--text-primary);
}

.department-list .empty {
  color: var(--text-secondary);
  font-style: italic;
}
</style>


