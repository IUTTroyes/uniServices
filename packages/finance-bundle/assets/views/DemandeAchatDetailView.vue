<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import { useToast } from 'primevue/usetoast';
import { HeaderComponent, Card, ListSkeleton, ErrorState } from '@components';
import { useConfirmation } from '@composables/useConfirmation';
import { useUsersStore } from '@stores';
import { getAllDepartementsService } from '@requests';
import {
    getFinanceBonCommandeDetailService,
    createFinanceBonCommandeService,
    updateFinanceBonCommandeService,
    deleteFinanceBonCommandeService,
    transitionFinanceBonCommandeService,
    updateFinanceReceptionService,
    uploadFinanceSignedPdfService,
    getFinanceSignedPdfBlobService,
    getFinancePdfBlobService,
    getFinanceTauxTvaService,
    telechargerBlob,
} from '@/requests/financeService';
import { ETAPES, etapeCourante, etapeTerminee, montantApi, iri, dateSaisie, messageErreurApi } from '@/utils/finance';
import BonCommandeWorkflowTimeline from '@/components/BonCommandeWorkflowTimeline.vue';
import PdfApercuDialog from '@/components/PdfApercuDialog.vue';
import ActionWorkflowDialog from '@/components/ActionWorkflowDialog.vue';
import EtapeDemande from '@/components/demande/EtapeDemande.vue';
import ResumeDemande from '@/components/demande/ResumeDemande.vue';
import EtapeVerification from '@/components/demande/EtapeVerification.vue';
import EtapeEngagement from '@/components/demande/EtapeEngagement.vue';
import EtapeServiceFait from '@/components/demande/EtapeServiceFait.vue';
import EtapeFacture from '@/components/demande/EtapeFacture.vue';

/**
 * Demande d'achat : création, saisie par étape et circuit de validation.
 * Les droits (actions possibles, champs modifiables) viennent du serveur avec la demande.
 */
const route = useRoute();
const router = useRouter();
const toast = useToast();
const { confirmer } = useConfirmation();
const userStore = useUsersStore();

/** Champs saisissables à la création (identique à FinanceWorkflow::champsCreation côté serveur). */
const CHAMPS_CREATION = ['prestation', 'objetDemande', 'montantHt', 'montantTtc', 'tauxTva', 'noteExplicative', 'centreFinancier', 'fournisseur', 'fournisseurPropose', 'departement'];
const CHAMPS_DATE = ['dateBonCommande', 'dateArriveeFacture', 'datePaiement'];
const ROLES_CREATION = ['ROLE_FINANCE_DEMANDEUR', 'ROLE_FINANCE_MANAGER', 'SUPER_ADMIN'];

const nouvelle = computed(() => !route.params.id);
const chargement = ref(false);
const erreur = ref('');
const enregistrement = ref(false);
const envoi = ref(null);
const depot = ref(false);
const receptionEnregistree = ref(null);
const commande = ref(null);
const form = ref({});
const tauxTva = ref([]);
const departements = ref([]);
const onglet = ref('1');
const apercu = ref(false);
const etapePdf = ref(5);
const actionEnCours = ref(null);
const dialogueAction = ref(false);

const droits = computed(() => commande.value?.droits ?? { actions: [], champs: [], supprimer: false, deposerDocument: false, gererReceptions: false, decisionDocument: false });
const champsModifiables = computed(() => (nouvelle.value ? CHAMPS_CREATION : droits.value.champs));
const modifiable = (champ) => champsModifiables.value.includes(champ);
const statut = computed(() => commande.value?.statut ?? 'BROUILLON');
const atteinte = (etape) => etape === 1 || etapeTerminee(statut.value, etape - 1);
const titre = computed(() => (nouvelle.value ? 'Nouvelle demande d\'achat' : `Demande d'achat : ${commande.value?.objetDemande || '#' + route.params.id}`));

/** Formulaire à partir de la demande reçue (ou vierge). */
function formulaireDepuis(c) {
    const parDefaut = tauxTva.value.find(t => t.parDefaut && t.actif);
    return {
        prestation: c?.prestation ?? 'Fournitures',
        objetDemande: c?.objetDemande ?? '',
        montantHt: c?.montantHt ?? null,
        montantTtc: c?.montantTtc ?? null,
        tauxTva: c ? (c.tauxTva ?? null) : (parDefaut?.taux ?? null),
        noteExplicative: c?.noteExplicative ?? '',
        centreFinancier: c?.centreFinancier ?? '',
        fournisseur: c?.fournisseur ?? null,
        fournisseurPropose: c?.fournisseurPropose ?? null,
        departement: c ? (c.departement?.id ?? null) : (departements.value.length === 1 ? departements.value[0].id : (userStore.departementDefaut?.id ?? null)),
        commandeSurMarche: Boolean(c?.commandeSurMarche),
        numeroBonCommande: c?.numeroBonCommande ?? '',
        dateBonCommande: dateSaisie(c?.dateBonCommande),
        dateLivraisonRenseignee: Boolean(c?.dateLivraisonRenseignee),
        pjSurSifac: Boolean(c?.pjSurSifac),
        colisAttendu: c?.colisAttendu ?? '',
        dateArriveeFacture: dateSaisie(c?.dateArriveeFacture),
        datePaiement: dateSaisie(c?.datePaiement),
        factureFinale: Boolean(c?.factureFinale),
    };
}

