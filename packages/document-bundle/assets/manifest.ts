import documentRoutes from './router/modules/documentRoutes.js';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import LayoutComponent from '@components/components/layout/AppLayout.vue';
import { registerWidgets } from './widgets/registerWidgets';

const documentMenu = {
  label: 'Documents',
  icon: 'pi pi-fw pi-folder',
  items: [
    { label: 'Tous les documents', icon: 'pi pi-fw pi-file', to: '/documents' },
  ]
};

export default {
  name: 'documents',
  primaryColor: 'blue',
  registerWidgets,
  routes: [
    {
      path: '/',
      component: LayoutComponent,
      props: (route: any) => ({
        logoUrl: Logo,
        appName: 'Documents',
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
