import dashboardRoutes from './router/modules/dashboardRoutes.js';
import edtRoutes from './router/modules/edtRoutes.js';
import progressionRoutes from './router/modules/progressionRoutes.js';
import contraintesRoutes from './router/modules/contraintesRoutes.js';
import calendrierRoutes from './router/modules/calendrierRoutes.js';
import { LayoutComponent } from '@components';

const edtMenu = [
  {
    items: [
      { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/edt/' },
      { label: 'Emploi du temps', icon: 'pi pi-fw pi-calendar', to: { name: 'emploi-du-temps' } },
      { label: 'Progression', icon: 'pi pi-fw pi-chart-line', to: { name: 'progression-pedagogique' } },
      { label: 'Contraintes', icon: 'pi pi-fw pi-unlock', to: { name: 'contraintes' } },
      { label: 'Calendrier', icon: 'pi pi-fw pi-unlock', to: { name: 'calendrier' } }
    ]
  }
];

const appName = 'Edt';

export default {
  name: 'edt',
  routes: [
    {
      path: '/edt',
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
    }
  ]
};
