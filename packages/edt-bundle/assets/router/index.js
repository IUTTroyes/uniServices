import { LayoutComponent, Access } from '@components'
import { createRouter, createWebHistory } from 'vue-router'
import dashboardRoutes from './modules/dashboardRoutes'
import edtRoutes from './modules/edtRoutes'
import progressionRoutes from './modules/progressionRoutes'
import contraintesRoutes from './modules/contraintesRoutes'
import calendrierRoutes from './modules/calendrierRoutes.js'
import { useUsersStore } from '@stores'
import { hasPermission } from '@utils'

const edtMenu = [
  {
    items: [
      { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' },
      { label: 'Emploi du temps', icon: 'pi pi-fw pi-calendar', to: { name: 'emploi-du-temps' } },
      { label: 'Progression', icon: 'pi pi-fw pi-chart-line', to: { name: 'progression-pedagogique' } },
      { label: 'Contraintes', icon: 'pi pi-fw pi-unlock', to: { name: 'contraintes' } },
      { label: 'Calendrier', icon: 'pi pi-fw pi-unlock', to: { name: 'calendrier' } }
    ]
  }
]

const appName = 'Edt'

const router = createRouter({
  history: createWebHistory('/edt/'),
  routes: [
    {
      path: '/',
      component: LayoutComponent,
      props: route => ({
        menuItems: edtMenu,
        appName: appName,
        breadcrumbItems: route.meta.breadcrumb || []
      }),
      children: [
        ...dashboardRoutes,
        ...edtRoutes,
        ...progressionRoutes,
        ...contraintesRoutes,
        ...calendrierRoutes
      ]
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
  document.title = to.meta.title ? (to.meta.title + ' | UniEdt - Uniservices ') : 'UniEdt - Uniservices'

  const userStore = useUsersStore()

  // Gestion du paramètre logout
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.has('logout')) {
    await userStore.logout()
    return
  }

  // Routes publiques
  if (to.meta.public) {
    return true
  }

  // Routes protégées : vérifier l'authentification
  try {
    const authInfo = await userStore.initAuth()

    if (!authInfo) {
      window.location.href = '/auth/login'
      return false
    }

    if (!userStore.isLoaded && !userStore.isLoading) {
      await userStore.getUser()
    }

    // Vérification des permissions
    const requiredPermission = to.meta.permission || to.matched.find(record => record.meta.permission)?.meta.permission;
    if (requiredPermission && !hasPermission(requiredPermission)) {
      return '/access';
    }

    return true
  } catch (error) {
    console.error('Auth error:', error)
    window.location.href = '/auth/login'
    return false
  }
})

export default router
