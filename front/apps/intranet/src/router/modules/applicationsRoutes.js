export default [
    {
        path: 'applications',
        component: () => import('@/views/ApplicationsView.vue'),
        name: 'Applications',
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/'}, { label: 'Applications', route: null, icon: 'pi pi-wrench'}] },
    },
    {
        path: 'applications/etudiants/periode-stage/:id',
        component: () => import('@/views/Stages/EtuPeriodeStageView.vue'),
        name: 'AppPeriodeStage',
        meta: { breadcrumb: [
          { label: 'Dashboard', route: '/'},
                { label: 'Applications', route: '/applications', icon: 'pi pi-wrench'},
                { label: 'Période de stage', route: null, icon: 'pi pi-calendar'}

            ] },
    },
];
