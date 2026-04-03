import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/auth/Login.vue'),
    meta: { guest: true },
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/auth/Register.vue'),
    meta: { guest: true },
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: () => import('../views/auth/ForgotPassword.vue'),
    meta: { guest: true },
  },
  {
    path: '/reset-password',
    name: 'ResetPassword',
    component: () => import('../views/auth/ResetPassword.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    component: () => import('../components/layout/AppLayout.vue'),
    meta: { auth: true },
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('../views/Dashboard.vue'),
      },
      {
        path: 'tenders',
        name: 'Tenders',
        component: () => import('../views/tenders/TenderList.vue'),
      },
      {
        path: 'tenders/:id',
        name: 'TenderDetail',
        component: () => import('../views/tenders/TenderDetail.vue'),
      },
      {
        path: 'favorites',
        name: 'Favorites',
        component: () => import('../views/tenders/Favorites.vue'),
      },
      {
        path: 'subscription',
        name: 'Subscription',
        component: () => import('../views/subscription/Subscription.vue'),
      },
      {
        path: 'applications',
        name: 'Applications',
        component: () => import('../views/applications/MyApplications.vue'),
      },
      {
        path: 'applications/:id',
        name: 'ApplicationDetail',
        component: () => import('../views/applications/ApplicationDetail.vue'),
      },
      {
        path: 'notifications',
        name: 'Notifications',
        component: () => import('../views/notifications/Notifications.vue'),
      },
      {
        path: 'profile',
        name: 'Profile',
        component: () => import('../views/profile/Profile.vue'),
      },
      { path: 'business', name: 'BusinessDashboard', component: () => import('../views/business/BusinessDashboard.vue') },
      { path: 'business/post', name: 'PostTender', component: () => import('../views/business/PostTender.vue') },
      { path: 'business/tenders', name: 'MyTenders', component: () => import('../views/business/MyTenders.vue') },
      { path: 'business/tenders/:id/applications', name: 'TenderApplications', component: () => import('../views/business/TenderApplications.vue') },
      { path: 'business/tenders/:id/edit', name: 'EditTender', component: () => import('../views/business/EditTender.vue') },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  if (to.meta.auth && !token) {
    next('/login')
  } else if (to.meta.guest && token) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
