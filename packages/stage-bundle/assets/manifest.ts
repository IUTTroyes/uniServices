import dashboardRoutes from './router/modules/dashboardRoutes.js';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import LayoutComponent from '@components/components/layout/AppLayout.vue';

const stageMenu = {
  label: 'Stages',
  icon: 'pi pi-fw pi-briefcase',
  items: [
    { label: 'Tableau de Bord', icon: 'pi pi-fw pi-home', to: '/stage/' },
    { label: 'Espace Étudiant', icon: 'pi pi-fw pi-user', to: '/stage/etudiant', permission: 'isEtudiant' },
    { label: 'Demande de Convention', icon: 'pi pi-fw pi-file-edit', to: '/stage/demande', permission: 'isEtudiant' },
    { label: 'Espace Tuteur', icon: 'pi pi-fw pi-users', to: '/stage/enseignant', permission: 'isPersonnel' },
    { label: 'Espace Responsable', icon: 'pi pi-fw pi-shield', to: '/stage/responsable', permission: 'ROLE_STAGE_MANAGER' },
    { label: 'Modèles de Convention', icon: 'pi pi-fw pi-cog', to: '/stage/admin/templates', permission: 'ROLE_SUPER_ADMIN' },
  ]
};

export default {
  name: 'stages',
  primaryColor: 'teal',
  routes: [
    {
      path: '/stage',
      component: LayoutComponent,
      props: route => ({
        logoUrl: Logo,
        appName: 'Stage',
        breadcrumbItems: typeof route.meta.breadcrumb === 'function'
          ? route.meta.breadcrumb(route)
          : (route.meta.breadcrumb || [])
      }),
      children: [
        ...dashboardRoutes
      ]
    }
  ],
  menu: stageMenu
};
