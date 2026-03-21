export default [
    {
        path: 'progression-pedagogique',
        component: () => import('@/views/ProgressionView.vue'),
        name: 'progression-pedagogique',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Progression pédagogique', route: null, icon: 'pi pi-calendar' }] },
    },
];
