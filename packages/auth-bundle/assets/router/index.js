import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '@/views/LoginView.vue'
import AppPortail from '@/views/PortailView.vue'
import AppProfil from '@/views/ProfilView.vue'
import { useUsersStore } from '@stores/user_stores/userStore.js'
import { LayoutComponent, Access } from '@components'
import { hasPermission } from '@utils'
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
      redirect: '/portail'
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { public: true }
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: ResetPasswordView,
      meta: { public: true }
    },
    {
      path: '/reset-password/confirm',
      name: 'reset-password-confirm',
      component: ResetPasswordConfirmView,
      meta: { public: true }
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
    },
    {
      path: '/access',
      name: 'access',
      component: Access,
      meta: { title: 'Accès Refusé' }
    }
  ]
})

router.beforeEach(async (to, from) => {
  // mise à jour du title
  document.title = to.meta.title ? (to.meta.title + ' | Uniservices ') : 'Uniservices';

  const userStore = useUsersStore()

  // Gestion du paramètre logout
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.has('logout')) {
    await userStore.logout()
    return // logout redirige automatiquement
  }

  // Routes publiques : pas besoin de vérifier l'authentification
  if (to.meta.public) {
    // Si déjà authentifié et va vers login, rediriger vers portail
    if (to.path === '/login' && userStore.isAuthInitialized && userStore.userId) {
      return '/portail'
    }
    return true
  }

  // Routes protégées : vérifier l'authentification
  try {
    // Initialiser l'authentification si pas encore fait
    const authInfo = await userStore.initAuth()

    if (!authInfo) {
      // Non authentifié, rediriger vers login
      window.location.href = window.location.origin + '/auth/login'
      return false
    }

    // Charger les données utilisateur si pas encore fait
    if (!userStore.isLoaded && !userStore.isLoading) {
      await userStore.getUser()
    }

    // Vérification des permissions
    const requiredPermission = to.meta.permission || to.matched.find(record => record.meta.permission)?.meta.permission;
    if (requiredPermission && !hasPermission(requiredPermission)) {
      return '/access'; // Rediriger vers la page d'accès refusé
    }

    return true
  } catch (error) {
    console.error('Auth error:', error)
    window.location.href = window.location.origin + '/auth/login'
    return false
  }
})

export default router
