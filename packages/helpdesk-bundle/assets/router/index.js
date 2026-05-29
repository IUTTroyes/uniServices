import { LayoutComponent, Access } from '@components';
import { createRouter, createWebHistory } from 'vue-router';
import {useUsersStore} from "@stores";
import { hasPermission } from '@utils';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import NewTicketView from '@/views/Agent/NewTicketView.vue';
import MyTicketView from '@/views/Agent/MyTicketView.vue';
import TicketListAdminView from '@/views/Personnel/TicketListAdminView.vue';
import TicketView from '@/views/TicketView.vue'
import dashboardRoutes from './modules/dashboardRoutes';

// Define the menu structure
const intranetMenu = [
    {
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' },
            { label: 'DashboardService', icon: 'pi pi-fw pi-home', to: '/service' , permission:'isPersonnelService'},
            { label: 'Nouveau Ticket', icon: 'pi pi-fw pi-receipt', to: '/nouveauticket' },
            { label: 'Mes Tickets', icon: 'pi pi-fw pi-ticket', to: '/mestickets' },
            { label: 'Tous les Tickets', icon: 'pi pi-fw pi-list', to: '/ticketsliste', permission: 'isPersonnelService'}
        ]
    }
];

const appName = 'Helpdesk';

const router = createRouter({
    history: createWebHistory('/helpdesk/'),
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
                {
                    path: '/nouveauticket',
                    name: 'NewTicketView',
                    component: NewTicketView,
                    meta: {
                        breadcrumb: [{ label: 'Dashboard', route: '/' }, {
                            label: 'Nouveau Ticket',
                            route: null,
                            icon: 'pi pi-wrench'
                        }]
                    },
                },
                {
                    path: '/mestickets',
                    name: 'MyTicketView',
                    component: MyTicketView,
                    meta: {
                        breadcrumb: [{ label: 'Dashboard', route: '/' }, {
                            label: 'Mes tickets',
                            route: null,
                            icon: 'pi pi-wrench'
                        }]
                    },
                },
                {
                    path: '/ticketsliste',
                    name: 'TicketListAdminView',
                    component: TicketListAdminView,
                    meta: {
                        permission: 'isPersonnel',
                        title: 'Gestion Tickets',
                        breadcrumb: [{ label: 'Dashboard', route: '/' }, {
                            label: 'Liste des Tickets',
                            route: null,
                            icon: 'pi pi-wrench'
                        }]
                    }
                },
                {
                    path: '/ticket',
                    name: 'TicketView',
                    component: 'TicketView',
                    meta: {
                        title: 'Ticket',
                        breadcrumb: [{ label: 'Dashboard', route: '/' }, {
                            label: 'Tickets',
                            route: null,
                            icon: 'pi pi-wrench'
                        }]
                    }
                },
                {
                    path: '/ticket/:id',
                    name: 'TicketView',
                    component: () => import('@/views/TicketView.vue'),
                    props: true,
                    meta: {
                        breadcrumb: [{ label: 'Dashboard', route: '/' },{ label: 'Mes Tickets', route: '/mestickets' }, {
                            label: 'Ticket',
                            route: null,
                            icon: 'pi pi-wrench'
                        }]
                    },
                }
            ],
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
    document.title = to.meta.title ? (to.meta.title + ' | Helpdesk - Uniservices ') : 'Helpdesk - Uniservices';

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
