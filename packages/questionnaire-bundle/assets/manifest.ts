import dashboardRoutes from './router/modules/dashboardRoutes.js';
import qualiteRoutes from './router/modules/questionnaireAdministrationRoutes.js';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import LayoutComponent from '@components/components/layout/AppLayout.vue';
import { registerWidgets } from './widgets/registerWidgets';

const questionnaireMenu = {
  label: 'Questionnaires',
  icon: 'pi pi-fw pi-check-square',
  items: [
    { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/questionnaire/' },
    { label: 'Qualité', icon: 'pi pi-fw pi-star', to: '/questionnaire/qualite/enquetes' },
  ]
};

export default { 
  name: 'questionnaire',
  primaryColor: 'yellow',
  registerWidgets,
  routes: [
    {
      path: '/questionnaire',
      component: LayoutComponent,
      props: route => ({
        logoUrl: Logo,
        appName: 'Questionnaires',
        breadcrumbItems: typeof route.meta.breadcrumb === 'function'
          ? route.meta.breadcrumb(route)
          : (route.meta.breadcrumb || [])
      }),
      children: [
        ...dashboardRoutes,
        ...qualiteRoutes,
      ]
    }
  ],
  menu: questionnaireMenu
};
