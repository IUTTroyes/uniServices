export default [
    {
        path: '',
        component: () => import('@/views/DashboardView.vue'),
        name: 'Dashboard',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}] },
    },
];
