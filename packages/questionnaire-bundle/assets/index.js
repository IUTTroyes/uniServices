import dashboardRoutes from './router/modules/dashboardRoutes.js';
import qualiteRoutes from './router/modules/questionnaireAdministrationRoutes.js';
import Logo from "@images/logo/logo_intranet_iut_troyes.svg";
import { LayoutComponent } from '@components';
import { hasPermission } from '@utils';

// Define the menu structure
const intranetMenu = [
    {
        items: [
            { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: '/questionnaire/' },
            { label: 'Qualité', icon: 'pi pi-fw pi-home', to: '/questionnaire/qualite/enquetes' },
        ]
    }
];

const appName = 'Questionnaires';

export default {
  name: 'questionnaire',
  routes: [
    {
      path: '/questionnaire',
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
        ...qualiteRoutes,
      ]
    }
  ]
};
