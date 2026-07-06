import LoginView from './views/LoginView.vue';
import AppPortail from './views/PortailView.vue';
import AppProfil from './views/ProfilView.vue';
import ConfigurationRoutes from './router/modules/configurationRoutes.js';
import ResetPasswordView from "./views/ResetPasswordView.vue";
import ResetPasswordConfirmView from "./views/ResetPasswordConfirmView.vue";
import LogoIut from "@images/logo/logo_iut.png";
import LayoutComponent from '@components/components/layout/AppLayout.vue';

const authMenu = {
  label: 'Mon Espace',
  icon: 'pi pi-fw pi-user',
  items: [
    { label: 'Portail', icon: 'pi pi-fw pi-th-large', to: '/auth/portail' }
  ]
};

export default {
  name: 'auth',
  routes: [
    {
      path: '/auth',
      redirect: '/auth/portail'
    },
    {
      path: '/auth/login',
      name: 'login',
      component: LoginView,
      meta: { public: true }
    },
    {
      path: '/auth/reset-password',
      name: 'reset-password',
      component: ResetPasswordView,
      meta: { public: true }
    },
    {
      path: '/auth/reset-password/confirm',
      name: 'reset-password-confirm',
      component: ResetPasswordConfirmView,
      meta: { public: true }
    },
    {
      path: '/auth/portail',
      name: 'portail',
      component: AppPortail,
      props: route => ({
        logoUrl: LogoIut,
        appName: 'Uniservices',
      }),
      children: [
        {
          path: 'widgets/:bundle',
          component: () => import('@components/components/Dashboard/DashboardWidgetsConfiguration.vue'),
          name: 'PortailDashboardWidgetsConfig',
        }
      ]
    },
    {
      path: '/auth/configuration/',
      component: LayoutComponent,
      props: route => ({
        logoUrl: LogoIut,
        appName: 'Uniservices',
        breadcrumbItems: typeof route.meta.breadcrumb === 'function'
          ? route.meta.breadcrumb(route)
          : (route.meta.breadcrumb || [])
      }),
      children: [
        ...ConfigurationRoutes
      ]
    },
    {
      path: '/auth/profil',
      component: AppProfil,
      props: route => ({
        logoUrl: LogoIut,
        appName: 'Profil',
      }),
    }
  ],
  menu: authMenu
};
