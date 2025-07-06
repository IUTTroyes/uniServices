import type { Student, Staff } from '../types';

export const mockStudents: Student[] = [
  {
    id: '1',
    firstName: 'Emma',
    lastName: 'Martin',
    email: 'emma.martin@etudiant.univ.fr',
    photo: 'https://images.pexels.com/photos/3769021/pexels-photo-3769021.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    semester: 3,
    groups: {
      CM: ['CM1'],
      TD: ['TD-AB'],
      TP: ['TP-A']
    },
    enrollmentYear: 2022,
    status: 'active'
  },
  {
    id: '2',
    firstName: 'Lucas',
    lastName: 'Dubois',
    email: 'lucas.dubois@etudiant.univ.fr',
    photo: 'https://images.pexels.com/photos/2379004/pexels-photo-2379004.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    semester: 3,
    groups: {
      CM: ['CM1'],
      TD: ['TD-AB'],
      TP: ['TP-B']
    },
    enrollmentYear: 2022,
    status: 'active'
  },
  {
    id: '3',
    firstName: 'Léa',
    lastName: 'Lefebvre',
    email: 'lea.lefebvre@etudiant.univ.fr',
    photo: 'https://images.pexels.com/photos/1587009/pexels-photo-1587009.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    semester: 5,
    groups: {
      CM: ['CM2'],
      TD: ['TD-CD'],
      TP: ['TP-C']
    },
    enrollmentYear: 2021,
    status: 'active'
  },
  {
    id: '4',
    firstName: 'Thomas',
    lastName: 'Moreau',
    email: 'thomas.moreau@etudiant.univ.fr',
    photo: 'https://images.pexels.com/photos/1040880/pexels-photo-1040880.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    semester: 5,
    groups: {
      CM: ['CM2'],
      TD: ['TD-CD'],
      TP: ['TP-A']
    },
    enrollmentYear: 2021,
    status: 'active'
  },
  {
    id: '5',
    firstName: 'Camille',
    lastName: 'Roux',
    email: 'camille.roux@etudiant.univ.fr',
    photo: 'https://images.pexels.com/photos/1239291/pexels-photo-1239291.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    semester: 1,
    groups: {
      CM: ['CM1'],
      TD: ['TD-EF'],
      TP: ['TP-B']
    },
    enrollmentYear: 2023,
    status: 'active'
  },
  {
    id: '6',
    firstName: 'Hugo',
    lastName: 'Simon',
    email: 'hugo.simon@etudiant.univ.fr',
    photo: 'https://images.pexels.com/photos/1043471/pexels-photo-1043471.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    semester: 1,
    groups: {
      CM: ['CM1'],
      TD: ['TD-EF'],
      TP: ['TP-C']
    },
    enrollmentYear: 2023,
    status: 'active'
  },
  {
    id: '7',
    firstName: 'Chloé',
    lastName: 'Laurent',
    email: 'chloe.laurent@etudiant.univ.fr',
    photo: 'https://images.pexels.com/photos/1065084/pexels-photo-1065084.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    semester: 3,
    groups: {
      CM: ['CM1'],
      TD: ['TD-AB'],
      TP: ['TP-A']
    },
    enrollmentYear: 2022,
    status: 'active'
  },
  {
    id: '8',
    firstName: 'Nathan',
    lastName: 'Michel',
    email: 'nathan.michel@etudiant.univ.fr',
    photo: 'https://images.pexels.com/photos/1542085/pexels-photo-1542085.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    semester: 5,
    groups: {
      CM: ['CM2'],
      TD: ['TD-CD'],
      TP: ['TP-B']
    },
    enrollmentYear: 2021,
    status: 'suspended'
  },
  {
    id: '9',
    firstName: 'Manon',
    lastName: 'Garcia',
    email: 'manon.garcia@etudiant.univ.fr',
    photo: 'https://images.pexels.com/photos/1181519/pexels-photo-1181519.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    semester: 1,
    groups: {
      CM: ['CM1'],
      TD: ['TD-EF'],
      TP: ['TP-A']
    },
    enrollmentYear: 2023,
    status: 'active'
  },
  {
    id: '10',
    firstName: 'Antoine',
    lastName: 'Bernard',
    email: 'antoine.bernard@etudiant.univ.fr',
    photo: 'https://images.pexels.com/photos/1043474/pexels-photo-1043474.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    semester: 3,
    groups: {
      CM: ['CM1'],
      TD: ['TD-AB'],
      TP: ['TP-C']
    },
    enrollmentYear: 2022,
    status: 'active'
  }
];

