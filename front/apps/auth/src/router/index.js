import AppLayout from '@/layout/AppLayout.vue';
import { createRouter, createWebHistory } from 'vue-router';
import { LoginComponent } from 'common-components';

const router = createRouter({
    history: createWebHistory('/auth/'),
    routes: [
        {
            path: '/connexion',
            component: LoginComponent,
        },
        {
            path: '/inscription',
            component: LoginComponent,
        }
    ]
});

export default router;
