import documentRoutes from './router/modules/documentRoutes.js';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import LayoutComponent from '@components/components/layout/AppLayout.vue';
import { registerWidgets } from './widgets/registerWidgets';
import { getApplicationMeta } from '@utils/applicationColors';

const app = getApplicationMeta('documents');

const documentMenu = {
  label: app.name,
  icon: `${app.icon} pi-fw`,
  items: [
    { label: 'Tous les documents', icon: 'pi pi-fw pi-file', to: '/documents' },
  ],
  bottomItems: [
    {
      label: 'Configuration',
      icon: 'pi pi-fw pi-sliders-h',
      to: '/documents/configuration',
      permission: 'isChefDepartement'
    }
  ]
};

export default {
  name: 'documents',
  registerWidgets,
  routes: [
    {
      path: '/',
      component: LayoutComponent,
      props: (route: any) => ({
        logoUrl: Logo,
        appName: app.name,
        breadcrumbItems: typeof route.meta.breadcrumb === 'function'
          ? route.meta.breadcrumb(route)
          : (route.meta.breadcrumb || [])
      }),
      children: [
        ...documentRoutes
      ]
    }
  ],
  menu: documentMenu
};