export const mockStaff: Staff[] = [
  {
    id: 's1',
    firstName: 'Marie',
    lastName: 'Dupont',
    email: 'marie.dupont@univ.fr',
    photo: 'https://images.pexels.com/photos/1181424/pexels-photo-1181424.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    status: 'Enseignant',
    department: 'Informatique',
    position: 'Professeur des Universités',
    hireDate: '2015-09-01'
  },
  {
    id: 's2',
    firstName: 'Jean',
    lastName: 'Leclerc',
    email: 'jean.leclerc@univ.fr',
    photo: 'https://images.pexels.com/photos/1043473/pexels-photo-1043473.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    status: 'Enseignant',
    department: 'Mathématiques',
    position: 'Maître de Conférences',
    hireDate: '2018-02-15'
  },
  {
    id: 's3',
    firstName: 'Sophie',
    lastName: 'Martinez',
    email: 'sophie.martinez@univ.fr',
    photo: 'https://images.pexels.com/photos/1181686/pexels-photo-1181686.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    status: 'Administratif',
    department: 'Scolarité',
    position: 'Secrétaire Pédagogique',
    hireDate: '2020-01-10'
  },
  {
    id: 's4',
    firstName: 'Pierre',
    lastName: 'Rousseau',
    email: 'pierre.rousseau@univ.fr',
    photo: 'https://images.pexels.com/photos/1040881/pexels-photo-1040881.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    status: 'Technique',
    department: 'Informatique',
    position: 'Ingénieur Système',
    hireDate: '2017-06-20'
  },
  {
    id: 's5',
    firstName: 'Catherine',
    lastName: 'Morel',
    email: 'catherine.morel@univ.fr',
    photo: 'https://images.pexels.com/photos/1181562/pexels-photo-1181562.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    status: 'Direction',
    department: 'Administration',
    position: 'Directrice des Études',
    hireDate: '2012-09-01'
  },
  {
    id: 's6',
    firstName: 'David',
    lastName: 'Leroy',
    email: 'david.leroy@univ.fr',
    photo: 'https://images.pexels.com/photos/1043472/pexels-photo-1043472.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    status: 'Enseignant',
    department: 'Physique',
    position: 'Professeur Agrégé',
    hireDate: '2019-09-01'
  },
  {
    id: 's7',
    firstName: 'Isabelle',
    lastName: 'Fontaine',
    email: 'isabelle.fontaine@univ.fr',
    photo: 'https://images.pexels.com/photos/1181690/pexels-photo-1181690.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    status: 'Administratif',
    department: 'Ressources Humaines',
    position: 'Gestionnaire RH',
    hireDate: '2021-03-15'
  },
  {
    id: 's8',
    firstName: 'Philippe',
    lastName: 'Robert',
    email: 'philippe.robert@univ.fr',
    photo: 'https://images.pexels.com/photos/1040882/pexels-photo-1040882.jpeg?auto=compress&cs=tinysrgb&w=150&h=150&fit=crop',
    status: 'Technique',
    department: 'Maintenance',
    position: 'Technicien Maintenance',
    hireDate: '2016-11-01'
  }
];

import type { Category, Document, DocumentType } from '@/types';

