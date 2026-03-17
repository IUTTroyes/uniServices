import { LayoutComponent } from '@components';
import { createRouter, createWebHistory } from 'vue-router';
import dashboardRoutes from './modules/dashboardRoutes';
import agendaRoutes from "./modules/agendaRoutes.js";
import trombinoscopeRoutes from "./modules/trombinoscopeRoutes.js";
import profilRoutes from "./modules/profilRoutes.js";
import administrationRoutes from "./modules/administrationRoutes.js";
import superAdministrationRoutes from "./modules/superAdministrationRoutes.js";
import {useUsersStore} from "@stores";
import { hasPermission } from '@utils';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";

// Define the menu structure
const intranetMenu = [
    {
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' },
            { label: 'Agenda', icon: 'pi pi-fw pi-calendar', to: '/agenda' },
            { label: 'Trombinoscope', icon: 'pi pi-fw pi-users', to: '/trombinoscope' },
            {
                label: 'Administration',
                icon: 'pi pi-fw pi-wrench',
                to: '/administration',
                permission: 'canViewAdministration'
            },
            {
                label: 'Super Admin',
                icon: 'pi pi-fw pi-cog',
                to: '/super-administration',
                permission: 'isSuperAdmin'
            }
        ]
    }
];

const appName = 'Intranet';

const router = createRouter({
    history: createWebHistory('/intranet/'),
    routes: [
        {
            path: '/',
            component: LayoutComponent,
            props: route => {
                // Process menu items and check permissions every time the component is rendered
                const processedMenu = intranetMenu.map(category => {
                    const processedItems = category.items.map(item => {
                        // If the item has a permission property, check if the user has that permission
                        if (item.permission) {
                            return {
                                ...item,
                                visible: hasPermission(item.permission)
                            };
                        }
                        return item;
                    });

                    return {
                        ...category,
                        items: processedItems
                    };
                });

                // If route.meta.breadcrumb is a function, call it to get the items (this allows reading stores lazily)
                let breadcrumbItems = route.meta.breadcrumb || [];
                if (typeof route.meta.breadcrumb === 'function') {
                    try {
                        breadcrumbItems = route.meta.breadcrumb(route) || [];
                    } catch (e) {
                        console.error('Error while evaluating breadcrumb function for route', route.name, e);
                        breadcrumbItems = [];
                    }
                }

                return {
                    menuItems: processedMenu,
                    logoUrl: Logo,
                    appName: appName,
                    breadcrumbItems
                };
            },
            children: [
                ...dashboardRoutes,
                ...agendaRoutes,
                ...trombinoscopeRoutes,
                ...profilRoutes,
                ...administrationRoutes,
                ...superAdministrationRoutes,
            ]
        },
    ]
});

router.beforeEach(async (to, from) => {
    document.title = to.meta.title ? (to.meta.title + ' | Intranet - Uniservices ') : 'UniTranet - Uniservices';

    const userStore = useUsersStore();

    // Gestion du paramètre logout
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('logout')) {
        await userStore.logout();
        return; // logout redirige automatiquement
    }

    // Routes publiques
    if (to.meta.public) {
        return true;
    }

    // Routes protégées : vérifier l'authentification
    try {
        // Initialiser l'authentification si pas encore fait
        const authInfo = await userStore.initAuth();

        if (!authInfo) {
            // Non authentifié, rediriger vers login
            window.location.href = '/auth/login';
            return false;
        }

        // Charger les données utilisateur si pas encore fait
        if (!userStore.isLoaded && !userStore.isLoading) {
            await userStore.getUser();
        }

        // Vérification des permissions
        const requiredPermission = to.meta.permission || to.matched.find(record => record.meta.permission)?.meta.permission;
        if (requiredPermission && !hasPermission(requiredPermission)) {
            console.warn(`Access denied to ${to.path}. Missing permission: ${requiredPermission}`);
            return { path: '/' }; // Rediriger vers le dashboard ou une page 403
        }

        return true;
    } catch (error) {
        console.error('Auth error:', error);
        window.location.href = '/auth/login';
        return false;
    }
});

export default router;
