// @vitest-environment jsdom
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createApp, nextTick } from 'vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';

const api = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn() }));
vi.mock('@helpers/axios', () => ({ default: api }));
const router = { push: vi.fn() };
vi.mock('vue-router', () => ({ useRouter: () => router }));
globalThis.ResizeObserver ??= class { observe() {} unobserve() {} disconnect() {} };
window.matchMedia ??= (q) => ({ matches: false, media: q, addEventListener() {}, removeEventListener() {}, addListener() {}, removeListener() {}, dispatchEvent: () => false });

import NotificationBell from './NotificationBell.vue';

const attendre = async () => { for (let i = 0; i < 10; i++) { await nextTick(); await new Promise((r) => setTimeout(r, 0)); } };
const notifs = [
  { uuid: 'a', source: { cle: 'unifolio', libelle: 'UniFolio' }, type: 'unifolio.commentaire_enseignant', texte: 'Alice a commenté votre trace « X ».', url: '/app/unifolio/traces/1', nombre: 3, lue: false, date: '2026-11-07T10:00:00+00:00' },
  { uuid: 'b', source: { cle: 'stage', libelle: 'Stages' }, type: 'stage.convention', texte: 'Convention validée.', url: 'https://exemple.org', nombre: 1, lue: true, date: '2026-11-06T09:00:00+00:00' },
];

const monter = async () => {
  const avertissements = [];
  const conteneur = document.createElement('div');
  document.body.appendChild(conteneur);
  const app = createApp(NotificationBell);
  app.config.warnHandler = (m) => avertissements.push(m);
  app.use(createPinia()).use(PrimeVue);
  app.mount(conteneur);
  await attendre();

  return { app, conteneur, avertissements };
};

beforeEach(() => {
  vi.clearAllMocks();
  document.body.innerHTML = '';
  api.get.mockImplementation((url) => Promise.resolve({ data: url.endsWith('/compteur') ? { nonLues: 2 } : { notifications: notifs, nonLues: 2 } }));
  api.post.mockResolvedValue({ data: { nonLues: 1 } });
});

describe('NotificationBell', () => {
  it('affiche la pastille de non-lues', async () => {
    const m = await monter();
    expect(m.avertissements).toEqual([]);
    expect(document.body.textContent).toContain('2');
    expect(document.body.querySelector('[aria-label="2 notification(s) non lue(s)"]')).not.toBeNull();
    m.app.unmount();
  });

  it('ouvre le panneau, ouvre une notification interne et la marque lue', async () => {
    const m = await monter();
    document.body.querySelector('button[aria-label="Notifications"]').click();
    await attendre();
    expect(document.body.textContent).toContain('Alice a commenté votre trace');
    expect(document.body.textContent).toContain('(×3)');
    expect(document.body.textContent).toContain('Stages');
    [...document.body.querySelectorAll('li button')].find((b) => b.textContent.includes('Alice')).click();
    await attendre();
    expect(api.post).toHaveBeenCalledWith('/api/notifications/a/lue');
    expect(router.push).toHaveBeenCalledWith('/unifolio/traces/1');
    m.app.unmount();
  });

  it('n\'ouvre jamais une adresse externe', async () => {
    const m = await monter();
    document.body.querySelector('button[aria-label="Notifications"]').click();
    await attendre();
    [...document.body.querySelectorAll('li button')].find((b) => b.textContent.includes('Convention')).click();
    await attendre();
    expect(router.push).not.toHaveBeenCalled();
    m.app.unmount();
  });

  it('propose de tout marquer comme lu et mène aux préférences', async () => {
    const m = await monter();
    document.body.querySelector('button[aria-label="Notifications"]').click();
    await attendre();
    [...document.body.querySelectorAll('button')].find((b) => b.textContent.includes('Tout marquer')).click();
    await attendre();
    expect(api.post).toHaveBeenCalledWith('/api/notifications/lues');
    document.body.querySelector('button[aria-label="Notifications"]').click();
    await attendre();
    [...document.body.querySelectorAll('button')].find((b) => b.textContent.includes('Préférences de notification'))?.click();
    await attendre();
    expect(router.push).toHaveBeenCalledWith('/auth/notifications');
    m.app.unmount();
  });
});
