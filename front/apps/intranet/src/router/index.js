import { LayoutComponent } from '@components';
import { createRouter, createWebHistory } from 'vue-router';
import dashboardRoutes from './modules/dashboardRoutes';
import agendaRoutes from "./modules/agendaRoutes.js";
import trombinoscopeRoutes from "./modules/trombinoscopeRoutes.js";
import profilRoutes from "./modules/profilRoutes.js";
import administrationRoutes from "./modules/administrationRoutes.js";
import MesFichesHeuresPage from '@/pages/FicheHeures/MesFichesHeuresPage.vue';
import FicheHeureCreatePage from '@/pages/FicheHeures/FicheHeureCreatePage.vue';
import FicheHeureEditPage from '@/pages/FicheHeures/FicheHeureEditPage.vue';
import FicheHeureDetailPage from '@/pages/FicheHeures/FicheHeureDetailPage.vue';
import ValidationFichesHeuresPage from '@/pages/FicheHeures/ValidationFichesHeuresPage.vue'; // Added import
import {useUsersStore} from "@stores";
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";

// Initialize user store to check roles/status for menu
const userStoreForMenu = useUsersStore(); 
// It's assumed that the store initializes the user state synchronously if possible (e.g., from localStorage)
// or that menu reactivity to async user loading is handled by LayoutComponent or by re-evaluating props.

const baseMenuItems = [
    { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' },
    { label: 'Agenda', icon: 'pi pi-fw pi-calendar', to: '/agenda' },
    { label: 'Trombinoscope', icon: 'pi pi-fw pi-users', to: '/trombinoscope' },
    { label: 'Administration', icon: 'pi pi-fw pi-wrench', to: '/administration' },
];

// Conditionally add FicheHeure menu item
// This check runs once when the router module is initialized.
// For dynamic menu updates based on login/logout or role changes during the session,
// LayoutComponent would need to be reactive to the user store.
if (userStoreForMenu.user && 
    ( (userStoreForMenu.user.roles && userStoreForMenu.user.roles.includes('ROLE_BIATSS')) ||
      (userStoreForMenu.user.statut && userStoreForMenu.user.statut.value === 'BIATSS') // Assuming statut.value holds the key like 'BIATSS'
    )
   ) {
  baseMenuItems.push({ 
    label: 'Mes Fiches d\'Heures', 
    icon: 'pi pi-fw pi-clock', 
    to: '/mes-fiches-heures' 
  });
}

// Conditionally add FicheHeure Validation menu item
if (userStoreForMenu.user && 
    userStoreForMenu.user.roles && 
    userStoreForMenu.user.roles.includes('ROLE_FICHE_HEURE_VALIDATEUR')
   ) {
  baseMenuItems.push({ 
    label: 'Validation Fiches d\'Heures', 
    icon: 'pi pi-fw pi-check-square', 
    to: '/validation/fiches-heures' 
  });
}

const intranetMenu = [
    {
        items: baseMenuItems
    }
];

const appName = 'Intranet';

const router = createRouter({
    history: createWebHistory('/intranet/'),
    routes: [
        {
            path: '/',
            component: LayoutComponent,
            props: route => ({
                menuItems: intranetMenu,
                logoUrl: Logo,
                appName: appName,
                breadcrumbItems: route.meta.breadcrumb || []
            }),
            children: [
                ...dashboardRoutes,
                ...agendaRoutes,
                ...trombinoscopeRoutes,
                ...profilRoutes,
                ...administrationRoutes,
                // Added Fiche Heures Route
                {
                  path: '/mes-fiches-heures',
                  name: 'MesFichesHeures',
                  component: MesFichesHeuresPage,
                  meta: { 
                    requiresAuth: true, 
                    roles: ['ROLE_BIATSS'], 
                    breadcrumb: [{ label: 'Mes Fiches d\'Heures' }] 
                  }
                },
                { 
                  path: '/fiches-heures/nouveau', 
                  name: 'FicheHeureCreate', 
                  component: FicheHeureCreatePage, 
                  meta: { 
                    requiresAuth: true, 
                    roles: ['ROLE_BIATSS'], 
                    breadcrumb: [{ label: 'Mes Fiches d\'Heures', to: '/mes-fiches-heures' }, { label: 'Nouvelle' }] 
                  } 
                },
                { 
                  path: '/fiches-heures/:id/modifier', 
                  name: 'FicheHeureEdit', 
                  component: FicheHeureEditPage, 
                  meta: { 
                    requiresAuth: true, 
                    roles: ['ROLE_BIATSS'], 
                    breadcrumb: [{ label: 'Mes Fiches d\'Heures', to: '/mes-fiches-heures' }, { label: 'Modifier' }] 
                  } 
                },
                { 
                  path: '/fiches-heures/:id', 
                  name: 'FicheHeureDetail', 
                  component: FicheHeureDetailPage, 
                  meta: { 
                    requiresAuth: true, 
                    roles: ['ROLE_BIATSS', 'ROLE_FICHE_HEURE_VALIDATEUR'], // Placeholder for validator access
                    breadcrumb: [{ label: 'Mes Fiches d\'Heures', to: '/mes-fiches-heures' }, { label: 'Détail' }] 
                  } 
                },
                {
                  path: '/validation/fiches-heures',
                  name: 'ValidationFichesHeures',
                  component: ValidationFichesHeuresPage,
                  meta: { 
                    requiresAuth: true, 
                    roles: ['ROLE_FICHE_HEURE_VALIDATEUR'], 
                    breadcrumb: [{ label: 'Validation Fiches d\'Heures' }] 
                  }
                }
            ]
        },
    ]
});

router.beforeEach(async(to, from, next) => {
    document.title = to.meta.title ?  (to.meta.title + ' | Intranet - Uniservices ') : 'UniTranet - Uniservices';

    const token = localStorage.getItem('token');
    const userStore = useUsersStore();

    if (!userStore.isLoaded && !userStore.isLoading) {
        try {
            // si la route est login, on ne charge pas l'utilisateur
            if (to.path === '/login') {
                return next();
            }
            await userStore.getUser()

        } catch (error) {
            console.error(error);
        }
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('logout')) {
        localStorage.removeItem('token');
        window.location.replace('http://localhost:3000/auth/login');
    }

    if (token) {
        const tokenParts = token.split('.');
        const payload = JSON.parse(atob(tokenParts[1]));
        const exp = payload.exp * 1000; // Convert to milliseconds

        if (Date.now() >= exp) {
            localStorage.removeItem('token');
            return window.location.href = 'http://localhost:3000/auth/login';
        }

        if (to.path === '/login') {
            return next('/portail');
        }
    }

    if (!token && to.path !== '/login') {
        return window.location.href = 'http://localhost:3000/auth/login';
    }

    // Role-based authorization check
    if (to.meta.roles && to.meta.roles.length > 0) {
        if (!userStore.user || !userStore.user.roles || !userStore.user.roles.some(role => to.meta.roles.includes(role))) {
            // User does not have any of the required roles
            console.warn(`User does not have required roles for ${to.path}. Required: ${to.meta.roles.join(', ')}. User roles: ${userStore.user?.roles?.join(', ')}`);
            return next('/'); // Redirect to dashboard or an unauthorized page
        }
    }

    next();
});

export default router;
