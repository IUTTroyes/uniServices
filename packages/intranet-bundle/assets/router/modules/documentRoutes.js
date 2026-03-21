export default [
    {
        path: 'documents',
        component: () => import('@/views/DocumentsView.vue'),
        name: 'Documents',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}, { label: 'Documents', route: null, icon: 'pi pi-users'}] },
    },
];
