import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '@/views/LoginView.vue'
import AppPortail from '@/views/PortailView.vue'
import AppProfil from '@/views/ProfilView.vue'
import { useUsersStore } from '@stores/user_stores/userStore.js'
import { LayoutComponent } from '@components'
import ConfigurationRoutes from '@/router/modules/configurationRoutes.js'
import ResetPasswordView from "@/views/ResetPasswordView.vue";
import ResetPasswordConfirmView from "@/views/ResetPasswordConfirmView.vue";
import LogoIut from "@common-images/logo/logo_iut.png"

const intranetMenu = [
  {
    items: [
      { label: 'Portail', icon: 'pi pi-fw pi-home', to: '/portail' },
    ]
  }
];

const router = createRouter({
  history: createWebHistory('/auth'),
  routes: [
    {
      path: '/',
      redirect: (to) => {
        const token = localStorage.getItem('token')
        if (token) {
          return '/portail'
        } else {
          return '/login'
        }
      }
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: ResetPasswordView,
    },
    {
      path: '/reset-password/confirm',
      name: 'reset-password-confirm',
      component: ResetPasswordConfirmView,
    },
    {
      path: '/portail',
      component: AppPortail,
      props: route => ({
        logoUrl: LogoIut,
        appName: 'Uniservices',
      }),
    },
    {
      path: '/configuration/',
      component: LayoutComponent,
      props: route => ({
        logoUrl: LogoIut,
        appName: 'Uniservices',
        menuItems: intranetMenu,
        breadcrumbItems: route.meta.breadcrumb || []
      }),
      children: [
        ...ConfigurationRoutes
        ]
    },
    {
      path: '/profil',
      component: AppProfil,
      props: route => ({
        logoUrl: LogoIut,
        appName: 'Profil',
      }),
    }
  ]
})

router.beforeEach(async (to, from, next) => {
  // mise à jour du title
  document.title = to.meta.title ?  (to.meta.title + ' | Uniservices ') : 'Uniservices';

  const token = localStorage.getItem('token')
  const userStore = useUsersStore()

  if (!userStore.isLoaded && !userStore.isLoading) {
    try {
      // si la route est login ou reset password, on ne charge pas l'utilisateur
      if (to.path === '/login' || to.path === '/reset-password' || to.path === '/reset-password/confirm') {
        return next()
      }
      await userStore.getUser()

    } catch (error) {
      console.error(error)
    }
  }

  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.has('logout')) {
    localStorage.removeItem('token')
    window.location.replace('http://localhost:3000/auth/login')
  }

  if (token) {
    const tokenParts = token.split('.')
    const payload = JSON.parse(atob(tokenParts[1]))
    const exp = payload.exp * 1000 // Convert to milliseconds

    if (Date.now() >= exp) {
      localStorage.removeItem('token')
      return window.location.href = 'http://localhost:3000/auth/login'
    }

    if (to.path === '/login') {
      return next('/portail')
    }
  }

  if (!token && to.path !== '/login' && to.path !== '/reset-password' && to.path !== '/reset-password/confirm') {
    return window.location.href = 'http://localhost:3000/auth/login'
  }

  next()
})

export default router
