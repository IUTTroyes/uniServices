export default [
    {
        path: '',
        alias: '/',
        component: () => import('@/views/DashboardView.vue'),
        name: 'HelpdeskDashboard',
        meta: {
            permission: 'isPersonnel',
            breadcrumb: [{ label: 'Dashboard', route: '/'}] },
    },
    {
        path: 'service',
        component: () => import('@/views/PersonnelService/DashboardServiceView.vue'),
        name: 'DashboardService',
        meta: { breadcrumb: [{ label: 'DashboardService', route: null}] },
    },
];
