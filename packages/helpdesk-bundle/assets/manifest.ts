import NewTicketView from './views/Personnel/NewTicketView.vue';
import MyTicketView from './views/Personnel/MyTicketView.vue';
import TicketListAdminView from './views/PersonnelService/TicketListAdminView.vue';
import dashboardRoutes from './router/modules/dashboardRoutes.js';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import { LayoutComponent } from '@components';

const helpdeskMenu = {
  label: 'Assistance',
  icon: 'pi pi-fw pi-question-circle',
  items: [
    { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/helpdesk/' },
    { label: 'Dashboard Service', icon: 'pi pi-fw pi-building', to: '/helpdesk/service', permission: 'isPersonnelService' },
    { label: 'Nouveau Ticket', icon: 'pi pi-fw pi-plus', to: '/helpdesk/nouveauticket' },
    { label: 'Mes Tickets', icon: 'pi pi-fw pi-ticket', to: '/helpdesk/mestickets' },
    { label: 'Tous les Tickets', icon: 'pi pi-fw pi-list', to: '/helpdesk/ticketsliste', permission: 'isPersonnelService' }
  ]
};

export default {
  name: 'helpdesk',
  routes: [
    {
      path: '/helpdesk',
      component: LayoutComponent,
      props: route => ({
        logoUrl: Logo,
        appName: 'Helpdesk',
        breadcrumbItems: route.meta.breadcrumb || []
      }),
      children: [
        ...dashboardRoutes,
        {
          path: 'nouveauticket',
          name: 'NewTicketView',
          component: NewTicketView,
          meta: {
            breadcrumb: [{ label: 'Dashboard', route: '/helpdesk/' }, {
              label: 'Nouveau Ticket',
              route: null,
              icon: 'pi pi-wrench'
            }]
          },
        },
        {
          path: 'mestickets',
          name: 'MyTicketView',
          component: MyTicketView,
          meta: {
            breadcrumb: [{ label: 'Dashboard', route: '/helpdesk/' }, {
              label: 'Mes tickets',
              route: null,
              icon: 'pi pi-wrench'
            }]
          },
        },
        {
          path: 'ticketsliste',
          name: 'TicketListAdminView',
          component: TicketListAdminView,
          meta: {
            permission: 'isPersonnel',
            title: 'Gestion Tickets',
            breadcrumb: [{ label: 'Dashboard', route: '/helpdesk/' }, {
              label: 'Liste des Tickets',
              route: null,
              icon: 'pi pi-wrench'
            }]
          }
        },
        {
          path: 'ticket/:id',
          name: 'TicketView',
          component: () => import('./views/TicketView.vue'),
          props: true,
          meta: {
            breadcrumb: [{ label: 'Dashboard', route: '/helpdesk/' },{ label: 'Mes Tickets', route: '/helpdesk/mestickets' }, {
              label: 'Ticket',
              route: null,
              icon: 'pi pi-wrench'
            }]
          },
        }
      ]
    }
  ],
  menu: helpdeskMenu
};
