import api from './index'
import type { Reward, ApiResponse, RewardCreateRequest, RewardRedeemRequest } from '@/types'

export const rewardsApi = {
  // Get all rewards
  getAll: async (): Promise<Reward[]> => {
    const response = await api.get<ApiResponse<Reward[]>>('/rewards')
    return response.data.data || []
  },

  // Create reward
  create: async (data: RewardCreateRequest): Promise<Reward> => {
    const formData = new FormData()
    formData.append('name', data.name)
    formData.append('star_cost', data.star_cost.toString())
    data.child_ids.forEach((id) => {
      formData.append('child_ids[]', id.toString())
    })
    if (data.image) {
      formData.append('image', data.image)
    }

    const response = await api.post<ApiResponse<Reward>>('/rewards', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (!response.data.data) {
      throw new Error('Failed to create reward')
    }
    return response.data.data
  },

  // Redeem reward
  redeem: async (id: number, data: RewardRedeemRequest): Promise<void> => {
    await api.post(`/rewards/${id}/redeem`, data)
  },

  // Delete reward
  delete: async (id: number): Promise<void> => {
    await api.delete(`/rewards/${id}`)
  },
}
