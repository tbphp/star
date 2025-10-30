import api from './index'
import type { Child, ApiResponse, ChildFormData } from '@/types'

export const childrenApi = {
  // Get all children
  getAll: async (): Promise<Child[]> => {
    const response = await api.get<ApiResponse<Child[]>>('/children')
    return response.data.data || []
  },

  // Get single child with details
  getById: async (id: number): Promise<Child> => {
    const response = await api.get<ApiResponse<Child>>(`/children/${id}`)
    if (!response.data.data) {
      throw new Error('Child not found')
    }
    return response.data.data
  },

  // Create child
  create: async (data: ChildFormData): Promise<Child> => {
    const formData = new FormData()
    formData.append('name', data.name)
    formData.append('birthday', data.birthday)
    formData.append('gender', data.gender)
    if (data.avatar) {
      formData.append('avatar', data.avatar)
    }

    const response = await api.post<ApiResponse<Child>>('/children', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (!response.data.data) {
      throw new Error('Failed to create child')
    }
    return response.data.data
  },

  // Update child
  update: async (id: number, data: Partial<ChildFormData>): Promise<Child> => {
    const formData = new FormData()
    if (data.name) formData.append('name', data.name)
    if (data.birthday) formData.append('birthday', data.birthday)
    if (data.gender) formData.append('gender', data.gender)
    if (data.avatar) formData.append('avatar', data.avatar)

    // Laravel method spoofing for PUT request with FormData
    formData.append('_method', 'PUT')

    const response = await api.post<ApiResponse<Child>>(`/children/${id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (!response.data.data) {
      throw new Error('Failed to update child')
    }
    return response.data.data
  },

  // Delete child
  delete: async (id: number): Promise<void> => {
    await api.delete(`/children/${id}`)
  },
}
