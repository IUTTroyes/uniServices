import { createRouter, createWebHistory } from 'vue-router';
import { bundles } from '../bundles-registry';
import { useUsersStore, useSecurity } from '@stores';
import { hasPermission } from '@utils';
import { applyBundleTheme } from '../composables/useBundleTheme';

// Gather routes from all registered bundles
const bundleRoutes = [];
bundles.forEach(bundle => {
  if (bundle.routes) {
    bundleRoutes.push(...bundle.routes);
  }
});

const router = createRouter({
  history: createWebHistory('/app/'),
  routes: [
    {
      path: '/',
      redirect: '/auth/portail'
    },
    {
      path: '/login',
      redirect: '/auth/login'
    },
    {
      path: '/portail',
      redirect: '/auth/portail'
    },
    {
      path: '/configuration',
      redirect: '/auth/configuration'
    },
    {
      path: '/profil',
      redirect: '/auth/profil'
    },
    ...bundleRoutes,
    {
      path: '/access',
      name: 'access',
      component: () => import('@components/pages/Access.vue'),
      meta: { title: 'Accès Refusé', public: true }
    },
    {
      path: '/404',
      component: () => import('@components/pages/NotFound.vue'),
      meta: { title: 'Page Introuvable', public: true }
    },
    // Catch-all route for 404
    {
      path: '/:pathMatch(.*)*',
      redirect: '/404'
    }
  ]
});

router.beforeEach(async (to, from) => {
  // Update document title
  document.title = to.meta.title ? (to.meta.title + ' | Uniservices') : 'Uniservices';

  const userStore = useUsersStore();

  // Handle logout parameter
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('logout')) {
    await userStore.logout();
    return;
  }

  // Public routes
  if (to.meta.public) {
    if (to.path === '/auth/login' && userStore.isAuthInitialized && userStore.userId) {
      return '/app/auth/portail';
    }
    return true;
  }

  // Protected routes
  try {
    const authInfo = await userStore.initAuth();
    if (!authInfo) {
      // Redirect to login page inside the Shell
      return '/app/auth/login';
    }

    if (!userStore.isLoaded && !userStore.isLoading) {
      await userStore.getUser();
    }

    const security = useSecurity();
    if (!security.isLoaded && !security.isLoading) {
      await security.loadSecurityContext();
    }

    // Check package access
    const requiredPackage = to.meta.package || to.matched.find(record => record.meta.package)?.meta.package;
    if (requiredPackage && !security.hasPackage(requiredPackage)) {
      return '/app/access';
    }

    // Permission checks
    const requiredPermission = to.meta.permission || to.matched.find(record => record.meta.permission)?.meta.permission;
    if (requiredPermission && !hasPermission(requiredPermission)) {
      return '/app/access';
    }

    return true;
  } catch (error) {
    console.error('Auth error in router guard:', error);
    return '/app/auth/login';
  }
});

router.afterEach((to) => {
  applyBundleTheme(to.path);
});

export default router;
