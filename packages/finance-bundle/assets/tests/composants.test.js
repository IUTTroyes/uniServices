// @vitest-environment jsdom
// Rendu des composants du package Finances avec une API simulée ; tout avertissement Vue fait échouer le test.
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createApp, defineComponent, h, nextTick, reactive } from 'vue';
import PrimeVue from 'primevue/config';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import Skeleton from 'primevue/skeleton';

const route = { params: { id: '42' }, query: {}, meta: {} };
const router = { push: vi.fn(), replace: vi.fn() };
vi.mock('vue-router', () => ({ useRoute: () => route, useRouter: () => router }));

vi.mock('@components', async () => ({
    Card: (await import('@components/components/Card.vue')).default,
    Kpi: defineComponent({ props: ['label', 'value'], setup: (p) => () => h('div', { class: 'kpi' }, `${p.label}: ${p.value}`) }),
    ActionButtonVertical: defineComponent({ props: ['label'], emits: ['click'], setup: (p, { emit }) => () => h('button', { onClick: () => emit('click') }, p.label) }),
    EmptyState: (await import('@components/components/EmptyState.vue')).default,
    ListSkeleton: (await import('@components/loader/ListSkeleton.vue')).default,
    ErrorState: defineComponent({ props: ['message'], setup: (p) => () => h('div', { class: 'erreur' }, p.message) }),
    ErrorView: defineComponent({ props: ['message'], setup: (p) => () => h('div', { class: 'erreur' }, p.message) }),
    HeaderComponent: defineComponent({ props: ['titre', 'description', 'icon', 'color', 'showBack', 'backUrl'], setup: (p, { slots }) => () => h('header', [h('h1', p.titre), slots.actions?.()]) }),
}));
vi.mock('@stores', () => ({ useUsersStore: () => ({ user: { id: 5 }, departements: [], departementDefaut: { id: 1 } }) }));
vi.mock('@utils/permissions', () => ({ hasPermission: () => true }));
vi.mock('@requests', () => ({ getAllDepartementsService: vi.fn(async () => ({ data: [] })) }));

// Confirmation PrimeVue (ConfirmDialog global du shell) : acceptée d'office dans les tests.
vi.mock('primevue/useconfirm', () => ({ useConfirm: () => ({ require: (options) => options.accept() }) }));

const api = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn(), delete: vi.fn() }));
vi.mock('@helpers/axios', () => ({ default: api }));

import EtapeVerification from '../components/demande/EtapeVerification.vue';
import EtapeDemande from '../components/demande/EtapeDemande.vue';
import BonCommandeWorkflowTimeline from '../components/BonCommandeWorkflowTimeline.vue';
import DemandeAchatDetailView from '../views/DemandeAchatDetailView.vue';
import StatutTag from '../components/StatutTag.vue';
import { estFinalisee } from '../utils/finance';
import FinanceDashboardView from '../views/FinanceDashboardView.vue';
import FinanceConfigurationView from '../views/Configuration/FinanceConfigurationView.vue';
import EtapeServiceFait from '../components/demande/EtapeServiceFait.vue';
import EtapeFacture from '../components/demande/EtapeFacture.vue';
import ActionWorkflowDialog from '../components/ActionWorkflowDialog.vue';

// jsdom ne fournit pas ResizeObserver (onglets PrimeVue) ni matchMedia (tableaux paginés).
globalThis.matchMedia ??= (query) => ({ matches: false, media: query, addEventListener() {}, removeEventListener() {}, addListener() {}, removeListener() {} });
globalThis.ResizeObserver ??= class { observe() {} unobserve() {} disconnect() {} };

let avertissements = [];
let montages = [];

const monter = async (composant, props = {}, ecouteurs = {}) => {
    const el = document.createElement('div');
    document.body.appendChild(el);
    const app = createApp(defineComponent({ setup: () => () => h(composant, { ...props, ...ecouteurs }) }));
    app.use(PrimeVue).use(ToastService).directive('tooltip', Tooltip).component('Skeleton', Skeleton);
    app.config.warnHandler = (msg) => avertissements.push(msg);
    app.mount(el);
    montages.push({ app, el });
    await attendre();
    return el;
};
const attendre = async () => { for (let i = 0; i < 6; i++) { await nextTick(); await new Promise(r => setTimeout(r, 0)); } };
const bouton = (el, texte) => [...el.querySelectorAll('button')].find(b => b.textContent.includes(texte));
const saisir = async (champ, valeur) => { champ.value = valeur; champ.dispatchEvent(new Event('input')); await attendre(); };

