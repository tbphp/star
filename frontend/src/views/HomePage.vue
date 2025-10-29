<template>
  <div class="home-page">
    <header class="header">
      <h1 class="title">🌟 星星存折 🌟</h1>
      <button class="btn-add" @click="showAddChildModal = true">➕</button>
    </header>

    <div v-if="loading" class="loading">Loading...</div>

    <div v-else-if="children.length === 0" class="empty-state">
      <p class="empty-text">还没有小朋友哦～</p>
      <p class="empty-hint">点击右上角 ➕ 添加第一个小朋友吧！</p>
    </div>

    <div v-else class="children-grid">
      <ChildCard
        v-for="child in children"
        :key="child.id"
        :child="child"
        @view="goToDetail"
        @add-star="handleAddStar"
        @subtract-star="handleSubtractStar"
      />
    </div>

    <button class="btn-rewards" @click="goToRewards">🎁</button>

    <!-- Star Operation Modal -->
    <StarModal
      v-if="selectedChild"
      v-model:show="showStarModal"
      :child="selectedChild"
      :type="starOperationType"
      @success="handleStarSuccess"
    />

    <!-- Add Child Modal -->
    <ChildFormModal
      v-model:show="showAddChildModal"
      @success="loadChildren"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { childrenApi } from '@/api/children'
import type { Child } from '@/types'
import ChildCard from '@/components/ChildCard.vue'
import StarModal from '@/components/StarModal.vue'
import ChildFormModal from '@/components/ChildFormModal.vue'

const router = useRouter()
const children = ref<Child[]>([])
const loading = ref(true)
const showStarModal = ref(false)
const showAddChildModal = ref(false)
const selectedChild = ref<Child | null>(null)
const starOperationType = ref<'add' | 'subtract'>('add')

const loadChildren = async () => {
  try {
    loading.value = true
    children.value = await childrenApi.getAll()
  } catch (error) {
    console.error('Failed to load children:', error)
  } finally {
    loading.value = false
  }
}

const goToDetail = (child: Child) => {
  router.push(`/child/${child.id}`)
}

const handleAddStar = (child: Child) => {
  selectedChild.value = child
  starOperationType.value = 'add'
  showStarModal.value = true
}

const handleSubtractStar = (child: Child) => {
  selectedChild.value = child
  starOperationType.value = 'subtract'
  showStarModal.value = true
}

const handleStarSuccess = () => {
  loadChildren()
}

const goToRewards = () => {
  router.push('/rewards')
}

onMounted(() => {
  loadChildren()
})
</script>

<style scoped>
.home-page {
  padding: 20px;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 30px;
  position: relative;
}

.title {
  font-size: 32px;
  font-weight: bold;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.btn-add {
  position: absolute;
  right: 0;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 28px;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
  transition: transform 0.2s;
}

.btn-add:hover {
  transform: scale(1.1);
}

.btn-add:active {
  transform: scale(0.95);
}

.loading {
  text-align: center;
  padding: 60px 20px;
  font-size: 20px;
  color: #999;
}

.empty-state {
  text-align: center;
  padding: 80px 20px;
}

.empty-text {
  font-size: 24px;
  color: #999;
  margin-bottom: 10px;
}

.empty-hint {
  font-size: 16px;
  color: #ccc;
}

.children-grid {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 20px;
  margin-bottom: 80px;
  max-width: 1040px;
  margin-left: auto;
  margin-right: auto;
}

.children-grid > * {
  width: 320px;
  flex-shrink: 0;
}

@media (max-width: 1080px) and (min-width: 769px) {
  .children-grid {
    max-width: 680px;
  }
}

@media (max-width: 768px) {
  .children-grid {
    max-width: 320px;
  }

  .children-grid > * {
    width: 100%;
  }
}

.btn-rewards {
  position: fixed;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  font-size: 40px;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(245, 87, 108, 0.5);
  transition: transform 0.2s;
  z-index: 100;
}

.btn-rewards:hover {
  transform: translateX(-50%) scale(1.1);
}

.btn-rewards:active {
  transform: translateX(-50%) scale(0.95);
}

@media (max-width: 768px) {
  .title {
    font-size: 24px;
  }
}
</style>
