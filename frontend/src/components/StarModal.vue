<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="show" class="modal-overlay" @click="handleClose">
        <Transition name="slide-up">
          <div v-if="show" class="modal-content" @click.stop>
            <button class="btn-close" @click="handleClose">×</button>

            <div class="modal-header">
              <div class="child-info">
                <span class="gender-emoji">{{ genderEmoji }}</span>
                <span class="child-name">{{ child.name }}</span>
              </div>
              <div class="operation-icon">
                {{ type === 'add' ? '➕' : '➖' }} 🌟
              </div>
            </div>

            <div class="modal-body">
              <div class="amount-section">
                <label class="label">⭐ 数量</label>
                <div class="amount-control">
                  <button
                    class="btn-control"
                    @click="decreaseAmount"
                    :disabled="amount <= 1"
                  >
                    －
                  </button>
                  <input
                    type="number"
                    v-model.number="amount"
                    class="amount-input"
                    min="1"
                    :max="type === 'subtract' ? child.star_count : 50"
                  />
                  <button
                    class="btn-control"
                    @click="increaseAmount"
                    :disabled="(type === 'subtract' && amount >= child.star_count) || amount >= 50"
                  >
                    ＋
                  </button>
                </div>
              </div>

              <div class="reason-section">
                <label class="label">📝 原因（选填）</label>
                <div class="reason-tags">
                  <button
                    v-for="tag in reasonTags"
                    :key="tag.text"
                    class="reason-tag"
                    :class="{ active: reason === tag.text }"
                    @click="reason = tag.text"
                  >
                    {{ tag.emoji }} {{ tag.text }}
                  </button>
                </div>
                <input
                  type="text"
                  v-model="reason"
                  class="reason-input"
                  placeholder="或输入其他原因..."
                  maxlength="50"
                />
              </div>

              <div class="error-message" v-if="errorMessage">
                {{ errorMessage }}
              </div>
            </div>

            <div class="modal-footer">
              <button
                class="btn-submit"
                @click="handleSubmit"
                :disabled="submitting"
                ref="submitButtonRef"
              >
                {{ submitting ? '处理中...' : '✓' }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { starsApi } from '@/api/stars'
import type { Child } from '@/types'
import { getGenderEmoji } from '@/utils/helpers'
import { useAnimations } from '@/composables/useAnimations'

interface Props {
  show: boolean
  child: Child
  type: 'add' | 'subtract'
}

interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const amount = ref(5)
const reason = ref('')
const errorMessage = ref('')
const submitting = ref(false)
const submitButtonRef = ref<HTMLButtonElement>()

const { flyStarIn, flyStarOut, shake, bounce } = useAnimations()

const genderEmoji = computed(() => getGenderEmoji(props.child.gender))

const reasonTags = computed(() => {
  if (props.type === 'add') {
    return [
      { emoji: '😊', text: '认真' },
      { emoji: '🏃', text: '主动' },
      { emoji: '😴', text: '按时' },
      { emoji: '🤝', text: '分享' },
    ]
  } else {
    return [
      { emoji: '😢', text: '不听话' },
      { emoji: '🎮', text: '玩太久' },
      { emoji: '😴', text: '不按时' },
      { emoji: '😤', text: '发脾气' },
    ]
  }
})

const decreaseAmount = () => {
  if (amount.value > 1) {
    amount.value--
  }
}

const increaseAmount = () => {
  const max = props.type === 'subtract' ? props.child.star_count : 50
  if (amount.value < max) {
    amount.value++
  }
}

const handleClose = () => {
  emit('update:show', false)
}

const handleSubmit = async () => {
  if (submitting.value) return

  // Validate
  if (props.type === 'subtract' && amount.value > props.child.star_count) {
    errorMessage.value = '星星不够哦～'
    if (submitButtonRef.value) {
      shake(submitButtonRef.value)
    }
    return
  }

  try {
    submitting.value = true
    errorMessage.value = ''

    if (props.type === 'add') {
      await starsApi.add(props.child.id, {
        amount: amount.value,
        reason: reason.value || undefined,
      })
    } else {
      await starsApi.subtract(props.child.id, {
        amount: amount.value,
        reason: reason.value || undefined,
      })
    }

    // Trigger animation
    if (submitButtonRef.value) {
      bounce(submitButtonRef.value)
    }

    // Close modal and emit success
    setTimeout(() => {
      emit('success')
      handleClose()
    }, 300)
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || '操作失败'
    if (submitButtonRef.value) {
      shake(submitButtonRef.value)
    }
  } finally {
    submitting.value = false
  }
}

// Reset form when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    amount.value = 5
    reason.value = ''
    errorMessage.value = ''
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
  max-width: 500px;
  width: 100%;
  position: relative;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
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

.child-info {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 12px;
}

.gender-emoji {
  margin-right: 8px;
}

.operation-icon {
  font-size: 36px;
}

.modal-body {
  margin-bottom: 32px;
}

.amount-section,
.reason-section {
  margin-bottom: 24px;
}

.label {
  display: block;
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #333;
}

.amount-control {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
}

.btn-control {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
  transition: transform 0.2s, opacity 0.2s;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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

.amount-input {
  width: 100px;
  height: 64px;
  font-size: 32px;
  font-weight: bold;
  text-align: center;
  border: 2px solid #667eea;
  border-radius: 16px;
  color: #667eea;
}

.amount-input:focus {
  outline: none;
  border-color: #764ba2;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
}

.reason-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}

.reason-tag {
  padding: 10px 16px;
  border-radius: 20px;
  border: 2px solid #e0e0e0;
  background: white;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.reason-tag:hover {
  border-color: #667eea;
  transform: translateY(-2px);
}

.reason-tag.active {
  border-color: #667eea;
  background: #667eea;
  color: white;
}

.reason-input {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  font-size: 16px;
}

.reason-input:focus {
  outline: none;
  border-color: #667eea;
}

.error-message {
  padding: 12px;
  background: #ffe6e6;
  border-radius: 12px;
  color: #d32f2f;
  text-align: center;
  margin-top: 16px;
}

.modal-footer {
  display: flex;
  justify-content: center;
}

.btn-submit {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
  color: white;
  font-size: 48px;
  cursor: pointer;
  box-shadow: 0 6px 20px rgba(132, 250, 176, 0.5);
  transition: transform 0.2s;
}

.btn-submit:hover:not(:disabled) {
  transform: scale(1.1);
}

.btn-submit:active:not(:disabled) {
  transform: scale(0.95);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .modal-content {
    padding: 24px;
  }

  .reason-tags {
    justify-content: center;
  }
}
</style>
