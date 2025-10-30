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
        <div class="name-section">
          <h2 class="name">{{ child.name }}</h2>
          <span class="meta">{{ child.age }}岁 {{ genderEmoji }}</span>
        </div>

        <div class="star-stack">
          <div class="star-large">⭐</div>
          <div class="star-count">×{{ child.star_count }}</div>
        </div>

        <div class="action-buttons">
          <button class="btn-action btn-add" @click="handleAddStar">➕</button>
          <button class="btn-action btn-subtract" @click="handleSubtractStar">
            ➖
          </button>
        </div>
      </div>

      <div v-if="availableRewardsCount > 0" class="rewards-section card">
        <h3 class="section-title">🎯 愿望单</h3>
        <div class="rewards-preview">
          <div
            v-for="reward in displayRewards"
            :key="reward.id"
            class="reward-card"
            :class="{ achieved: isRewardAchieved(reward) }"
          >
            <div class="reward-header">
              <div class="reward-image">
                <img
                  v-if="reward.image"
                  :src="reward.image"
                  :alt="reward.name"
                />
                <span v-else class="reward-placeholder">🎁</span>
              </div>
              <div class="reward-main">
                <div class="reward-name">{{ reward.name }}</div>
                <div class="reward-meta">
                  <div class="reward-cost">需要 {{ reward.star_cost }}⭐</div>
                  <div class="reward-participants">
                    <span
                      v-for="participant in getRewardParticipants(reward)"
                      :key="participant.id"
                      class="participant-tag"
                    >
                      {{ getGenderEmoji(participant.gender) }}
                      {{ participant.name }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div class="reward-progress">
              <div class="progress-bar">
                <div
                  class="progress-fill"
                  :style="{ width: getRewardProgress(reward) + '%' }"
                ></div>
              </div>
              <div class="progress-text">
                {{ getRewardStars(reward) }}/{{ reward.star_cost }}⭐
              </div>
            </div>
            <button
              v-if="isRewardAchieved(reward) && !reward.is_redeemed"
              class="btn-redeem-reward"
              @click="openRedeemModal(reward)"
            >
              🎉 立即兑换
            </button>
          </div>
        </div>
        <button
          v-if="availableRewardsCount > 5"
          class="btn-view-more"
          @click="goToRewards"
        >
          查看更多 ({{ availableRewardsCount - 5 }}个) →
        </button>
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
            <div class="record-left">
              <div class="record-amount" :class="record.type">
                {{ record.amount > 0 ? "+" : "" }}{{ record.amount }}
              </div>
              <div class="record-time">{{ record.created_at }}</div>
            </div>
            <div class="record-right">
              <div v-if="record.reason" class="record-reason">
                {{ record.reason }}
              </div>
              <div v-if="record.reward" class="record-reward">
                🎁 {{ record.reward.name }}
              </div>
            </div>
          </div>
        </div>
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

    <!-- Redeem Modal -->
    <RedeemModal
      v-if="selectedReward"
      v-model:show="showRedeemModal"
      :reward="selectedReward"
      @success="handleRedeemSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { childrenApi } from "@/api/children";
import type { Child, StarRecord, Reward } from "@/types";
import { getGenderEmoji } from "@/utils/helpers";
import StarModal from "@/components/StarModal.vue";
import RedeemModal from "@/components/RedeemModal.vue";

const router = useRouter();
const route = useRoute();

const child = ref<Child | null>(null);
const records = ref<StarRecord[]>([]);
const rewards = ref<Reward[]>([]);
const loading = ref(true);
const showStarModal = ref(false);
const showRedeemModal = ref(false);
const starOperationType = ref<"add" | "subtract">("add");
const selectedReward = ref<Reward | null>(null);

const genderEmoji = computed(() =>
  child.value ? getGenderEmoji(child.value.gender) : ""
);

const displayRewards = computed(() => {
  return rewards.value.filter((r) => !r.is_redeemed).slice(0, 5);
});

const availableRewardsCount = computed(() => {
  return rewards.value.filter((r) => !r.is_redeemed).length;
});

const getRewardStars = (reward: Reward) => {
  return reward.total_stars || 0;
};

const getRewardProgress = (reward: Reward) => {
  const current = reward.total_stars || 0;
  const total = reward.star_cost;
  return Math.min((current / total) * 100, 100);
};

const isRewardAchieved = (reward: Reward) => {
  const current = reward.total_stars || 0;
  return current >= reward.star_cost;
};

const getRewardParticipants = (reward: Reward) => {
  return reward.children || [];
};

const loadChildDetail = async () => {
  try {
    loading.value = true;
    const childId = parseInt(route.params.id as string);
    const data = await childrenApi.getById(childId);

    child.value = data;
    records.value = data.star_records || [];
    rewards.value = data.rewards || [];
  } catch (error) {
    console.error("Failed to load child detail:", error);
  } finally {
    loading.value = false;
  }
};

const goBack = () => {
  router.push("/");
};

const handleAddStar = () => {
  starOperationType.value = "add";
  showStarModal.value = true;
};

const handleSubtractStar = () => {
  starOperationType.value = "subtract";
  showStarModal.value = true;
};

const goToRewards = () => {
  router.push("/rewards");
};

const openRedeemModal = (reward: Reward) => {
  selectedReward.value = reward;
  showRedeemModal.value = true;
};

const handleRedeemSuccess = () => {
  loadChildDetail();
};

onMounted(() => {
  loadChildDetail();
});
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

.name-section {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 8px;
  margin-bottom: 20px;
}

.name {
  font-size: 32px;
  font-weight: bold;
  margin: 0;
  color: #333;
}

.meta {
  font-size: 14px;
  color: #999;
}

.star-stack {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 16px 32px;
  background: linear-gradient(135deg, #fff5e1 0%, #ffe4b5 100%);
  border-radius: 20px;
  margin: 0 auto 24px;
}

.star-large {
  font-size: 48px;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.star-count {
  font-size: 40px;
  font-weight: bold;
  color: #ff8c00;
}

.action-buttons {
  display: flex;
  gap: 20px;
  justify-content: center;
}

.btn-action {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  border: none;
  font-size: 28px;
  cursor: pointer;
  transition: transform 0.2s;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-action:hover {
  transform: scale(1.1);
}

.btn-action:active {
  transform: scale(0.95);
}

.btn-add {
  background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
  color: white;
}

.btn-subtract {
  background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
  color: white;
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

.rewards-preview {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 16px;
}

.reward-card {
  padding: 16px;
  background: #f9f9f9;
  border-radius: 16px;
  border-left: 4px solid #667eea;
  transition: transform 0.2s;
}

.reward-card.achieved {
  border-left-color: #4caf50;
  background: linear-gradient(135deg, #f0fff4 0%, #f9f9f9 100%);
}

.reward-card:hover {
  transform: translateX(4px);
}

.reward-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.reward-image {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  overflow: hidden;
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.reward-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.reward-placeholder {
  font-size: 32px;
}

.reward-main {
  flex: 1;
}

.reward-name {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin-bottom: 6px;
}

.reward-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.reward-cost {
  font-size: 14px;
  color: #ff8c00;
  font-weight: 600;
  flex-shrink: 0;
}

.reward-participants {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  justify-content: flex-end;
}

.reward-progress {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.progress-bar {
  flex: 1;
  height: 12px;
  background: #f0f0f0;
  border-radius: 6px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #ffd700 0%, #ffed4e 50%, #ffd700 100%);
  border-radius: 6px;
  transition: width 0.3s ease;
  box-shadow: 0 2px 4px rgba(255, 215, 0, 0.3);
}

.reward-card.achieved .progress-fill {
  background: linear-gradient(90deg, #ffd700 0%, #ffed4e 50%, #ffd700 100%);
  box-shadow: 0 2px 4px rgba(255, 215, 0, 0.5);
}

.progress-text {
  font-size: 14px;
  font-weight: 600;
  color: #ff8c00;
  white-space: nowrap;
  min-width: 80px;
  text-align: right;
}

.participant-tag {
  padding: 4px 10px;
  background: white;
  border-radius: 12px;
  font-size: 12px;
  color: #666;
  border: 1px solid #e0e0e0;
}

.achievement-badge {
  display: inline-block;
  padding: 6px 12px;
  background: #4caf50;
  color: white;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 8px;
}

.btn-redeem-reward {
  width: 100%;
  padding: 12px 24px;
  border-radius: 16px;
  border: none;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);
  transition: transform 0.2s;
}

.btn-redeem-reward:hover {
  transform: translateY(-2px);
}

.btn-redeem-reward:active {
  transform: translateY(0);
}

.btn-view-more {
  width: 100%;
  padding: 12px;
  border-radius: 12px;
  border: 2px solid #667eea;
  background: white;
  color: #667eea;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-view-more:hover {
  background: #667eea;
  color: white;
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
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: #f9f9f9;
  border-radius: 12px;
  border-left: 4px solid #ccc;
}

.record-item.add {
  border-left-color: #84fab0;
  background: linear-gradient(135deg, #f0fff4 0%, #f9f9f9 100%);
}

.record-item.subtract {
  border-left-color: #fdcb6e;
  background: linear-gradient(135deg, #fffbf0 0%, #f9f9f9 100%);
}

.record-item.redeem {
  border-left-color: #f093fb;
  background: linear-gradient(135deg, #fff0f9 0%, #f9f9f9 100%);
}

.record-left {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 80px;
}

.record-amount {
  font-size: 24px;
  font-weight: bold;
}

.record-amount.add {
  color: #4caf50;
}

.record-amount.subtract {
  color: #ff9800;
}

.record-amount.redeem {
  color: #e91e63;
}

.record-time {
  font-size: 11px;
  color: #999;
}

.record-right {
  flex: 1;
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: flex-end;
}

.record-reason {
  font-size: 15px;
  color: #333;
  font-weight: 500;
}

.record-reward {
  font-size: 13px;
  color: #666;
  padding: 4px 8px;
  background: white;
  border-radius: 8px;
}

@media (max-width: 768px) {
  .name-section {
    flex-direction: column;
    gap: 4px;
  }
}
</style>
