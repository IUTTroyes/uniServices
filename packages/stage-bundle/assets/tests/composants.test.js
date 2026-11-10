// @vitest-environment jsdom
// Rendu des composants du package Stages avec des réponses d'API simulées ; tout avertissement Vue fait échouer le test.
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createApp, defineComponent, h, nextTick } from 'vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import Skeleton from 'primevue/skeleton';

const route = { params: { jeton: 'jeton-de-test-0123456789' }, query: {}, meta: {} };
vi.mock('vue-router', () => ({ useRoute: () => route, useRouter: () => ({ push: vi.fn() }) }));

vi.mock('@components', async () => ({
  Card: (await import('@components/components/Card.vue')).default,
  EmptyState: (await import('@components/components/EmptyState.vue')).default,
  ListSkeleton: (await import('@components/loader/ListSkeleton.vue')).default,
  SimpleSkeleton: (await import('@components/loader/SimpleSkeleton.vue')).default,
  HeaderComponent: defineComponent({ props: ['titre', 'description', 'icon', 'color', 'showBack', 'backUrl'], setup: (p) => () => h('header', [h('h1', p.titre), h('p', p.description)]) }),
}));

// Confirmation PrimeVue (ConfirmDialog global du shell) : acceptée d'office dans les tests.
vi.mock('primevue/useconfirm', () => ({ useConfirm: () => ({ require: (options) => options.accept() }) }));

const api = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn(), delete: vi.fn() }));
vi.mock('@helpers/axios', () => ({ default: api }));
vi.mock('@helpers/apiCall', () => ({ default: async (fn, args) => (await fn(...args)).data }));

import RapportStage from '../components/RapportStage.vue';
import AvenantsEtudiant from '../components/AvenantsEtudiant.vue';
import EvaluationEntreprisePanel from '../components/EvaluationEntreprisePanel.vue';
import EvaluationEntrepriseView from '../views/Public/EvaluationEntrepriseView.vue';

let avertissements = [];
let montages = [];

const monter = async (composant, props = {}) => {
  const el = document.createElement('div');
  document.body.appendChild(el);
  const evenements = {};
  const app = createApp(defineComponent({
    setup: () => () => h(composant, {
      ...props,
      onChange: (v) => { evenements.change = v; },
      onCree: () => { evenements.cree = true; },
    }),
  }));
  // Comme dans le shell : Skeleton et la directive tooltip sont enregistrés globalement.
  app.use(PrimeVue).use(ToastService).directive('tooltip', Tooltip).component('Skeleton', Skeleton);
  app.config.warnHandler = (msg) => avertissements.push(msg);
  app.mount(el);
  montages.push({ app, el });
  await attendre();
  return { el, evenements };
};
const attendre = async () => { for (let i = 0; i < 5; i++) { await nextTick(); await new Promise(r => setTimeout(r, 0)); } };
const bouton = (el, texte) => [...el.querySelectorAll('button')].find(b => b.textContent.includes(texte));

beforeEach(() => {
  avertissements = [];
  Object.values(api).forEach(f => f.mockReset());
});
afterEach(() => {
  montages.forEach(({ app, el }) => { app.unmount(); el.remove(); });
  montages = [];
  expect(avertissements).toEqual([]);
});

describe('RapportStage', () => {
  it('dépose un PDF, affiche le lien de téléchargement et remonte l\'état', async () => {
    api.post.mockResolvedValue({ data: { reportUploaded: true, reportName: 'rapport.pdf', reportDate: '2027-06-01T10:00:00+02:00' } });
    const { el, evenements } = await monter(RapportStage, { stage: { id: 7, reportUploaded: false } });

    expect(el.textContent).toContain('Déposer mon rapport de stage');
    const champ = el.querySelector('input[type=file]');
    Object.defineProperty(champ, 'files', { value: [new File(['%PDF-1.4'], 'rapport.pdf', { type: 'application/pdf' })] });
    champ.dispatchEvent(new Event('change'));
    await attendre();

    expect(api.post).toHaveBeenCalledWith('/api/stage_etudiants/7/rapport', expect.any(FormData), expect.any(Object));
    expect(evenements.change).toMatchObject({ reportUploaded: true, reportName: 'rapport.pdf' });
  });

  it('refuse un fichier qui n\'est pas un PDF sans appeler l\'API', async () => {
    const { el } = await monter(RapportStage, { stage: { id: 7, reportUploaded: false } });
    const champ = el.querySelector('input[type=file]');
    Object.defineProperty(champ, 'files', { value: [new File(['x'], 'image.png', { type: 'image/png' })] });
    champ.dispatchEvent(new Event('change'));
    await attendre();
    expect(api.post).not.toHaveBeenCalled();
  });

  it('propose le téléchargement et le retrait d\'un rapport déposé', async () => {
    api.delete.mockResolvedValue({ data: { reportUploaded: false, reportName: null, reportDate: null } });
    const { el, evenements } = await monter(RapportStage, { stage: { id: 7, reportUploaded: true, reportName: 'Mon rapport.pdf', reportDate: '01/06/2027' } });

    expect(el.querySelector('a[href="/api/stage_etudiants/7/rapport"]').textContent).toContain('Mon rapport.pdf');
    el.querySelector('button[aria-label="Retirer le rapport"]').click();
    await attendre();
    expect(api.delete).toHaveBeenCalledWith('/api/stage_etudiants/7/rapport');
    expect(evenements.change.reportUploaded).toBe(false);
  });
});