/** Valeur d'un champ telle que l'API l'attend. */
function valeurApi(champ, valeur) {
    if (champ === 'fournisseur') return iri('finance_fournisseurs', valeur);
    if (champ === 'departement') return iri('structure_departements', valeur);
    if (champ === 'montantHt' || champ === 'montantTtc') return montantApi(valeur);
    if (champ === 'tauxTva') return valeur === null || valeur === '' ? null : String(valeur);
    if (CHAMPS_DATE.includes(champ)) return valeur || null;
    if (typeof valeur === 'string') return valeur.trim() === '' ? null : valeur;
    return valeur;
}

/** Corps de la requête : uniquement les champs permis, et en modification uniquement ceux qui ont changé. */
function corps() {
    const reference = nouvelle.value ? null : formulaireDepuis(commande.value);
    const resultat = {};
    for (const champ of champsModifiables.value) {
        if (!(champ in form.value)) continue;
        const valeur = valeurApi(champ, form.value[champ]);
        if (reference && JSON.stringify(valeur) === JSON.stringify(valeurApi(champ, reference[champ]))) continue;
        resultat[champ] = valeur;
    }
    if (nouvelle.value && resultat.objetDemande === null) delete resultat.objetDemande;
    return resultat;
}

async function charger() {
    erreur.value = '';
    if (nouvelle.value) {
        commande.value = null;
        form.value = formulaireDepuis(null);
        return;
    }
    chargement.value = true;
    try {
        commande.value = await getFinanceBonCommandeDetailService(route.params.id);
        form.value = formulaireDepuis(commande.value);
        onglet.value = String(Math.max(1, etapeCourante(statut.value)));
    } catch (e) {
        erreur.value = messageErreurApi(e, 'Impossible de charger la demande.');
    } finally {
        chargement.value = false;
    }
}

/** Enregistre la saisie ; renvoie false en cas d'échec. */
async function enregistrer({ silencieux = false } = {}) {
    const donnees = corps();
    if (!nouvelle.value && Object.keys(donnees).length === 0) return true;
    enregistrement.value = true;
    try {
        if (nouvelle.value) {
            const creee = await createFinanceBonCommandeService(donnees);
            toast.add({ severity: 'success', summary: 'Demande créée', detail: 'Vous pouvez la compléter puis la soumettre.', life: 3000 });
            await router.replace({ name: 'finance_detail', params: { id: creee.id } });
            return true;
        }
        await updateFinanceBonCommandeService(route.params.id, donnees);
        if (!silencieux) toast.add({ severity: 'success', summary: 'Enregistré', detail: 'Modifications enregistrées.', life: 2500 });
        await charger();
        return true;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Enregistrement impossible', detail: messageErreurApi(e), life: 6000 });
        return false;
    } finally {
        enregistrement.value = false;
    }
}

/** Action du circuit : saisie complémentaire si besoin, confirmation, enregistrement préalable de la saisie. */
async function executer(action) {
    actionEnCours.value = action;
    if (action.commentaire || ['constater_service_fait', 'recevoir_facture', 'payer'].includes(action.action)) {
        dialogueAction.value = true;
        return;
    }
    if (action.confirmer && !(await confirmer({
        titre: action.libelle,
        message: `Confirmer « ${action.libelle} » ? Cette étape sera enregistrée à votre nom.`,
        libelle: action.libelle,
        danger: action.severite === 'danger',
    }))) return;
    await envoyerAction(action, {});
}

async function envoyerAction(action, donnees) {
    if (champsModifiables.value.length && !(await enregistrer({ silencieux: true }))) return;
    envoi.value = action.action;
    try {
        const resultat = await transitionFinanceBonCommandeService(route.params.id, action.action, donnees);
        toast.add({ severity: 'success', summary: action.libelle, detail: `Statut : ${resultat.statutLibelle}`, life: 3000 });
        dialogueAction.value = false;
        await charger();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Action impossible', detail: messageErreurApi(e), life: 6000 });
    } finally {
        envoi.value = null;
    }
}

