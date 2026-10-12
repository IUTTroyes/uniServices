<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    getFinanceBonCommandeDetailService,
    updateFinanceBonCommandeService,
    createFinanceBonCommandeService,
    getFinanceFournisseursService
} from '@/requests/financeService';
import BonCommandeWorkflowTimeline from '../components/BonCommandeWorkflowTimeline.vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Card from 'primevue/card';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const isNew = ref(!route.params.id || route.params.id === 'nouveau');
const loading = ref(false);
const saving = ref(false);
const showPdfModal = ref(false);
const activeTab = ref('1');

const typePrestationOptions = [
    { label: 'Fournitures', value: 'Fournitures' },
    { label: 'Services', value: 'Services' },
    { label: 'Travaux', value: 'Travaux' },
];

const avisDirecteurOptions = [
    { label: 'Favorable', value: 'FAVORABLE' },
    { label: 'Défavorable', value: 'DEFAVORABLE' },
    { label: 'Demande d’information complémentaire', value: 'DEMANDE_INFO' },
];

const typeReceptionOptions = [
    { label: 'Totalité de la commande', value: 'TOTALE' },
    { label: 'Partie des biens/prestations', value: 'PARTIELLE' },
];

const form = ref({
    prestation: 'Fournitures',
    objetDemande: '',
    montantHt: null,
    montantTtc: null,
    noteExplicative: '',
    centreFinancier: 'ACFA9B5FGL',
    commandeSurMarche: false,
    numeroBonCommande: '',
    dateLivraisonRenseignee: false,
    pjSurSifac: false,
    bcTransmis: false,
    colisAttendu: '',
    avisDirecteur: null,
    avisDirecteurCommentaire: '',
    dateAvisDirecteur: null,
    dateVerificationFinanciere: null,
    dateVisaEngagement: null,
    dateCertificationServiceFait: null,
    dateArriveeFacture: null,
    rejetFactureMotif: '',
    datePaiement: null,
    factureFinale: false,
    statut: 'BROUILLON',
    fournisseur: {
        numeroFournisseur: '',
        nomFournisseur: ''
    },
    receptions: [
        {
            dateEffectiveReception: null,
            typeReception: 'TOTALE',
            montantTtcConstate: null,
            prestationsConformes: true,
            detailsReceptionPartielle: '',
            numerosMigo: [],
            dateMigo103: null,
            dateMigo105: null,
            blRattacheSifac: false,
            qualiteReceptionnaire: 'Demandeur / Réceptionnaire',
        }
    ]
});

const currentReception = computed(() => {
    if (!form.value.receptions || form.value.receptions.length === 0) {
        form.value.receptions = [{
            dateEffectiveReception: null,
            typeReception: 'TOTALE',
            montantTtcConstate: form.value.montantTtc,
            prestationsConformes: true,
            detailsReceptionPartielle: '',
            numerosMigo: [],
            dateMigo103: null,
            dateMigo105: null,
            blRattacheSifac: false,
            qualiteReceptionnaire: 'Demandeur / Réceptionnaire',
        }];
    }
    return form.value.receptions[0];
});

const newMigoInput = ref('');

function addMigo() {
    if (newMigoInput.value.trim()) {
        if (!currentReception.value.numerosMigo) {
            currentReception.value.numerosMigo = [];
        }
        currentReception.value.numerosMigo.push(newMigoInput.value.trim());
        newMigoInput.value = '';
    }
}

function removeMigo(index) {
    if (currentReception.value.numerosMigo) {
        currentReception.value.numerosMigo.splice(index, 1);
    }
}

