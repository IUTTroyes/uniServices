import { registerWidgetComponent } from '@components';
import EmploiDuTempsWidget from './widgets/EmploiDuTempsWidget.vue';
import ActionsUrgentesWidget from './widgets/ActionsUrgentesWidget.vue';
import DocumentsRecentsWidget from './widgets/DocumentsRecentsWidget.vue';
import NotesWidget from './widgets/NotesWidget.vue';

export const registerWidgets = () => {
    registerWidgetComponent('EmploiDuTempsWidget', EmploiDuTempsWidget);
    registerWidgetComponent('ActionsUrgentesWidget', ActionsUrgentesWidget);
    registerWidgetComponent('DocumentsRecentsWidget', DocumentsRecentsWidget);
    registerWidgetComponent('NotesWidget', NotesWidget);
};
