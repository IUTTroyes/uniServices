export default [
    {
        path: '',
        component: () => import('@/views/DashboardView.vue'),
        name: 'Dashboard',
        meta: {
            breadcrumb: [{ label: 'Dashboard', route: '/'}] },
    },
    {
        path: 'service',
        component: () => import('@/views/PersonnelService/DashboardServiceView.vue'),
        name: 'DashboardService',
        meta: {
            permission: 'isPersonnelService',
            breadcrumb: [{ label: 'DashboardService', route: null}] },
    },
];
