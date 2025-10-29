<template>
  <div class="child-card card">
    <div class="card-header">
      <div class="avatar">
        <img v-if="child.avatar" :src="child.avatar" :alt="child.name" />
        <span v-else class="avatar-placeholder">{{ genderEmoji }}</span>
      </div>
      <div class="info">
        <h3 class="name">{{ child.name }}</h3>
        <p class="age">{{ child.age }}岁 {{ genderEmoji }}</p>
      </div>
    </div>

    <div class="star-display">
      <span class="star-icon">⭐</span>
      <span class="star-count">×{{ child.star_count }}</span>
    </div>

    <div class="actions">
      <button class="btn-action btn-view" @click="emit('view', child)" title="查看详情">
        👁️
      </button>
      <button class="btn-action btn-add" @click="emit('addStar', child)" title="加星星">
        ➕
      </button>
      <button class="btn-action btn-subtract" @click="emit('subtractStar', child)" title="减星星">
        ➖
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Child } from '@/types'
import { getGenderEmoji } from '@/utils/helpers'

interface Props {
  child: Child
}

interface Emits {
  (e: 'view', child: Child): void
  (e: 'addStar', child: Child): void
  (e: 'subtractStar', child: Child): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const genderEmoji = computed(() => getGenderEmoji(props.child.gender))
</script>

<style scoped>
.child-card {
  padding: 24px;
  transition: transform 0.3s, box-shadow 0.3s;
}

.child-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.16);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  font-size: 40px;
}

.info {
  flex: 1;
}

.name {
  font-size: 24px;
  font-weight: bold;
  margin: 0 0 4px 0;
  color: #333;
}

.age {
  font-size: 16px;
  color: #666;
  margin: 0;
}

.star-display {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  background: linear-gradient(135deg, #fff5e1 0%, #ffe4b5 100%);
  border-radius: 16px;
  margin-bottom: 20px;
}

.star-icon {
  font-size: 32px;
}

.star-count {
  font-size: 28px;
  font-weight: bold;
  color: #ff8c00;
}

.actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}

.btn-action {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  font-size: 24px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.btn-action:hover {
  transform: scale(1.1);
}

.btn-action:active {
  transform: scale(0.95);
}

.btn-view {
  background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
}

.btn-add {
  background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
}

.btn-subtract {
  background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
}

@media (max-width: 768px) {
  .card-header {
    flex-direction: column;
    text-align: center;
  }

  .info {
    display: flex;
    flex-direction: column;
    align-items: center;
  }
}
</style>
