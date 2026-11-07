// @vitest-environment jsdom
// Test de rendu des écrans : chaque vue est montée avec des réponses d'API réalistes ; le moindre avertissement
// Vue (propriété non définie, composant inconnu…) fait échouer le test.
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createApp, defineComponent, h, nextTick } from 'vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';

const route = { params: {}, query: {}, meta: {} };
const router = { push: vi.fn(), replace: vi.fn() };
vi.mock('vue-router', () => ({ useRoute: () => route, useRouter: () => router }));

vi.mock('@components', () => ({
  HeaderComponent: defineComponent({
    props: ['titre', 'description', 'icon', 'showBack', 'backUrl', 'color'],
    setup: (p, { slots }) => () => h('header', [h('h1', p.titre), h('p', p.description), slots.actions?.()]),
  }),
  EmptyState: defineComponent({
    props: ['title', 'description', 'icon', 'color', 'actionLabel', 'actionIcon', 'compact'],
    setup: (p) => () => h('div', { class: 'empty' }, [h('strong', p.title), h('span', p.description)]),
  }),
  registerWidgetComponent: vi.fn(),
}));

const api = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() }));
vi.mock('@helpers/axios', () => ({ default: api }));
vi.mock('vue-draggable-next', () => ({ VueDraggableNext: defineComponent({ setup: (_, { slots }) => () => h('div', slots.default?.()) }) }));

import EvaluationsView from '../views/EvaluationsView.vue';
import EvaluationEtudiantView from '../views/EvaluationEtudiantView.vue';
import EvaluationTraceView from '../views/EvaluationTraceView.vue';
import CampagnesView from '../views/admin/CampagnesView.vue';
import CampagneEditView from '../views/admin/CampagneEditView.vue';
import TraceDetailView from '../views/TraceDetailView.vue';
import AutoEvaluationView from '../views/AutoEvaluationView.vue';
import EvaluationLot from '../components/evaluation/EvaluationLot.vue';
import EvaluationBilansView from '../views/EvaluationBilansView.vue';
import PortfoliosView from '../views/PortfoliosView.vue';
import PortfolioEditView from '../views/PortfolioEditView.vue';
import CvView from '../views/CvView.vue';
import ModelesView from '../views/admin/ModelesView.vue';
import ModeleEditView from '../views/admin/ModeleEditView.vue';
import PartageDialog from '../components/portfolio/PartageDialog.vue';
import CopieVitrineDialog from '../components/portfolio/CopieVitrineDialog.vue';
import ApparenceDialog from '../components/portfolio/ApparenceDialog.vue';
import ModerationView from '../views/admin/ModerationView.vue';
import CommentairesThread from '../components/commentaires/CommentairesThread.vue';

// jsdom n'implémente pas ces API navigateur, utilisées par PrimeVue.
globalThis.ResizeObserver ??= class { observe() {} unobserve() {} disconnect() {} };
window.matchMedia ??= (requete) => ({ matches: false, media: requete, addEventListener() {}, removeEventListener() {}, addListener() {}, removeListener() {}, dispatchEvent: () => false });

