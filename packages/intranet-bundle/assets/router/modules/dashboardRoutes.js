export default [
    {
        path: '',
        component: () => import('@/views/DashboardView.vue'),
        name: 'Dashboard',
    },
    {
        path: 'widgets',
        component: () => import('@components/components/Dashboard/DashboardWidgetsConfiguration.vue'),
        name: 'IntranetDashboardWidgetsConfig',
    },
];
