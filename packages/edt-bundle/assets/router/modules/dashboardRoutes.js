export default [
    {
        path: '',
        alias: '/',
        component: () => import('@/views/DashboardView.vue'),
        name: 'EdtDashboard',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}] },
    },
];
