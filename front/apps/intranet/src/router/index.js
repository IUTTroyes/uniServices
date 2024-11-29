import { LayoutComponent } from 'common-components';
import { createRouter, createWebHistory } from 'vue-router';
import dashboardRoutes from './modules/dashboardRoutes';
import agendaRoutes from "@/router/modules/agendaRoutes.js";

const intranetMenu = [
    {
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' },
            { label: 'Agenda', icon: 'pi pi-fw pi-calendar', to: '/agenda' },
            { label: 'Trombinoscope', icon: 'pi pi-fw pi-users', to: '/trombinoscope' },
        ]
    }
];

const logoUrl = new URL('@/assets/logo/logo_intranet_iut_troyes.svg', import.meta.url).href;

const appName = 'Intranet';

const router = createRouter({
    history: createWebHistory('/intranet/'),
    routes: [
        {
            path: '/',
            component: LayoutComponent,
            props: route => ({
                menuItems: intranetMenu,
                logoUrl: logoUrl,
                appName: appName,
                breadcrumbItems: route.meta.breadcrumb || []
            }),
            children: [
                ...dashboardRoutes, ...agendaRoutes
            ]
        },
    ]
});

export default router;
