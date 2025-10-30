<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="show" class="modal-overlay" @click="handleClose">
        <Transition name="slide-up">
          <div v-if="show" class="modal-content" @click.stop>
            <button class="btn-close" @click="handleClose">×</button>

            <h2 class="modal-title">{{ isEdit ? '编辑奖品' : '创建奖品' }}</h2>

            <form @submit.prevent="handleSubmit" class="form">
              <div class="form-group image-group">
                <label class="image-label">
                  <input
                    type="file"
                    accept="image/*"
                    @change="handleImageChange"
                    style="display: none"
                  />
                  <div class="image-preview">
                    <img v-if="imagePreview" :src="imagePreview" alt="Preview" />
                    <span v-else class="image-placeholder">🎁<br />点击上传图片</span>
                  </div>
                </label>
              </div>

              <div class="form-group">
                <label class="label">🎁 奖品名称</label>
                <input
                  v-model="formData.name"
                  type="text"
                  class="input"
                  placeholder="例如：去游乐园"
                  required
                  maxlength="50"
                />
              </div>

              <div class="form-group">
                <label class="label">⭐ 需要星星数</label>
                <input
                  v-model.number="formData.star_cost"
                  type="number"
                  class="input"
                  placeholder="例如：100"
                  required
                  min="1"
                  max="9999"
                />
              </div>

              <div class="form-group">
                <label class="label">👥 选择参与的小朋友（至少1个）</label>
                <div v-if="children.length === 0" class="no-children">
                  <p>还没有小朋友，请先添加小朋友</p>
                </div>
                <div v-else class="children-grid">
                  <div
                    v-for="child in children"
                    :key="child.id"
                    class="child-option"
                    :class="{ selected: isChildSelected(child.id) }"
                    @click="toggleChild(child.id)"
                  >
                    <div class="child-avatar">
                      <img v-if="child.avatar" :src="child.avatar" :alt="child.name" />
                      <span v-else class="avatar-placeholder">{{ getGenderEmoji(child.gender) }}</span>
                    </div>
                    <div class="child-name">{{ child.name }}</div>
                    <div v-if="isChildSelected(child.id)" class="check-mark">✓</div>
                  </div>
                </div>
                <p v-if="selectedChildren.length > 0" class="selected-hint">
                  已选 {{ selectedChildren.length }} 个小朋友
                </p>
              </div>

              <div class="error-message" v-if="errorMessage">
                {{ errorMessage }}
              </div>

              <button
                type="submit"
                class="btn-submit"
                :disabled="submitting || !isValid"
              >
                {{ submitting ? '保存中...' : '💾 保存' }}
              </button>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { childrenApi } from '@/api/children'
import { rewardsApi } from '@/api/rewards'
import type { Child, Reward } from '@/types'
import { validateImageFile, getGenderEmoji } from '@/utils/helpers'

interface Props {
  show: boolean
  reward?: Reward
}

interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const formData = ref({
  name: '',
  star_cost: 100,
})

const imageFile = ref<File>()
const imagePreview = ref('')
const selectedChildren = ref<number[]>([])
const children = ref<Child[]>([])
const errorMessage = ref('')
const submitting = ref(false)

const isEdit = computed(() => !!props.reward)

const isValid = computed(() => {
  return formData.value.name && formData.value.star_cost > 0 && selectedChildren.value.length > 0
})

const isChildSelected = (childId: number) => {
  return selectedChildren.value.includes(childId)
}

const toggleChild = (childId: number) => {
  const index = selectedChildren.value.indexOf(childId)
  if (index > -1) {
    selectedChildren.value.splice(index, 1)
  } else {
    selectedChildren.value.push(childId)
  }
}

const handleImageChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  const validation = validateImageFile(file)
  if (!validation.valid) {
    errorMessage.value = validation.error || '图片格式错误'
    return
  }

  imageFile.value = file

  // Preview
  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