export const categories: Category[] = [
  {
    id: '1',
    name: 'Ressources Humaines',
    documentCount: 15,
    icon: '👥',
    color: 'bg-blue-500',
    children: [
      {
        id: '1-1',
        name: 'Contrats',
        parentId: '1',
        documentCount: 8,
        icon: '📋',
        color: 'bg-blue-400'
      },
      {
        id: '1-2',
        name: 'Formations',
        parentId: '1',
        documentCount: 7,
        icon: '🎓',
        color: 'bg-blue-400'
      }
    ]
  },
  {
    id: '2',
    name: 'Finance',
    documentCount: 23,
    icon: '💰',
    color: 'bg-green-500',
    children: [
      {
        id: '2-1',
        name: 'Comptabilité',
        parentId: '2',
        documentCount: 12,
        icon: '📊',
        color: 'bg-green-400'
      },
      {
        id: '2-2',
        name: 'Budgets',
        parentId: '2',
        documentCount: 11,
        icon: '📈',
        color: 'bg-green-400'
      }
    ]
  },
  {
    id: '3',
    name: 'Technique',
    documentCount: 31,
    icon: '⚙️',
    color: 'bg-purple-500',
    children: [
      {
        id: '3-1',
        name: 'Documentation API',
        parentId: '3',
        documentCount: 15,
        icon: '🔧',
        color: 'bg-purple-400'
      },
      {
        id: '3-2',
        name: 'Guides utilisateur',
        parentId: '3',
        documentCount: 16,
        icon: '📖',
        color: 'bg-purple-400'
      }
    ]
  }
];

const documentTypes: DocumentType[] = ['pdf', 'excel', 'word', 'powerpoint', 'image', 'video', 'audio', 'text', 'archive'];

const generateRandomDocument = (id: string, categoryId: string): Document => {
  const titles = [
    'Rapport annuel 2024', 'Guide utilisateur', 'Contrat de travail', 'Présentation client',
    'Analyse des ventes', 'Documentation technique', 'Procédure qualité', 'Budget prévisionnel',
    'Cahier des charges', 'Spécifications fonctionnelles', 'Manuel d\'installation',
    'Rapport de test', 'Politique de sécurité', 'Guide de démarrage', 'Tutoriel vidéo'
  ];

  const authors = ['Marie Dubois', 'Pierre Martin', 'Sophie Leroy', 'Jean Dupont', 'Claire Bernard'];
  const tags = ['important', 'urgent', 'confidentiel', 'brouillon', 'validé', 'archivé'];

  const type = documentTypes[Math.floor(Math.random() * documentTypes.length)];
  const title = titles[Math.floor(Math.random() * titles.length)];
  const author = authors[Math.floor(Math.random() * authors.length)];
  const size = Math.floor(Math.random() * 50000000) + 1000;
  const lastModified = new Date(Date.now() - Math.floor(Math.random() * 365 * 24 * 60 * 60 * 1000));
  const isFavorite = Math.random() > 0.8;
  const version = `v${Math.floor(Math.random() * 5) + 1}.${Math.floor(Math.random() * 10)}`;
  const randomTags = tags.sort(() => 0.5 - Math.random()).slice(0, Math.floor(Math.random() * 3) + 1);

  return {
    id,
    title,
    type,
    size,
    lastModified,
    description: Math.random() > 0.7 ? `Description du document ${title}` : undefined,
    categoryId,
    isFavorite,
    author,
    version,
    tags: randomTags
  };
};

export const documents: Document[] = [];
let documentId = 1;

const allCategories = categories.reduce((acc, cat) => {
  acc.push(cat);
  if (cat.children) {
    acc.push(...cat.children);
  }
  return acc;
}, [] as Category[]);

allCategories.forEach(category => {
  const docCount = category.documentCount;
  for (let i = 0; i < docCount; i++) {
    documents.push(generateRandomDocument(documentId.toString(), category.id));
    documentId++;
  }
});

export const getAllCategories = (): Category[] => categories;

export const getDocumentsByCategory = (categoryId: string): Document[] => {
  return documents.filter(doc => doc.categoryId === categoryId);
};

export const getFavoriteDocuments = (): Document[] => {
  return documents.filter(doc => doc.isFavorite);
};

export const searchDocuments = (query: string, filters?: any): Document[] => {
  return documents.filter(doc => {
    const matchesQuery = !query ||
        doc.title.toLowerCase().includes(query.toLowerCase()) ||
        doc.description?.toLowerCase().includes(query.toLowerCase()) ||
        doc.author.toLowerCase().includes(query.toLowerCase()) ||
        doc.tags.some(tag => tag.toLowerCase().includes(query.toLowerCase()));

    const matchesCategory = !filters?.category || doc.categoryId === filters.category;

    return matchesQuery && matchesCategory;
  });
};