const TAUX = [
    { id: 1, taux: '20.00', libelle: 'Taux normal (20 %)', actif: true, parDefaut: true },
    { id: 2, taux: '5.50', libelle: 'Taux réduit (5,5 %)', actif: true, parDefaut: false },
    { id: 3, taux: '19.60', libelle: 'Ancien taux (19,6 %)', actif: false, parDefaut: false },
];

const commande = (surcharge = {}) => ({
    id: 42, statut: 'SOUMIS', objetDemande: 'Vidéoprojecteur', prestation: 'Fournitures', montantHt: '1000.00', montantTtc: '1200.00', tauxTva: '20.00',
    departement: { id: 1, libelle: 'MMI' }, demandeur: { id: 5, prenom: 'Alice', nom: 'DUPONT' }, receptions: [], pdfSigne: false,
    droits: { actions: [], champs: [], supprimer: false, deposerDocument: false, gererReceptions: false },
    ...surcharge,
});

beforeEach(() => {
    avertissements = [];
    Object.values(api).forEach(f => f.mockReset());
    router.push.mockReset();
    router.replace.mockReset();
});
afterEach(() => {
    montages.forEach(({ app, el }) => { app.unmount(); el.remove(); });
    montages = [];
    expect(avertissements, avertissements.join('\n')).toEqual([]);
});

describe('EtapeDemande : montants HT / TTC', () => {
    const monterEtape = async (form) => monter(EtapeDemande, {
        form, nouvelle: true, modifiable: () => true, tauxTva: TAUX, departements: [{ id: 1, libelle: 'MMI' }],
    });

    const vierge = () => reactive({ prestation: 'Fournitures', objetDemande: '', montantHt: null, montantTtc: null, tauxTva: '20.00', departement: 1, fournisseur: null, fournisseurPropose: null });

    it('calcule le TTC depuis le HT', async () => {
        const form = vierge();
        const el = await monterEtape(form);
        await saisir(el.querySelector('#demande-ht'), '100');
        expect(form.montantTtc).toBe(120);
    });

    it('calcule le HT depuis le TTC', async () => {
        const form = vierge();
        const el = await monterEtape(form);
        await saisir(el.querySelector('#demande-ttc'), '211');
        expect(form.montantHt).toBeCloseTo(175.83, 2);
    });

    it('ne réécrit pas un montant saisi et signale l\'écart', async () => {
        const form = reactive({ prestation: 'Fournitures', objetDemande: '', montantHt: null, montantTtc: null, tauxTva: '20.00', departement: 1, fournisseur: null, fournisseurPropose: null });
        const el = await monterEtape(form);
        await saisir(el.querySelector('#demande-ht'), '100');
        await saisir(el.querySelector('#demande-ttc'), '119');
        await saisir(el.querySelector('#demande-ht'), '200');
        expect(form.montantTtc).toBe('119');
        expect(el.textContent).toContain('ne correspond pas');
        bouton(el, 'Recalculer le TTC').click();
        await attendre();
        expect(form.montantTtc).toBe(240);
    });

    it('ne propose pas les taux désactivés', async () => {
        const form = reactive({ prestation: 'Fournitures', objetDemande: '', montantHt: null, montantTtc: null, tauxTva: '20.00', departement: 1, fournisseur: null, fournisseurPropose: null });
        const el = await monterEtape(form);
        const options = [...el.querySelectorAll('#demande-taux option')].map(o => o.textContent);
        expect(options).toContain('Taux réduit (5,5 %)');
        expect(options).not.toContain('Ancien taux (19,6 %)');
    });
});

describe('Frise des étapes', () => {
    it('suit le statut', async () => {
        const el = await monter(BonCommandeWorkflowTimeline, { commande: commande({ statut: 'SERVICE_FAIT_CERTIFIE', dateDemande: '2026-10-01' }) });
        expect(el.querySelectorAll('.pi-check').length).toBe(4);
        expect(el.querySelector('[aria-current="step"]').textContent).toContain('Facture');
    });
});

