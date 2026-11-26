// @vitest-environment jsdom
// Page d'aide « Comprendre mes accès » : affectations de la personne et catalogue des rôles.
import { describe, it, expect, vi, afterEach } from 'vitest';
import { createApp, defineComponent, h, nextTick, reactive } from 'vue';
import PrimeVue from 'primevue/config';

const store = reactive({
    isLoaded: true,
    userType: 'personnels',
    departementDefaut: { id: 1 },
    departements: [
        { id: 1, libelle: 'MMI', departementPersonnel: { permissions: ['ROLE_FINANCE_MANAGER', 'ROLE_ADMIN'], packages: ['intranet', 'finance'] } },
        { id: 2, libelle: 'GMP', departementPersonnel: { permissions: [], packages: ['intranet'] } },
    ],
    getUser: vi.fn(),
});
vi.mock('@stores', () => ({ useUsersStore: () => store }));
vi.mock('@components', async () => ({
    HeaderComponent: defineComponent({ props: ['titre'], setup: (p) => () => h('h1', p.titre) }),
    ApplicationBadge: defineComponent({ props: ['app'], setup: (p) => () => h('span', { class: 'badge-app' }, p.app) }),
    Card: (await import('@components/components/Card.vue')).default,
    EmptyState: (await import('@components/components/EmptyState.vue')).default,
    Kpi: (await import('@components/Kpi.vue')).default,
}));

import AideAccesView from '../views/AideAccesView.vue';
import { ROLES, GROUPES, filtrerRoles, libelleRole } from '@utils/aideAcces';

let montages = [];
const monter = async () => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp(AideAccesView);
    app.use(PrimeVue);
    app.mount(el);
    montages.push({ app, el });
    await nextTick();
    return el;
};
afterEach(() => { montages.forEach(({ app, el }) => { app.unmount(); el.remove(); }); montages = []; store.userType = 'personnels'; });

describe('catalogue', () => {
    it('chaque rôle appartient à un groupe connu et a un libellé', () => {
        const groupes = new Set(GROUPES.map(g => g.id));
        for (const r of ROLES) {
            expect(groupes.has(r.groupe), r.role).toBe(true);
            expect(r.libelle).toBeTruthy();
            expect(r.permet).toBeTruthy();
        }
        expect(new Set(ROLES.map(r => r.role)).size).toBe(ROLES.length);
    });
    it('couvre les rôles des modules', () => {
        const roles = ROLES.map(r => r.role);
        for (const role of ['ROLE_FINANCE_MANAGER', 'ROLE_FINANCE_VALIDEUR', 'ROLE_FINANCE_DEMANDEUR', 'ROLE_FINANCE_VIEW', 'ROLE_STAGE_MANAGER', 'ROLE_HELPDESK_ADMIN', 'ROLE_QUESTIONNAIRE_MANAGER', 'ROLE_PORTFOLIO_CORRECTOR', 'SUPER_ADMIN']) {
            expect(roles).toContain(role);
        }
    });
    it('filtre et traduit', () => {
        expect(filtrerRoles('financier').some(r => r.role === 'ROLE_FINANCE_MANAGER')).toBe(true);
        expect(filtrerRoles('zzzz')).toEqual([]);
        expect(libelleRole('ROLE_ADMIN')).toBe('Administrateur');
        expect(libelleRole('ROLE_INCONNU')).toBe('ROLE_INCONNU');
    });
});

describe('page', () => {
    it('affiche les affectations avec rôles lisibles et le département actif', async () => {
        const el = await monter();
        const lignes = [...el.querySelectorAll('[data-test="affectation"]')];
        expect(lignes).toHaveLength(2);
        expect(lignes[0].textContent).toContain('MMI');
        expect(lignes[0].textContent).toContain('Département actif');
        expect(lignes[0].textContent).toContain('Gestionnaire (service financier)');
        expect(lignes[1].textContent).toContain('aucun rôle particulier');
    });
    it('liste les rôles par groupe et le tableau finances', async () => {
        const el = await monter();
        expect(el.querySelectorAll('[data-test="groupe"]').length).toBe(GROUPES.length);
        expect(el.textContent).toContain('qui fait quoi');
    });
    it('filtre par recherche', async () => {
        const el = await monter();
        const champ = el.querySelector('#catalogue-recherche');
        champ.value = 'portfolio';
        champ.dispatchEvent(new Event('input'));
        await nextTick();
        const noms = [...el.querySelectorAll('[data-test="role"]')].map(l => l.textContent);
        expect(noms.length).toBeGreaterThan(0);
        expect(noms.every(t => /portfolio/i.test(t))).toBe(true);
    });
    it('un étudiant ne voit pas d\'affectations', async () => {
        store.userType = 'etudiants';
        const el = await monter();
        expect(el.querySelectorAll('[data-test="affectation"]')).toHaveLength(0);
        expect(el.textContent).toContain('compte étudiant');
    });
});

import AideAccesGlobaleView from '../views/configuration/AideAccesGlobaleView.vue';
import { MATRICE, MATRICE_COLONNES, GROUPES as G } from '@utils/aideAcces';

describe('vue globale (super administrateur)', () => {
    it('affiche catalogue, matrice, réservé et vigilance', async () => {
        const el = document.createElement('div');
        document.body.appendChild(el);
        const app = createApp(AideAccesGlobaleView);
        app.use(PrimeVue);
        app.mount(el);
        await nextTick();
        expect(el.querySelectorAll('[data-test="groupe"]').length).toBe(G.length);
        expect(el.querySelectorAll('[data-test="matrice"] tbody tr').length).toBe(MATRICE.length);
        expect(el.querySelectorAll('[data-test="reserve"] li').length).toBeGreaterThan(0);
        expect(el.querySelectorAll('[data-test="vigilance"] li').length).toBeGreaterThan(0);
        app.unmount(); el.remove();
    });
    it('chaque ligne de la matrice a une valeur par colonne', () => {
        for (const l of MATRICE) expect(l.valeurs, l.domaine).toHaveLength(MATRICE_COLONNES.length);
    });
});
