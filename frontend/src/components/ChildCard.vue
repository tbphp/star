<template>
  <div class="child-card card" @click="handleCardClick">
    <div class="avatar">
      <img v-if="child.avatar" :src="child.avatar" :alt="child.name" />
      <span v-else class="avatar-placeholder">{{ genderEmoji }}</span>
    </div>

    <h3 class="name">{{ child.name }}</h3>

    <div class="star-display">
      <span class="star-icon">⭐</span>
      <span class="star-count">×{{ child.star_count }}</span>
    </div>

    <div class="actions" @click.stop>
      <button
        class="btn-action btn-add"
        @click="emit('addStar', child)"
        title="加星星"
      >
        ➕
      </button>
      <button
        class="btn-action btn-subtract"
        @click="emit('subtractStar', child)"
        title="减星星"
      >
        ➖
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { Child } from "@/types";
import { getGenderEmoji } from "@/utils/helpers";

interface Props {
  child: Child;
}

interface Emits {
  (e: "view", child: Child): void;
  (e: "addStar", child: Child): void;
  (e: "subtractStar", child: Child): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const genderEmoji = computed(() => getGenderEmoji(props.child.gender));

const handleCardClick = () => {
  emit("view", props.child);
};
</script>

<style scoped>
.child-card {
  padding: 32px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  cursor: pointer;
  transition: transform 0.3s, box-shadow 0.3s;
}

/* .child-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.16);
} */

.avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  font-size: 64px;
}

.name {
  font-size: 28px;
  font-weight: bold;
  margin: 0 0 16px 0;
  color: #333;
}

.star-display {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px 32px;
  background: linear-gradient(135deg, #fff5e1 0%, #ffe4b5 100%);
  border-radius: 20px;
  margin-bottom: 20px;
  min-width: 160px;
}

.star-icon {
  font-size: 36px;
}

.star-count {
  font-size: 32px;
  font-weight: bold;
  color: #ff8c00;
}

.actions {
  display: flex;
  gap: 16px;
  justify-content: center;
}

.btn-action {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  border: none;
  font-size: 28px;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-action:hover {
  transform: scale(1.15);
}

.btn-action:active {
  transform: scale(0.95);
}

.btn-add {
  background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
}

.btn-subtract {
  background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
}

@media (max-width: 768px) {
  .avatar {
    width: 100px;
    height: 100px;
  }

  .avatar-placeholder {
    font-size: 56px;
  }

  .name {
    font-size: 24px;
  }

  .star-icon {
    font-size: 32px;
  }

  .star-count {
    font-size: 28px;
  }

  .btn-action {
    width: 56px;
    height: 56px;
    font-size: 24px;
  }
}
</style>
