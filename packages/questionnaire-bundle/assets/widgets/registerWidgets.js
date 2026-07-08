import { registerWidgetComponent } from '@components';
import QuestionnairePendingWidget from './widgets/QuestionnairePendingWidget.vue';
import QuestionnaireStatsWidget from './widgets/QuestionnaireStatsWidget.vue';
import QuestionnaireLastAnswersWidget from './widgets/QuestionnaireLastAnswersWidget.vue';

export const registerWidgets = () => {
    registerWidgetComponent('QuestionnairePendingWidget', QuestionnairePendingWidget);
    registerWidgetComponent('QuestionnaireStatsWidget', QuestionnaireStatsWidget);
    registerWidgetComponent('QuestionnaireLastAnswersWidget', QuestionnaireLastAnswersWidget);
};
