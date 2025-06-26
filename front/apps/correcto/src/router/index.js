import { LayoutComponent } from '@components';
import { createRouter, createWebHistory } from 'vue-router';
import {useUsersStore} from "@stores";
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import dashboardRoutes from './modules/dashboardRoutes';

const intranetMenu = [
    {
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' },
            { label: 'Administration', icon: 'pi pi-fw pi-wrench', to: '/administration' },
        ]
    }
];

const appName = 'Correcto';

const router = createRouter({
    history: createWebHistory('/correcto/'),
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
            ]
        },
    ]
});

router.beforeEach(async(to, from, next) => {
    document.title = to.meta.title ?  (to.meta.title + ' | Correcto - Uniservices ') : 'Correcto - Uniservices';

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

    next();
});

export default router;
