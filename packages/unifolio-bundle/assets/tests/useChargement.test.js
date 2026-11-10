// @vitest-environment jsdom
import { describe, it, expect } from 'vitest';
import { createApp, defineComponent, h, nextTick } from 'vue';
import { useChargement } from '../composables/useChargement';

const monter = async (charger, options) => {
  let etat;
  const app = createApp(defineComponent({ setup() { etat = useChargement(charger, options); return () => h('div'); } }));
  app.mount(document.createElement('div'));
  for (let i = 0; i < 5; i++) await nextTick();

  return etat;
};

describe('useChargement', () => {
  it('charge au montage et expose les données', async () => {
    const etat = await monter(() => Promise.resolve({ data: [1, 2] }), { initial: [] });
    expect(etat.donnees.value).toEqual([1, 2]);
    expect(etat.chargement.value).toBe(false);
    expect(etat.erreur.value).toBeNull();
  });

  it('valeur initiale avant la réponse', async () => {
    let fin;
    const etat = await monter(() => new Promise((r) => { fin = r; }), { initial: [] });
    expect(etat.donnees.value).toEqual([]);
    expect(etat.chargement.value).toBe(true);
    fin({ data: ['x'] });
    await Promise.resolve(); await nextTick();
    expect(etat.donnees.value).toEqual(['x']);
  });

  it('message de l\'API sinon message par défaut', async () => {
    const avecMessage = await monter(() => Promise.reject({ response: { data: { message: 'Refusé' } } }), { messageParDefaut: 'Défaut' });
    expect(avecMessage.erreur.value).toBe('Refusé');
    expect(avecMessage.chargement.value).toBe(false);
    const sans = await monter(() => Promise.reject(new Error('réseau')), { messageParDefaut: 'Défaut' });
    expect(sans.erreur.value).toBe('Défaut');
  });

  it('plusieurs erreurs de validation sont réunies', async () => {
    const etat = await monter(() => Promise.reject({ response: { data: { erreurs: ['A.', 'B.'] } } }));
    expect(etat.erreur.value).toBe('A. B.');
  });

  it('traitement personnalisé de la réponse et rechargement', async () => {
    let n = 0; const vus = [];
    const etat = await monter(() => Promise.resolve({ data: ++n }), { apres: (d) => vus.push(d) });
    await etat.recharger();
    expect(vus).toEqual([1, 2]);
    expect(etat.donnees.value).toBeNull();
  });

  it('immediat: false ne charge qu\'à la demande', async () => {
    let appels = 0;
    const etat = await monter(() => { appels++; return Promise.resolve({ data: 1 }); }, { immediat: false });
    expect(appels).toBe(0);
    await etat.recharger();
    expect(appels).toBe(1);
  });
});