async function loadCommande() {
    if (isNew.value) return;
    loading.value = true;
    try {
        const res = await getFinanceBonCommandeDetailService(route.params.id);
        form.value = { ...res.data };
        if (!form.value.fournisseur) {
            form.value.fournisseur = { numeroFournisseur: '', nomFournisseur: '' };
        }
        if (!form.value.receptions || form.value.receptions.length === 0) {
            form.value.receptions = [{
                dateEffectiveReception: null,
                typeReception: 'TOTALE',
                montantTtcConstate: res.data.montantTtc,
                prestationsConformes: true,
                detailsReceptionPartielle: '',
                numerosMigo: [],
                dateMigo103: null,
                dateMigo105: null,
                blRattacheSifac: false,
                qualiteReceptionnaire: 'Demandeur / Réceptionnaire',
            }];
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger la demande' });
    } finally {
        loading.value = false;
    }
}

async function save() {
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (isNew.value) {
            const res = await createFinanceBonCommandeService(payload);
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Demande créée avec succès' });
            router.push({ name: 'finance_detail', params: { id: res.data.id } });
        } else {
            await updateFinanceBonCommandeService(route.params.id, payload);
            toast.add({ severity: 'success', summary: 'Enregistré', detail: 'Modifications sauvegardées avec succès' });
            await loadCommande();
        }
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors de l’enregistrement' });
    } finally {
        saving.value = false;
    }
}

// Actions de visas rapides
function visaDemande() {
    form.value.statut = 'SOUMIS';
    form.value.dateDemande = new Date().toISOString();
    save();
}

function visaDirecteur(favorable = true) {
    form.value.avisDirecteur = favorable ? 'FAVORABLE' : 'DEFAVORABLE';
    form.value.dateAvisDirecteur = new Date().toISOString();
    form.value.statut = 'VERIFICATION_FINANCIERE';
    save();
}

function visaFinancier() {
    form.value.dateVerificationFinanciere = new Date().toISOString();
    form.value.statut = 'VISA_ENGAGEMENT';
    save();
}

function visaEngagement() {
    form.value.dateVisaEngagement = new Date().toISOString();
    form.value.statut = 'COMMANDE_VALIDEE';
    save();
}

function visaConstatationServiceFait() {
    currentReception.value.dateEffectiveReception = new Date().toISOString();
    currentReception.value.dateSignatureServiceFait = new Date().toISOString();
    form.value.statut = 'SERVICE_FAIT_CONSTATE';
    save();
}

function visaCertification() {
    form.value.dateCertificationServiceFait = new Date().toISOString();
    form.value.statut = 'SERVICE_FAIT_CERTIFIE';
    save();
}

const pdfUrl = computed(() => {
    if (isNew.value || !route.params.id) return '';
    return `/api/finance_bon_commandes/${route.params.id}/pdf`;
});

function openPdfExternal() {
    if (!isNew.value) {
        window.open(pdfUrl.value, '_blank');
    }
}

onMounted(() => {
    loadCommande();
});
</script>