// ---------- Jeux de données ----------
const phase = { value: 'depot', libelle: 'Dépôt en cours', badge: 'info' };
const campagne = {
  uuid: 'c1c1c1c1-0000-4000-8000-000000000001', libelle: 'MMI 2026-2027',
  diplome: { id: 35, libelle: 'MMI', parcours: 'DWeb-DI', departement: 'MMI' },
  anneeUniversitaire: { id: 9, libelle: '2026-2027' }, anneeFormation: { id: 67, libelle: 'MMI - DWeb-DI - 2' },
  ouvertureLe: '2026-09-01T08:00:00+00:00', depotLimite: '2026-12-15T08:00:00+00:00', evaluationLimite: '2027-01-15T08:00:00+00:00', clotureLe: null,
  autoEvaluationRequise: false, phase, enRetard: false, nbEtudiants: 30, nbAffectes: 12, nbEvaluations: 4,
};
const competence = { id: 49, nomCourt: 'Comprendre', libelle: 'Comprendre les écosystèmes', couleur: 'c1' };
const echelons = [{ id: 1, libelle: 'Non acquis', valeur: 0, couleur: '#b91c1c', descripteur: 'Non démontré' }, { id: 2, libelle: 'Acquis', valeur: 2, couleur: '#16a34a', descripteur: 'Démontré' }];
const criteres = [
  { id: 10, libelle: 'Maîtrise', type: 'echelle', source: 'saisie', poids: 2, obligatoire: true, description: 'Niveau', visibleEtudiant: true },
  { id: 11, libelle: 'Usage de l\'IA', type: 'echelle', source: 'declaration_ia', poids: 0, obligatoire: false, visibleEtudiant: true },
  { id: 12, libelle: 'Appréciation', type: 'texte', source: 'saisie', poids: 0, obligatoire: false, visibleEtudiant: true },
];
const grille = { libelle: 'BUT — AC', version: 1, echelons, criteres };
const resumeEval = (extra = {}) => ({
  uuid: 'e1e1e1e1-0000-4000-8000-000000000001', bilan: false, statut: { value: 'en_cours', libelle: 'En cours', badge: 'warn' }, score: null, rendueLe: null, visibleEtudiant: false,
  evaluateur: { id: 7, nom: 'Dupont Alice' }, cible: { type: 'ac', id: 479, code: 'AC21.01', libelle: 'Analyser la stratégie' }, competence,
  trace: { uuid: 't1', titre: 'Maquette', rattachementId: 5 }, mienne: true, ...extra,
});
const detailEval = (extra = {}) => ({
  ...resumeEval(), appreciation: null, modifiable: true, erreursRendu: [], etudiant: { id: 1, nom: 'LAMOUREUX Lauren' }, campagne: { uuid: campagne.uuid, libelle: campagne.libelle, phase: 'depot' },
  grille, reponses: [], reponsesDetaillees: [{ critere: 10, libelle: 'Maîtrise', type: 'echelle', normalisee: 1, echelon: { libelle: 'Acquis', couleur: '#16a34a', valeur: 2 }, texte: null }],
  autoEvaluation: { uuid: 'a1', score: 0.5, reflexion: 'Je progresse', soumiseLe: '2026-11-01T10:00:00+00:00', reponses: [{ critere: 10, libelle: 'Maîtrise', type: 'echelle', normalisee: 0.4, echelon: { libelle: 'Non acquis', couleur: '#b91c1c', valeur: 0 }, texte: null }] },
  autres: [{ ...resumeEval({ mienne: false, statut: { value: 'rendue', libelle: 'Rendue', badge: 'success' }, score: 0.8 }), evaluateur: { id: 8, nom: 'Martin Paul' } }], ...extra,
});
const itemLot = (cle, code, extra = {}) => ({
  cle, cible: { type: 'ac', id: Number(cle.split(':')[1]), code, ordre: null, libelle: `Libellé ${code}` }, competence, argumentaire: 'Parce que', grilleVersion: 1, evaluation: null,
  autoEvaluation: { uuid: 'a1', score: 0.5, reflexion: 'Je progresse', soumiseLe: '2026-11-01T10:00:00+00:00', reponses: [{ critere: 10, libelle: 'Maîtrise', type: 'echelle', normalisee: 0.4, echelon: { libelle: 'Non acquis', couleur: '#b91c1c', valeur: 0 }, texte: null }] },
  autres: [], ...extra,
});
const evalLegere = (extra = {}) => ({ ...resumeEval(), appreciation: null, modifiable: true, grilleVersion: 1, erreursRendu: [], reponses: [], reponsesDetaillees: [], ...extra });
const lotPayload = (extra = {}) => ({
  mode: 'trace', campagne, etudiant: { id: 1, nom: 'LAMOUREUX Lauren' }, peutEvaluer: true, autoEvaluationRequise: false, versionCourante: 1, grilles: { 1: grille },
  items: [itemLot('r:5', 'AC21.01'), itemLot('r:6', 'AC21.02')], traceStatut: { value: 'en_evaluation', libelle: 'En évaluation' }, ...extra,
});
const traceDetail = (extra = {}) => ({
  uuid: 't1t1t1t1-0000-4000-8000-000000000001', titre: 'Maquette', dateRealisation: '2026-10-15', typeActivite: { value: 'sae', libelle: 'SAÉ' },
  statut: { value: 'en_evaluation', libelle: 'En évaluation', badge: 'warn' }, scolarite: { id: 1, annee: 'MMI - DWeb-DI - 2', anneeUniversitaire: '2026-2027', active: true },
  modifiable: false, contexte: 'Contexte', conceptionIa: { value: 0, libelle: 'Aucune utilisation' }, realisationIa: { value: 1, libelle: 'Utilisation ponctuelle' }, usageIaDetail: 'Correcteur',
  medias: [{ id: 1, type: 'texte', libelleType: 'Texte', icone: 'pi pi-align-left', contenu: 'Compte rendu', legende: null, fichier: null, url: null, metadata: {} }],
  rattachements: [{
    id: 5, type: 'ac', cibleId: 479, code: 'AC21.01', libelle: 'Analyser', competence, argumentaire: 'Parce que',
    autoEvaluation: { uuid: 'a1', statut: { value: 'soumise', libelle: 'Soumise', badge: 'success' }, score: 0.5 },
    retours: [], ...extra.rattachement,
  }], erreursDepot: [], ...extra.trace,
});

const routeur = (table) => (url) => {
  const [chemin] = url.split('?');
  for (const [motif, reponse] of table) {
    if (typeof motif === 'string' ? chemin === motif : motif.test(chemin)) return Promise.resolve({ data: typeof reponse === 'function' ? reponse() : reponse });
  }
  return Promise.reject({ response: { status: 404, data: { message: `Pas de jeu de données pour ${chemin}` } } });
};