describe('DemandeAchatDetailView', () => {
    const repondre = (c) => {
        api.get.mockImplementation(async (url) => {
            if (url === '/api/finance_taux_tvas') return { data: { member: TAUX, totalItems: TAUX.length } };
            if (url === '/api/finance_bon_commandes/42') return { data: c };
            throw new Error('URL inattendue ' + url);
        });
    };

    it('affiche les seules actions fournies par le serveur et envoie la transition après confirmation', async () => {
        repondre(commande({
            droits: { actions: [{ action: 'avis_favorable', libelle: 'Avis favorable', severite: 'primary', commentaire: false, confirmer: true }], champs: [], supprimer: false, deposerDocument: false, gererReceptions: false },
        }));
        api.post.mockResolvedValue({ data: { statut: 'VERIFICATION_FINANCIERE', statutLibelle: 'Vérification financière' } });
        const el = await monter(DemandeAchatDetailView);

        expect(bouton(el, 'Avis favorable')).toBeTruthy();
        expect(bouton(el, 'Enregistrer')).toBeFalsy();
        expect(el.querySelector('#demande-objet').disabled).toBe(true);

        bouton(el, 'Avis favorable').click();
        await attendre();
        expect(api.post).toHaveBeenCalledWith('/api/finance_bon_commandes/42/transitions/avis_favorable', {});
        expect(api.patch).not.toHaveBeenCalled();
    });

    it('n\'envoie que les champs permis et modifiés', async () => {
        repondre(commande({
            statut: 'BROUILLON',
            droits: { actions: [], champs: ['objetDemande', 'montantHt', 'montantTtc', 'tauxTva', 'noteExplicative', 'prestation', 'centreFinancier', 'fournisseur', 'fournisseurPropose'], supprimer: true, deposerDocument: true, gererReceptions: false },
        }));
        api.patch.mockResolvedValue({ data: {} });
        const el = await monter(DemandeAchatDetailView);

        const objet = el.querySelector('#demande-objet');
        objet.value = 'Deux vidéoprojecteurs';
        objet.dispatchEvent(new Event('input'));
        await attendre();
        bouton(el, 'Enregistrer').click();
        await attendre();
        expect(api.patch).toHaveBeenCalledWith('/api/finance_bon_commandes/42', { objetDemande: 'Deux vidéoprojecteurs' }, expect.anything());
    });
});

describe('EtapeDemande : PDF à faire signer', () => {
    const monterSoumise = (surcharge, ecouteurs = {}) => monter(EtapeDemande, {
        form: reactive({ prestation: 'Fournitures', objetDemande: 'x', montantHt: '100', montantTtc: '120', tauxTva: '20.00', departement: 1, service: null }),
        commande: commande({ statut: 'SOUMIS', ...surcharge }), modifiable: () => false, tauxTva: TAUX, peutDeposer: true,
    }, ecouteurs);

    it('met en avant le PDF à signer tant que la demande soumise n\'a pas de document signé', async () => {
        const telecharger = vi.fn();
        const el = await monterSoumise({ pdfSigne: false }, { onTelechargerPdf: telecharger });
        expect(el.querySelector('#pdf-a-signer-titre')?.textContent).toContain('faire signer la demande');
        bouton(el, 'Télécharger le PDF à signer').click();
        expect(telecharger).toHaveBeenCalledWith(1);
        expect(bouton(el, 'Déposer le PDF signé')).toBeTruthy();
    });

    it('disparaît une fois le document déposé, ou en brouillon', async () => {
        expect((await monterSoumise({ pdfSigne: true })).querySelector('#pdf-a-signer-titre')).toBeNull();
        expect((await monterSoumise({ statut: 'BROUILLON' })).querySelector('#pdf-a-signer-titre')).toBeNull();
    });
});

