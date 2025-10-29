import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'Home',
    component: () => import('@/views/HomePage.vue'),
  },
  {
    path: '/child/:id',
    name: 'ChildDetail',
    component: () => import('@/views/ChildDetail.vue'),
  },
  {
    path: '/rewards',
    name: 'Rewards',
    component: () => import('@/views/RewardList.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