const loadChildren = async () => {
  try {
    children.value = await childrenApi.getAll()
  } catch (error) {
    console.error('Failed to load children:', error)
  }
}

const handleClose = () => {
  emit('update:show', false)
}

const handleSubmit = async () => {
  if (!isValid.value || submitting.value) return

  if (selectedChildren.value.length === 0) {
    errorMessage.value = '请至少选择一个小朋友'
    return
  }

  try {
    submitting.value = true
    errorMessage.value = ''

    if (isEdit.value && props.reward) {
      // Edit mode
      await rewardsApi.update(props.reward.id, {
        name: formData.value.name,
        star_cost: formData.value.star_cost,
        child_ids: selectedChildren.value,
        image: imageFile.value,
      })
    } else {
      // Create mode
      await rewardsApi.create({
        name: formData.value.name,
        star_cost: formData.value.star_cost,
        child_ids: selectedChildren.value,
        image: imageFile.value,
      })
    }

    emit('success')
    handleClose()
  } catch (error: any) {
    errorMessage.value = error.response?.data?.message || '保存失败'
  } finally {
    submitting.value = false
  }
}

// Initialize form data
const initializeForm = () => {
  if (props.reward) {
    // Edit mode - pre-fill form
    formData.value = {
      name: props.reward.name,
      star_cost: props.reward.star_cost,
    }
    imagePreview.value = props.reward.image || ''
    imageFile.value = undefined
    selectedChildren.value = props.reward.children?.map(c => c.id) || []
  } else {
    // Create mode - reset form
    formData.value = {
      name: '',
      star_cost: 100,
    }
    imageFile.value = undefined
    imagePreview.value = ''
    selectedChildren.value = []
  }
  errorMessage.value = ''
}

// Reset form when modal opens or reward changes
watch(
  [() => props.show, () => props.reward],
  ([newShow, newReward]) => {
    if (newShow) {
      loadChildren()
      initializeForm()
    }
  },
  { immediate: true }
)

onMounted(() => {
  if (props.show) {
    loadChildren()
    initializeForm()
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

.modal-title {
  text-align: center;
  margin-bottom: 24px;
  font-size: 24px;
  color: #333;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.image-group {
  align-items: center;
  margin-bottom: 8px;
}

.image-label {
  cursor: pointer;
}

.image-preview {
  width: 200px;
  height: 200px;
  border-radius: 16px;
  overflow: hidden;
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 4px solid #f093fb;
  transition: transform 0.2s;
}

.image-label:hover .image-preview {
  transform: scale(1.05);
}

.image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-placeholder {
  font-size: 32px;
  color: #999;
  text-align: center;
  line-height: 1.4;
}

.label {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #333;
}

.input {
  padding: 12px 16px;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  font-size: 16px;
  transition: border-color 0.2s;
}

.input:focus {
  outline: none;
  border-color: #f093fb;
}

.no-children {
  text-align: center;
  padding: 20px;
  color: #999;
  background: #f5f5f5;
  border-radius: 12px;
}

.children-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 12px;
}

.child-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 12px;
  border: 3px solid #e0e0e0;
  border-radius: 16px;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.child-option:hover {
  border-color: #f093fb;
  transform: translateY(-2px);
}

.child-option.selected {
  border-color: #f093fb;
  background: linear-gradient(135deg, #f093fb20 0%, #f5576c20 100%);
}

.child-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
}

.child-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  font-size: 32px;
}

.child-name {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  text-align: center;
}

.check-mark {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 24px;
  height: 24px;
  background: #f093fb;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: bold;
}

.selected-hint {
  margin-top: 8px;
  font-size: 14px;
  color: #666;
  text-align: center;
}

.error-message {
  padding: 12px;
  background: #ffe6e6;
  border-radius: 12px;
  color: #d32f2f;
  text-align: center;
}

.btn-submit {
  padding: 16px 32px;
  border-radius: 24px;
  border: none;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
  box-shadow: 0 4px 15px rgba(245, 87, 108, 0.4);
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
  .children-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>
