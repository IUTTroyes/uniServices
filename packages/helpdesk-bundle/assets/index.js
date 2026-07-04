import NewTicketView from './views/Personnel/NewTicketView.vue';
import MyTicketView from './views/Personnel/MyTicketView.vue';
import TicketListAdminView from './views/PersonnelService/TicketListAdminView.vue';
import dashboardRoutes from './router/modules/dashboardRoutes.js';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import { LayoutComponent } from '@components';
import { hasPermission } from '@utils';

// Define the menu structure
const intranetMenu = [
    {
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/helpdesk/' },
            { label: 'DashboardService', icon: 'pi pi-fw pi-home', to: '/helpdesk/service' , permission:'isPersonnelService'},
            { label: 'Nouveau Ticket', icon: 'pi pi-fw pi-receipt', to: '/helpdesk/nouveauticket' },
            { label: 'Mes Tickets', icon: 'pi pi-fw pi-ticket', to: '/helpdesk/mestickets' },
            { label: 'Tous les Tickets', icon: 'pi pi-fw pi-list', to: '/helpdesk/ticketsliste', permission: 'isPersonnelService'}
        ]
    }
];

const appName = 'Helpdesk';

export default {
  name: 'helpdesk',
  routes: [
    {
      path: '/helpdesk',
      component: LayoutComponent,
      props: route => {
        // Process menu items and check permissions every time the component is rendered
        const processedMenu = intranetMenu.map(category => {
          const processedItems = category.items.map(item => {
            if (item.permission) {
              return {
                ...item,
                visible: hasPermission(item.permission)
              };
            }
            return item;
          });

          return {
            ...category,
            items: processedItems
          };
        });

        let breadcrumbItems = route.meta.breadcrumb || [];
        if (typeof route.meta.breadcrumb === 'function') {
          try {
            breadcrumbItems = route.meta.breadcrumb(route) || [];
          } catch (e) {
            console.error('Error while evaluating breadcrumb function for route', route.name, e);
            breadcrumbItems = [];
          }
        }

        return {
          menuItems: processedMenu,
          logoUrl: Logo,
          appName: appName,
          breadcrumbItems
        };
      },
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
  ]
};
