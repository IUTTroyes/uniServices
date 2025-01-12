import { LayoutComponent } from '@components';
import { createRouter, createWebHistory } from 'vue-router';
import dashboardRoutes from './modules/dashboardRoutes';
import agendaRoutes from "@/router/modules/agendaRoutes.js";
import profilRoutes from "@/router/modules/profilRoutes.js";
import administrationRoutes from '@/router/modules/administrationRoutes.js'

const intranetMenu = [
    {
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' },
            { label: 'Agenda', icon: 'pi pi-fw pi-calendar', to: '/agenda' },
            { label: 'Trombinoscope', icon: 'pi pi-fw pi-users', to: '/trombinoscope' },
            { label: 'Administration', icon: 'pi pi-fw pi-wrench', to: '/administration' },
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
            props: route => ({
                menuItems: intranetMenu,
                appName: appName,
                breadcrumbItems: route.meta.breadcrumb || []
            }),
            children: [
                ...dashboardRoutes,
                ...agendaRoutes,
                ...profilRoutes,
                ...administrationRoutes
            ]
        },
    ]
});

export default router;
