<template>
  <div class="reward-list">
    <header class="header">
      <button class="btn-back" @click="goBack">←</button>
      <h1 class="title">🎁 奖品墙</h1>
      <button class="btn-add" @click="showAddRewardModal = true">➕</button>
    </header>

    <div v-if="loading" class="loading">Loading...</div>

    <div v-else class="rewards-container">
      <!-- Available Rewards -->
      <section v-if="availableRewards.length > 0" class="rewards-section">
        <h2 class="section-title">🎯 可兑换奖品</h2>
        <div class="rewards-grid">
          <div
            v-for="reward in availableRewards"
            :key="reward.id"
            class="reward-card card"
          >
            <div class="reward-image">
              <img v-if="reward.image" :src="reward.image" :alt="reward.name" />
              <span v-else class="image-placeholder">🎁</span>
            </div>

            <h3 class="reward-name">{{ reward.name }}</h3>
            <div class="reward-cost">需要 {{ reward.star_cost }}⭐</div>

            <div class="participants">
              <span
                v-for="child in reward.children"
                :key="child.id"
                class="participant"
              >
                {{ getGenderEmoji(child) }} {{ child.name }}
              </span>
            </div>

            <div class="progress-section">
              <div class="star-progress">
                {{ generateStarStack(reward.total_stars || 0, reward.star_cost) }}
              </div>
              <div class="progress-text">
                ({{ reward.total_stars || 0 }}/{{ reward.star_cost }})
              </div>
            </div>

            <div v-if="reward.is_achieved" class="status-badge achieved">
              ✅ 可以兑换了！
            </div>
            <div v-else class="status-badge pending">
              ⏳ 还差{{ reward.star_cost - (reward.total_stars || 0) }}⭐
            </div>

            <button
              class="btn-redeem"
              :disabled="!reward.is_achieved"
              @click="handleRedeem(reward)"
            >
              {{ reward.is_achieved ? '🎉 兑换' : '🔒 未达成' }}
            </button>
          </div>
        </div>
      </section>

      <!-- Redeemed Rewards -->
      <section v-if="redeemedRewards.length > 0" class="rewards-section">
        <h2 class="section-title">✅ 已兑换</h2>
        <div class="rewards-grid">
          <div
            v-for="reward in redeemedRewards"
            :key="reward.id"
            class="reward-card card redeemed"
          >
            <div class="reward-image">
              <img v-if="reward.image" :src="reward.image" :alt="reward.name" />
              <span v-else class="image-placeholder">🎁</span>
              <div class="redeemed-overlay">✓</div>
            </div>

            <h3 class="reward-name">{{ reward.name }}</h3>
            <div class="reward-cost">需要 {{ reward.star_cost }}⭐</div>

            <div class="participants">
              <span
                v-for="child in reward.children"
                :key="child.id"
                class="participant"
              >
                {{ getGenderEmoji(child) }} {{ child.name }}
              </span>
            </div>

            <div class="redeemed-date">
              已兑换 {{ reward.redeemed_at }}
            </div>
          </div>
        </div>
      </section>

      <div v-if="rewards.length === 0" class="empty-state">
        <p class="empty-text">还没有奖品哦～</p>
        <p class="empty-hint">点击右上角 ➕ 创建第一个奖品吧！</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { rewardsApi } from '@/api/rewards'
import type { Reward } from '@/types'
import { generateStarStack } from '@/utils/helpers'

const router = useRouter()
const rewards = ref<Reward[]>([])
const loading = ref(true)
const showAddRewardModal = ref(false)

const availableRewards = computed(() =>
  rewards.value.filter((r) => !r.is_redeemed)
)

const redeemedRewards = computed(() =>
  rewards.value.filter((r) => r.is_redeemed)
)

const getGenderEmoji = (child: any) => {
  // Simple gender emoji - you can import from helpers if needed
  return '👤'
}

const loadRewards = async () => {
  try {
    loading.value = true
    rewards.value = await rewardsApi.getAll()
  } catch (error) {
    console.error('Failed to load rewards:', error)
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push('/')
}

const handleRedeem = (reward: Reward) => {
  // TODO: Open redeem modal
  console.log('Redeem reward:', reward)
  alert('兑换功能即将开放！请稍后...')
}

onMounted(() => {
  loadRewards()
})
</script>

<style scoped>
.reward-list {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
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

.title {
  font-size: 28px;
  font-weight: bold;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.btn-add {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  font-size: 28px;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
  transition: transform 0.2s;
}

.btn-add:hover {
  transform: scale(1.1);
}

.loading {
  text-align: center;
  padding: 60px 20px;
  font-size: 20px;
  color: #999;
}

.rewards-container {
  display: flex;
  flex-direction: column;
  gap: 40px;
}

.rewards-section {
  margin-bottom: 20px;
}

.section-title {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 20px;
  color: #333;
}

.rewards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.reward-card {
  padding: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.reward-card.redeemed {
  opacity: 0.7;
}

.reward-image {
  width: 100%;
  height: 180px;
  border-radius: 16px;
  overflow: hidden;
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  position: relative;
}

.reward-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  font-size: 64px;
}

.redeemed-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(76, 175, 80, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 80px;
  color: white;
}

.reward-name {
  font-size: 20px;
  font-weight: bold;
  margin: 0 0 8px 0;
  color: #333;
}

.reward-cost {
  font-size: 16px;
  color: #ff8c00;
  font-weight: 600;
  margin-bottom: 12px;
}

.participants {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: center;
  margin-bottom: 12px;
}

.participant {
  padding: 6px 12px;
  background: #f0f0f0;
  border-radius: 12px;
  font-size: 14px;
  color: #666;
}

.progress-section {
  margin-bottom: 12px;
  width: 100%;
}

.star-progress {
  font-size: 20px;
  margin-bottom: 4px;
  word-wrap: break-word;
}

.progress-text {
  font-size: 14px;
  color: #666;
}

.status-badge {
  padding: 8px 16px;
  border-radius: 16px;
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 12px;
}

.status-badge.achieved {
  background: #e8f5e9;
  color: #4caf50;
}

.status-badge.pending {
  background: #fff3e0;
  color: #ff9800;
}

.btn-redeem {
  width: 100%;
  padding: 14px 24px;
  border-radius: 20px;
  border: none;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
}

.btn-redeem:not(:disabled) {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);
}

.btn-redeem:not(:disabled):hover {
  transform: translateY(-2px);
}

.btn-redeem:disabled {
  background: #e0e0e0;
  color: #999;
  cursor: not-allowed;
}

.redeemed-date {
  font-size: 14px;
  color: #999;
  margin-top: 8px;
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

@media (max-width: 768px) {
  .rewards-grid {
    grid-template-columns: 1fr;
  }
}
</style>
