export default [
    {
        path: '',
        alias: '/',
        component: () => import('@/views/DashboardView.vue'),
        name: 'StageDashboard',
        meta: {
            breadcrumb: [{ label: 'Dashboard', route: '/' }]
        },
    },
    {
        path: 'demande',
        component: () => import('@/views/Etudiant/ConventionRequestView.vue'),
        name: 'ConventionRequest',
        meta: {
            breadcrumb: [
                { label: 'Dashboard', route: '/' },
                { label: 'Demande de Convention', route: null, icon: 'pi pi-file-edit' }
            ]
        },
    },
    {
        path: 'etudiant',
        component: () => import('@/views/Etudiant/EtudiantDashboardView.vue'),
        name: 'EtudiantDashboard',
        meta: {
            breadcrumb: [
                { label: 'Dashboard', route: '/' },
                { label: 'Espace Étudiant', route: null, icon: 'pi pi-user' }
            ]
        },
    },
    {
        path: 'enseignant',
        component: () => import('@/views/Enseignant/EnseignantDashboardView.vue'),
        name: 'EnseignantDashboard',
        meta: {
            breadcrumb: [
                { label: 'Dashboard', route: '/' },
                { label: 'Espace Tuteur', route: null, icon: 'pi pi-users' }
            ]
        },
    },
    {
        path: 'responsable',
        component: () => import('@/views/Responsable/ResponsableDashboardView.vue'),
        name: 'ResponsableDashboard',
        meta: {
            breadcrumb: [
                { label: 'Dashboard', route: '/' },
                { label: 'Espace Responsable', route: null, icon: 'pi pi-shield' }
            ]
        },
    },
    {
        path: 'admin/templates',
        component: () => import('@/views/SuperAdmin/TemplateEditorView.vue'),
        name: 'TemplateEditor',
        meta: {
            permission: 'isSuperAdmin',
            breadcrumb: [
                { label: 'Dashboard', route: '/' },
                { label: 'Modèles de Conventions', route: null, icon: 'pi pi-cog' }
            ]
        },
    }
];
