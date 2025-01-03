import { createRouter, createWebHistory } from 'vue-router';
import LoginView from "@/views/LoginView.vue";
import AppPortail from "@/views/PortailView.vue";
const logoUrl = new URL('@/assets/logo/logo_iut.png', import.meta.url).href;

const router = createRouter({
    history: createWebHistory('/auth'),
    routes: [
        {
            path: '/',
            redirect: (to) => {
                const token = localStorage.getItem('token');
                if (token) {
                    return '/portail';
                } else {
                    return '/login';
                }
            }
        },
        {
            path: '/login',
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

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');

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
