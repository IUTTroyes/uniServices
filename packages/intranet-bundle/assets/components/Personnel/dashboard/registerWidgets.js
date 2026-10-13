import { registerWidgetComponent } from '@components';
import EmploiDuTempsWidget from './widgets/EmploiDuTempsWidget.vue';
import ListingsEtudiantsWidget from './widgets/ListingsEtudiantsWidget.vue';
import ActionsUrgentesWidget from './widgets/ActionsUrgentesWidget.vue';
import DocumentsRecentsWidget from './widgets/DocumentsRecentsWidget.vue';
import NotesWidget from './widgets/NotesWidget.vue';

export const registerWidgets = () => {
    registerWidgetComponent('EmploiDuTempsWidget', EmploiDuTempsWidget);
    registerWidgetComponent('ListingsEtudiantsWidget', ListingsEtudiantsWidget);
    registerWidgetComponent('ActionsUrgentesWidget', ActionsUrgentesWidget);
    registerWidgetComponent('DocumentsRecentsWidget', DocumentsRecentsWidget);
    registerWidgetComponent('NotesWidget', NotesWidget);
};
