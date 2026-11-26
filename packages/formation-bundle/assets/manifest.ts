import Logo from '@images/logo/logo_intranet_iut_troyes.svg';
import LayoutComponent from '@components/components/layout/AppLayout.vue';
import { formationRoutes, anciennesAdresses } from './router/routes.js';

const racine = { label: 'Formations', route: '/formation', icon: 'pi pi-sitemap' };

export default {
  name: 'formation',
  routes: [
    {
      path: '/formation',
      component: LayoutComponent,
      props: (route) => ({
        logoUrl: Logo,
        appName: 'Formations',
        breadcrumbItems: typeof route.meta.breadcrumb === 'function'
          ? route.meta.breadcrumb(route)
          : (route.meta.breadcrumb || [{ ...racine, route: null }]),
      }),
      children: formationRoutes,
    },
    ...anciennesAdresses,
  ],
  menu: {
    label: 'Formations',
    icon: 'pi pi-fw pi-sitemap',
    items: [
      { label: 'Accueil', icon: 'pi pi-fw pi-home', to: '/formation', permission: 'canViewAdministration' },
      { label: 'Compétences', icon: 'pi pi-fw pi-list', to: '/formation/referentiels-competences', permission: 'canViewAdministration' },
      { label: 'Programmes (PN)', icon: 'pi pi-fw pi-book', to: '/formation/pn', permission: 'canViewAdministration' },
      { label: 'Étudiants', icon: 'pi pi-fw pi-graduation-cap', to: '/formation/etudiants', permission: 'canViewAdministration' },
      { label: 'Personnels', icon: 'pi pi-fw pi-users', to: '/formation/personnels', permission: 'canViewAdministration' },
      { label: 'Départements', icon: 'pi pi-fw pi-building', to: '/formation/departements', permission: 'SUPER_ADMIN' },
      { label: 'Types de diplômes', icon: 'pi pi-fw pi-tags', to: '/formation/types-diplomes', permission: 'SUPER_ADMIN' },
    ],
  },
};
