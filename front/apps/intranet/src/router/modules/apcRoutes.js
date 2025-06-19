export default [
    {
        path: 'apc/referentiels',
        name: 'apc-referentiels',
        component: () => import('@/views/Apc/ReferentielsView.vue'),
        meta: {
            title: 'Référentiels de compétences',
            breadcrumb: [
                { label: 'Accueil', to: '/' },
                { label: 'APC', to: '/apc' },
                { label: 'Référentiels', to: '/apc/referentiels' }
            ]
        }
    }
]; 