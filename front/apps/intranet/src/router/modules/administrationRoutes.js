import {LayoutComponent} from "@components";
import previsionnelRoutes from './previsionnelRoutes.js'

export default [
    {
        path: 'administration',
        component: () => import('@/views/AdministrationView.vue'),
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Administration', route: null, icon: 'pi pi-wrench' }] },
    },
    {
        path: 'administration/pn',
        component: () => import('@/components/administration/Pn.vue'),
        meta: { breadcrumb: [{ label: 'Dashboard', route: '/' }, { label: 'Administration', route: '/administration' }, { label: 'PN', route: null }] },
    },
    {
        path: 'administration/previsionnel',
        component: () => import('@/views/previsionnel/PrevisionnelView.vue'),
        meta: {
            breadcrumb: [
                { label: 'Dashboard', route: '/' },
                { label: 'Administration', route: '/administration', icon: 'pi pi-wrench' },
                { label: 'Prévisionnel', route: null, icon: 'pi pi-clock' }]
        },
        children: [
          ...previsionnelRoutes
          ]
    },

];
