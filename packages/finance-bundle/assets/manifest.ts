import FinanceDashboardView from './views/FinanceDashboardView.vue';
import DemandeAchatDetailView from './views/DemandeAchatDetailView.vue';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import LayoutComponent from '@components/components/layout/AppLayout.vue';
import { getApplicationMeta } from '@utils/applicationColors';

const app = getApplicationMeta('finance');

const financeMenu = {
  label: app.name,
  icon: `${app.icon} pi-fw`,
  items: [
    { label: 'Tableau de bord', icon: 'pi pi-fw pi-chart-bar', to: '/finance' },
    { label: 'Nouvelle demande', icon: 'pi pi-fw pi-plus-circle', to: '/finance/demande/nouveau', permission: 'ROLE_FINANCE_DEMANDEUR' },
  ],
  bottomItems: [
    {
      label: 'Configuration',
      icon: 'pi pi-fw pi-sliders-h',
      to: '/finance/configuration',
      permission: 'ROLE_FINANCE_MANAGER'
    }
  ]
};

export default {
  name: 'finance',
  routes: [
    {
      path: '/finance',
      component: LayoutComponent,
      props: route => ({
        logoUrl: Logo,
        appName: app.name,
        breadcrumbItems: typeof route.meta.breadcrumb === 'function'
          ? route.meta.breadcrumb(route)
          : (route.meta.breadcrumb || [])
      }),
      children: [
        {
          path: '',
          name: 'finance_dashboard',
          component: FinanceDashboardView,
          meta: {
            title: 'Tableau de bord financier',
            breadcrumb: [{ label: app.name, route: '/finance' }, { label: 'Tableau de bord', route: null }]
          }
        },
        {
          path: 'demande/nouveau',
          name: 'finance_demande_new',
          component: DemandeAchatDetailView,
          meta: {
            title: 'Nouvelle demande',
            breadcrumb: [{ label: app.name, route: '/finance' }, { label: 'Nouvelle demande', route: null }]
          }
        },
        {
          path: 'demande/:id',
          name: 'finance_detail',
          component: DemandeAchatDetailView,
          props: true,
          meta: {
            title: 'Détail Demande',
            breadcrumb: [{ label: app.name, route: '/finance' }, { label: 'Détail de la commande', route: null }]
          }
        },
        {
          path: 'configuration',
          name: 'finance_configuration',
          component: () => import('./views/Configuration/FinanceConfigurationView.vue'),
          meta: {
            permission: 'ROLE_FINANCE_MANAGER',
            title: 'Configuration des Finances',
            breadcrumb: [{ label: app.name, route: '/finance' }, { label: 'Configuration', route: null, icon: 'pi pi-sliders-h' }]
          }
        }
      ]
    }
  ],
  menu: financeMenu
};
