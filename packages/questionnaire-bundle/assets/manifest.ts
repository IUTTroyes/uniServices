import dashboardRoutes from './router/modules/dashboardRoutes.js';
import qualiteRoutes from './router/modules/questionnaireAdministrationRoutes.js';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import { LayoutComponent } from '@components';

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
  routes: [
    {
      path: '/questionnaire',
      component: LayoutComponent,
      props: route => ({
        logoUrl: Logo,
        appName: 'Questionnaires',
        breadcrumbItems: route.meta.breadcrumb || []
      }),
      children: [
        ...dashboardRoutes,
        ...qualiteRoutes,
      ]
    }
  ],
  menu: questionnaireMenu
};