describe('EtapeDemande : imputation département ou service', () => {
    const vierge = () => reactive({ prestation: 'Fournitures', objetDemande: '', montantHt: null, montantTtc: null, tauxTva: '20.00', departement: 1, service: null, fournisseur: null, fournisseurPropose: null });
    const choisir = async (el, valeur) => {
        const radio = el.querySelector(`input[name="demande-imputation"][value="${valeur}"]`);
        radio.checked = true;
        radio.dispatchEvent(new Event('change'));
        await attendre();
    };

    it('pré-remplit le département et bascule sur un service sans garder les deux', async () => {
        const form = vierge();
        const el = await monter(EtapeDemande, {
            form, nouvelle: true, modifiable: () => true, tauxTva: TAUX,
            departements: [{ id: 1, libelle: 'MMI' }], services: [{ id: 7, libelle: 'Informatique' }],
        });
        expect(el.querySelector('#demande-departement').textContent).toContain('MMI');
        expect(el.querySelector('#demande-service')).toBeNull();

        await choisir(el, 'service');
        expect(form.departement).toBeNull();
        const select = el.querySelector('#demande-service');
        select.value = '7';
        select.dispatchEvent(new Event('change'));
        await attendre();
        expect(form.service).toBe(7);
        expect(el.textContent).toContain('validée par le service financier');

        await choisir(el, 'departement');
        expect(form.service).toBeNull();
        expect(form.departement).toBe(1);
    });

    it('sans département, impose un service', async () => {
        const form = reactive({ ...vierge(), departement: null });
        const el = await monter(EtapeDemande, { form, nouvelle: true, modifiable: () => true, tauxTva: TAUX, departements: [], services: [{ id: 7, libelle: 'Informatique' }] });
        expect(el.querySelector('#demande-service')).not.toBeNull();
        expect(el.querySelector('input[value="departement"]').disabled).toBe(true);
    });
});

describe('Service fait : bons de livraison et visa par réception', () => {
    const reception = (surcharge = {}) => ({
        id: 31, typeReception: 'TOTALE', prestationsConformes: true, dateEffectiveReception: '2026-11-03', montantTtcConstate: '1200.00',
        receptionnaire: { id: 5, prenom: 'Alice', nom: 'DUPONT' }, numerosMigo: [], visee: false, dateVisa: null, viseur: null,
        documents: [{ id: 7, nomFichier: 'BL-2026-118.pdf', dateDepot: '2026-11-03T10:00:00+01:00' }],
        ...surcharge,
    });
    const monterEtape = (receptions, droits = {}) => monter(EtapeServiceFait, {
        commande: commande({ statut: 'SERVICE_FAIT_CONSTATE', receptions }), atteinte: true, ...droits,
    });

    it('le service financier vise une réception avec ses numéros MIGO', async () => {
        api.post.mockResolvedValue({ data: { certifie: true, statut: 'SERVICE_FAIT_CERTIFIE' } });
        const el = await monterEtape([reception()], { gererReceptions: true, viserReceptions: true });
        expect(el.textContent).toContain('BL-2026-118.pdf');
        expect(el.textContent).toContain('1 réception(s) à viser');

        await saisir(el.querySelector('#migo-31'), '5000412345');
        bouton(el, 'Ajouter').click();
        await attendre();
        bouton(el, 'Viser cette réception').click();
        await attendre();
        expect(api.post).toHaveBeenCalledWith('/api/finance_receptions/31/viser', { numerosMigo: ['5000412345'] });
    });

    it('pas de visa sans bon de livraison', async () => {
        const el = await monterEtape([reception({ documents: [] })], { gererReceptions: true, viserReceptions: true });
        expect(el.textContent).toContain('doit être déposé avant le visa');
        expect(bouton(el, 'Viser cette réception').disabled).toBe(true);
    });

    it('le demandeur ajoute un bon de livraison tant que la réception n\'est pas visée', async () => {
        const el = await monterEtape([reception(), reception({ id: 32, visee: true, dateVisa: '2026-11-04', viseur: { id: 9, prenom: 'Max', nom: 'MAISTRE' } })], { bonsLivraison: true });
        expect(el.querySelector('#bl-ajout-31')).not.toBeNull();
        expect(el.querySelector('#bl-ajout-32')).toBeNull();
        expect(bouton(el, 'Viser cette réception')).toBeUndefined();
        expect(el.textContent).toContain('MAISTRE');
    });
});

