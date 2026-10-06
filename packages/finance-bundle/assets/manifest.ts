import FinanceDashboardView from './views/FinanceDashboardView.vue';
import DemandeAchatDetailView from './views/DemandeAchatDetailView.vue';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import LayoutComponent from '@components/components/layout/AppLayout.vue';

const financeMenu = {
  label: 'Finances & Achats',
  icon: 'pi pi-fw pi-wallet',
  items: [
    { label: 'Tableau de bord', icon: 'pi pi-fw pi-chart-bar', to: '/finance/' },
    { label: 'Nouvelle demande', icon: 'pi pi-fw pi-plus-circle', to: '/finance/demande/nouveau' },
  ]
};

export default {
  name: 'finance',
  primaryColor: 'emerald',
  routes: [
    {
      path: '/finance',
      component: LayoutComponent,
      props: route => ({
        logoUrl: Logo,
        appName: 'Finances & Achats',
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
            breadcrumb: [{ label: 'Finances', route: '/finance/' }, { label: 'Tableau de bord', route: null }]
          }
        },
        {
          path: 'demande/nouveau',
          name: 'finance_demande_new',
          component: DemandeAchatDetailView,
          meta: {
            title: 'Nouvelle demande',
            breadcrumb: [{ label: 'Finances', route: '/finance/' }, { label: 'Nouvelle demande', route: null }]
          }
        },
        {
          path: 'demande/:id',
          name: 'finance_detail',
          component: DemandeAchatDetailView,
          props: true,
          meta: {
            title: 'Détail Demande',
            breadcrumb: [{ label: 'Finances', route: '/finance/' }, { label: 'Détail de la commande', route: null }]
          }
        }
      ]
    }
  ],
  menu: financeMenu
};
