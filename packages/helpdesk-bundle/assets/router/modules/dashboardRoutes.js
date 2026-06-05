export default [
    {
        path: '',
        component: () => import('@/views/DashboardView.vue'),
        name: 'Dashboard',
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
