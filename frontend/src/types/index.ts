// Child types
export interface Child {
  id: number
  name: string
  birthday: string
  age: number
  gender: 'male' | 'female'
  avatar: string | null
  star_count: number
  star_records?: StarRecord[]
  rewards?: Reward[]
}

// Star record types
export interface StarRecord {
  id: number
  amount: number
  type: 'add' | 'subtract' | 'redeem'
  reason?: string
  reward?: {
    id: number
    name: string
    image: string | null
  } | null
  created_at: string
}

// Reward types
export interface Reward {
  id: number
  name: string
  image: string | null
  star_cost: number
  is_redeemed: boolean
  redeemed_at?: string
  children?: ChildInReward[]
  total_stars?: number
  is_achieved?: boolean
}

export interface ChildInReward {
  id: number
  name: string
  star_count: number
  gender: 'male' | 'female'
  avatar: string | null
}

// API Response types
export interface ApiResponse<T> {
  success: boolean
  data?: T
  message?: string
  errors?: Record<string, string[]>
}

// Star operation request
export interface StarOperationRequest {
  amount: number
  reason?: string
}

// Reward creation request
export interface RewardCreateRequest {
  name: string
  star_cost: number
  child_ids: number[]
  image?: File
}

// Reward redemption request
export interface RewardRedeemRequest {
  deductions: Array<{
    child_id: number
    amount: number
  }>
}

// Reward update request
export interface RewardUpdateRequest {
  name?: string
  star_cost?: number
  child_ids?: number[]
  image?: File
}

// Child form data
export interface ChildFormData {
  name: string
  birthday: string
  gender: 'male' | 'female'
  avatar?: File
}