describe('EtapeFacture : dates saisies dans l\'étape', () => {
    const ACTIONS = {
        recevoir_facture: { action: 'recevoir_facture', libelle: 'Facture reçue', severite: 'primary' },
        rejeter_facture: { action: 'rejeter_facture', libelle: 'Rejeter la facture', severite: 'danger', commentaire: true },
        payer: { action: 'payer', libelle: 'Paiement effectué', severite: 'primary' },
    };
    const monterFacture = (statut, actions, ecouteurs = {}) => monter(EtapeFacture, {
        form: reactive({ dateArriveeFacture: '', datePaiement: '', factureFinale: true }),
        commande: commande({ statut }), modifiable: () => false, atteinte: true, actions,
    }, ecouteurs);

    it('enregistre l\'arrivée de la facture avec sa date', async () => {
        const envoyer = vi.fn();
        const el = await monterFacture('SERVICE_FAIT_CERTIFIE', [ACTIONS.recevoir_facture], { onEnvoyer: envoyer });
        expect(el.querySelector('#facture-arrivee').tagName).toBe('SPAN');
        await saisir(el.querySelector('#facture-arrivee-saisie'), '2026-11-02');
        el.querySelector('#facture-arrivee-saisie').form.dispatchEvent(new Event('submit'));
        await attendre();
        expect(envoyer).toHaveBeenCalledWith(ACTIONS.recevoir_facture, { date: '2026-11-02' });
        expect(bouton(el, 'Facture reçue')).toBeUndefined();
    });

    it('enregistre le paiement ; le rejet de facture reste un bouton', async () => {
        const envoyer = vi.fn();
        const el = await monterFacture('FACTURE_RECUE', [ACTIONS.rejeter_facture, ACTIONS.payer], { onEnvoyer: envoyer });
        expect(bouton(el, 'Rejeter la facture')).toBeTruthy();
        await saisir(el.querySelector('#facture-paiement-saisie'), '2026-11-05');
        const finale = el.querySelector('#facture-finale-saisie');
        finale.checked = false;
        finale.dispatchEvent(new Event('change'));
        bouton(el, 'Enregistrer le paiement').click();
        await attendre();
        expect(envoyer).toHaveBeenCalledWith(ACTIONS.payer, { date: '2026-11-05', factureFinale: false });
    });
});

describe('ActionWorkflowDialog : constatation du service fait', () => {
    it('exige au moins un bon de livraison en PDF', async () => {
        const valider = vi.fn();
        await monter(ActionWorkflowDialog, { visible: true, action: { action: 'constater_service_fait', libelle: 'Constater le service fait' }, montantTtc: '1200.00' }, { onValider: valider });
        const envoyer = [...document.querySelectorAll('button[type="submit"]')].find(b => b.textContent.includes('Constater'));
        expect(envoyer.disabled).toBe(true);

        const champ = document.querySelector('#sf-bl');
        const pdf = new File(['%PDF-1.4'], 'BL.pdf', { type: 'application/pdf' });
        const image = new File(['x'], 'photo.png', { type: 'image/png' });
        Object.defineProperty(champ, 'files', { value: [pdf, image], configurable: true });
        champ.dispatchEvent(new Event('change'));
        await attendre();
        expect(document.body.textContent).toContain('« photo.png » n\'est pas un PDF.');
        expect(envoyer.disabled).toBe(false);

        document.querySelector('#form-action-workflow').dispatchEvent(new Event('submit'));
        await attendre();
        expect(valider).toHaveBeenCalledWith(expect.objectContaining({ typeReception: 'TOTALE', bonsLivraison: [pdf] }));
    });
});

