import {defineStore} from 'pinia';
import {computed, ref} from 'vue';
import type {Question, QuestionType, Section, Survey} from '@types';
import {v4 as uuidv4} from 'uuid';
import {
    createQuestionInSection,
    createQuestionnaire,
    duplicateQuestionnaire,
    createSectionQuestionnaire,
    deleteQuestionInSection,
    deleteQuestionnaire,
    deleteSectionQuestionnaire,
    getAllQuestionnaires,
    getQuestionnaire,
    getQuestionnaireSections,
    updateQuestionInSection,
    updateQuestionnaire,
    updateSectionQuestionnaire,
    publishQuestionnaire
} from '@/requests/questionnaire_services/questionnaireService'


export const useSurveyStore = defineStore('survey', () => {
    const surveys = ref<Survey[]>([]);
    const currentSurvey = ref(null); //<Survey | null>
    const currentSection = ref<Section | null>(null);
    const currentSections = ref([]);
    const surveyCount = ref(0)

    // Getters
    const publishedSurveys = computed(() =>
        surveys.value.filter(s => s.status === 'published')
    );
    const draftSurveys = computed(() =>
        surveys.value.filter(s => s.status === 'draft')
    );

    // Survey Management
    async function createSurvey(title: string, description?: string): Promise<Survey> {
        const survey: Survey = {
            uuid: uuidv4(),
            title,
            description,
            sections: [],
            opt: {
                anonymous: false,
                autoSave: true,
                allowBack: true,
                showProgress: true,
                requireCompletion: false
            },
            status: 'draft',
            createdBy: 'current-user'
        };

        surveys.value.push(survey);
        currentSurvey.value = await saveQuestionnaire(survey);

        // Create an initial section
        addSection('Section 1');

        return survey;
    }

    async function saveQuestionnaire(survey: Survey): Promise<Survey> {
        return await createQuestionnaire(survey, true)
    }

    async function duplicateSurvey(surveyId: string, newTitle?: string): Promise<Survey | null> {
        const original = surveys.value.find(s => s.uuid === surveyId || s.id === surveyId);
        const targetUuid = original ? (original.uuid || original.id) : surveyId;

        const res = await duplicateQuestionnaire(targetUuid, newTitle, true);
        if (res && res.uuid) {
            await loadQuestionnaires();
            return surveys.value.find(s => s.uuid === res.uuid) || null;
        }
        return null;
    }

    async function updateSurvey(updates: Partial<Survey>) {
        if (!currentSurvey.value) return;
        currentSurvey.value = await updateQuestionnaire(currentSurvey.value.uuid, updates, true)
    }

    function deleteSurvey(surveyId: string) {
        const index = surveys.value.findIndex(s => s.uuid === surveyId);
        if (index !== -1) {
            surveys.value.splice(index, 1);
            if (currentSurvey.value?.uuid === surveyId) {
                currentSurvey.value = null;
            }
            deleteQuestionnaire(surveyId, true)
        }
    }

    async function publishSurvey(surveyId: string, recipients: string[]): Promise<any> {
        const result = await publishQuestionnaire(surveyId, { recipients }, true);
        if (currentSurvey.value && currentSurvey.value.uuid === surveyId) {
            currentSurvey.value.status = 'published';
            currentSurvey.value.publishedAt = result.publishedAt;
        }
        return result;
    }

    // function setSurveyStatut(surveyId: string, status: Survey['status']) {
    //     const survey = surveys.value.find(s => s.uuid === surveyId);
    //     if (survey) {
    //         survey.status = status;
    //         if (status === 'published') {
    //             survey.version += 1;
    //         }
    //         saveToLocalStorage();
    //     }
    // }

    // Section Management
    async function addSection(title: string, description?: string, typeSection: 'normal' | 'configurable' = 'normal', opt?: any): Promise<Section> {
        if (!currentSurvey.value) throw new Error('No current survey');

        const section: Section = {
            uuid: uuidv4(),
            sortOrder: currentSections.value.length + 1,
            title,
            description,
            typeSection,
            questions: [],
            opt
        };

        const newSection = await createSectionQuestionnaire(section, currentSurvey.value.uuid)
        currentSections.value.push(newSection);
        currentSection.value = newSection;

        return newSection;
    }

    async function duplicateSection(
        sectionId: string,
        options: {
            newTitle?: string;
            duplicateQuestions?: boolean;
            adaptConditionalRules?: boolean;
        } = {}
    ): Promise<Section> {
        if (!currentSurvey.value) throw new Error('No current survey');

        const sourceSection = currentSections.value.find(s => s.uuid === sectionId || s.id === sectionId);
        if (!sourceSection) throw new Error('Section to duplicate not found');

        const title = options.newTitle || `${sourceSection.title} (copie)`;
        const shouldDuplicateQuestions = options.duplicateQuestions !== false;
        const shouldAdaptRules = options.adaptConditionalRules !== false;

        // 1. Create new section
        const newSection = await addSection(
            title,
            sourceSection.description,
            sourceSection.typeSection,
            sourceSection.opt
        );

        if (!shouldDuplicateQuestions || !sourceSection.questions || sourceSection.questions.length === 0) {
            return newSection;
        }

        // 2. Build Old UUID/ID -> New UUID map
        const uuidMap = new Map<string, string>();
        const preparedQuestions: { oldQ: Question; newUuid: string }[] = [];

        for (const q of sourceSection.questions) {
            const newUuid = uuidv4();
            if (q.uuid) uuidMap.set(q.uuid, newUuid);
            if (q.id !== null && q.id !== undefined) uuidMap.set(String(q.id), newUuid);
            preparedQuestions.push({ oldQ: q, newUuid });
        }

        // 3. Clone questions & re-map conditional rules
        for (const { oldQ, newUuid } of preparedQuestions) {
            let remappedRules = oldQ.conditionalRules ? JSON.parse(JSON.stringify(oldQ.conditionalRules)) : undefined;

            if (shouldAdaptRules && remappedRules && Array.isArray(remappedRules)) {
                remappedRules = remappedRules.map((r: any) => {
                    const newDependsOn = uuidMap.has(String(r.dependsOn))
                        ? uuidMap.get(String(r.dependsOn))
                        : r.dependsOn;

                    const newTargetIds = r.targetQuestionIds && Array.isArray(r.targetQuestionIds)
                        ? r.targetQuestionIds.map((tid: any) => uuidMap.has(String(tid)) ? uuidMap.get(String(tid)) : tid)
                        : r.targetQuestionIds;

                    return {
                        ...r,
                        dependsOn: newDependsOn,
                        targetQuestionIds: newTargetIds
                    };
                });
            }

            const newQPayload: Question = {
                id: null,
                uuid: newUuid,
                typeQuestion: oldQ.typeQuestion,
                label: oldQ.label,
                help: oldQ.help,
                sortOrder: newSection.questions.length + 1,
                required: oldQ.required,
                choices: oldQ.choices ? JSON.parse(JSON.stringify(oldQ.choices)) : undefined,
                conditionalRules: remappedRules,
                opt: oldQ.opt ? JSON.parse(JSON.stringify(oldQ.opt)) : undefined
            };

            await addQuestion(newSection.uuid, oldQ.typeQuestion, newQPayload);
        }

        return newSection;
    }

    async function updateSection(sectionId: string, updates: Partial<Section>) {
        if (!currentSurvey.value) return;

        const section = currentSections.value.find(s => s.uuid === sectionId);
        if (section) {
            if ('id' in updates) {
                delete updates.id;
            }
            await updateSectionQuestionnaire(section.uuid, updates, currentSurvey.value.uuid, true)
            // mettre à jour la section dans le store
            Object.assign(section, updates);
            if (currentSection.value?.uuid === sectionId) {
                currentSection.value = section;
            }

            //mettre à jour le currentSections
            const index = currentSections.value.findIndex(s => s.uuid === sectionId);
            if (index !== -1) {
                currentSections.value[index] = section;
            }


        }
    }

    async function deleteSection(sectionId: string) {
        console.log('deleteSection', sectionId);
        if (!currentSurvey.value) return;

        const index = currentSections.value.findIndex(s => s.uuid === sectionId || s.id === sectionId);
        if (index !== -1) {
            const sectionToDelete = currentSections.value[index];
            currentSections.value.splice(index, 1);
            if (currentSection.value?.uuid === sectionId || currentSection.value?.id === sectionId) {
                currentSection.value = currentSections.value[0] || null;
            }
            await deleteSectionQuestionnaire(sectionToDelete.uuid, true);
            updateSurvey({});
        }
    }

    function reorderSections(fromIndex: number, toIndex: number) {
        if (!currentSurvey.value) return;

        const sections = currentSections.value;
        const [moved] = sections.splice(fromIndex, 1);
        sections.splice(toIndex, 0, moved);
        updateSurvey({});
    }

    // Question Management
    async function addQuestion(sectionId: string, typeQuestion: QuestionType, newQuestion: Question| null = null): Promise<Question> {
        if (!currentSection.value) throw new Error('Section not found');
        if (!currentSurvey.value) throw new Error('Survey not found');

        let _newQuestion: Question;

        if (newQuestion === null) {
            const question: Question = {
                id: null,
                uuid: uuidv4(),
                typeQuestion,
                label: 'Question ' + (currentSection.value.questions.length + 1),
                sortOrder: currentSection.value.questions.length + 1,
                required: true,
                choices: ['single_choice', 'multiple_choice', 'scale', 'ranking'].includes(typeQuestion)
                    ? [
                        {id: uuidv4(), text: 'Option 1', value: 'option1'},
                        {id: uuidv4(), text: 'Option 2', value: 'option2'}
                    ]
                    : undefined
            };
            //todo: passer uuid ? gérer côté back
            _newQuestion = await createQuestionInSection(currentSection.value.uuid, question, true)
        } else {
            _newQuestion = await createQuestionInSection(currentSection.value.uuid, newQuestion, true)
        }

        currentSection.value.questions.push(_newQuestion);

        return _newQuestion;
    }

    async function duplicateQuestion(
        sectionId: string,
        question: Question,
        options: {
            newLabel?: string;
            copyRulesMode?: 'copy_adapt' | 'none';
        } = {}
    ): Promise<Question> {
        if (!currentSection.value) throw new Error('Section not found');
        if (!currentSurvey.value) throw new Error('Survey not found');

        const newUuid = uuidv4();
        const copyMode = options.copyRulesMode || 'copy_adapt';

        let remappedRules: any[] | undefined = undefined;
        if (copyMode === 'copy_adapt' && question.conditionalRules && Array.isArray(question.conditionalRules)) {
            const oldId = String(question.uuid || question.id);
            remappedRules = JSON.parse(JSON.stringify(question.conditionalRules)).map((r: any) => ({
                ...r,
                dependsOn: String(r.dependsOn) === oldId ? newUuid : r.dependsOn
            }));
        }

        const _newQuestion: Question = {
            id: null,
            uuid: newUuid,
            typeQuestion: question.typeQuestion,
            label: options.newLabel || `${question.label} (Copie)`,
            help: question.help,
            sortOrder: currentSection.value.questions.length + 1,
            required: question.required,
            choices: question.choices ? JSON.parse(JSON.stringify(question.choices)) : undefined,
            conditionalRules: remappedRules,
            opt: question.opt ? JSON.parse(JSON.stringify(question.opt)) : undefined
        };

        const newQuestion = await createQuestionInSection(sectionId, _newQuestion, true);
        currentSection.value.questions.push(newQuestion);

        return newQuestion;
    }

    async function updateQuestion(sectionId: string, questionId: string, updates: Partial<Question>) {
        console.log(questionId)
        const section = currentSections.value.find(s => s.uuid === sectionId);
        const question = section?.questions.find(q => q.uuid === questionId);
        console.log(section)
        console.log(question)
        if ('id' in updates) {
            delete updates.id;
        }

        if ('uuid' in updates) {
            delete updates.uuid;
        }

        if (question) {
            Object.assign(question, updates);
            await updateQuestionInSection(questionId, updates, true)
        }
    }

    function removeQuestion(sectionId: string, questionId: string) {
        const section = currentSections.value.find(s => s.uuid === sectionId);
        if (!section) return;
console.log(questionId)
        const index = section.questions.findIndex(q => q.uuid === questionId);
        if (index !== -1) {
            console.log('delete')
            section.questions.splice(index, 1);
            deleteQuestionInSection(questionId, true);
        }
    }

    function reorderQuestions(sectionId: string, fromIndex: number, toIndex: number) {
        const section = currentSections.value.find(s => s.id === sectionId);
        if (!section) return;

        const questions = section.questions;
        const [moved] = questions.splice(fromIndex, 1);
        questions.splice(toIndex, 0, moved);
        updateSurvey({});
    }

    // Utility functions
    async function selectSurvey(surveyId: string) {
        console.log('selectSurvey', surveyId)
        const survey = await getQuestionnaire(surveyId);
        if (survey) {
            currentSurvey.value = survey;
            currentSections.value = await getQuestionnaireSections(surveyId);
            console.log('section', currentSections.value)
            currentSection.value = currentSections.value[0] || null;
        }
    }

    function selectSection(sectionId: string) {
        const section = currentSections.value.find(s => s.uuid === sectionId);
        if (section) {
            currentSection.value = section;
        }
    }

    // Persistence
    // function saveToLocalStorage() {
    //     localStorage.setItem('surveys', JSON.stringify(surveys.value));
    // }

    async function loadQuestionnaires() {
        const _questionnaires = await getAllQuestionnaires()
        console.log(_questionnaires)
        if (_questionnaires) {
            try {
                const parsed = await _questionnaires['member'];
                console.log(parsed)
                surveyCount.value = await _questionnaires['totalItems']
                surveys.value = parsed.map((s: any) => ({
                    ...s,
                    createdAt: new Date(s.created),
                    updatedAt: new Date(s.updated),
                    openingDate: s.openingDate ? new Date(s.openingDate) : undefined,
                    closingDate: s.closingDate ? new Date(s.closingDate) : undefined,
                    settings: {
                        ...s.opt,
                    }
                }));
            } catch (e) {
                console.error('Failed to load surveys :', e);
            }
        }
    }

    return {
        // State
        surveys,
        currentSurvey,
        currentSection,
        currentSections,

        // Getters
        surveyCount,
        publishedSurveys,
        draftSurveys,

        // Survey actions
        createSurvey,
        duplicateSurvey,
        updateSurvey,
        deleteSurvey,
        publishSurvey,
        // setSurveyStatut,

        // Section actions
        addSection,
        duplicateSection,
        updateSection,
        deleteSection,
        reorderSections,

        // Question actions
        addQuestion,
        updateQuestion,
        removeQuestion,
        duplicateQuestion,
        reorderQuestions,

        // Utility actions
        selectSurvey,
        selectSection,
        // saveToLocalStorage,
        loadQuestionnaires
    };
});
