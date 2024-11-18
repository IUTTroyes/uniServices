export default [
    {
        path: '',
        component: () => import('@/views/Dashboard.vue'),
        name: 'Dashboard',
        meta: { breadcrumb: [{ label: 'Home', route: '/', icon:'pi pi-home'}] },
        //meta: { requiresAuth: true },
    },
];
