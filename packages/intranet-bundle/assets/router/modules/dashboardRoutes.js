export default [
    {
        path: '',
        component: () => import('@/views/DashboardView.vue'),
        name: 'Dashboard',
    },
    {
        path: 'widgets',
        component: () => import('@/views/DashboardView.vue'),
        name: 'DashboardWidgetsConfig',
    },
];
