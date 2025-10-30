<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="show" class="modal-overlay" @click="handleClose">
        <Transition name="slide-up">
          <div v-if="show" class="modal-content" @click.stop>
            <button class="btn-close" @click="handleClose">×</button>

            <div class="modal-header">
              <div class="reward-image">
                <img v-if="reward.image" :src="reward.image" :alt="reward.name" />
                <span v-else class="reward-placeholder">🎁</span>
              </div>
              <h3 class="reward-name">{{ reward.name }}</h3>
            </div>

            <div class="modal-body">
              <div class="total-section">
                <div class="total-label">总共需要</div>
                <div class="total-value">{{ reward.star_cost }}⭐</div>
              </div>

              <div class="deduction-section">
                <div class="children-list">
                  <div
                    v-for="child in participants"
                    :key="child.id"
                    class="child-item"
                  >
                    <div class="child-info">
                      <span class="child-emoji">{{ getGenderEmoji(child.gender) }}</span>
                      <span class="child-name">{{ child.name }}</span>
                      <span class="child-stars">(有 {{ child.star_count }}⭐)</span>
                    </div>
                    <div class="deduction-control">
                      <button
                        class="btn-control"
                        @click="decreaseDeduction(child.id)"
                        :disabled="getDeduction(child.id) === 0"
                      >
                        －
                      </button>
                      <input
                        type="number"
                        :value="getDeduction(child.id)"
                        @input="updateDeduction(child.id, $event)"
                        class="deduction-input"
                        min="0"
                        :max="child.star_count"
                      />
                      <button
                        class="btn-control"
                        @click="increaseDeduction(child.id)"
                        :disabled="getDeduction(child.id) >= child.star_count"
                      >
                        ＋
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="error-message" v-if="errorMessage">
                {{ errorMessage }}
              </div>
            </div>

            <div class="modal-footer">
              <button
                class="btn-submit"
                @click="handleSubmit"
                :disabled="submitting || !isValid"
                ref="submitButtonRef"
              >
                {{ submitting ? '兑换中...' : '🎉 确认兑换' }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { rewardsApi } from '@/api/rewards'
import type { Reward } from '@/types'
import { getGenderEmoji } from '@/utils/helpers'
import { useAnimations } from '@/composables/useAnimations'

interface Props {
  show: boolean
  reward: Reward
}

interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const deductions = ref<Record<number, number>>({})
const errorMessage = ref('')
const submitting = ref(false)
const submitButtonRef = ref<HTMLButtonElement>()

const { shake, bounce } = useAnimations()

const participants = computed(() => props.reward.children || [])

const totalDeduction = computed(() => {
  return Object.values(deductions.value).reduce((sum, val) => sum + val, 0)
})

const remaining = computed(() => {
  return Math.max(0, props.reward.star_cost - totalDeduction.value)
})

const isValid = computed(() => {
  return totalDeduction.value >= props.reward.star_cost
})

const getDeduction = (childId: number) => {
  return deductions.value[childId] || 0
}

const updateDeduction = (childId: number, event: Event) => {
  const target = event.target as HTMLInputElement
  let value = parseInt(target.value) || 0

  const child = participants.value.find(c => c.id === childId)
  if (child) {
    if (value < 0) {
      value = 0
    } else if (value > child.star_count) {
      value = child.star_count
    }
  }

  deductions.value[childId] = value
}

const decreaseDeduction = (childId: number) => {
  if ((deductions.value[childId] || 0) > 0) {
    deductions.value[childId] = (deductions.value[childId] || 0) - 1
  }
}

const increaseDeduction = (childId: number) => {
  const child = participants.value.find(c => c.id === childId)
  if (child && (deductions.value[childId] || 0) < child.star_count) {
    deductions.value[childId] = (deductions.value[childId] || 0) + 1
  }
}

const initializeDeductions = () => {
  const children = [...participants.value]

  if (children.length === 0) {
    deductions.value = {}
    return
  }

  // Clear existing deductions
  deductions.value = {}

  // Step 1: Calculate average
  const average = Math.floor(props.reward.star_cost / children.length)
  let remaining = props.reward.star_cost

  // Step 2: First pass - assign average or max available
  const shortfalls: { childId: number; shortfall: number }[] = []

  for (const child of children) {
    if (child.star_count >= average) {
      // This child can afford the average
      deductions.value[child.id] = average
      remaining -= average
    } else {
      // This child doesn't have enough, use their maximum
      deductions.value[child.id] = child.star_count
      remaining -= child.star_count
      shortfalls.push({
        childId: child.id,
        shortfall: average - child.star_count
      })
    }
  }

  // Step 3: Redistribute the remaining to children who can afford more
  if (remaining > 0) {
    // Sort children by available stars (descending)
    const sortedChildren = children
      .filter(child => child.star_count > (deductions.value[child.id] || 0))
      .sort((a, b) => {
        const aAvailable = a.star_count - (deductions.value[a.id] || 0)
        const bAvailable = b.star_count - (deductions.value[b.id] || 0)
        return bAvailable - aAvailable
      })

    for (const child of sortedChildren) {
      if (remaining === 0) break

      const available = child.star_count - (deductions.value[child.id] || 0)
      const toAdd = Math.min(available, remaining)

      deductions.value[child.id] = (deductions.value[child.id] || 0) + toAdd
      remaining -= toAdd
    }
  }
}

const handleClose = () => {
  emit('update:show', false)
}

const handleSubmit = async () => {
  if (submitting.value || !isValid.value) return

  if (totalDeduction.value < props.reward.star_cost) {
    errorMessage.value = `还需要分配 ${remaining.value} 颗星星`
    if (submitButtonRef.value) {
      shake(submitButtonRef.value)
    }
    return
  }

  try {
    submitting.value = true
    errorMessage.value = ''

    const deductionArray = Object.entries(deductions.value)
      .filter(([_, amount]) => amount > 0)
      .map(([childId, amount]) => ({
        child_id: parseInt(childId),
        amount,
      }))

    await rewardsApi.redeem(props.reward.id, { deductions: deductionArray })

    if (submitButtonRef.value) {
      bounce(submitButtonRef.value)
    }

    setTimeout(() => {
      emit('success')
      handleClose()
    }, 300)
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || '兑换失败'
    if (submitButtonRef.value) {
      shake(submitButtonRef.value)
    }
  } finally {
    submitting.value = false
  }
}

watch(
  [() => props.show, () => props.reward],
  ([newShow]) => {
    if (newShow) {
      initializeDeductions()
      errorMessage.value = ''
    }
  }
)

onMounted(() => {
  if (props.show) {
    initializeDeductions()
  }
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 24px;
  padding: 32px;
  max-width: 600px;
  width: 100%;
  position: relative;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-height: 90vh;
  overflow-y: auto;
}

.btn-close {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: #f5f5f5;
  font-size: 28px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.btn-close:hover {
  background: #e0e0e0;
}

.modal-header {
  text-align: center;
  margin-bottom: 32px;
}

.reward-image {
  width: 100px;
  height: 100px;
  border-radius: 16px;
  overflow: hidden;
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.reward-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.reward-placeholder {
  font-size: 56px;
}

.reward-name {
  font-size: 24px;
  font-weight: bold;
  color: #333;
  margin: 0;
}

.modal-body {
  margin-bottom: 32px;
}

.total-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  background: linear-gradient(135deg, #fff5e1 0%, #ffe4b5 100%);
  border-radius: 16px;
  margin-bottom: 24px;
}

.total-label {
  font-size: 16px;
  color: #666;
}

.total-value {
  font-size: 28px;
  font-weight: bold;
  color: #ff8c00;
}

.deduction-section {
  margin-bottom: 16px;
}

.children-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.child-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f9f9f9;
  border-radius: 12px;
}

.child-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.child-emoji {
  font-size: 20px;
}

.child-name {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.child-stars {
  font-size: 13px;
  color: #999;
}

.deduction-control {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-control {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  font-size: 20px;
  font-weight: bold;
  cursor: pointer;
  transition: transform 0.2s, opacity 0.2s;
}

.btn-control:hover:not(:disabled) {
  transform: scale(1.1);
}

.btn-control:active:not(:disabled) {
  transform: scale(0.95);
}

.btn-control:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.deduction-input {
  width: 60px;
  height: 40px;
  font-size: 18px;
  font-weight: bold;
  text-align: center;
  border: 2px solid #f093fb;
  border-radius: 12px;
  color: #f093fb;
}

.deduction-input:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(240, 147, 251, 0.2);
}

.error-message {
  padding: 12px;
  background: #ffe6e6;
  border-radius: 12px;
  color: #d32f2f;
  text-align: center;
}

.modal-footer {
  display: flex;
  justify-content: center;
}

.btn-submit {
  padding: 16px 48px;
  border-radius: 24px;
  border: none;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4);
  transition: transform 0.2s;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
}

.btn-submit:active:not(:disabled) {
  transform: translateY(0);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .modal-content {
    padding: 24px;
  }

  .child-item {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }

  .deduction-control {
    width: 100%;
    justify-content: center;
  }
}
</style>
