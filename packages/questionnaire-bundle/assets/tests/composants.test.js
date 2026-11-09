// @vitest-environment jsdom
// Rendu des composants du package Questionnaires avec des réponses d'API simulées ; tout avertissement Vue fait échouer le test.
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createApp, defineComponent, h, nextTick } from 'vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import Skeleton from 'primevue/skeleton';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

// jsdom ne fournit pas matchMedia (utilisé par les fenêtres PrimeVue).
window.matchMedia ??= () => ({ matches: false, addEventListener() {}, removeEventListener() {}, addListener() {}, removeListener() {} });

vi.mock('vue-router', () => ({ useRoute: () => ({ params: { id: 'uuid-1' }, query: {}, meta: {} }), useRouter: () => ({ push: vi.fn() }) }));

vi.mock('@components', async () => ({
  Card: (await import('@components/components/Card.vue')).default,
  EmptyState: (await import('@components/components/EmptyState.vue')).default,
  ListSkeleton: (await import('@components/loader/ListSkeleton.vue')).default,
  SimpleSkeleton: (await import('@components/loader/SimpleSkeleton.vue')).default,
  HeaderComponent: defineComponent({ props: ['titre', 'description', 'icon', 'color', 'showBack', 'backUrl'], setup: (p, { slots }) => () => h('header', [h('h1', p.titre), h('p', p.description), slots.actions?.()]) }),
  ActionButtonVertical: defineComponent({ props: ['label', 'icon', 'severity', 'disabled', 'to'], emits: ['click'], setup: (p, { emit }) => () => h('button', { type: 'button', disabled: p.disabled, onClick: () => emit('click') }, p.label) }),
}));

const toasts = vi.hoisted(() => ({ showSuccess: vi.fn(), showDanger: vi.fn(), showWarn: vi.fn(), showInfo: vi.fn() }));
vi.mock('@helpers/toast.js', () => toasts);
vi.mock('@utils/permissions', () => ({ hasPermission: () => true }));
vi.mock('@stores', () => ({
  useUsersStore: () => ({ departements: [{ id: 1, libelle: 'Informatique' }], departementDefaut: { id: 1 } }),
  useEtablissementStore: () => ({ etablissement: { libelle: 'IUT de test' }, getEtablissement: vi.fn() }),
}));
// Confirmation PrimeVue : acceptée d'office dans les tests.
vi.mock('primevue/useconfirm', () => ({ useConfirm: () => ({ require: (options) => options.accept() }) }));

const api = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() }));
vi.mock('@helpers/axios', () => ({ default: api }));
vi.mock('@helpers/apiCall', () => ({ default: async (fn, args) => (await fn(...args)).data }));

import ReminderSendModal from '../components/Questionnaire/ReminderSendModal.vue';
import ReminderHistoryModal from '../components/Questionnaire/ReminderHistoryModal.vue';
import InviteParticipantsModal from '../components/Questionnaire/InviteParticipantsModal.vue';
import QuestionnairePendingWidget from '../widgets/widgets/QuestionnairePendingWidget.vue';
import DashboardStudent from '../components/Dashboard/DashboardStudent.vue';
import ResultatsSection from '../components/Resultats/ResultatsSection.vue';
import ComparaisonView from '../views/Questionnaire/ComparaisonView.vue';
import ModelesView from '../views/Questionnaire/ModelesView.vue';

let avertissements = [];
let montages = [];

const monter = async (composant, props = {}) => {
  const el = document.createElement('div');
  document.body.appendChild(el);
  const evenements = {};
  const app = createApp(defineComponent({
    setup: () => () => h(composant, {
      ...props,
      onSent: () => { evenements.sent = true; },
      onClose: () => { evenements.close = true; },
      onInvited: () => { evenements.invited = true; },
      onRelancer: (r) => { evenements.relancer = r; },
    }),
  }));
  // Comme dans le shell : composants PrimeVue courants et directive tooltip enregistrés globalement ; RouterLink simulé.
  app.use(createPinia()).use(PrimeVue).use(ToastService).directive('tooltip', Tooltip)
    .component('Skeleton', Skeleton).component('Button', Button).component('Tag', Tag)
    .component('RouterLink', defineComponent({ props: ['to'], setup: (_, { slots }) => () => h('a', slots.default?.()) }));
  app.config.warnHandler = (msg) => avertissements.push(msg);
  app.mount(el);
  montages.push({ app, el });
  await attendre();
  return { el, evenements };
};
const attendre = async () => { for (let i = 0; i < 5; i++) { await nextTick(); await new Promise(r => setTimeout(r, 0)); } };
const bouton = (texte) => [...document.body.querySelectorAll('button')].find(b => b.textContent.includes(texte));