describe('AvenantsEtudiant', () => {
  it('liste les avenants et ne permet la demande que sur une convention signée', async () => {
    const avenants = [{ id: 1, libelle: 'Prolongation', typeModification: 'DATES', valide: true, dateCreation: '2027-05-01', dateValidation: '2027-05-03' }];
    const { el } = await monter(AvenantsEtudiant, { stage: { id: 3, etatStage: 'ETAT_STAGE_CONVENTION_RECUE', avenants } });
    expect(el.textContent).toContain('Prolongation');
    expect(el.textContent).toContain('Validé le 03/05/2027');
    expect(el.querySelector('.p-tag')).not.toBeNull();
    expect(bouton(el, 'Demander un avenant').disabled).toBe(false);

    const nonSigne = await monter(AvenantsEtudiant, { stage: { id: 4, etatStage: 'ETAT_STAGE_VALIDE', avenants: [] } });
    expect(bouton(nonSigne.el, 'Demander un avenant')).toBeUndefined();
  });

  it('bloque une nouvelle demande tant qu\'une autre attend', async () => {
    const avenants = [{ id: 2, libelle: 'Horaires', typeModification: 'HORAIRES', valide: false, dateCreation: '2027-05-01' }];
    const { el } = await monter(AvenantsEtudiant, { stage: { id: 3, etatStage: 'ETAT_STAGE_CONVENTION_RECUE', avenants } });
    expect(el.textContent).toContain('En attente de validation');
    expect(bouton(el, 'Demander un avenant').disabled).toBe(true);
  });
});

describe('EvaluationEntreprisePanel', () => {
  it('propose l\'envoi quand rien n\'a été demandé', async () => {
    api.get.mockResolvedValue({ data: { statut: 'non_envoyee', destinataire: 'tuteur@acme.test', criteres: 3, reponses: [], niveaux: {} } });
    const { el } = await monter(EvaluationEntreprisePanel, { stageId: 5 });
    expect(api.get).toHaveBeenCalledWith('/api/stage_etudiants/5/evaluation-entreprise');
    expect(bouton(el, 'Envoyer le formulaire au tuteur').disabled).toBe(false);
    expect(el.textContent).toContain('tuteur@acme.test');
  });

  it('empêche l\'envoi sans critère et affiche une évaluation rendue', async () => {
    api.get.mockResolvedValueOnce({ data: { statut: 'non_envoyee', destinataire: 'tuteur@acme.test', criteres: 0, reponses: [], niveaux: {} } });
    const sansCritere = await monter(EvaluationEntreprisePanel, { stageId: 5 });
    expect(bouton(sansCritere.el, 'Envoyer').disabled).toBe(true);
    expect(sansCritere.el.textContent).toContain('Aucun critère');

    api.get.mockResolvedValueOnce({ data: {
      statut: 'soumise', soumisLe: '2027-06-20T10:00:00+02:00', evaluateurNom: 'M. Durand', noteProposee: 16, appreciation: 'Très bien',
      reponses: [{ critere: 1, libelle: 'Autonomie', niveau: 4, commentaire: 'Excellent' }], niveaux: { 4: 'Très satisfaisant' },
    } });
    const rendue = await monter(EvaluationEntreprisePanel, { stageId: 6 });
    expect(rendue.el.textContent).toContain('M. Durand');
    expect(rendue.el.textContent).toContain('4/4 — Très satisfaisant');
    expect(rendue.el.textContent).toContain('16 / 20');
  });
});

describe('EvaluationEntrepriseView (page publique)', () => {
  const formulaire = {
    statut: 'ouverte', etudiant: 'Alice MARTIN', periode: 'Stage BUT2', entreprise: 'ACME', dates: 'du 05/04/2027 au 25/06/2027',
    evaluateurNom: 'M. Durand', criteres: [{ id: 11, libelle: 'Autonomie', description: null }],
    niveaux: { 1: 'Insuffisant', 2: 'Passable', 3: 'Satisfaisant', 4: 'Très satisfaisant' },
  };

  it('n\'autorise l\'envoi qu\'une fois chaque critère noté, puis confirme', async () => {
    api.get.mockResolvedValue({ data: formulaire });
    api.post.mockResolvedValue({ data: { statut: 'soumise' } });
    const { el } = await monter(EvaluationEntrepriseView);

    expect(el.textContent).toContain('Alice MARTIN');
    const envoyer = bouton(el, 'Transmettre');
    expect(el.querySelector('h1').textContent).toBe('Évaluation du stagiaire');
    expect(envoyer.disabled).toBe(true);

    el.querySelector('input[type=radio][value="3"]').click();
    await attendre();
    expect(envoyer.disabled).toBe(false);

    el.querySelector('form').dispatchEvent(new Event('submit'));
    await attendre();
    expect(api.post).toHaveBeenCalledWith('/api/stage/evaluation-entreprise/jeton-de-test-0123456789',
      expect.objectContaining({ evaluateurNom: 'M. Durand', reponses: { 11: { niveau: 3, commentaire: '' } } }));
    expect(el.textContent).toContain('votre évaluation a bien été transmise');
  });

  it('signale un lien expiré ou déjà utilisé', async () => {
    api.get.mockRejectedValue({ response: { status: 404, data: { statut: 'invalide' } } });
    const { el } = await monter(EvaluationEntrepriseView);
    expect(el.textContent).toContain('Ce lien n\'est plus utilisable');
    expect(el.querySelector('form')).toBeNull();
  });
});
