import { createRouter, createWebHistory } from 'vue-router';
import LoginView from "@/views/LoginView.vue";
import AppPortail from "@/views/PortailView.vue";
const logoUrl = new URL('@/assets/logo/logo_iut.png', import.meta.url).href;


const router = createRouter({
    history: createWebHistory('/auth/'),
    routes: [
        {
            path: '/',
            component: LoginView,
            props: route => ({
                logoUrl: logoUrl,
            }),
        },
        {
            path: '/portail',
            component: AppPortail,
        }
    ]
});

export default router;
