<template>
  <div class="user-card">
    <div v-if="user.PERSONAL_PHOTO" class="user-photo">
      <img :src="user.PERSONAL_PHOTO" :alt="userFullName" />
    </div>
    <div class="user-info">
      <h3>{{ userFullName }}</h3>
      <p v-if="user.EMAIL">Email: {{ user.EMAIL }}</p>
      <p>ID: #{{ user.ID }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
});

const userFullName = computed(() => {
  if (!props.user) return '';
  const name = props.user.NAME || '';
  const lastName = props.user.LAST_NAME || '';
  return `${name} ${lastName}`.trim() || 'Пользователь';
});
</script>

<style scoped>
.user-card {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 15px;
  background: var(--bg-primary);
  border-radius: 8px;
  border: 1px solid var(--border-color);
}

.user-photo {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}

.user-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-info h3 {
  margin: 0 0 5px 0;
  color: var(--text-primary);
}

.user-info p {
  margin: 3px 0;
  color: var(--text-secondary);
  font-size: 14px;
}
</style>





