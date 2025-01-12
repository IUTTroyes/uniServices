import { LayoutComponent } from '@components'
import { createRouter, createWebHistory } from 'vue-router'
import dashboardRoutes from './modules/dashboardRoutes'
import edtRoutes from './modules/edtRoutes'
import progressionRoutes from './modules/progressionRoutes'
import contraintesRoutes from './modules/contraintesRoutes'
import calendrierRoutes from './modules/calendrierRoutes.js'

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
  ]
})

export default router