describe('FinanceDashboardView', () => {
    const liste = [
        commande({ id: 1, statut: 'SOUMIS', demandeur: { id: 5, prenom: 'Alice', nom: 'DUPONT' }, montantTtc: '120.00', montantHt: '100.00' }),
        commande({ id: 2, statut: 'PAYE', demandeur: { id: 9, prenom: 'Bob', nom: 'MARTIN' }, objetDemande: 'Ordinateurs', montantTtc: '2400.00', montantHt: '2000.00' }),
    ];

    it('affiche les statuts en Tag, les indicateurs et filtre « mes demandes »', async () => {
        api.get.mockResolvedValue({ data: { member: liste, totalItems: 2 } });
        const el = await monter(FinanceDashboardView);

        expect(el.querySelectorAll('.p-tag').length).toBe(2);
        expect(el.textContent).toContain('Demande soumise');
        expect(el.textContent).toContain('Payée');
        expect(el.querySelector('.animate-spin, .pi-spin')).toBeNull();
        expect(el.textContent).toContain('Demandes: 2');
        expect(el.textContent).toContain('Montant engagé (TTC): 2');

        const perimetre = el.querySelector('select[aria-label="Périmètre"]');
        perimetre.value = 'miennes';
        perimetre.dispatchEvent(new Event('change'));
        await attendre();
        expect(el.textContent).toContain('Demandes: 1');
        expect(el.textContent).not.toContain('Ordinateurs');
    });

    it('déroule le détail d\'une demande : cinq blocs aux couleurs des étapes', async () => {
        api.get.mockResolvedValue({ data: { member: [liste[1]], totalItems: 1 } });
        const el = await monter(FinanceDashboardView);
        expect(el.querySelector('section[aria-label^="Étape"]')).toBeNull();

        el.querySelector('button.p-datatable-row-toggle-button, .p-datatable-row-toggle-button').click();
        await attendre();
        const blocs = [...el.querySelectorAll('section[aria-label^="Étape"]')];
        expect(blocs).toHaveLength(5);
        expect(blocs.map(b => b.className.match(/border-l-(\w+)-/)[1])).toEqual(['blue', 'amber', 'purple', 'teal', 'pink']);
        expect(blocs.map(b => b.querySelector('i').className.match(/text-(\w+)-600/)[1])).toEqual(['blue', 'amber', 'purple', 'teal', 'pink']);
        expect(el.textContent).toContain('Facture finale');
        expect(bouton(el, 'Ouvrir la demande')).toBeTruthy();
    });

    it('affiche le département ou le service d\'imputation, filtrable', async () => {
        api.get.mockResolvedValue({ data: { member: [
            commande({ id: 1, objetDemande: 'Papier' }),
            commande({ id: 2, objetDemande: 'Serveur', departement: null, service: { id: 7, libelle: 'Informatique' } }),
        ], totalItems: 2 } });
        const el = await monter(FinanceDashboardView);
        expect([...el.querySelectorAll('thead th')].map(th => th.textContent.trim())).toContain('Département / Service');
        expect(el.textContent).toContain('MMI');
        expect(el.textContent).toContain('Informatique');

        await saisir(el.querySelector('input[aria-label="Filtrer par Département / Service"]'), 'info');
        expect(el.textContent).toContain('Serveur');
        expect(el.textContent).not.toContain('Papier');
    });

    it('affiche une colonne Fournisseur filtrable depuis l\'en-tête', async () => {
        api.get.mockResolvedValue({ data: { member: [
            commande({ id: 1, objetDemande: 'Papier', fournisseur: { id: 3, nomFournisseur: 'Bureau Vallée' } }),
            commande({ id: 2, objetDemande: 'Câbles', fournisseur: null, fournisseurPropose: 'LDLC' }),
        ], totalItems: 2 } });
        const el = await monter(FinanceDashboardView);

        const entetes = [...el.querySelectorAll('thead th')].map(th => th.textContent.trim());
        expect(entetes).toContain('Fournisseur');
        expect(el.textContent).toContain('Bureau Vallée');
        expect(el.textContent).toContain('LDLC');

        await saisir(el.querySelector('input[aria-label="Filtrer par Fournisseur"]'), 'ldlc');
        expect(el.textContent).toContain('Câbles');
        expect(el.textContent).not.toContain('Papier');
    });

    it('exporte en xlsx les lignes affichées, filtres d\'en-tête appliqués', async () => {
        api.get.mockResolvedValue({ data: { member: [
            commande({ id: 1, objetDemande: 'Papier', fournisseur: { id: 3, nomFournisseur: 'Bureau Vallée' } }),
            commande({ id: 2, objetDemande: 'Câbles', fournisseur: null, fournisseurPropose: 'LDLC' }),
            commande({ id: 3, objetDemande: 'Souris', fournisseur: null, fournisseurPropose: 'LDLC Pro' }),
        ], totalItems: 3 } });
        api.post.mockResolvedValue({ data: 'xlsx', headers: { 'content-disposition': 'attachment; filename="demandes_achat_20261109.xlsx"' } });
        const creerUrl = vi.fn(() => 'blob:x');
        globalThis.URL.createObjectURL = creerUrl;
        globalThis.URL.revokeObjectURL = () => {};
        const clic = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
        const el = await monter(FinanceDashboardView);

        await saisir(el.querySelector('input[aria-label="Filtrer par Fournisseur"]'), 'ldlc');
        bouton(el, 'Exporter').click();
        await attendre();

        expect(api.post).toHaveBeenCalledWith('/api/finance_bon_commandes/export-excel', { ids: [2, 3] }, { responseType: 'blob' });
        expect(creerUrl).toHaveBeenCalled();
        expect(clic).toHaveBeenCalled();
        clic.mockRestore();
        el.querySelector('.p-datatable-row-toggle-button').click();
        await attendre();
        expect(el.querySelectorAll('section[aria-label^="Étape"]')).toHaveLength(5);
    });

    it('filtre par objet depuis l\'en-tête sans perdre le déroulé', async () => {
        api.get.mockResolvedValue({ data: { member: liste, totalItems: 2 } });
        const el = await monter(FinanceDashboardView);

        await saisir(el.querySelector('input[aria-label="Filtrer par Objet"]'), 'ordi');
        expect(el.textContent).toContain('Ordinateurs');
        expect(el.textContent).not.toContain('Vidéoprojecteur');

        el.querySelector('.p-datatable-row-toggle-button').click();
        await attendre();
        expect(el.querySelectorAll('section[aria-label^="Étape"]')).toHaveLength(5);
    });

    it('affiche un état vide sans demande', async () => {
        api.get.mockResolvedValue({ data: { member: [], totalItems: 0 } });
        const el = await monter(FinanceDashboardView);
        expect(el.textContent).toContain('Aucune demande d\'achat');
    });

    it('pagine jusqu\'à avoir toutes les demandes', async () => {
        const page1 = Array.from({ length: 100 }, (_, i) => commande({ id: i + 1 }));
        api.get.mockResolvedValueOnce({ data: { member: page1, totalItems: 101 } })
            .mockResolvedValueOnce({ data: { member: [commande({ id: 101 })], totalItems: 101 } });
        const el = await monter(FinanceDashboardView);
        expect(api.get).toHaveBeenCalledTimes(2);
        expect(el.textContent).toContain('Demandes: 101');
    });
});

