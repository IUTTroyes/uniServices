// @vitest-environment jsdom
// Configuration > Services de l'établissement : liste, création, modification, suppression (API simulée).
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createApp, defineComponent, h, nextTick } from 'vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import Button from 'primevue/button';

vi.mock('@components', async () => ({
    EmptyState: (await import('@components/components/EmptyState.vue')).default,
    ListSkeleton: defineComponent({ setup: () => () => h('div', { class: 'squelette' }) }),
    ErrorState: defineComponent({ props: ['message'], setup: (p) => () => h('div', { class: 'erreur' }, p.message) }),
}));
vi.mock('primevue/useconfirm', () => ({ useConfirm: () => ({ require: (options) => options.accept() }) }));

const requetes = vi.hoisted(() => ({
    getServicesListeService: vi.fn(),
    createServiceService: vi.fn(),
    updateServiceService: vi.fn(),
    deleteServiceService: vi.fn(),
}));
vi.mock('@requests', () => requetes);

import ServicesView from '../views/configuration/ServicesView.vue';

globalThis.matchMedia ??= (query) => ({ matches: false, media: query, addEventListener() {}, removeEventListener() {}, addListener() {}, removeListener() {} });

let avertissements = [];
let montages = [];
const attendre = async () => { for (let i = 0; i < 6; i++) { await nextTick(); await new Promise(r => setTimeout(r, 0)); } };
const monter = async () => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp(ServicesView);
    app.use(PrimeVue).use(ToastService).directive('tooltip', Tooltip).component('Button', Button);
    app.config.warnHandler = (msg) => avertissements.push(msg);
    app.mount(el);
    montages.push({ app, el });
    await attendre();
    return el;
};
const bouton = (racine, texte) => [...racine.querySelectorAll('button')].find(b => (b.textContent + (b.getAttribute('aria-label') || '')).includes(texte));
const saisir = async (champ, valeur) => { champ.value = valeur; champ.dispatchEvent(new Event('input')); await attendre(); };

beforeEach(() => {
    avertissements = [];
    Object.values(requetes).forEach(f => f.mockReset());
    requetes.getServicesListeService.mockResolvedValue([{ id: 1, libelle: 'Informatique' }, { id: 2, libelle: 'Patrimoine' }]);
});
afterEach(() => {
    montages.forEach(({ app, el }) => { app.unmount(); el.remove(); });
    montages = [];
    document.body.innerHTML = '';
    expect(avertissements, avertissements.join('\n')).toEqual([]);
});

describe('ServicesView', () => {
    it('liste les services et en crée un', async () => {
        requetes.createServiceService.mockResolvedValue({ id: 3, libelle: 'Scolarité' });
        const el = await monter();
        expect(el.textContent).toContain('Informatique');
        expect(el.textContent).toContain('Patrimoine');

        bouton(el, 'Ajouter').click();
        await attendre();
        await saisir(document.querySelector('#service-libelle'), '  Scolarité ');
        bouton(document.body, 'Créer').click();
        await attendre();
        expect(requetes.createServiceService).toHaveBeenCalledWith('Scolarité');
        expect(requetes.getServicesListeService).toHaveBeenCalledTimes(2);
    });

    it('affiche le refus du serveur (doublon) sans fermer la fenêtre', async () => {
        requetes.createServiceService.mockRejectedValue({ response: { data: { violations: [{ message: 'Ce service existe déjà.' }] } } });
        const el = await monter();
        bouton(el, 'Ajouter').click();
        await attendre();
        await saisir(document.querySelector('#service-libelle'), 'Informatique');
        bouton(document.body, 'Créer').click();
        await attendre();
        expect(document.body.textContent).toContain('Ce service existe déjà.');
        expect(document.querySelector('#service-libelle')).not.toBeNull();
    });

    it('modifie puis supprime un service', async () => {
        requetes.updateServiceService.mockResolvedValue({});
        requetes.deleteServiceService.mockResolvedValue({});
        const el = await monter();

        bouton(el, 'Modifier').click();
        await attendre();
        const champ = document.querySelector('#service-libelle');
        expect(champ.value).toBe('Informatique');
        await saisir(champ, 'Informatique & réseaux');
        bouton(document.body, 'Enregistrer').click();
        await attendre();
        expect(requetes.updateServiceService).toHaveBeenCalledWith(1, 'Informatique & réseaux');

        bouton(el, 'Supprimer').click();
        await attendre();
        expect(requetes.deleteServiceService).toHaveBeenCalledWith(1);
    });
});
