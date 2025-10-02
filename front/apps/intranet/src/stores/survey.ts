import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Survey, Section, Question, QuestionType } from '@types/survey';
import { v4 as uuidv4 } from 'uuid';
import {  getAllQuestionnaires,
  getQuestionnaire,
  getQuestionnaireSections,

  createQuestionnaire,
  deleteQuestionnaire,
  updateQuestionnaire,

  createSectionQuestionnaire,
  updateSectionQuestionnaire,
  deleteSectionQuestionnaire,

  createQuestionInSection,
  updateQuestionInSection,
  deleteQuestionInSection} from '@requests'


export const useSurveyStore = defineStore('survey', () => {
  const surveys = ref<Survey[]>([]);
  const currentSurvey = ref(null); //<Survey | null>
  const currentSection = ref<Section | null>(null);
  const currentSections = ref([]);

  // Getters
  const surveyCount = computed(() => surveys.value.length);
  const publishedSurveys = computed(() =>
      surveys.value.filter(s => s.status === 'published')
  );
  const draftSurveys = computed(() =>
      surveys.value.filter(s => s.status === 'draft')
  );

  // Survey Management
  async function createSurvey(titre: string, description?: string): Survey {
    const survey: Survey = {
      uuid: uuidv4(),
      titre,
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
      createdBy: 'current-user',
      version: 1
    };

    surveys.value.push(survey);
    currentSurvey.value = await saveQuestionnaire(survey);

    // Create initial section
    addSection('Section 1');

    return survey;
  }

  async function saveQuestionnaire(survey: Survey): Survey {
    return await createQuestionnaire(survey, true)
  }

  function duplicateSurvey(surveyId: string): Survey | null {
    const original = surveys.value.find(s => s.uuid === surveyId);
    if (!original) return null;

    const duplicate: Survey = {
      ...structuredClone(original),
      uuid: uuidv4(),
      titre: `${original.titre} (Copy)`,
      status: 'draft',
      version: 1
    };


    // Generate new IDs for sections and questions
    duplicate.sections = duplicate.sections.map(section => ({
      ...section,
      id: uuidv4(),
      questions: section.questionnaireQuestions.map(question => ({
        ...question,
        id: uuidv4(),
        reponses: question.reponses?.map(option => ({
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

  function setSurveyStatus(surveyId: string, status: Survey['status']) {
    const survey = surveys.value.find(s => s.uuid === surveyId);
    if (survey) {
      survey.status = status;
      if (status === 'published') {
        survey.version += 1;
      }
      saveToLocalStorage();
    }
  }

  // Section Management
  async function addSection(titre: string, description?: string): Section {
    if (!currentSurvey.value) throw new Error('No current survey');

    const section: Section = {
      uuid: uuidv4(),
      ordre: currentSections.value.length + 1,
      titre,
      description,
      typeSection: 'normal',
      questionnaireQuestions: []
    };

    const newSection = await createSectionQuestionnaire(section, currentSurvey.value.uuid)
    currentSections.value.push(newSection);
    currentSection.value = newSection;

    return newSection;
  }

  async function updateSection(sectionId: number, updates: Partial<Section>) {
    if (!currentSurvey.value) return;

    const section = currentSections.value.find(s => s.id === sectionId);
    if (section) {
      if ('id' in updates) {
        delete updates.id;
      }
      await updateSectionQuestionnaire(section.id, updates, true)
      // mettre à jour la section dans le store
        Object.assign(section, updates);
        if (currentSection.value?.id === sectionId) {
            currentSection.value = section;
        }

        //mettre à jour le currentSections
        const index = currentSections.value.findIndex(s => s.id === sectionId);
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
  async function addQuestion(sectionId: number, typeQuestion: QuestionType): Question {
    if (!currentSection.value) throw new Error('Section not found');

    const question: Question = {
      id: null,
      uuid: uuidv4(),
      typeQuestion,
      libelle : 'Question ' + (currentSection.value.questionnaireQuestions.length + 1),
      ordre: currentSection.value.questionnaireQuestions.length + 1,
      obligatoire: true,
      reponses: ['single_choice', 'multiple_choice', 'scale', 'ranking'].includes(typeQuestion)
          ? [
            { id: uuidv4(), text: 'Option 1', value: 'option1' },
            { id: uuidv4(), text: 'Option 2', value: 'option2' }
          ]
          : undefined
    };

    const newQuestion = await createQuestionInSection(sectionId, question, true)
    currentSection.value.questionnaireQuestions.push(newQuestion);

    return newQuestion;
  }

  async function updateQuestion(sectionId: string, questionId: string, updates: Partial<Question>) {
    console.log(questionId)
    const section = currentSections.value.find(s => s.id === sectionId);
    const question = section?.questionnaireQuestions.find(q => q.uuid === questionId);
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
    const section = currentSections.value.find(s => s.id === sectionId);
    if (!section) return;

    const index = section.questionnaireQuestions.findIndex(q => q.uuid === questionId);
    if (index !== -1) {
      section.questionnaireQuestions.splice(index, 1);
      deleteQuestionInSection(questionId, true);
    }
  }

  function reorderQuestions(sectionId: string, fromIndex: number, toIndex: number) {
    const section = currentSections.value.find(s => s.id === sectionId);
    if (!section) return;

    const questions = section.questionnaireQuestions;
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
      currentSection.value = currentSections.value[0] || null;
    }
  }

  function selectSection(sectionId: string) {
    const section = currentSections.value.find(s => s.id === sectionId);
    if (section) {
      currentSection.value = section;
    }
  }

  // Persistence
  function saveToLocalStorage() {
    localStorage.setItem('surveys', JSON.stringify(surveys.value));
  }

  async function loadFromLocalStorage() {
    const saved = await getAllQuestionnaires() //localStorage.getItem('surveys');
    console.log('loadFromLocalStorage', saved);
    if (saved) {
      try {
        const parsed = saved;
        surveys.value = parsed.map((s: any) => ({
          ...s,
          created: new Date(s.created),
          updated: new Date(s.updated),
          settings: {
            ...s.settings,
            startDate: s.dateDebut ? new Date(s.dateDebut) : undefined,
            endDate: s.dateFin ? new Date(s.dateFin) : undefined
          }
        }));
        console.log('Surveys loaded from localStorage:', surveys.value);
      } catch (e) {
        console.error('Failed to load surveys from localStorage:', e);
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
    setSurveyStatus,

    // Section actions
    addSection,
    updateSection,
    deleteSection,
    reorderSections,

    // Question actions
    addQuestion,
    updateQuestion,
    removeQuestion,
    reorderQuestions,

    // Utility actions
    selectSurvey,
    selectSection,
    saveToLocalStorage,
    loadFromLocalStorage
  };
});
