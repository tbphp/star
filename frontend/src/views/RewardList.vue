<template>
  <div class="reward-list" @click="closeMenuOnClickOutside">
    <header class="header">
      <button class="btn-back" @click="goBack">←</button>
      <h1 class="title">🎁 许愿墙</h1>
      <button class="btn-add" @click="showAddRewardModal = true">➕</button>
    </header>

    <div v-if="loading" class="loading">Loading...</div>

    <div v-else class="rewards-container">
      <!-- Available Rewards -->
      <section v-if="availableRewards.length > 0" class="rewards-section">
        <h2 class="section-title">🎯 可兑换</h2>
        <div class="rewards-grid">
          <div
            v-for="reward in availableRewards"
            :key="reward.id"
            class="reward-card card"
          >
            <!-- Card Menu -->
            <div class="card-menu">
              <button
                class="menu-trigger"
                @click.stop="toggleMenu(reward.id)"
                title="操作"
              >
                ⋮
              </button>
              <div
                v-if="activeMenuId === reward.id"
                class="menu-dropdown"
                @click.stop
              >
                <button class="menu-item" @click="handleEditReward(reward)">
                  ✏️ 编辑
                </button>
                <button
                  class="menu-item delete"
                  @click="handleDeleteReward(reward)"
                >
                  🗑️ 删除
                </button>
              </div>
            </div>

            <div class="reward-image">
              <img v-if="reward.image" :src="reward.image" :alt="reward.name" />
              <span v-else class="image-placeholder">🎁</span>
            </div>

            <h3 class="reward-name">{{ reward.name }}</h3>

            <div class="cost-status-row">
              <div class="reward-cost-inline">
                需要 {{ reward.star_cost }}⭐
              </div>
              <div v-if="reward.is_achieved" class="status-badge achieved">
                ✅ 可以兑换了
              </div>
              <div v-else class="status-badge pending">
                ⏳ 还差{{ reward.star_cost - (reward.total_stars || 0) }}⭐
              </div>
            </div>

            <div class="participants">
              <div
                v-for="child in reward.children"
                :key="child.id"
                class="participant-avatar"
                :title="child.name"
              >
                <img
                  v-if="child.avatar"
                  :src="child.avatar"
                  :alt="child.name"
                  class="avatar-image"
                />
                <div v-else class="avatar-placeholder">
                  {{ child.name.charAt(0) }}
                </div>
              </div>
            </div>

            <div class="progress-section">
              <div class="progress-bar-container">
                <div
                  class="progress-bar-fill"
                  :style="{
                    width: `${Math.min(
                      ((reward.total_stars || 0) / reward.star_cost) * 100,
                      100
                    )}%`,
                  }"
                ></div>
              </div>
              <div class="progress-text">
                {{ reward.total_stars || 0 }}/{{ reward.star_cost }}⭐
              </div>
            </div>

            <button
              class="btn-redeem"
              :disabled="!reward.is_achieved"
              @click="openRedeemModal(reward)"
            >
              {{ reward.is_achieved ? "🎉 兑换" : "🔒 未达成" }}
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
              <div
                v-for="child in reward.children"
                :key="child.id"
                class="participant-avatar"
                :title="child.name"
              >
                <img
                  v-if="child.avatar"
                  :src="child.avatar"
                  :alt="child.name"
                  class="avatar-image"
                />
                <div v-else class="avatar-placeholder">
                  {{ child.name.charAt(0) }}
                </div>
              </div>
            </div>

            <div class="redeemed-date">已兑换 {{ reward.redeemed_at }}</div>
          </div>
        </div>
      </section>

      <div v-if="rewards.length === 0" class="empty-state">
        <p class="empty-text">还没有奖品哦～</p>
        <p class="empty-hint">点击右上角 ➕ 创建第一个奖品吧！</p>
      </div>
    </div>

    <!-- Reward Form Modal (Add) -->
    <RewardFormModal
      :show="showAddRewardModal"
      @update:show="showAddRewardModal = $event"
      @success="loadRewards"
    />

    <!-- Reward Form Modal (Edit) -->
    <RewardFormModal
      v-if="editingReward"
      :show="showEditRewardModal"
      :reward="editingReward"
      @update:show="showEditRewardModal = $event"
      @success="loadRewards"
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
import { useRouter } from "vue-router";
import { rewardsApi } from "@/api/rewards";
import type { Reward } from "@/types";
import RewardFormModal from "@/components/RewardFormModal.vue";
import RedeemModal from "@/components/RedeemModal.vue";

