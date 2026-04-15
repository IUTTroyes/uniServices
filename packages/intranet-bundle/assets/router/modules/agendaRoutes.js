export default [
    {
        path: 'agenda',
        component: () => import('@/views/AgendaView.vue'),
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Agenda', route: null, icon: 'pi pi-calendar' }] },
    }
];
