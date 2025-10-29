<template>
  <div class="child-detail">
    <div v-if="loading" class="loading">Loading...</div>

    <div v-else-if="child" class="detail-container">
      <header class="detail-header">
        <button class="btn-back" @click="goBack">←</button>
        <h1 class="page-title">{{ child.name }}</h1>
      </header>

      <div class="child-info card">
        <div class="avatar-large">
          <img v-if="child.avatar" :src="child.avatar" :alt="child.name" />
          <span v-else class="avatar-placeholder">{{ genderEmoji }}</span>
        </div>
        <h2 class="name">{{ child.name }}</h2>
        <p class="meta">{{ child.age }}岁 {{ genderEmoji }}</p>

        <div class="star-balance">
          <h3 class="balance-title">🌟 当前星星 🌟</h3>
          <div class="star-stack">
            <div class="star-large">⭐</div>
            <div class="star-count">×{{ child.star_count }}</div>
          </div>
        </div>

        <div class="action-buttons">
          <button class="btn-action btn-add" @click="handleAddStar">
            ➕ 加星星
          </button>
          <button class="btn-action btn-subtract" @click="handleSubtractStar">
            ➖ 减星星
          </button>
        </div>
      </div>

      <div class="records-section card">
        <h3 class="section-title">📋 最近记录</h3>
        <div v-if="records.length === 0" class="empty-records">
          <p>还没有记录哦～</p>
        </div>
        <div v-else class="records-list">
          <div
            v-for="record in records"
            :key="record.id"
            class="record-item"
            :class="record.type"
          >
            <div class="record-icon">
              <span v-if="record.type === 'add'">➕</span>
              <span v-else-if="record.type === 'subtract'">➖</span>
              <span v-else>🎁</span>
            </div>
            <div class="record-content">
              <div class="record-amount">
                {{ record.amount > 0 ? '+' : '' }}{{ record.amount }}⭐
              </div>
              <div v-if="record.reason" class="record-reason">
                📝 {{ record.reason }}
              </div>
              <div v-if="record.reward" class="record-reward">
                🎁 兑换: {{ record.reward.name }}
              </div>
              <div class="record-time">{{ record.created_at }}</div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="rewards.length > 0" class="rewards-section card">
        <h3 class="section-title">🎯 Ta的奖品目标 ({{ rewards.length }}个)</h3>
        <button class="btn-view-all" @click="goToRewards">查看全部 →</button>
      </div>
    </div>

    <!-- Star Operation Modal -->
    <StarModal
      v-if="child"
      v-model:show="showStarModal"
      :child="child"
      :type="starOperationType"
      @success="loadChildDetail"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { childrenApi } from '@/api/children'
import type { Child, StarRecord, Reward } from '@/types'
import { getGenderEmoji } from '@/utils/helpers'
import StarModal from '@/components/StarModal.vue'

const router = useRouter()
const route = useRoute()

const child = ref<Child | null>(null)
const records = ref<StarRecord[]>([])
const rewards = ref<Reward[]>([])
const loading = ref(true)
const showStarModal = ref(false)
const starOperationType = ref<'add' | 'subtract'>('add')

const genderEmoji = computed(() =>
  child.value ? getGenderEmoji(child.value.gender) : ''
)

const loadChildDetail = async () => {
  try {
    loading.value = true
    const childId = parseInt(route.params.id as string)
    const data = await childrenApi.getById(childId)

    child.value = data
    records.value = data.star_records || []
    rewards.value = data.rewards || []
  } catch (error) {
    console.error('Failed to load child detail:', error)
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push('/')
}

const handleAddStar = () => {
  starOperationType.value = 'add'
  showStarModal.value = true
}

const handleSubtractStar = () => {
  starOperationType.value = 'subtract'
  showStarModal.value = true
}

const goToRewards = () => {
  router.push('/rewards')
}

onMounted(() => {
  loadChildDetail()
})
</script>

<style scoped>
.child-detail {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.loading {
  text-align: center;
  padding: 60px 20px;
  font-size: 20px;
  color: #999;
}

.detail-container {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 10px;
}

.btn-back {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  border: none;
  background: white;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s;
}

.btn-back:hover {
  transform: scale(1.1);
}

.page-title {
  font-size: 28px;
  font-weight: bold;
  color: #333;
  margin: 0;
}

.child-info {
  text-align: center;
  padding: 32px;
}

.avatar-large {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  border: 4px solid #667eea;
}

.avatar-large img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  font-size: 60px;
}

.name {
  font-size: 32px;
  font-weight: bold;
  margin: 0 0 8px 0;
  color: #333;
}

.meta {
  font-size: 18px;
  color: #666;
  margin: 0 0 24px 0;
}

.star-balance {
  margin-bottom: 24px;
}

.balance-title {
  font-size: 20px;
  margin-bottom: 16px;
  color: #333;
}

.star-stack {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 20px;
  background: linear-gradient(135deg, #fff5e1 0%, #ffe4b5 100%);
  border-radius: 20px;
}

.star-large {
  font-size: 48px;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.star-count {
  font-size: 40px;
  font-weight: bold;
  color: #ff8c00;
}

.action-buttons {
  display: flex;
  gap: 16px;
  justify-content: center;
}

.btn-action {
  flex: 1;
  max-width: 180px;
  padding: 16px 24px;
  border-radius: 20px;
  border: none;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-action:hover {
  transform: translateY(-2px);
}

.btn-action:active {
  transform: translateY(0);
}

.btn-add {
  background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
  color: white;
}

.btn-subtract {
  background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
  color: #333;
}

.records-section,
.rewards-section {
  padding: 24px;
}

.section-title {
  font-size: 20px;
  font-weight: bold;
  margin: 0 0 20px 0;
  color: #333;
}

.empty-records {
  text-align: center;
  padding: 40px 20px;
  color: #999;
}

.records-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.record-item {
  display: flex;
  gap: 16px;
  padding: 16px;
  background: #f9f9f9;
  border-radius: 12px;
  border-left: 4px solid #ccc;
}

.record-item.add {
  border-left-color: #84fab0;
}

.record-item.subtract {
  border-left-color: #fdcb6e;
}

.record-item.redeem {
  border-left-color: #f093fb;
}

.record-icon {
  font-size: 24px;
  flex-shrink: 0;
}

.record-content {
  flex: 1;
}

.record-amount {
  font-size: 20px;
  font-weight: bold;
  color: #333;
  margin-bottom: 4px;
}

.record-reason,
.record-reward {
  font-size: 14px;
  color: #666;
  margin-bottom: 4px;
}

.record-time {
  font-size: 12px;
  color: #999;
}

.btn-view-all {
  width: 100%;
  padding: 12px;
  border-radius: 12px;
  border: 2px solid #667eea;
  background: white;
  color: #667eea;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-view-all:hover {
  background: #667eea;
  color: white;
}

@media (max-width: 768px) {
  .action-buttons {
    flex-direction: column;
  }

  .btn-action {
    max-width: none;
  }
}
</style>
