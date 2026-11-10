// @vitest-environment jsdom
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createApp, nextTick } from 'vue';
import PrimeVue from 'primevue/config';

const api = vi.hoisted(() => ({ get: vi.fn(), put: vi.fn() }));
vi.mock('@helpers/axios', () => ({ default: api }));
vi.mock('@helpers/toast', () => ({ showSuccess: vi.fn(), showDanger: vi.fn() }));
vi.mock('../Header.vue', () => ({ default: { props: ['titre'], template: '<h1>{{ titre }}</h1>' } }));
vi.mock('../components/EmptyState.vue', () => ({ default: { props: ['title', 'description'], template: '<div class="empty">{{ title }} {{ description }}</div>' } }));
globalThis.ResizeObserver ??= class { observe() {} unobserve() {} disconnect() {} };
window.matchMedia ??= (q) => ({ matches: false, media: q, addEventListener() {}, removeEventListener() {}, addListener() {}, removeListener() {}, dispatchEvent: () => false });

import NotificationPreferences from './NotificationPreferences.vue';
import { showDanger } from '@helpers/toast';

const attendre = async () => { for (let i = 0; i < 10; i++) { await nextTick(); await new Promise((r) => setTimeout(r, 0)); } };
const modes = [{ value: 'immediat', libelle: 'Tout de suite' }, { value: 'resume', libelle: 'Dans le résumé' }, { value: 'aucun', libelle: 'Jamais' }];
const type = (cle, source, libelle, mode, extra = {}) => ({ cle, source: { cle: source, libelle: source === 'unifolio' ? 'UniFolio' : 'Stages' }, libelle, description: `Desc ${libelle}`, mode, defaut: mode, modes, ...extra });
const prefs = (extra = {}) => ({
  actif: true, frequence: { value: 'quotidien', libelle: 'Chaque soir' }, frequences: [{ value: 'quotidien', libelle: 'Chaque soir' }, { value: 'hebdomadaire', libelle: 'Chaque lundi matin' }],
  types: [type('unifolio.commentaire', 'unifolio', 'Un enseignant commente', 'resume'), type('unifolio.evaluation', 'unifolio', 'Une évaluation m\'est communiquée', 'immediat'), type('stage.convention', 'stage', 'Une convention change', 'immediat')],
  enAttente: 2, dernierResume: '2026-11-06T18:00:00+00:00', ...extra,
});

const monter = async () => {
  const avertissements = [];
  const conteneur = document.createElement('div');
  document.body.appendChild(conteneur);
  const app = createApp(NotificationPreferences);
  app.config.warnHandler = (m) => avertissements.push(m);
  app.use(PrimeVue);
  app.mount(conteneur);
  await attendre();

  return { app, avertissements };
};

beforeEach(() => {
  vi.clearAllMocks();
  document.body.innerHTML = '';
});

describe('NotificationPreferences', () => {
  it('regroupe les types par application', async () => {
    api.get.mockResolvedValue({ data: prefs() });
    const m = await monter();
    expect(m.avertissements).toEqual([]);
    const t = document.body.textContent;
    expect(t).toContain('UniFolio');
    expect(t).toContain('Stages');
    expect(t).toContain('Un enseignant commente');
    expect(t).toContain('2 événement(s) en attente');
    expect(document.body.querySelectorAll('section h3').length).toBe(2);
    m.app.unmount();
  });

  it('envoie le changement de mode puis reflète la réponse', async () => {
    api.get.mockResolvedValue({ data: prefs() });
    api.put.mockResolvedValue({ data: prefs({ types: prefs().types.map((x) => (x.cle === 'unifolio.commentaire' ? { ...x, mode: 'aucun' } : x)) }) });
    const m = await monter();
    [...document.body.querySelectorAll('[role="group"][aria-label="Un enseignant commente"] .p-togglebutton')].find((b) => b.textContent.includes('Jamais')).click();
    await attendre();
    expect(api.put).toHaveBeenCalledWith('/api/notifications/preferences', { reglages: { 'unifolio.commentaire': 'aucun' } });
    m.app.unmount();
  });

  it('interrupteur général coupé : réglages grisés et avertissement', async () => {
    api.get.mockResolvedValue({ data: prefs({ actif: false }) });
    const m = await monter();
    expect(document.body.textContent).toContain('désactivées');
    expect(document.body.querySelectorAll('.p-togglebutton[data-p-disabled="true"]').length).toBeGreaterThan(0);
    m.app.unmount();
  });

  it('un refus du serveur est signalé et l\'état réel est rechargé', async () => {
    api.get.mockResolvedValue({ data: prefs() });
    api.put.mockRejectedValue({ response: { data: { erreurs: ['Mode impossible.'] } } });
    const m = await monter();
    [...document.body.querySelectorAll('[role="group"][aria-label="Une évaluation m\'est communiquée"] .p-togglebutton')].find((b) => b.textContent.includes('Jamais')).click();
    await attendre();
    expect(showDanger).toHaveBeenCalledWith('Mode impossible.', 'Enregistrement impossible', 6000);
    expect(api.get).toHaveBeenCalledTimes(2);
    m.app.unmount();
  });

  it('aucune application ne notifie', async () => {
    api.get.mockResolvedValue({ data: prefs({ types: [] }) });
    const m = await monter();
    expect(document.body.textContent).toContain('Rien à régler');
    m.app.unmount();
  });
});
