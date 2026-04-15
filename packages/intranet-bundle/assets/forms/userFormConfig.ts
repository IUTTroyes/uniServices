import type { StepsArray } from '@types/FromStepsConfigModule';

const fetchRoles = async () => [
    { value: 'admin', label: 'Administrateur' },
    { value: 'editor', label: 'Éditeur' },
    { value: 'viewer', label: 'Lecteur' },
];

const userFormSteps: StepsArray = [
    {
        label: 'Informations personnelles',
        formConfig:
            {
                title: 'Informations personnelles',
                fields: [
                    {
                        name: 'firstName',
                        label: 'Nom',
                        type: 'text',
                        required: true,
                        gridClass: '',
                    },
                    {
                        name: 'lastName',
                        label: 'Prénom',
                        type: 'text',
                        required: true,
                        gridClass: '',
                    },
                    {
                        name: 'email',
                        label: 'Email',
                        type: 'email',
                        required: true,
                        gridClass: 'sm:col-span-2',
                    },
                ],
            },
        formOptions: {
            readonly: false,
            autosave: true,
            autosaveUrl: '/api/save-form',
            buttons: {
                next: { label: 'Continuer' },
            },
            onBeforeSubmit: async (values) => {
                console.log('Avant soumission étape 1', values);
            },
        },
        initialValues: {
            firstName: '',
            lastName: '',
            email: '',
        },
    },
    {
        label: 'Profil utilisateur',
        formConfig:
            {
                title: 'Profil utilisateur',
                fields: [
                    {
                        name: 'role',
                        label: 'Rôle',
                        type: 'select',
                        required: true,
                        options: fetchRoles,
                        gridClass: 'p-col-12 p-md-6',
                    },
                    {
                        name: 'extraInfo',
                        label: 'Info Supplémentaire',
                        type: 'text',
                        visibleIf: (values) => values && values.role === 'admin',
                        gridClass: 'p-col-12 p-md-6',
                    },
                    {
                        name: 'birthdate',
                        label: 'Date de naissance',
                        type: 'calendar',
                        gridClass: 'p-col-12 p-md-6',
                    },
                    {
                        name: 'document',
                        label: 'Document à joindre',
                        type: 'file',
                        gridClass: 'p-col-12',
                    },
                ],
            },
        formOptions: {
            readonly: false,
            autosave: true,
            autosaveUrl: '/api/save-form',
            buttons: {
                prev: { label: 'Retour', class: 'p-button-secondary' },
                submit: { label: 'Enregistrer', class: 'p-button-success' },
            },
            onBeforeSubmit: async (values) => {
                console.log('Avant soumission étape 2', values);
            },
        },
        initialValues: {
            role: '',
            extraInfo: '',
            birthdate: '',
            document: null,
        },
    },
];

export default userFormSteps;
