import dashboardRoutes from './router/modules/dashboardRoutes.js';
import agendaRoutes from './router/modules/agendaRoutes.js';
import trombinoscopeRoutes from './router/modules/trombinoscopeRoutes.js';
import profilRoutes from './router/modules/profilRoutes.js';
import administrationRoutes from './router/modules/administrationRoutes.js';
import superAdministrationRoutes from './router/modules/superAdministrationRoutes.js';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import { LayoutComponent } from '@components';

const intranetMenu = {
  label: 'Intranet',
  icon: 'pi pi-fw pi-desktop',
  items: [
    { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/intranet/' },
    { label: 'Agenda', icon: 'pi pi-fw pi-calendar', to: '/intranet/agenda' },
    { label: 'Trombinoscope', icon: 'pi pi-fw pi-users', to: '/intranet/trombinoscope' },
    {
      label: 'Administration',
      icon: 'pi pi-fw pi-wrench',
      to: '/intranet/administration',
      permission: 'canViewAdministration'
    },
    {
      label: 'Super Admin',
      icon: 'pi pi-fw pi-cog',
      to: '/intranet/super-administration',
      permission: 'ROLE_SUPER_ADMIN'
    }
  ]
};

export default {
  name: 'intranet',
  routes: [
    {
      path: '/intranet',
      component: LayoutComponent,
      props: route => ({
        logoUrl: Logo,
        appName: 'Intranet',
        breadcrumbItems: route.meta.breadcrumb || []
      }),
      children: [
        ...dashboardRoutes,
        ...agendaRoutes,
        ...trombinoscopeRoutes,
        ...profilRoutes,
        ...administrationRoutes,
        ...superAdministrationRoutes,
      ]
    }
  ],
  menu: intranetMenu
};
