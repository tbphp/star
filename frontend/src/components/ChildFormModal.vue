<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="show" class="modal-overlay" @click="handleClose">
        <Transition name="slide-up">
          <div v-if="show" class="modal-content" @click.stop>
            <button class="btn-close" @click="handleClose">×</button>

            <h2 class="modal-title">{{ isEdit ? '编辑小朋友' : '添加小朋友' }}</h2>

            <form @submit.prevent="handleSubmit" class="form">
              <div class="form-group avatar-group">
                <label class="avatar-label">
                  <input
                    type="file"
                    accept="image/*"
                    @change="handleAvatarChange"
                    style="display: none"
                  />
                  <div class="avatar-preview">
                    <img v-if="avatarPreview" :src="avatarPreview" alt="Avatar preview" />
                    <span v-else class="avatar-placeholder">👤<br />点击上传</span>
                  </div>
                </label>
              </div>

              <div class="form-group">
                <label class="label">✏️ 姓名</label>
                <input
                  v-model="formData.name"
                  type="text"
                  class="input"
                  placeholder="请输入姓名"
                  required
                  maxlength="20"
                />
              </div>

              <div class="form-group">
                <label class="label">📅 生日</label>
                <input
                  v-model="formData.birthday"
                  type="date"
                  class="input"
                  required
                  :max="maxDate"
                />
                <p v-if="age" class="age-hint">🎂 年龄: {{ age }}岁</p>
              </div>

              <div class="form-group">
                <label class="label">性别</label>
                <div class="gender-buttons">
                  <button
                    type="button"
                    class="gender-btn"
                    :class="{ active: formData.gender === 'male' }"
                    @click="formData.gender = 'male'"
                  >
                    👦 男孩
                  </button>
                  <button
                    type="button"
                    class="gender-btn"
                    :class="{ active: formData.gender === 'female' }"
                    @click="formData.gender = 'female'"
                  >
                    👧 女孩
                  </button>
                </div>
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
import { ref, computed, watch } from 'vue'
import { childrenApi } from '@/api/children'
import { calculateAge, validateImageFile } from '@/utils/helpers'
import type { Child } from '@/types'

interface Props {
  show: boolean
  child?: Child
}

interface Emits {
  (e: 'update:show', value: boolean): void
  (e: 'success'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const formData = ref({
  name: '',
  birthday: '',
  gender: 'male' as 'male' | 'female',
})

const avatarFile = ref<File>()
const avatarPreview = ref('')
const errorMessage = ref('')
const submitting = ref(false)

const isEdit = computed(() => !!props.child)

const maxDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const age = computed(() => {
  if (!formData.value.birthday) return null
  return calculateAge(formData.value.birthday)
})

const isValid = computed(() => {
  return formData.value.name && formData.value.birthday && formData.value.gender
})

const handleAvatarChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  const validation = validateImageFile(file)
  if (!validation.valid) {
    errorMessage.value = validation.error || '图片格式错误'
    return
  }

  avatarFile.value = file

  // Preview
  const reader = new FileReader()
  reader.onload = (e) => {
    avatarPreview.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

const handleClose = () => {
  emit('update:show', false)
}

const handleSubmit = async () => {
  if (!isValid.value || submitting.value) return

  try {
    submitting.value = true
    errorMessage.value = ''

    if (isEdit.value && props.child) {
      // Edit mode
      await childrenApi.update(props.child.id, {
        ...formData.value,
        avatar: avatarFile.value,
      })
    } else {
      // Create mode
      await childrenApi.create({
        ...formData.value,
        avatar: avatarFile.value,
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
  if (props.child) {
    // Edit mode - pre-fill form
    formData.value = {
      name: props.child.name,
      birthday: props.child.birthday,
      gender: props.child.gender,
    }
    avatarPreview.value = props.child.avatar || ''
    avatarFile.value = undefined
  } else {
    // Create mode - reset form
    formData.value = {
      name: '',
      birthday: '',
      gender: 'male',
    }
    avatarFile.value = undefined
    avatarPreview.value = ''
  }
  errorMessage.value = ''
}

// Reset form when modal opens or child changes
watch(
  [() => props.show, () => props.child],
  ([newShow, newChild]) => {
    if (newShow) {
      initializeForm()
    }
  },
  { immediate: true }
)
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

.avatar-group {
  align-items: center;
  margin-bottom: 8px;
}

.avatar-label {
  cursor: pointer;
}

.avatar-preview {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 4px solid #667eea;
  transition: transform 0.2s;
}

.avatar-label:hover .avatar-preview {
  transform: scale(1.05);
}

.avatar-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  font-size: 20px;
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
  border-color: #667eea;
}

.age-hint {
  margin-top: 8px;
  font-size: 14px;
  color: #666;
}

.gender-buttons {
  display: flex;
  gap: 12px;
}

.gender-btn {
  flex: 1;
  padding: 16px;
  border-radius: 16px;
  border: 2px solid #e0e0e0;
  background: white;
  font-size: 18px;
  cursor: pointer;
  transition: all 0.2s;
}

.gender-btn:hover {
  border-color: #667eea;
  transform: translateY(-2px);
}

.gender-btn.active {
  border-color: #667eea;
  background: #667eea;
  color: white;
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
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
</style>