async function supprimer() {
    if (!(await confirmer({ titre: 'Supprimer la demande', message: 'Supprimer définitivement cette demande d\'achat ?', libelle: 'Supprimer', danger: true }))) return;
    try {
        await deleteFinanceBonCommandeService(route.params.id);
        toast.add({ severity: 'success', summary: 'Demande supprimée', life: 2500 });
        router.push({ name: 'finance_dashboard' });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Suppression impossible', detail: messageErreurApi(e), life: 6000 });
    }
}

/** Dépôt du document signé ; avec `decision`, il vaut avis (favorable) ou refus, sans ressaisie. */
async function deposer({ fichier, decision = null, commentaire = '' }) {
    if (decision === 'favorable' && !(await confirmer({
        titre: 'Avis favorable',
        message: 'Le document signé vaut avis favorable : la demande passera à la vérification du service financier.',
        libelle: 'Confirmer l\'avis favorable',
    }))) return;
    const donnees = new FormData();
    if (fichier) donnees.append('file', fichier);
    if (decision) donnees.append('decision', decision);
    if (commentaire) donnees.append('commentaire', commentaire);
    depot.value = true;
    try {
        const resultat = await uploadFinanceSignedPdfService(route.params.id, donnees);
        const detail = decision === 'favorable' ? 'Avis favorable enregistré : la demande passe au service financier.'
            : (decision === 'defavorable' ? 'Refus enregistré : la demande est rejetée.' : 'Le PDF signé est enregistré.');
        toast.add({ severity: 'success', summary: decision ? 'Décision enregistrée' : 'Document déposé', detail, life: 3500 });
        if (resultat.statut) await charger();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Dépôt impossible', detail: messageErreurApi(e), life: 6000 });
    } finally {
        depot.value = false;
    }
}

async function consulterDocument() {
    try {
        const url = window.URL.createObjectURL(await getFinanceSignedPdfBlobService(route.params.id));
        window.open(url, '_blank', 'noopener');
        setTimeout(() => window.URL.revokeObjectURL(url), 60000);
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Document indisponible', detail: messageErreurApi(e), life: 5000 });
    }
}

async function telechargerPdf(etape) {
    try {
        telechargerBlob(await getFinancePdfBlobService(route.params.id, etape), `demande_achat_${route.params.id}_etape_${etape}.pdf`);
    } catch (e) {
        toast.add({ severity: 'error', summary: 'PDF indisponible', detail: messageErreurApi(e), life: 5000 });
    }
}

async function enregistrerReception(id, donnees) {
    receptionEnregistree.value = id;
    try {
        await updateFinanceReceptionService(id, donnees);
        toast.add({ severity: 'success', summary: 'Réception enregistrée', life: 2500 });
        await charger();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Enregistrement impossible', detail: messageErreurApi(e), life: 6000 });
    } finally {
        receptionEnregistree.value = null;
    }
}

/** Départements où l'on peut créer une demande (le serveur le vérifie de toute façon). */
async function chargerDepartements() {
    const miens = (userStore.departements || []).filter(d => (d.departementPersonnel?.permissions || []).some(p => ROLES_CREATION.includes(p)));
    if (miens.some(d => (d.departementPersonnel?.permissions || []).includes('SUPER_ADMIN'))) {
        try {
            const res = await getAllDepartementsService();
            departements.value = res.data || res || [];
            return;
        } catch { /* repli sur ses départements */ }
    }
    departements.value = miens;
}

watch(() => route.params.id, charger);

onMounted(async () => {
    try {
        tauxTva.value = await getFinanceTauxTvaService();
    } catch {
        tauxTva.value = [];
    }
    if (nouvelle.value) await chargerDepartements();
    await charger();
});
</script>

