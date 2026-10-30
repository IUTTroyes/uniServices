import { registerWidgetComponent } from '@components';
import MessageWidget from './MessageWidget.vue';

export const registerWidgets = () => {
  registerWidgetComponent('MessageWidget', MessageWidget);
};

