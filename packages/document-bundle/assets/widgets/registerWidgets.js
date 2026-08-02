import { registerWidgetComponent } from '@components';
import DocumentRecentsWidget from './widgets/DocumentRecentsWidget.vue';
import DocumentStatsWidget from './widgets/DocumentStatsWidget.vue';

export const registerWidgets = () => {
  registerWidgetComponent('DocumentRecentsWidget', DocumentRecentsWidget);
  registerWidgetComponent('DocumentStatsWidget', DocumentStatsWidget);
};