beforeEach(() => {
  avertissements = [];
  Object.values(api).forEach(f => f.mockReset());
  Object.values(toasts).forEach(f => f.mockReset());
});
afterEach(() => {
  montages.forEach(({ app, el }) => { app.unmount(); el.remove(); });
  montages = [];
  document.body.innerHTML = '';
  expect(avertissements).toEqual([]);
});

const cible = { id: '7', participantName: 'alice@exemple.fr', participantEmail: 'alice@exemple.fr', statusLabel: 'En cours', statusSeverity: 'warn' };

describe('ReminderSendModal', () => {
  it('envoie la relance par identifiant d\'invitation et signale le résultat', async () => {
    api.post.mockResolvedValue({ data: { sentCount: 1, failedCount: 0, invitations: [] } });
    const { evenements } = await monter(ReminderSendModal, { visible: true, surveyId: 'uuid-1', surveyTitle: 'Enquête', targets: [cible] });

    expect(document.body.textContent).toContain('alice@exemple.fr');
    bouton('Envoyer le rappel').click();
    await attendre();

    expect(api.post).toHaveBeenCalledWith('/api/questionnaires/uuid-1/reminders', { invitations: [7], subject: undefined, message: undefined });
    expect(toasts.showSuccess).toHaveBeenCalled();
    expect(evenements.sent).toBe(true);
  });
});

describe('ReminderHistoryModal', () => {
  it('liste les relances de la plus récente à la plus ancienne', async () => {
    const response = { ...cible, completed: false, remindersCount: 2, remindersHistory: [
      { id: 'a', sentAt: '2026-11-01T10:00:00+01:00', subject: 'Premier rappel', type: 'manual' },
      { id: 'b', sentAt: '2026-11-05T10:00:00+01:00', subject: 'Second rappel', type: 'automatic' },
    ] };
    await monter(ReminderHistoryModal, { visible: true, response });
    const texte = document.body.textContent;
    expect(texte.indexOf('Second rappel')).toBeLessThan(texte.indexOf('Premier rappel'));
  });

  it('affiche un état vide et propose une relance', async () => {
    const { evenements } = await monter(ReminderHistoryModal, { visible: true, response: { ...cible, completed: false, remindersHistory: [] } });
    expect(document.body.textContent).toContain('Aucune relance effectuée');
    bouton('Nouvelle relance').click();
    await attendre();
    expect(evenements.relancer?.id).toBe('7');
  });
});

describe('InviteParticipantsModal', () => {
  it('invite les adresses saisies et ignore les invalides', async () => {
    api.post.mockResolvedValue({ data: { invitationsCount: 2, emailsCount: 2, ignores: 0 } });
    api.get.mockResolvedValue({ data: [] });
    const { evenements } = await monter(InviteParticipantsModal, { surveyId: 'uuid-1' });

    const zone = document.body.querySelector('#invite-adresses');
    zone.value = 'a@exemple.fr, B@Exemple.fr ; pas-une-adresse';
    zone.dispatchEvent(new Event('input'));
    await attendre();
    expect(document.body.textContent).toContain('pas-une-adresse');

    bouton('Envoyer les invitations').click();
    await attendre();
    expect(api.post).toHaveBeenCalledWith('/api/questionnaires/uuid-1/invitations', { emails: ['a@exemple.fr', 'b@exemple.fr'] });
    expect(evenements.invited).toBe(true);
  });
});

describe('QuestionnairePendingWidget', () => {
  it('liste les questionnaires à remplir ou affiche un état vide', async () => {
    const { el } = await monter(QuestionnairePendingWidget, { data: { items: [{ titre: 'Satisfaction S3', echeance: '30/11/2026', lien: '/questionnaire/x' }] } });
    expect(el.textContent).toContain('Satisfaction S3');
    expect(el.textContent).toContain('30/11/2026');

    const vide = await monter(QuestionnairePendingWidget, { data: { items: [] } });
    expect(vide.el.textContent).toContain('Aucun questionnaire à remplir');
  });
});