<template>
    <div class="demande-achat-detail p-4 max-w-6xl mx-auto">
        <!-- Header & Actions -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4 pb-3 border-b border-gray-200">
            <div>
                <Button label="Retour au tableau de bord" icon="pi pi-arrow-left" text severity="secondary" @click="router.push('/finance/')" class="p-0 mb-1" />
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ isNew ? 'Nouvelle demande de bon de commande' : `Bon de commande : ${form.objetDemande || ('#' + route.params.id)}` }}
                    </h1>
                    <Tag v-if="!isNew" :value="form.statut" severity="info" class="text-xs" />
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button v-if="!isNew" label="Aperçu PDF (Émargement)" icon="pi pi-file-pdf" severity="danger" outlined @click="showPdfModal = true" />
                <Button v-if="!isNew" label="Ouvrir PDF" icon="pi pi-external-link" severity="secondary" outlined @click="openPdfExternal" />
                <Button :label="saving ? 'Enregistrement...' : 'Enregistrer'" icon="pi pi-save" severity="primary" :loading="saving" @click="save" />
            </div>
        </div>

        <!-- Workflow Stepper / Timeline -->
        <Card v-if="!isNew" class="mb-6 shadow-sm border border-gray-200">
            <template #content>
                <BonCommandeWorkflowTimeline :commande="form" />
            </template>
        </Card>

        <!-- Formulaire par Onglets / Étapes -->
        <Tabs v-model:value="activeTab">
            <TabList class="mb-4">
                <Tab value="1"><i class="pi pi-file-edit mr-2 text-blue-700"></i>1. Demande & Avis</Tab>
                <Tab value="2"><i class="pi pi-search mr-2 text-amber-700"></i>2. Vérification SIFAC</Tab>
                <Tab value="3"><i class="pi pi-check-circle mr-2 text-purple-700"></i>3. Visa Engagement</Tab>
                <Tab value="4"><i class="pi pi-box mr-2 text-emerald-700"></i>4. Service Fait</Tab>
                <Tab value="5"><i class="pi pi-wallet mr-2 text-rose-700"></i>5. Facture & Paiement</Tab>
            </TabList>

            <TabPanels>
                <!-- 1. DEMANDE & AVIS DIRECTION -->
                <TabPanel value="1">
                    <div class="space-y-6">
                        <Card class="shadow-sm border-l-4 border-l-blue-700">
                            <template #title>
                                <div class="text-base font-bold text-blue-900 flex items-center justify-between">
                                    <span>1 - DEMANDE DE BON DE COMMANDE</span>
                                    <Tag v-if="form.dateDemande" severity="success" :value="'Demande soumise le ' + new Date(form.dateDemande).toLocaleDateString('fr-FR')" />
                                </div>
                            </template>
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-gray-700">Prestation *</label>
                                        <Select v-model="form.prestation" :options="typePrestationOptions" option-label="label" option-value="value" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-gray-700">Centre Financier</label>
                                        <InputText v-model="form.centreFinancier" placeholder="Ex: ACFA9B5FGL" />
                                    </div>
                                    <div class="flex flex-col gap-1 md:col-span-2">
                                        <label class="text-sm font-semibold text-gray-700">Objet de la demande (Libellé) *</label>
                                        <InputText v-model="form.objetDemande" placeholder="Ex: Gardiennage JPO, Matériel réseau..." />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-gray-700">Montant HT (€) *</label>
                                        <InputNumber v-model="form.montantHt" mode="currency" currency="EUR" locale="fr-FR" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-gray-700">Montant TTC (€)</label>
                                        <InputNumber v-model="form.montantTtc" mode="currency" currency="EUR" locale="fr-FR" />
                                    </div>
                                    <div class="flex flex-col gap-1 md:col-span-2">
                                        <label class="text-sm font-semibold text-gray-700">Note explicative synthétique (contexte / motivation / objectifs…)</label>
                                        <Textarea v-model="form.noteExplicative" rows="4" auto-resize placeholder="Détailler le contexte et l'objectif de la dépense..." />
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t flex justify-end">
                                    <Button label="Valider et soumettre la demande" icon="pi pi-check" severity="info" @click="visaDemande" />
                                </div>
                            </template>
                        </Card>

                        <!-- Avis Direction -->
                        <Card class="shadow-sm border-l-4 border-l-indigo-600">
                            <template #title>
                                <div class="text-base font-bold text-indigo-900">AVIS DU DIRECTEUR</div>
                            </template>
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-gray-700">Décision de la direction</label>
                                        <Select v-model="form.avisDirecteur" :options="avisDirecteurOptions" option-label="label" option-value="value" placeholder="Sélectionner l'avis" show-clear />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-gray-700">Date de l'avis</label>
                                        <InputText :value="form.dateAvisDirecteur ? new Date(form.dateAvisDirecteur).toLocaleDateString('fr-FR') : 'Non visé'" disabled />
                                    </div>
                                    <div class="flex flex-col gap-1 md:col-span-2" v-if="form.avisDirecteur === 'DEMANDE_INFO' || form.avisDirecteurCommentaire">
                                        <label class="text-sm font-semibold text-gray-700">Demande d'information complémentaire / Motif</label>
                                        <InputText v-model="form.avisDirecteurCommentaire" placeholder="Préciser les compléments d'informations nécessaires..." />
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t flex justify-end gap-2">
                                    <Button label="Avis Favorable" icon="pi pi-thumbs-up" severity="success" @click="visaDirecteur(true)" />
                                    <Button label="Avis Défavorable" icon="pi pi-thumbs-down" severity="danger" outlined @click="visaDirecteur(false)" />
                                </div>
                            </template>
                        </Card>
                    </div>
                </TabPanel>

                <!-- 2. VERIFICATION SERVICE FINANCIER -->
                <TabPanel value="2">
                    <Card class="shadow-sm border-l-4 border-l-amber-600">
                        <template #title>
                            <div class="text-base font-bold text-amber-900">2 - VÉRIFICATION SERVICE FINANCIER</div>
                        </template>
                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-gray-700">N° Fournisseur (Code SIFAC)</label>
                                    <InputText v-model="form.fournisseur.numeroFournisseur" placeholder="Ex: 1650, 1471" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-gray-700">Nom du fournisseur (Raison sociale)</label>
                                    <InputText v-model="form.fournisseur.nomFournisseur" placeholder="Ex: ELITE SECURITE, TOUSSAINT" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-gray-700">N° Bon de Commande SIFAC *</label>
                                    <InputText v-model="form.numeroBonCommande" placeholder="Ex: 4500264424" />
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-gray-700">Colis attendu</label>
                                    <InputText v-model="form.colisAttendu" placeholder="Informations colis / livraison" />
                                </div>

                                <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-3 bg-gray-50 rounded-lg border">
                                    <div class="flex items-center gap-2">
                                        <Checkbox v-model="form.commandeSurMarche" :binary="true" input-id="csm" />
                                        <label for="csm" class="text-sm font-medium">Commande sur marché</label>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Checkbox v-model="form.dateLivraisonRenseignee" :binary="true" input-id="dlr" />
                                        <label for="dlr" class="text-sm font-medium">Date livraison renseignée</label>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Checkbox v-model="form.pjSurSifac" :binary="true" input-id="pjs" />
                                        <label for="pjs" class="text-sm font-medium">PJ rattachée SIFAC</label>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Checkbox v-model="form.bcTransmis" :binary="true" input-id="bct" />
                                        <label for="bct" class="text-sm font-medium">BC transmis fournisseur</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t flex justify-between items-center">
                                <span class="text-xs text-gray-500">
                                    {{ form.dateVerificationFinanciere ? ('Vérifié le ' + new Date(form.dateVerificationFinanciere).toLocaleDateString('fr-FR')) : 'Non encore vérifié' }}
                                </span>
                                <Button label="Valider la vérification financière" icon="pi pi-check" severity="warn" @click="visaFinancier" />
                            </div>
                        </template>
                    </Card>
                </TabPanel>

                <!-- 3. VISA ENGAGEMENT -->
                <TabPanel value="3">
                    <Card class="shadow-sm border-l-4 border-l-purple-700">
                        <template #title>
                            <div class="text-base font-bold text-purple-900">3 - VISA CHEF DES SERVICES ADMINISTRATIFS / RESPONSABLE FINANCIER</div>
                        </template>
                        <template #content>
                            <div class="p-4 bg-purple-50 border border-purple-200 rounded-lg mb-4">
                                <p class="text-sm text-purple-950 font-medium">
                                    Visa d'engagement budgétaire et d'autorisation de passation de la commande vers le fournisseur.
                                </p>
                                <div class="mt-2 text-xs text-purple-800">
                                    Date du visa : <strong>{{ form.dateVisaEngagement ? new Date(form.dateVisaEngagement).toLocaleDateString('fr-FR') : 'En attente de visa' }}</strong>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Button label="Accorder le Visa d'engagement (CSA)" icon="pi pi-check-circle" severity="help" @click="visaEngagement" />
                            </div>
                        </template>
                    </Card>
                </TabPanel>

                <!-- 4. CONSTATATION DU SERVICE FAIT -->
                <TabPanel value="4">
                    <Card class="shadow-sm border-l-4 border-l-emerald-700">
                        <template #title>
                            <div class="text-base font-bold text-emerald-900">4 - CONSTATATION SERVICE FAIT (RÉCEPTION)</div>
                        </template>
                        <template #content>
                            <div class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-gray-700">Qualité du signataire / réceptionnaire</label>
                                        <InputText v-model="currentReception.qualiteReceptionnaire" placeholder="Ex: Demandeur, Enseignant, Technicien..." />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-gray-700">Type de réception</label>
                                        <Select v-model="currentReception.typeReception" :options="typeReceptionOptions" option-label="label" option-value="value" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-gray-700">Montant TTC constaté (€)</label>
                                        <InputNumber v-model="currentReception.montantTtcConstate" mode="currency" currency="EUR" locale="fr-FR" />
                                    </div>
                                    <div class="flex items-center gap-2 mt-6">
                                        <Checkbox v-model="currentReception.prestationsConformes" :binary="true" input-id="conf" />
                                        <label for="conf" class="text-sm font-semibold text-gray-800">Prestations exécutées et conformes</label>
                                    </div>
                                </div>

                                <div v-if="currentReception.typeReception === 'PARTIELLE'" class="flex flex-col gap-1">
                                    <label class="text-sm font-semibold text-gray-700">Précisions en cas de réception partielle (quantité / valeur)</label>
                                    <Textarea v-model="currentReception.detailsReceptionPartielle" rows="3" placeholder="Indiquer les biens ou prestations effectivement reçus..." />
                                </div>

                                <!-- Gestion des Numéros MIGO -->
                                <div class="p-3 bg-gray-50 rounded-lg border">
                                    <label class="text-sm font-bold text-gray-800 block mb-2">Numéros MIGO (SIFAC) & Dates</label>
                                    <div class="flex gap-2 mb-2">
                                        <InputText v-model="newMigoInput" placeholder="N° MIGO (ex: 5000123456)" class="p-inputtext-sm flex-1" @keyup.enter="addMigo" />
                                        <Button label="Ajouter MIGO" icon="pi pi-plus" size="small" @click="addMigo" />
                                    </div>
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        <span v-for="(migo, idx) in (currentReception.numerosMigo || [])" :key="idx" class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-md flex items-center gap-1 font-semibold">
                                            {{ migo }}
                                            <i class="pi pi-times cursor-pointer text-red-500 hover:text-red-700" @click="removeMigo(idx)"></i>
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Checkbox v-model="currentReception.blRattacheSifac" :binary="true" input-id="bls" />
                                        <label for="bls" class="text-sm font-medium">Bon de Livraison (BL) rattaché dans SIFAC</label>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t flex justify-end">
                                    <Button label="Signer la constatation du service fait" icon="pi pi-check" severity="success" @click="visaConstatationServiceFait" />
                                </div>
                            </div>
                        </template>
                    </Card>
                </TabPanel>

                <!-- 5. FACTURE & PAIEMENT -->
                <TabPanel value="5">
                    <div class="space-y-6">
                        <Card class="shadow-sm border-l-4 border-l-rose-700">
                            <template #title>
                                <div class="text-base font-bold text-rose-900">5 - CERTIFICATION DU SERVICE FAIT</div>
                            </template>
                            <template #content>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-700">
                                        Certification officielle du service fait par le Chef des Services Administratifs / Responsable Financier.
                                    </span>
                                    <Button label="Certifier le Service Fait" icon="pi pi-verified" severity="danger" @click="visaCertification" />
                                </div>
                            </template>
                        </Card>

                        <Card class="shadow-sm border-l-4 border-l-emerald-600">
                            <template #title>
                                <div class="text-base font-bold text-emerald-900">SUIVI FACTURATION & PAIEMENT SIFAC</div>
                            </template>
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-sm font-semibold text-gray-700">Motif de rejet de facture éventuel</label>
                                        <InputText v-model="form.rejetFactureMotif" placeholder="Indiquer le motif si facture rejetée..." />
                                    </div>
                                    <div class="flex items-center gap-2 mt-6">
                                        <Checkbox v-model="form.factureFinale" :binary="true" input-id="ff2" />
                                        <label for="ff2" class="text-sm font-bold text-gray-900">Facture finale soldée</label>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </TabPanel>
            </TabPanels>
        </Tabs>

        <!-- Modal Visualiseur PDF Intégré -->
        <Dialog v-model:visible="showPdfModal" modal header="Formulaire Bon de Commande (Aperçu pour Émargement)" :style="{ width: '80vw', height: '85vh' }">
            <div class="w-full h-[70vh]">
                <iframe v-if="pdfUrl" :src="pdfUrl" class="w-full h-full border-0 rounded"></iframe>
            </div>
            <template #footer>
                <Button label="Fermer" severity="secondary" @click="showPdfModal = false" />
                <Button label="Télécharger PDF" icon="pi pi-download" severity="primary" @click="openPdfExternal" />
            </template>
        </Dialog>
    </div>
</template>
