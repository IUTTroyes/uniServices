import { LayoutComponent, Access } from '@components';
import { createRouter, createWebHistory } from 'vue-router';
import { useUsersStore } from "@stores";
import { hasPermission } from '@utils';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import dashboardRoutes from './modules/dashboardRoutes';

// Define the menu structure
const intranetMenu = [
    {
        items: [
            { label: 'Tableau de Bord', icon: 'pi pi-fw pi-home', to: '/' },
            { label: 'Espace Étudiant', icon: 'pi pi-fw pi-user', to: '/etudiant', permission: 'isEtudiant' },
            { label: 'Demande de Convention', icon: 'pi pi-fw pi-file-edit', to: '/demande', permission: 'isEtudiant' },
            { label: 'Espace Tuteur', icon: 'pi pi-fw pi-users', to: '/enseignant', permission: 'isPersonnel' },
            { label: 'Espace Responsable', icon: 'pi pi-fw pi-shield', to: '/responsable', permission: 'isPersonnel' },
            { label: 'Modèles de Convention', icon: 'pi pi-fw pi-cog', to: '/admin/templates', permission: 'isSuperAdmin' },
        ]
    }
];

const appName = 'Stage';

const router = createRouter({
    history: createWebHistory('/stage/'),
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
                ...dashboardRoutes
            ]
        },
        {
            path: '/access',
            name: 'access',
            component: Access,
            meta: { title: 'Accès Refusé' }
        }
    ]
});

router.beforeEach(async (to, from) => {
    document.title = to.meta.title ? (to.meta.title + ' | Stages & Alternances - Uniservices ') : 'Stages & Alternances - Uniservices';

    const userStore = useUsersStore();

    // Restauration du rôle simulé pour la démo
    const storedRole = sessionStorage.getItem('simulated_role');
    if (storedRole) {
        if (storedRole === 'REAL') {
            userStore.clearTemporaryRole();
        } else {
            userStore.setTemporaryRole(storedRole);
        }
    }

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
            window.location.href = window.location.origin + '/auth/login';
            return false;
        }

        // Charger les données utilisateur si pas encore fait
        if (!userStore.isLoaded && !userStore.isLoading) {
            await userStore.getUser();
        }

        // Vérification des permissions
        const requiredPermission = to.meta.permission || to.matched.find(record => record.meta.permission)?.meta.permission;
        if (requiredPermission && !hasPermission(requiredPermission)) {
            return '/access';
        }

        return true;
    } catch (error) {
        console.error('Auth error:', error);
        window.location.href = window.location.origin + '/auth/login';
        return false;
    }
});

export default router;
