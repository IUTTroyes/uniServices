export default [
    {
        path: '',
        alias: '/',
        component: () => import('@/views/DashboardView.vue'),
        name: 'IntranetDashboard',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/intranet' }] },
    },
    {
        path: 'widgets',
        redirect: { name: 'PortailDashboardWidgetsConfig', params: { bundle: 'portail' } },
        name: 'IntranetDashboardWidgetsConfig',
    },
];
