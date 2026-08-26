import { registerWidgetComponent } from '@components';
import ExtActusWidget from './widgets/ExtActusWidget.vue';
import IntActusWidget from './widgets/IntActusWidget.vue';

export const registerWidgets = () => {
    registerWidgetComponent('ExtActusWidget', ExtActusWidget);
    registerWidgetComponent('IntActusWidget', IntActusWidget);
};
