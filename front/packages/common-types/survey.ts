export interface Question {
    id?: number;
    uuid?: string;
    typeQuestion: QuestionType;
    libelle: string;
    ordre: number;
    description?: string;
    obligatoire: boolean;
    reponses?: QuestionOption[];
    validation?: QuestionValidation;
    conditional?: ConditionalRule;
    conditionalRules?: ConditionalRule[];
    metadata?: Record<string, any>;
}

export interface QuestionOption {
    id: string;
    text: string;
    value: string;
}

export interface QuestionValidation {
    minLength?: number;
    maxLength?: number;
    min?: number;
    max?: number;
    pattern?: string;
}

export interface ConditionalRule {
    dependsOn: string; // question ID
    operator: 'equals' | 'not_equals' | 'contains' | 'greater_than' | 'less_than';
    value: any;
    action?: string;
    targetQuestionIds?: string[];
    targetSectionId?: string;
    endMessage?: string;
    type?: string;
}

export type QuestionType =
    | 'single_choice'
    | 'multiple_choice'
    | 'text_short'
    | 'text_long'
    | 'scale'
    | 'matrix'
    | 'ranking';

export interface Section {
    id?: number;
    uuid: string;
    ordre: number;
    titre: string;
    description?: string;
    questionnaireQuestions: Question[];
    conditional?: ConditionalRule;
    typeSection: 'normal' | 'configurable';
    opt?: {
        sourceType: 'subjects' | 'departments' | 'teams' | 'products' | 'custom';
        sourceLabel: string; // Label for the source type (e.g., "Matières", "Départements")
        elements: ConfigurableElement[];
        titleTemplate: string; // Template for section title (e.g., "Évaluation de {element}")
    };
}

export interface ConfigurableElement {
    id: string;
    name: string;
    code?: string;
    description?: string;
    metadata?: Record<string, any>;
}

export interface Survey {
    id?: number;
    uuid: string;
    titre: string;
    description?: string;
    sections: Section[];
    opt: SurveySettings;
    status: 'draft' | 'published' | 'closed';
    createdBy: string;
    version: number;
}

export interface SurveySettings {
    anonymous: boolean;
    autoSave: boolean;
    allowBack: boolean;
    showProgress: boolean;
    requireCompletion: boolean;
    startDate?: Date;
    endDate?: Date;
    thankYouMessage?: string;
}

export interface Response {
    id: string;
    surveyId: string;
    participantId?: string;
    answers: Record<string, any>;
    completed: boolean;
    submittedAt?: Date;
    startedAt: Date;
    lastActivity: Date;
    metadata?: Record<string, any>;
}

export interface Participant {
    id: string;
    email?: string;
    name?: string;
    group?: string;
    customFields?: Record<string, any>;
    inviteToken: string;
    invitedAt?: Date;
    respondedAt?: Date;
}

export interface SurveyInvitation {
    id: string;
    surveyId: string;
    participants: Participant[];
    template: EmailTemplate;
    sentAt?: Date;
    status: 'draft' | 'sent' | 'scheduled';
}

export interface EmailTemplate {
    subject: string;
    body: string;
    variables: string[];
}

export interface SurveyAnalytics {
    surveyId: string;
    totalInvited: number;
    totalResponses: number;
    completionRate: number;
    averageTimeSpent: number;
    responsesByDate: Record<string, number>;
    questionAnalytics: Record<string, QuestionAnalytics>;
}

export interface QuestionAnalytics {
    questionId: string;
    type: QuestionType;
    totalResponses: number;
    responseDistribution: Record<string, number>;
    averageRating?: number;
    textResponses?: string[];
}