<template>
    <div class="space-y-6">
        <HeaderComponent
            icon="pi pi-wallet"
            color="emerald"
            :titre="titre"
            :description="nouvelle ? 'Renseignez l\'objet, les montants et le département, puis enregistrez.' : 'Circuit de la demande : avis, vérification, engagement, service fait et paiement.'"
            :back-url="{ name: 'finance_dashboard' }"
        >
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <Button v-if="!nouvelle && commande" label="Aperçu PDF" icon="pi pi-file-pdf" severity="secondary" outlined size="small" @click="etapePdf = 5; apercu = true" />
                    <Button v-if="droits.supprimer" label="Supprimer" icon="pi pi-trash" severity="danger" outlined size="small" @click="supprimer" />
                    <Button v-if="champsModifiables.length" :label="nouvelle ? 'Créer la demande' : 'Enregistrer'" icon="pi pi-save" size="small" :loading="enregistrement" @click="enregistrer()" />
                </div>
            </template>
        </HeaderComponent>

        <ListSkeleton v-if="chargement && !commande" />
        <ErrorState v-else-if="erreur" :message="erreur" />

        <template v-else>
            <Message v-if="statut === 'REJETE'" severity="error" :closable="false">
                Demande rejetée<span v-if="commande?.motifRejet"> : {{ commande.motifRejet }}</span>
            </Message>
            <Message v-else-if="commande?.avisDirecteur === 'DEMANDE_INFO' && statut === 'BROUILLON'" severity="warn" :closable="false">
                Précisions demandées : {{ commande.avisDirecteurCommentaire }} — complétez la demande puis soumettez-la à nouveau.
            </Message>

            <Card v-if="!nouvelle && commande" body-class="p-4">
                <BonCommandeWorkflowTimeline :commande="commande" :selection="Number(onglet)" @select-step="onglet = String($event)" />
            </Card>

            <EtapeDemande
                v-if="nouvelle"
                :form="form"
                nouvelle
                :modifiable="modifiable"
                :taux-tva="tauxTva"
                :departements="departements"
            />

            <div v-else-if="commande" class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem] gap-6 items-start">
            <aside class="lg:order-2 lg:sticky lg:top-4" aria-label="Résumé de la demande">
                <ResumeDemande :commande="commande" />
            </aside>
            <Tabs v-model:value="onglet" class="lg:order-1 min-w-0">
                <TabList>
                    <Tab v-for="e in ETAPES" :key="e.id" :value="String(e.id)">
                        <span class="flex items-center gap-2 text-sm">
                            <span class="w-2.5 h-2.5 rounded-full" :class="atteinte(e.id) ? e.fond : 'bg-gray-300 dark:bg-gray-600'" aria-hidden="true" />
                            {{ e.id }}. {{ e.titre }}
                            <i v-if="etapeTerminee(statut, e.id)" class="pi pi-check text-emerald-600" aria-label="terminée" />
                            <i v-else-if="!atteinte(e.id)" class="pi pi-lock text-gray-400" aria-label="pas encore ouverte" />
                        </span>
                    </Tab>
                </TabList>
                <TabPanels>
                    <TabPanel value="1">
                        <EtapeDemande
                            :form="form"
                            :commande="commande"
                            :modifiable="modifiable"
                            :taux-tva="tauxTva"
                            :actions="droits.actions"
                            :envoi="envoi"
                            :peut-deposer="droits.deposerDocument"
                            :peut-decider="droits.decisionDocument"
                            :peut-creer-fournisseur="droits.gererReceptions"
                            :depot-en-cours="depot"
                            @executer="executer"
                            @deposer="deposer"
                            @consulter-document="consulterDocument"
                            @telecharger-pdf="telechargerPdf"
                        />
                    </TabPanel>
                    <TabPanel value="2">
                        <EtapeVerification :form="form" :commande="commande" :modifiable="modifiable" :atteinte="atteinte(2)" :actions="droits.actions" :envoi="envoi" :peut-creer-fournisseur="droits.gererReceptions" @executer="executer" />
                    </TabPanel>
                    <TabPanel value="3">
                        <EtapeEngagement :form="form" :commande="commande" :modifiable="modifiable" :atteinte="atteinte(3)" :actions="droits.actions" :envoi="envoi" @executer="executer" />
                    </TabPanel>
                    <TabPanel value="4">
                        <EtapeServiceFait :commande="commande" :atteinte="atteinte(4)" :gerer-receptions="droits.gererReceptions" :actions="droits.actions" :envoi="envoi" :reception-enregistree="receptionEnregistree" @executer="executer" @enregistrer-reception="enregistrerReception" />
                    </TabPanel>
                    <TabPanel value="5">
                        <EtapeFacture :form="form" :commande="commande" :modifiable="modifiable" :atteinte="atteinte(5)" :actions="droits.actions" :envoi="envoi" @executer="executer" />
                    </TabPanel>
                </TabPanels>
            </Tabs>
            </div>
        </template>

        <PdfApercuDialog v-model:visible="apercu" :commande-id="route.params.id" :etape-initiale="etapePdf" />
        <ActionWorkflowDialog
            v-model:visible="dialogueAction"
            :action="actionEnCours"
            :montant-ttc="commande?.montantTtc"
            :envoi="envoi !== null"
            @valider="envoyerAction(actionEnCours, $event)"
        />
    </div>
</template>
