export default [
    {
        path: 'documents',
        component: () => import('@/views/DocumentsView.vue'),
        name: 'Documents',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}, { label: 'Documents', route: null, icon: 'pi pi-folder'}] },
    },
    {
        path: 'documents/configuration',
        component: () => import('@/views/Configuration/DocumentConfigurationView.vue'),
        name: 'DocumentConfiguration',
        meta: {
            permission: 'isChefDepartement',
            breadcrumb: [{ label: 'Documents', route: '/documents'}, { label: 'Configuration', route: null, icon: 'pi pi-sliders-h'}]
        },
    },
    {
        path: '',
        component: () => import('@/views/DocumentsView.vue'),
        name: 'DocumentsRoot',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}, { label: 'Documents', route: null, icon: 'pi pi-folder'}] },
    },
];