const router = useRouter();
const rewards = ref<Reward[]>([]);
const loading = ref(true);
const showAddRewardModal = ref(false);
const showEditRewardModal = ref(false);
const showRedeemModal = ref(false);
const selectedReward = ref<Reward | null>(null);
const editingReward = ref<Reward | null>(null);
const activeMenuId = ref<number | null>(null);

const availableRewards = computed(() =>
  rewards.value.filter((r) => !r.is_redeemed)
);

const redeemedRewards = computed(() =>
  rewards.value.filter((r) => r.is_redeemed)
);

const loadRewards = async () => {
  try {
    loading.value = true;
    rewards.value = await rewardsApi.getAll();
  } catch (error) {
    console.error("Failed to load rewards:", error);
  } finally {
    loading.value = false;
  }
};

const goBack = () => {
  router.push("/");
};

const openRedeemModal = (reward: Reward) => {
  selectedReward.value = reward;
  showRedeemModal.value = true;
};

const handleRedeemSuccess = () => {
  loadRewards();
};

const toggleMenu = (rewardId: number) => {
  activeMenuId.value = activeMenuId.value === rewardId ? null : rewardId;
};

const handleEditReward = (reward: Reward) => {
  editingReward.value = reward;
  showEditRewardModal.value = true;
  activeMenuId.value = null;
};

const handleDeleteReward = async (reward: Reward) => {
  const confirmed = confirm(`确定要删除 "${reward.name}" 吗？此操作无法撤销。`);
  if (!confirmed) return;

  try {
    await rewardsApi.delete(reward.id);
    await loadRewards();
  } catch (error: any) {
    const message = error.response?.data?.message || "删除失败";
    alert(message);
  } finally {
    activeMenuId.value = null;
  }
};

// Close menu when clicking outside
const closeMenuOnClickOutside = () => {
  activeMenuId.value = null;
};

onMounted(() => {
  loadRewards();
});
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
  position: relative;
}

.reward-card.redeemed {
  opacity: 0.7;
}

.card-menu {
  position: absolute;
  top: 12px;
  right: 12px;
  z-index: 10;
}

.menu-trigger {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: rgba(255, 255, 255, 0.9);
  font-size: 20px;
  font-weight: bold;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.menu-trigger:hover {
  background: white;
  transform: scale(1.1);
}

.menu-trigger:active {
  transform: scale(0.95);
}

.menu-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 8px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  min-width: 120px;
  animation: slideDown 0.2s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.menu-item {
  width: 100%;
  padding: 12px 16px;
  border: none;
  background: white;
  text-align: left;
  font-size: 14px;
  cursor: pointer;
  transition: background 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
}

.menu-item:hover {
  background: #f5f5f5;
}

.menu-item.delete {
  color: #f44336;
}

.menu-item.delete:hover {
  background: #ffebee;
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
  width: 100%;
}

.participant-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  border: 2px solid #f0f0f0;
  transition: transform 0.2s;
  cursor: pointer;
}

.participant-avatar:hover {
  transform: scale(1.1);
  border-color: #f093fb;
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 18px;
  text-transform: uppercase;
}

.cost-status-row {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  margin-bottom: 12px;
  gap: 16px;
}

.reward-cost-inline {
  font-size: 16px;
  color: #ff8c00;
  font-weight: 600;
}

.status-badge {
  padding: 4px 10px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.status-badge.achieved {
  background: #e8f5e9;
  color: #4caf50;
}

.status-badge.pending {
  background: #fff3e0;
  color: #ff9800;
}

.progress-section {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.progress-bar-container {
  flex: 1;
  height: 12px;
  background: #f0f0f0;
  border-radius: 6px;
  overflow: hidden;
  position: relative;
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #ffd700 0%, #ffed4e 50%, #ffd700 100%);
  border-radius: 6px;
  transition: width 0.3s ease;
  box-shadow: 0 2px 4px rgba(255, 215, 0, 0.3);
}

.progress-text {
  font-size: 14px;
  font-weight: 600;
  color: #ff8c00;
  white-space: nowrap;
  min-width: 80px;
  text-align: right;
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
