import { LayoutComponent } from 'common-components';
import { createRouter, createWebHistory } from 'vue-router';
import dashboardRoutes from './modules/dashboardRoutes';

const intranetMenu = [
    {
        label: 'Home',
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/' },
            { label: 'Agenda', icon: 'pi pi-fw pi-calendar', to: '/agenda' },
            { label: 'Trombinoscope', icon: 'pi pi-fw pi-users', to: '/trombinoscope' },
        ]
    }
];

const logoUrl = new URL('@/assets/images/logo_intranet_iut_troyes.svg', import.meta.url).href;

const router = createRouter({
    history: createWebHistory('/intranet/'),
    routes: [
        {
            path: '/',
            component: LayoutComponent,
            props: {
                menuItems: intranetMenu,
                logoUrl: logoUrl
            },
            children: [
                ...dashboardRoutes,
                {
                    path: '/uikit/formlayout',
                    name: 'formlayout',
                    component: () => import('@/views/uikit/FormLayout.vue')
                },
            ]
        }
    ]
});

export default router;
