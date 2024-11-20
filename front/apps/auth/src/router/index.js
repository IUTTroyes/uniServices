import AppLayout from '@/layout/AppLayout.vue';
import { createRouter, createWebHistory } from 'vue-router';
import { LoginComponent } from 'common-components';

const logoUrl = new URL('@/assets/logo/logo_iut.png', import.meta.url).href;


const router = createRouter({
    history: createWebHistory('/auth/'),
    routes: [
        {
            path: '/',
            component: LoginComponent,
            props: route => ({
                logoUrl: logoUrl,
            }),
        },
        {
            path: '/inscription',
            component: LoginComponent,
        }
    ]
});

export default router;
