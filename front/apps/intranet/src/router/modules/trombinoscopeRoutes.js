export default [
    {
        path: 'trombinoscope',
        component: () => import('@/views/TrombinoscopeView.vue'),
        name: 'Trombinoscope',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}, { label: 'Trombinoscope', route: null, icon: 'pi pi-users'}] },
    },
];