describe('DashboardStudent', () => {
  it('sépare les questionnaires à remplir, remplis et mes résultats publiés', async () => {
    api.get.mockImplementation(async (url) => ({
      data: url.includes('mes-invitations')
        ? [
          { uuid: 'q1', title: 'À remplir', token: 't1', status: 'pending', ouvert: true, modifiable: true, anonymous: true },
          { uuid: 'q2', title: 'Déjà rempli', token: 't2', status: 'submitted', ouvert: true, modifiable: true, submittedAt: '2026-11-02T10:00:00+01:00', anonymous: true },
          { uuid: 'q3', title: 'Fermé', token: 't3', status: 'pending', ouvert: false, modifiable: false, anonymous: true },
        ]
        : [{ uuid: 'q9', title: 'Résultats R1', anneeUniversitaire: '2026/2027', sections: [{ id: 3, titre: 'Ressource R1' }] }],
    }));
    const { el } = await monter(DashboardStudent);
    const texte = el.textContent;
    expect(texte).toContain('À remplir');
    expect(texte).toContain('Déjà rempli');
    expect(texte).not.toContain('Fermé');
    expect(texte).toContain('Résultats R1');
    expect(texte).toContain('Modifier');
  });
});

describe('ResultatsSection', () => {
  it('restitue les réponses en tableaux accessibles', async () => {
    const section = { sectionInstanceId: 5, sectionTitle: 'Ressource R1', enseignement: 'R1', intervenant: 'Jean Martin', reponses: 2, masquee: false,
      questions: [
        { questionId: '1', questionLabel: 'Clarté', totalResponses: 2, stats: { choices: [{ text: 'Oui', count: 2, percentage: 100 }] } },
        { questionId: '2', questionLabel: 'Note', totalResponses: 2, stats: { min: 1, max: 5, average: 4.5, distribution: { 4: 1, 5: 1 } } },
        { questionId: '3', questionLabel: 'Remarques', totalResponses: 1, stats: { samples: ['Très clair'] } },
      ] };
    const { el } = await monter(ResultatsSection, { section, seuil: 0 });
    expect(el.querySelectorAll('table caption').length).toBe(2);
    expect(el.querySelector('th[scope="row"]').textContent).toBe('Oui');
    expect(el.textContent).toContain('Moyenne : 4.5');
    expect(el.textContent).toContain('Très clair');
    expect(el.textContent).toContain('Jean Martin');
  });

  it('masque une section sous le seuil d\'anonymat', async () => {
    const { el } = await monter(ResultatsSection, { section: { sectionInstanceId: 6, sectionTitle: 'R2', reponses: 1, masquee: true, questions: [] }, seuil: 5 });
    expect(el.textContent).toContain('Résultats masqués');
    expect(el.textContent).toContain('Moins de 5 réponses');
  });
});

describe('ComparaisonView', () => {
  it('compare les campagnes en tableaux, une colonne par année', async () => {
    api.get.mockResolvedValue({ data: {
      modele: { uuid: 'm', title: 'Satisfaction' },
      campagnes: [
        { uuid: 'a', title: 'S 2025', annee: '2025/2026', invites: 10, reponses: 8, taux: 80 },
        { uuid: 'b', title: 'S 2026', annee: '2026/2027', invites: 10, reponses: 9, taux: 90 },
      ],
      questions: [{ cle: 'q', label: 'Note globale', type: 'scale', valeurs: [{ n: 8, moyenne: 3.5, choix: null }, null] }],
    } });
    const { el } = await monter(ComparaisonView);
    const texte = el.textContent;
    expect(texte).toContain('Comparaison — Satisfaction');
    expect(texte).toContain('90 %');
    expect(texte).toContain('3.5 (8 rép.)');
    expect(texte).toContain('—');
  });

  it('explique qu\'il faut deux campagnes', async () => {
    api.get.mockResolvedValue({ data: { modele: null, campagnes: [{ uuid: 'a', title: 'Seule', invites: 1, reponses: 1, taux: 100 }], questions: [] } });
    const { el } = await monter(ComparaisonView);
    expect(el.textContent).toContain('Pas encore de comparaison possible');
  });
});

describe('ModelesView', () => {
  it('liste les modèles et crée un questionnaire depuis l\'un d\'eux', async () => {
    api.get.mockResolvedValue({ data: [{ uuid: 'm1', title: 'Satisfaction', departement: null }] });
    api.post.mockResolvedValue({ data: { uuid: 'nouveau', title: 'Satisfaction 2026' } });
    const { el } = await monter(ModelesView);
    expect(el.textContent).toContain('Satisfaction');
    bouton('Utiliser').click();
    await attendre();
    const titre = document.body.querySelector('#modele-titre');
    expect(titre.value).toBe('Satisfaction');
  });
});
