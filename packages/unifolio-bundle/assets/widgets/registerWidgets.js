import { registerWidgetComponent } from '@components';
import PortfolioToCorrectWidget from './widgets/PortfolioToCorrectWidget.vue';
import PortfolioProgressWidget from './widgets/PortfolioProgressWidget.vue';
import PortfolioAlertsWidget from './widgets/PortfolioAlertsWidget.vue';

export const registerWidgets = () => {
    registerWidgetComponent('PortfolioToCorrectWidget', PortfolioToCorrectWidget);
    registerWidgetComponent('PortfolioProgressWidget', PortfolioProgressWidget);
    registerWidgetComponent('PortfolioAlertsWidget', PortfolioAlertsWidget);
};
