export default [
    {
        path: 'contraintes',
        component: () => import('@/views/ContraintesView.vue'),
        name: 'contraintes',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Contraintes', route: null, icon: 'pi pi-calendar' }] },
    },
];