describe('FinanceConfigurationView', () => {
    it('liste les taux de TVA et crée un taux', async () => {
        api.get.mockResolvedValue({ data: { member: TAUX, totalItems: 3 } });
        api.post.mockResolvedValue({ data: {} });
        const el = await monter(FinanceConfigurationView);

        expect(el.textContent).toContain('Taux normal (20 %)');
        expect(el.textContent).toContain('Désactivé');
        expect(el.textContent).toContain('Par défaut');

        bouton(el, 'Ajouter un taux').click();
        await attendre();
        const dialogue = document.body;
        await saisir(dialogue.querySelector('#taux-valeur'), '7');
        await saisir(dialogue.querySelector('#taux-libelle'), 'Taux test');
        dialogue.querySelector('button[type="submit"]').click();
        await attendre();
        expect(api.post).toHaveBeenCalledWith('/api/finance_taux_tvas', expect.objectContaining({ taux: '7', libelle: 'Taux test' }), expect.anything());
    });
});

describe('Tags de statut', () => {
    it('reprennent la couleur de l\'étape en cours', async () => {
        const cas = [
            ['SOUMIS', 'bg-blue-100!'],
            ['VERIFICATION_FINANCIERE', 'bg-amber-100!'],
            ['COMMANDE_VALIDEE', 'bg-teal-100!'],
            ['SERVICE_FAIT_CERTIFIE', 'bg-pink-100!'],
            ['PAYE', 'bg-pink-100!'],
            ['REJETE', 'bg-red-100!'],
            ['BROUILLON', 'bg-gray-100!'],
            ['CLOTURE', 'bg-emerald-100!'],
        ];
        for (const [statut, classe] of cas) {
            const el = await monter(StatutTag, { statut });
            expect(el.querySelector('.p-tag').className, statut).toContain(classe);
        }
    });
});

