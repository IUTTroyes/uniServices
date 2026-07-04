export default [
    {
        path: '',
        alias: '/',
        component: () => import('@/views/IntranetDashboardView.vue'),
        name: 'IntranetDashboard',
    },
    {
        path: 'widgets',
        component: () => import('@components/components/Dashboard/DashboardWidgetsConfiguration.vue'),
        name: 'IntranetDashboardWidgetsConfig',
    },
];
