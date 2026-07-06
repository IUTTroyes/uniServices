export default [
    {
        path: '',
        alias: '/',
        component: () => import('@/views/DashboardView.vue'),
        name: 'QuestionnaireDashboard',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}] },
    },
];