// ---------- Montage ----------
const attendre = async () => {
  for (let i = 0; i < 12; i++) {
    await nextTick();
    await new Promise((r) => setTimeout(r, 0));
  }
};

const monter = async (composant, props = {}) => {
  const avertissements = [];
  const conteneur = document.createElement('div');
  document.body.appendChild(conteneur);
  const app = createApp({ render: () => h(composant, props) });
  app.config.warnHandler = (msg, _inst, trace) => avertissements.push(`${msg}\n${trace}`);
  app.config.errorHandler = (err) => avertissements.push(`ERREUR: ${err?.stack ?? err}`);
  app.use(createPinia()).use(PrimeVue).use(ToastService).use(ConfirmationService);
  app.mount(conteneur);
  await attendre();
  return { app, conteneur, avertissements, texte: () => document.body.textContent.replace(/\s+/g, ' ') };
};

const demonter = (m) => {
  m.app.unmount();
  m.conteneur.remove();
  document.body.innerHTML = '';
};

beforeEach(() => {
  vi.clearAllMocks();
  Object.assign(route, { params: {}, query: {}, meta: {} });
  for (const m of Object.values(api)) m.mockReset();
  api.get.mockImplementation(() => Promise.reject({ response: { status: 404, data: {} } }));
});

describe('rendu des écrans (aucun avertissement Vue)', () => {
  it('file d\'évaluation', async () => {
    api.get.mockImplementation(routeur([['/api/unifolio/evaluations/file', [{
      campagne: { ...campagne, enRetard: true },
      etudiants: [{ id: 1, nom: 'LAMOUREUX', prenom: 'Lauren', scolarite: 1, nbTraces: 3, aEvaluer: 2, enCours: 1, rendues: 0, aReprendre: 1, enAttente: 0, attenteAutoEvaluation: 1 }],
      totaux: { aEvaluer: 2, enCours: 1, aReprendre: 1, enAttente: 0 },
    }]]]));
    const m = await monter(EvaluationsView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('LAMOUREUX Lauren');
    expect(m.texte()).toContain('Limite d\'évaluation dépassée');
    expect(m.texte()).toContain('4 élément(s) restent à traiter');
    demonter(m);
  });

  it('file vide', async () => {
    api.get.mockImplementation(routeur([['/api/unifolio/evaluations/file', []]]));
    const m = await monter(EvaluationsView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Aucun étudiant à évaluer');
    demonter(m);
  });

  it('suivi d\'un étudiant', async () => {
    route.params = { campagne: campagne.uuid, etudiant: '1' };
    api.get.mockImplementation(routeur([[/\/api\/unifolio\/evaluations\/etudiants\//, {
      campagne, etudiant: { id: 1, nom: 'LAMOUREUX', prenom: 'Lauren' }, scolarite: { id: 1, annee: 'MMI - DWeb-DI - 2' }, disponible: true,
      granularite: { value: 'ac', libelle: 'Apprentissage critique' }, grille: { libelle: 'BUT — AC', version: 1 },
      elements: [{
        type: 'ac', id: 479, code: 'AC21.01', ordre: null, libelle: 'Analyser la stratégie', competence,
        traces: [{ uuid: 't1', titre: 'Maquette', statut: 'En évaluation', statutValue: 'en_evaluation', rattachementId: 5, autoEvaluation: { statut: { value: 'soumise' } }, evaluations: [resumeEval({ score: 0.66, statut: { value: 'rendue', libelle: 'Rendue', badge: 'success' } })] }],
        bilan: { statut: { value: 'soumise', libelle: 'Soumise', badge: 'success' } }, evaluationsBilan: [],
        synthese: { score: 0.66, nbTraces: 1, regle: 'max' },
      }],
    }]]));
    const m = await monter(EvaluationEtudiantView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('AC21.01');
    expect(m.texte()).toContain('Synthèse 66 %');
    expect(m.texte()).toContain('Mon évaluation : rendue');
    demonter(m);
  });

  it('page d\'évaluation d\'une trace : tous les points sont visibles sans ouvrir de fenêtre', async () => {
    route.params = { uuid: 't1t1t1t1-0000-4000-8000-000000000001' };
    api.get.mockImplementation(routeur([
      [/\/api\/unifolio\/evaluations\/traces\//, { trace: traceDetail(), etudiant: { id: 1, nom: 'LAMOUREUX Lauren' }, campagne, evaluations: {} }],
      [/\/api\/unifolio\/evaluations\/lot\/trace\//, lotPayload()],
      [/\/commentaires$/, []],
    ]));
    const m = await monter(EvaluationTraceView);
    expect(m.avertissements).toEqual([]);
    const t = m.texte();
    expect(t).toContain('Maquette');
    expect(t).toContain('AC21.01');
    expect(t).toContain('AC21.02');
    expect(t).toContain('2 point(s)');
    expect(t).toContain('Auto 50 %');
    expect(document.querySelectorAll('[role="radiogroup"]').length).toBeGreaterThanOrEqual(2); // une grille par point, toutes affichées
    expect(document.querySelector('[role="dialog"]')).toBeNull(); // aucun panneau ni fenêtre modale
    demonter(m);
  });

  it('lot : l\'appréciation est facultative (critère texte non obligatoire, aucun astérisque)', async () => {
    api.get.mockImplementation(routeur([[/\/lot\/trace\//, lotPayload()]]));
    const m = await monter(EvaluationLot, { portee: { type: 'trace', uuid: 'T' } });
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('(facultatif)');
    expect(m.texte()).not.toContain('Ajouter un commentaire'); // la grille a déjà un critère « Appréciation »
    demonter(m);
  });

  it('lot : sans critère texte, un commentaire facultatif est proposé', async () => {
    const grilleSansTexte = { ...grille, criteres: grille.criteres.filter((c) => c.type !== 'texte') };
    api.get.mockImplementation(routeur([[/\/lot\/trace\//, lotPayload({ grilles: { 1: grilleSansTexte } })]]));
    const m = await monter(EvaluationLot, { portee: { type: 'trace', uuid: 'T' } });
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Ajouter un commentaire');
    demonter(m);
  });

  it('lot : saisir deux points puis rendre enregistre tout, puis remet tout en une fois', async () => {
    api.get.mockImplementation(routeur([[/\/lot\/trace\//, lotPayload()]]));
    api.put.mockResolvedValue({ data: lotPayload({ items: [itemLot('r:5', 'AC21.01', { evaluation: evalLegere() }), itemLot('r:6', 'AC21.02', { evaluation: evalLegere() })] }) });
    api.post.mockResolvedValue({ data: { ...lotPayload(), nbRendues: 2 } });
    const m = await monter(EvaluationLot, { portee: { type: 'trace', uuid: 'T' } });

    const niveaux = [...document.querySelectorAll('[role="radiogroup"][aria-label="Maîtrise"] [role="radio"]')].filter((b) => b.textContent.trim() === 'Acquis');
    expect(niveaux.length).toBe(2);
    niveaux.forEach((b) => b.click());
    await attendre();
    expect(m.texte()).toContain('2 renseigné(s)');
    expect(m.texte()).toContain('2 à enregistrer');

    [...document.querySelectorAll('button')].find((b) => b.textContent.includes('Rendre')).click();
    await attendre();

    expect(api.put).toHaveBeenCalledTimes(1);
    const [url, corps] = api.put.mock.calls[0];
    expect(url).toBe('/api/unifolio/evaluations/lot/trace/T');
    expect(corps.items.map((i) => i.cle)).toEqual(['r:5', 'r:6']);
    expect(corps.items[0].reponses[0]).toMatchObject({ critere: 10, echelon: 2 });
    expect(api.post).toHaveBeenCalledWith('/api/unifolio/evaluations/lot/trace/T/rendre', { visibleEtudiant: true });
    expect(m.avertissements).toEqual([]);
    demonter(m);
  });

  it('lot : « Remplir d\'un coup » applique le même niveau à tous les points', async () => {
    api.get.mockImplementation(routeur([[/\/lot\/trace\//, lotPayload()]]));
    const m = await monter(EvaluationLot, { portee: { type: 'trace', uuid: 'T' } });

    [...document.querySelectorAll('button')].find((b) => b.textContent.includes('Remplir d\'un coup')).click();
    await attendre();
    const section = [...document.querySelectorAll('section')][0];
    [...section.querySelectorAll('button')].find((b) => b.textContent.trim() === 'Maîtrisé' || b.textContent.trim() === 'Acquis').click();
    await attendre();

    expect(m.texte()).toContain('2 renseigné(s)');
    expect(m.avertissements).toEqual([]);
    demonter(m);
  });

  it('lot : les anomalies d\'une remise « tout ou rien » sont toutes listées', async () => {
    api.get.mockImplementation(routeur([[/\/lot\/trace\//, lotPayload({ items: [itemLot('r:5', 'AC21.01', { evaluation: evalLegere({ reponses: [{ critere: 10, echelon: 2 }] }) })] })]]));
    api.post.mockRejectedValue({ response: { status: 422, data: { message: 'x', erreurs: ['AC21.01 — Répondez aux critères obligatoires : Maîtrise.', 'AC21.02 — Répondez aux critères obligatoires : Maîtrise.'] } } });
    const m = await monter(EvaluationLot, { portee: { type: 'trace', uuid: 'T' } });

    [...document.querySelectorAll('button')].find((b) => b.textContent.includes('Rendre')).click();
    await attendre();

    expect(m.texte()).toContain('AC21.01 — Répondez aux critères obligatoires');
    expect(m.texte()).toContain('AC21.02 — Répondez aux critères obligatoires');
    demonter(m);
  });

  it('lot : des points rendus se rouvrent ensemble ou un par un', async () => {
    const rendue = evalLegere({ modifiable: false, statut: { value: 'rendue', libelle: 'Rendue', badge: 'success' }, score: 0.66, visibleEtudiant: true });
    api.get.mockImplementation(routeur([[/\/lot\/trace\//, lotPayload({ items: [itemLot('r:5', 'AC21.01', { evaluation: rendue }), itemLot('r:6', 'AC21.02', { evaluation: rendue })] })]]));
    const m = await monter(EvaluationLot, { portee: { type: 'trace', uuid: 'T' } });

    expect(m.texte()).toContain('2 rendu(s)');
    expect(m.texte()).toContain('Tout rouvrir');
    expect(m.texte()).toContain('Ce point est rendu.');
    expect([...document.querySelectorAll('[role="radio"]')].every((b) => b.disabled)).toBe(true);
    expect(m.avertissements).toEqual([]);
    demonter(m);
  });

  it('lot : campagne clôturée, lecture seule', async () => {
    api.get.mockImplementation(routeur([[/\/lot\/trace\//, lotPayload({ peutEvaluer: false })]]));
    const m = await monter(EvaluationLot, { portee: { type: 'trace', uuid: 'T' } });

    expect(m.texte()).toContain('n\'accepte plus');
    expect([...document.querySelectorAll('button')].some((b) => b.textContent.includes('Rendre'))).toBe(false);
    demonter(m);
  });

  it('page des bilans de l\'année', async () => {
    route.params = { campagne: campagne.uuid, etudiant: '1' };
    api.get.mockImplementation(routeur([[/\/lot\/bilans\//, lotPayload({ mode: 'bilans', traceStatut: null, items: [itemLot('ac:479', 'AC21.01'), itemLot('ac:480', 'AC21.02'), itemLot('ac:481', 'AC21.03')] })]]));
    const m = await monter(EvaluationBilansView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Bilans de LAMOUREUX Lauren');
    expect(m.texte()).toContain('3 point(s)');
    demonter(m);
  });

  it('fil de discussion', async () => {
    api.get.mockImplementation(routeur([[/\/commentaires$/, [
      { uuid: 'c1', parent: null, rattachementId: 5, auteur: { type: 'etudiant', nom: 'LAMOUREUX Lauren', moi: true }, contenu: 'Bonjour', creeLe: '2026-11-06T10:00:00+00:00' },
      { uuid: 'c2', parent: 'c1', rattachementId: 5, auteur: { type: 'personnel', nom: 'Dupont Alice', moi: false }, contenu: 'Bonjour Lauren', creeLe: '2026-11-06T11:00:00+00:00' },
    ]]]));
    const m = await monter(CommentairesThread, { traceUuid: 't1', rattachements: traceDetail().rattachements });
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Bonjour Lauren');
    expect(m.texte()).toContain('Enseignant');
    demonter(m);
  });

  it('liste des campagnes', async () => {
    api.get.mockImplementation(routeur([
      ['/api/unifolio/admin/campagnes', [campagne]],
      ['/api/unifolio/admin/campagnes/options', { formations: [{ id: 35, libelle: 'MMI', parcours: 'DWeb-DI', departement: 'MMI', annees: [{ id: 67, libelle: 'MMI - DWeb-DI - 2', ordre: 2, anneeUniversitaire: 9 }] }], anneesUniversitaires: [{ id: 9, libelle: '2026-2027' }], anneeCourante: 9 }],
    ]));
    const m = await monter(CampagnesView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('MMI 2026-2027');
    expect(m.texte()).toContain('12 / 30 étudiants affectés');
    demonter(m);
  });

  it('édition d\'une campagne et affectations', async () => {
    route.params = { uuid: campagne.uuid };
    api.get.mockImplementation(routeur([
      [`/api/unifolio/admin/campagnes/${campagne.uuid}`, campagne],
      [`/api/unifolio/admin/campagnes/${campagne.uuid}/affectations`, {
        campagne, evaluateurs: [{ id: 7, nom: 'Dupont Alice', nbEtudiants: 1 }], groupes: [{ id: 3, libelle: 'TD1', nbEtudiants: 15 }], candidats: [{ id: 7, nom: 'Dupont Alice' }, { id: 8, nom: 'Martin Paul' }],
        etudiants: [{ id: 1, nom: 'LAMOUREUX', prenom: 'Lauren', annee: 'MMI - DWeb-DI - 2', groupes: ['TD1'], evaluateurs: [7] }, { id: 2, nom: 'DUPUIS', prenom: 'Jean', annee: 'MMI - DWeb-DI - 2', groupes: [], evaluateurs: [] }],
      }],
    ]));
    const m = await monter(CampagneEditView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('LAMOUREUX Lauren');
    expect(m.texte()).toContain('Dupont Alice');
    expect(m.texte()).toContain('Clôturer maintenant');
    demonter(m);
  });

  it('trace côté étudiant : retour de l\'enseignant et révision demandée', async () => {
    route.params = { uuid: 't1t1t1t1-0000-4000-8000-000000000001' };
    const retour = {
      uuid: 'r1', bilan: false, statut: { value: 'revision_demandee', libelle: 'Révision demandée', badge: 'warn' }, score: null, rendueLe: '2026-11-06T10:00:00+00:00',
      evaluateur: { id: 7, nom: 'Dupont Alice' }, appreciation: 'Précisez votre démarche.', reponses: [],
    };
    const retourRendu = {
      uuid: 'r2', statut: { value: 'rendue', libelle: 'Rendue', badge: 'success' }, score: 0.66, rendueLe: '2026-11-07T10:00:00+00:00', evaluateur: { id: 8, nom: 'Martin Paul' }, appreciation: 'Très bien',
      reponses: [{ critere: 10, libelle: 'Maîtrise', type: 'echelle', echelon: { libelle: 'Acquis', couleur: '#16a34a', valeur: 2 }, normalisee: 1 }],
    };
    api.get.mockImplementation(routeur([
      [/\/api\/unifolio\/traces\/[^/]+$/, traceDetail({ trace: { statut: { value: 'a_reviser', libelle: 'À réviser', badge: 'danger' }, modifiable: true }, rattachement: { retours: [retour, retourRendu] } })],
      [/\/commentaires$/, []],
    ]));
    const m = await monter(TraceDetailView);
    expect(m.avertissements).toEqual([]);
    const t = m.texte();
    expect(t).toContain('Dupont Alice vous demande de réviser cette trace');
    expect(t).toContain('Précisez votre démarche.');
    expect(t).toContain('66 %');
    expect(t).toContain('Très bien');
    demonter(m);
  });

  it('bilan de l\'étudiant avec synthèse des évaluations', async () => {
    api.get.mockImplementation(routeur([
      ['/api/unifolio/scolarites', [{ id: 1, annee: 'MMI - DWeb-DI - 2', anneeUniversitaire: '2026-2027', active: true }]],
      ['/api/unifolio/auto-evaluations/bilan', {
        scolarite: { id: 1, annee: 'MMI - DWeb-DI - 2' }, disponible: true, granularite: { value: 'ac', libelle: 'Apprentissage critique' }, grille: { libelle: 'BUT — AC', version: 1 },
        elements: [{ type: 'ac', id: 479, code: 'AC21.01', ordre: null, libelle: 'Analyser', competence, traces: [], bilan: null, evaluationsBilan: [], synthese: { score: 0.66, nbTraces: 1, regle: 'max' } }],
      }],
    ]));
    const m = await monter(AutoEvaluationView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Évalué : 66 %');
    demonter(m);
  });

  // ---------- Lot 5 : portfolio à blocs ----------
  const U = (n) => `p0p0p0p0-0000-4000-8000-00000000000${n}`;
  const visibilite = { value: 'prive', libelle: 'Privé' };
  const resumePortfolio = (extra = {}) => ({
    uuid: U(1), type: { value: 'academique', libelle: 'Académique' }, titre: 'Portfolio 2026-2027', visibilite,
    scolarite: { id: 1, annee: 'MMI - DWeb-DI - 2', anneeUniversitaire: '2026-2027', diplome: 'MMI', actif: true },
    campagne: campagne, modele: null, modifiable: true, nbPages: 2, misAJourLe: '2026-11-06T10:00:00+00:00', ...extra,
  });
  const pageBlocs = (extra = {}) => ({
    uuid: U(2), titre: 'Présentation', ordre: 0, verrouillee: false, verrouilles: ['b-titre'], version: 1, incomplets: [],
    blocs: [
      { id: 'b-titre', type: 'heading', attrs: { texte: 'Bonjour', niveau: 2 } },
      { id: 'b-para', type: 'paragraph', attrs: { texte: 'Un **texte** <script>alert(1)</script>' } },
      { id: 'b-col', type: 'columns', attrs: { ratio: '50-50' }, children: [
        { id: 'c1', type: 'column', attrs: {}, children: [{ id: 'c1p', type: 'paragraph', attrs: { texte: 'Gauche' } }] },
        { id: 'c2', type: 'column', attrs: {}, children: [{ id: 'c2p', type: 'divider', attrs: {} }] },
      ] },
      { id: 'b-prog', type: 'progression', attrs: {} },
    ], ...extra,
  });
  const donneesPortfolio = () => ({
    traces: [{ uuid: 't1', titre: 'Maquette', contexte: 'Contexte', statut: { value: 'deposee', libelle: 'Déposée' }, medias: [], rattachements: [{ competence }] }],
    progression: [{ competence, total: 4, couverts: 1 }], cv: { poste: null, description: null, langues: [], softSkills: [], hardSkills: [], reseaux: [], experiences: [], formations: [] }, parcours: [],
  });

  it('liste des portfolios', async () => {
    api.get.mockImplementation(routeur([['/api/unifolio/portfolios', [resumePortfolio(), resumePortfolio({ uuid: U(3), type: { value: 'vitrine', libelle: 'Vitrine' }, titre: 'Ma vitrine', scolarite: null, campagne: null })]]]));
    const m = await monter(PortfoliosView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Portfolio 2026-2027');
    demonter(m);
  });

  it('éditeur de portfolio : blocs, bloc imposé, aucun HTML injecté', async () => {
    route.params = { uuid: U(1) };
    api.get.mockImplementation(routeur([
      [/\/portfolios\/[^/]+\/donnees$/, donneesPortfolio()],
      [/\/portfolios\/[^/]+$/, { ...resumePortfolio(), theme: null, pages: [pageBlocs(), pageBlocs({ uuid: U(4), titre: 'Comprendre', verrouillee: true, verrouilles: [], blocs: [] })] }],
    ]));
    const m = await monter(PortfolioEditView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Présentation');
    expect(m.texte()).toContain('Comprendre');
    expect(document.body.querySelector('script')).toBeNull();
    demonter(m);
  });

  it('éditeur de portfolio : portfolio clôturé en lecture seule', async () => {
    route.params = { uuid: U(1) };
    api.get.mockImplementation(routeur([
      [/\/portfolios\/[^/]+\/donnees$/, donneesPortfolio()],
      [/\/portfolios\/[^/]+$/, { ...resumePortfolio({ modifiable: false }), theme: null, pages: [pageBlocs()] }],
    ]));
    const m = await monter(PortfolioEditView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Bonjour');
    demonter(m);
  });

  it('éditeur de portfolio : accès refusé', async () => {
    route.params = { uuid: U(1) };
    api.get.mockImplementation(() => Promise.reject({ response: { status: 403, data: {} } }));
    const m = await monter(PortfolioEditView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('ne vous appartient pas');
    demonter(m);
  });

  it('CV', async () => {
    api.get.mockImplementation(routeur([['/api/unifolio/cv', {
      poste: 'Intégrateur', description: 'Alternant', langues: ['Anglais'], softSkills: ['Rigueur'], hardSkills: ['Vue'], reseaux: ['https://example.org'],
      experiences: [{ poste: 'Stagiaire', entreprise: 'Acme', dateDebut: '2026-04-01', dateFin: null, description: 'Dev' }],
      formations: [{ diplome: 'BUT MMI', etablissement: 'IUT', dateDebut: '2025-09-01', dateFin: null, description: null }], misAJourLe: null,
    }]]));
    const m = await monter(CvView);
    expect(m.avertissements).toEqual([]);
    demonter(m);
  });

  const modele = (extra = {}) => ({
    uuid: U(5), libelle: 'Modèle MMI', description: 'Départ', portee: 'departement', libellePortee: 'MMI', actif: true, nbPages: 1, nbCampagnes: 1, modifiable: true, ...extra,
  });

  it('liste des modèles', async () => {
    api.get.mockImplementation(routeur([
      ['/api/unifolio/admin/modeles', [modele(), modele({ uuid: U(6), libelle: 'National', portee: 'national', modifiable: false })]],
      ['/api/unifolio/admin/campagnes/options', { formations: [{ id: 35, libelle: 'MMI' }] }],
    ]));
    const m = await monter(ModelesView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Modèle MMI');
    demonter(m);
  });

  it('édition d\'un modèle', async () => {
    route.params = { uuid: U(5) };
    api.get.mockImplementation(routeur([[/\/admin\/modeles\/[^/]+$/, { ...modele(), pages: [{ titre: 'Présentation', verrouillee: true, verrouilles: ['b-titre'], blocs: pageBlocs().blocs }] }]]));
    const m = await monter(ModeleEditView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Présentation');
    demonter(m);
  });

  // ---------- Lot 6 : vitrine, partage, publication, modération ----------
  const partage = (extra = {}) => ({
    visibilite: { value: 'public', libelle: 'Public' },
    visibilitesAutorisees: ['prive', 'lien_secret', 'etablissement', 'public'].map((v) => ({ value: v, libelle: v })),
    slug: 'lauren-lamoureux', indexable: true, description: 'Mon portfolio', url: 'http://localhost/api/unifolio/public/lauren-lamoureux/index.html',
    apercu: 'http://localhost/api/unifolio/portfolios/x/publication/index.html', renduDisponible: true, peutPublier: true, type: 'vitrine',
    publicationActive: { uuid: 'pub1', numero: 2, statut: { value: 'genere', libelle: 'Généré' }, active: true, moderee: false, motifModeration: null, erreur: null, pages: 3, genereLe: '2026-11-07T09:00:00+00:00', creeLe: '2026-11-07T09:00:00+00:00' },
    historique: [
      { uuid: 'pub1', numero: 2, statut: { value: 'genere', libelle: 'Généré' }, active: true, moderee: false, erreur: null, genereLe: '2026-11-07T09:00:00+00:00', creeLe: '2026-11-07T09:00:00+00:00' },
      { uuid: 'pub0', numero: 1, statut: { value: 'erreur', libelle: 'Erreur' }, active: false, moderee: false, erreur: 'boom', genereLe: null, creeLe: '2026-11-06T09:00:00+00:00' },
    ],
    ...extra,
  });

  it('dialogue de partage : publication en ligne, versions, exports', async () => {
    api.get.mockImplementation(routeur([[/\/partage$/, partage()]]));
    const m = await monter(PartageDialog, { visible: true, portfolio: { uuid: U(1), pages: [] }, modifsEnAttente: true });
    expect(m.avertissements).toEqual([]);
    const t = m.texte();
    expect(t).toContain('Qui peut voir ce portfolio');
    expect(t).toContain('Version 2 en ligne');
    expect(t).toContain('pas enregistrées');
    expect(t).toContain('Archive ZIP');
    expect((document.body.querySelector('input[aria-label="Adresse de partage"]') ?? {}).value).toContain('/public/lauren-lamoureux');
    demonter(m);
  });

  it('dialogue de partage : portfolio privé non publiable, publication modérée signalée', async () => {
    api.get.mockImplementation(routeur([[/\/partage$/, partage({ visibilite: { value: 'prive', libelle: 'Privé' }, url: null, publicationActive: { ...partage().publicationActive, moderee: true, motifModeration: 'Contenu inapproprié' } })]]));
    const m = await monter(PartageDialog, { visible: true, portfolio: { uuid: U(1), pages: [] } });
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('un portfolio privé n\'est pas publié');
    expect(m.texte()).toContain('retirée par la modération');
    expect(m.texte()).toContain('Contenu inapproprié');
    demonter(m);
  });

  it('copie vers la vitrine', async () => {
    const m = await monter(CopieVitrineDialog, { visible: true, portfolio: { uuid: U(1), pages: [{ uuid: U(2), titre: 'Présentation' }, { uuid: U(4), titre: 'Comprendre' }] } });
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Comprendre');
    demonter(m);
  });

  it('modération des publications', async () => {
    api.get.mockImplementation(routeur([['/api/unifolio/admin/publications', [
      { uuid: 'pub1', numero: 2, moderee: false, motifModeration: null, genereLe: '2026-11-07T09:00:00+00:00', url: 'http://localhost/x', portfolio: { uuid: U(1), titre: 'Ma vitrine', etudiant: 'Lauren LAMOUREUX', type: { value: 'vitrine', libelle: 'Vitrine' }, visibilite: { value: 'public', libelle: 'Public' } } },
      { uuid: 'pub2', numero: 1, moderee: true, motifModeration: 'Hors sujet', genereLe: null, url: null, portfolio: { uuid: U(3), titre: 'Autre', etudiant: 'Paul MARTIN', type: { value: 'academique', libelle: 'Académique' }, visibilite: { value: 'etablissement', libelle: 'Établissement' } } },
    ]]]));
    const m = await monter(ModerationView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Lauren LAMOUREUX');
    expect(m.texte()).toContain('Retirée : Hors sujet');
    demonter(m);
  });

  it('liste des portfolios : proposition de créer la vitrine', async () => {
    api.get.mockImplementation(routeur([['/api/unifolio/portfolios', [resumePortfolio()]]]));
    const m = await monter(PortfoliosView);
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Créer ma vitrine');
    demonter(m);
    api.get.mockImplementation(routeur([['/api/unifolio/portfolios', [resumePortfolio(), resumePortfolio({ uuid: U(3), type: { value: 'vitrine', libelle: 'Vitrine' }, scolarite: null, campagne: null })]]]));
    const m2 = await monter(PortfoliosView);
    expect(m2.texte()).not.toContain('Créer ma vitrine');
    demonter(m2);
  });

  it('apparence : réglages, aperçu, logo, couleur trop claire refusée', async () => {
    const portfolioAvecLogo = { uuid: U(1), apparence: { couleur: '#7c3aed', police: 'classique', entete: 'couleur', largeur: 'large', coins: 'droits' }, logo: { nom: 'logo.png' } };
    const m = await monter(ApparenceDialog, { visible: true, portfolio: portfolioAvecLogo });
    expect(m.avertissements).toEqual([]);
    expect(m.texte()).toContain('Couleur d\'accent');
    expect(m.texte()).toContain('Classique');
    expect(m.texte()).toContain('Retirer');
    expect(m.texte()).not.toContain('trop claire');
    expect(document.body.querySelector('img[alt^="Logo"]')?.getAttribute('src')).toContain('/portfolios/');
    demonter(m);

    const clair = await monter(ApparenceDialog, { visible: true, portfolio: { uuid: U(1), apparence: { couleur: '#ffff99', police: 'systeme', entete: 'clair', largeur: 'standard', coins: 'arrondis' }, logo: null } });
    expect(clair.avertissements).toEqual([]);
    expect(clair.texte()).toContain('trop claire');
    expect(clair.texte()).toContain('Aucun logo');
    demonter(clair);
  });
});
