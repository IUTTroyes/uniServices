import Logo from '@images/logo/logo_intranet_iut_troyes.svg';
import LayoutComponent from '@components/components/layout/AppLayout.vue';
import { registerWidgets } from './widgets/registerWidgets';
import { unifolioSections } from './navigation';

const racine = { label: 'UniFolio', route: '/unifolio', icon: 'pi pi-folder' };

const sectionRoutes = unifolioSections.flatMap((section) =>
  section.items.map((item) => ({
    path: item.path,
    name: `unifolio-${item.path.replace(/\//g, '-')}`,
    component: () => import('./views/UnifolioAVenirView.vue'),
    meta: {
      permission: item.permission,
      titre: item.label,
      icon: item.icon,
      lot: item.lot,
      breadcrumb: [racine, { label: item.label, route: null, icon: item.icon }],
    },
  })),
);

export default {
  name: 'unifolio',
  primaryColor: 'emerald',
  registerWidgets,
  routes: [
    {
      path: '/unifolio',
      component: LayoutComponent,
      props: (route) => ({
        logoUrl: Logo,
        appName: 'UniFolio',
        breadcrumbItems: route.meta.breadcrumb || [{ ...racine, route: null }],
      }),
      children: [
        {
          path: '',
          name: 'unifolio-home',
          component: () => import('./views/UnifolioHomeView.vue'),
          meta: { breadcrumb: [{ ...racine, route: null }] },
        },
        ...sectionRoutes,
      ],
    },
  ],
  menu: {
    label: 'Portfolios',
    icon: 'pi pi-fw pi-folder',
    items: [
      { label: 'Accueil', icon: 'pi pi-fw pi-home', to: '/unifolio' },
      ...unifolioSections.flatMap((section) =>
        section.items.map(({ label, icon, to, permission }) => ({ label, icon, to, permission })),
      ),
    ],
  },
};
