export default [
    {
        path: 'emploi-du-temps',
        component: () => import('@/views/EdtView.vue'),
        name: 'emploi-du-temps',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Emploi du temps', route: null, icon: 'pi pi-calendar' }] },
    },
];
