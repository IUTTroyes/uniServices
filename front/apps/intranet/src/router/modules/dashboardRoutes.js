export default [
    {
        path: '',
        component: () => import('@/views/DashboardView.vue'),
        name: 'Dashboard',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}] },
    },
    {
        path: 'trombinoscope',
        component: () => import('@/views/TrombinoscopeView.vue'),
        name: 'Dashboard',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}, { label: 'Trombinoscope', route: null}] },
    },
];
