export default [
    {
        path: 'documents',
        component: () => import('@/views/DocumentsView.vue'),
        name: 'Documents',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}, { label: 'Documents', route: null, icon: 'pi pi-folder'}] },
    },
    {
        path: '',
        component: () => import('@/views/DocumentsView.vue'),
        name: 'DocumentsRoot',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}, { label: 'Documents', route: null, icon: 'pi pi-folder'}] },
    },
];
