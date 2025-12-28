import {defineStore} from 'pinia';
import {computed, ref} from 'vue';
import type {Question, QuestionType, Section, Survey} from '@types';
import {v4 as uuidv4} from 'uuid';
import {
    createQuestionInSection,
    createQuestionnaire,
    createSectionQuestionnaire,
    deleteQuestionInSection,
    deleteQuestionnaire,
    getAllQuestionnaires,
    getQuestionnaire,
    getQuestionnaireSections,
    updateQuestionInSection,
    updateQuestionnaire,
    updateSectionQuestionnaire
} from '@requests'


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

    function duplicateSurvey(surveyId: string): Survey | null {
        const original = surveys.value.find(s => s.uuid === surveyId);
        if (!original) return null;

        const duplicate: Survey = {
            ...structuredClone(original),
            uuid: uuidv4(),
            title: `${original.title} (Copy)`,
            status: 'draft',
            createdAt: new Date(),
            updatedAt: new Date(),
            publishedAt: null,
        };


        // Generate new IDs for sections and questions
        duplicate.sections = duplicate.sections.map(section => ({
            ...section,
            id: uuidv4(),
            questions: section.questions.map(question => ({
                ...question,
                id: uuidv4(),
                reponses: question.choices?.map(option => ({
                    ...option,
                    id: uuidv4()
                }))
            }))
        }));
        createQuestionnaire(duplicate, true) //todo: gérer les sections et questions
        surveys.value.push(duplicate);
        return duplicate;
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
    async function addSection(title: string, description?: string): Promise<Section> {
        if (!currentSurvey.value) throw new Error('No current survey');

        const section: Section = {
            uuid: uuidv4(),
            sortOrder: currentSections.value.length + 1,
            title,
            description,
            typeSection: 'normal',
            questions: []
        };

        const newSection = await createSectionQuestionnaire(section, currentSurvey.value.uuid)
        currentSections.value.push(newSection);
        currentSection.value = newSection;

        return newSection;
    }

    async function updateSection(sectionId: string, updates: Partial<Section>) {
        if (!currentSurvey.value) return;

        const section = currentSections.value.find(s => s.uuid === sectionId);
        if (section) {
            if ('id' in updates) {
                delete updates.id;
            }
            await updateSectionQuestionnaire(section.uuid, updates, true)
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

    function deleteSection(sectionId: string) {
        console.log('deleteSection', sectionId);
        if (!currentSurvey.value) return;

        const index = currentSections.value.findIndex(s => s.id === sectionId);
        if (index !== -1) {
            currentSections.value.splice(index, 1);
            if (currentSection.value?.id === sectionId) {
                currentSection.value = currentSections.value[0] || null;
            }
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
            _newQuestion = await createQuestionInSection(currentSection.value.id, question, true)
        } else {
            _newQuestion = await createQuestionInSection(currentSection.value.id, newQuestion, true)
        }

        currentSection.value.questions.push(_newQuestion);

        return _newQuestion;
    }

    async function duplicateQuestion(sectionId: string, question: Question): Promise<Question> {
        if (!currentSection.value) throw new Error('Section not found');

        const _newQuestion: Question = {
            id: null,
            uuid: uuidv4(),
            typeQuestion: question.typeQuestion,
            label: question.label + ' (Copie)',
            description: question.description,
            sortOrder: currentSection.value.questions.length + 1,
            required: question.required,
            choices: question.choices,
            conditionalRules: question.conditionalRules,
            //opt: question.opt
        };

        const newQuestion = await createQuestionInSection(sectionId, _newQuestion, true)
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

        const index = section.questions.findIndex(q => q.uuid === questionId);
        if (index !== -1) {
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
        // setSurveyStatut,

        // Section actions
        addSection,
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