describe('Demande finalisée', () => {
    it('passe en vert quand tout est terminé (payée et soldée, ou clôturée)', async () => {
        const solde = await monter(StatutTag, { statut: 'PAYE', finalisee: true });
        expect(solde.querySelector('.p-tag').className).toContain('bg-emerald-100!');
        const partiel = await monter(StatutTag, { statut: 'PAYE', finalisee: false });
        expect(partiel.querySelector('.p-tag').className).toContain('bg-pink-100!');
        expect(estFinalisee({ statut: 'PAYE', factureFinale: true })).toBe(true);
        expect(estFinalisee({ statut: 'PAYE', factureFinale: false })).toBe(false);
        expect(estFinalisee({ statut: 'CLOTURE' })).toBe(true);
        expect(estFinalisee({ statut: 'SERVICE_FAIT_CERTIFIE', factureFinale: true })).toBe(false);
    });
});

describe('Document signé : décision sans ressaisie', () => {
    const avecDecision = (c) => commande({ statut: 'BROUILLON', pdfSigne: false, ...c, droits: { actions: [], champs: [], supprimer: true, deposerDocument: true, decisionDocument: true, gererReceptions: false } });

    it('propose avis favorable / refus et envoie le refus avec son motif', async () => {
        api.get.mockImplementation(async (url) => (url === '/api/finance_taux_tvas' ? { data: { member: TAUX, totalItems: 3 } } : { data: avecDecision() }));
        api.post.mockResolvedValue({ data: { success: true, statut: 'REJETE', pdfSigne: false } });
        const el = await monter(DemandeAchatDetailView);

        expect(bouton(el, 'Avis favorable : déposer le document signé')).toBeTruthy();
        bouton(el, 'La demande est refusée').click();
        await attendre();
        await saisir(document.body.querySelector('#refus-motif'), 'Hors budget');
        document.body.querySelector('button[type="submit"][form="form-refus"]').click();
        await attendre();

        const [url, corps] = api.post.mock.calls.find(([u]) => u.endsWith('/upload-signed-pdf'));
        expect(url).toBe('/api/finance_bon_commandes/42/upload-signed-pdf');
        expect(corps.get('decision')).toBe('defavorable');
        expect(corps.get('commentaire')).toBe('Hors budget');
        expect(corps.has('file')).toBe(false);
    });

    it('sans droit de décision, seul le dépôt simple est proposé', async () => {
        api.get.mockImplementation(async (url) => (url === '/api/finance_taux_tvas' ? { data: { member: TAUX, totalItems: 3 } } : { data: commande({ droits: { actions: [], champs: [], supprimer: false, deposerDocument: true, decisionDocument: false, gererReceptions: true } }) }));
        const el = await monter(DemandeAchatDetailView);
        expect(bouton(el, 'Avis favorable : déposer le document signé')).toBeFalsy();
        expect(bouton(el, 'Déposer le PDF signé')).toBeTruthy();
    });
});

describe('Mise en page des cases à cocher', () => {
    it('q-check est sur la case (16 px), jamais sur son libellé', async () => {
        const form = reactive({ fournisseur: null, fournisseurPropose: null, numeroBonCommande: '', centreFinancier: '', colisAttendu: '', commandeSurMarche: false, dateLivraisonRenseignee: false, pjSurSifac: false });
        const el = await monter(EtapeVerification, { form, commande: commande(), modifiable: () => true, atteinte: true });
        const cases = [...el.querySelectorAll('input[type="checkbox"]')];
        expect(cases).toHaveLength(3);
        for (const c of cases) {
            expect(c.className).toContain('q-check');
            expect(c.closest('label').className).not.toContain('q-check');
            expect(c.closest('label').className).toContain('flex');
        }
    });
});
