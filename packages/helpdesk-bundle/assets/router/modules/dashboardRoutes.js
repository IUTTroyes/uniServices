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
        component: () => import('@/views/Personnel/DashboardServiceView.vue'),
        name: 'DashboardService',
        meta: { breadcrumb: [{ label: 'DashboardService', route: null}] },
    },
];
