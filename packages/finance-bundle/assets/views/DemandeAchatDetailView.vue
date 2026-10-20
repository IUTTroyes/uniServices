<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { HeaderComponent, Card as CustomCard } from '@components';
import {
    getFinanceBonCommandeDetailService,
    updateFinanceBonCommandeService,
    createFinanceBonCommandeService,
    uploadFinanceSignedPdfService,
    getFinancePdfBlobService,
    downloadFinancePdfService,
    downloadFinanceSignedPdfService,
    openFinanceSignedPdfService
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
import Message from 'primevue/message';
import { useToast } from 'primevue/usetoast';
import { useUsersStore } from '@stores';
import { hasPermission } from '@utils/permissions';
import { getAllDepartementsService } from '@requests';
import { getStatusBadgeInfo } from '../utils/workflowConstants';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const userStore = useUsersStore();

const isNew = ref(!route.params.id || route.params.id === 'nouveau');
const loading = ref(false);
const saving = ref(false);
const uploadingSignedPdf = ref(false);
const showPdfModal = ref(false);
const pdfModalStep = ref(5);
const modalPdfLoading = ref(false);
const modalPdfBlobUrl = ref('');
const activeTab = ref('1');

const allDepartements = ref([]);

// Roles & Permissions
const isSuperAdmin = computed(() => {
    return hasPermission('SUPER_ADMIN') || Boolean(userStore?.isSuperAdmin);
});

const isFinanceManager = computed(() => {
    return isSuperAdmin.value ||
        hasPermission('ROLE_ADMIN') ||
        hasPermission('ROLE_FINANCE_MANAGER');
});

const isDirection = computed(() => {
    return isSuperAdmin.value ||
        hasPermission('ROLE_DIRECTION') ||
        hasPermission('isDirection');
});

// Saisie de l'avis réservée au gestionnaire de la décision (Direction / Service financier / Super admin)
const isDecisionManager = computed(() => {
    return isFinanceManager.value || isDirection.value;
});

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

const pdfStepOptions = [
    { label: 'Étape 1 : Demande initiale à émarger / signer', value: 1 },
    { label: 'Étape 2 : Vérification SIFAC', value: 2 },
    { label: 'Étape 3 : Visa engagement CSA', value: 3 },
    { label: 'Étape 4 : Constatation Service Fait', value: 4 },
    { label: 'Document complet (Toutes étapes validées)', value: 5 },
];

const selectedDeptId = ref(null);
const fileInputRef = ref(null);

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
    pdfFilePath: null,
    demandeur: null,
    departement: null,
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

// Auto-compute TTC from HT if not explicitly entered
watch(() => form.value.montantHt, (newHt) => {
    if (newHt && (!form.value.montantTtc || isNew.value)) {
        form.value.montantTtc = Math.round(Number(newHt) * 1.2 * 100) / 100;
    }
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

// État de complétion et de déverrouillage strict N -> N+1
const isStep1Completed = computed(() => {
    return form.value.avisDirecteur === 'FAVORABLE';
});

const isStep2Completed = computed(() => {
    return isStep1Completed.value && Boolean(form.value.dateVerificationFinanciere);
});

const isStep3Completed = computed(() => {
    return isStep2Completed.value && Boolean(form.value.dateVisaEngagement);
});

const isStep4Completed = computed(() => {
    return isStep3Completed.value && Boolean(currentReception.value?.dateEffectiveReception);
});

const isStep5Completed = computed(() => {
    return isStep4Completed.value && (Boolean(form.value.dateCertificationServiceFait) || Boolean(form.value.factureFinale) || Boolean(form.value.datePaiement));
});

// Déverrouillage des onglets
const isStep2Unlocked = computed(() => isStep1Completed.value);
const isStep3Unlocked = computed(() => isStep2Completed.value);
const isStep4Unlocked = computed(() => isStep3Completed.value);
const isStep5Unlocked = computed(() => isStep4Completed.value);

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

// Department options for selection
const departementSelectOptions = computed(() => {
    return allDepartements.value.map(d => ({
        label: d.libelle || `Département ${d.id}`,
        value: d.id
    }));
});

const currentDepartmentDisplay = computed(() => {
    if (form.value.departement?.libelle) {
        return form.value.departement.libelle;
    }
    if (selectedDeptId.value) {
        const found = allDepartements.value.find(d => d.id === selectedDeptId.value);
        if (found) return found.libelle;
    }
    return userStore.departementDefaut?.libelle || 'IUT';
});

const currentDemandeurDisplay = computed(() => {
    if (form.value.demandeur) {
        if (typeof form.value.demandeur === 'object') {
            return `${form.value.demandeur.prenom || ''} ${form.value.demandeur.nom || ''}`.trim();
        }
    }
    return userStore.user?.display ||
        (userStore.user?.prenom ? `${userStore.user.prenom} ${userStore.user.nom}` : 'Utilisateur connecté');
});

async function loadCommande() {
    if (isNew.value) {
        if (userStore.departementDefaut?.id) {
            selectedDeptId.value = userStore.departementDefaut.id;
        }
        return;
    }

    loading.value = true;
    try {
        const res = await getFinanceBonCommandeDetailService(route.params.id);
        form.value = { ...res.data };
        if (form.value.departement?.id) {
            selectedDeptId.value = form.value.departement.id;
        }
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

        if (!payload.fournisseur || !payload.fournisseur.nomFournisseur) {
            delete payload.fournisseur;
        }

        const targetDeptId = selectedDeptId.value || userStore.departementDefaut?.id;
        if (targetDeptId) {
            payload.departement = `/api/structure_departements/${targetDeptId}`;
        }

        if (userStore.user?.id) {
            payload.demandeur = `/api/personnels/${userStore.user.id}`;
        }

        if (isNew.value) {
            if (!payload.dateDemande) {
                payload.dateDemande = new Date().toISOString();
            }
            if (payload.montantHt !== null && payload.montantHt !== undefined) {
                payload.montantHt = String(payload.montantHt);
            }
            if (payload.montantTtc !== null && payload.montantTtc !== undefined) {
                payload.montantTtc = String(payload.montantTtc);
            }

            const res = await createFinanceBonCommandeService(payload);
            toast.add({ severity: 'success', summary: 'Succès', detail: 'Demande créée avec succès' });
            router.push({ name: 'finance_detail', params: { id: res.data.id } });
        } else {
            if (payload.demandeur && typeof payload.demandeur === 'object') {
                payload.demandeur = `/api/personnels/${payload.demandeur.id}`;
            }
            if (payload.departement && typeof payload.departement === 'object') {
                payload.departement = `/api/structure_departements/${payload.departement.id}`;
            }
            if (payload.gestionnaire && typeof payload.gestionnaire === 'object') {
                payload.gestionnaire = `/api/personnels/${payload.gestionnaire.id}`;
            }
            if (payload.signataireDirecteur && typeof payload.signataireDirecteur === 'object') {
                payload.signataireDirecteur = `/api/personnels/${payload.signataireDirecteur.id}`;
            }
            if (payload.montantHt !== null && payload.montantHt !== undefined) {
                payload.montantHt = String(payload.montantHt);
            }
            if (payload.montantTtc !== null && payload.montantTtc !== undefined) {
                payload.montantTtc = String(payload.montantTtc);
            }

            delete payload['@context'];
            delete payload['@id'];
            delete payload['@type'];
            delete payload.id;
            delete payload.createdAt;
            delete payload.updatedAt;

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

// Visas actions
function visaDemande() {
    form.value.statut = 'SOUMIS';
    if (!form.value.dateDemande) {
        form.value.dateDemande = new Date().toISOString();
    }
    save();
}

function visaDirecteur(favorable = true) {
    if (!isDecisionManager.value) {
        toast.add({ severity: 'warn', summary: 'Accès restreint', detail: 'Seul le gestionnaire de la décision (Direction / Finances) peut émettre cet avis.' });
        return;
    }
    form.value.avisDirecteur = favorable ? 'FAVORABLE' : 'DEFAVORABLE';
    form.value.dateAvisDirecteur = new Date().toISOString();
    if (userStore.user?.id) {
        form.value.signataireDirecteur = `/api/personnels/${userStore.user.id}`;
    }
    form.value.statut = favorable ? 'VERIFICATION_FINANCIERE' : 'REJETE';
    save();
}

function visaFinancier() {
    if (!isStep1Completed.value) {
        toast.add({ severity: 'error', summary: 'Étape bloquée', detail: 'L\'avis favorable de la direction est requis.' });
        return;
    }
    form.value.dateVerificationFinanciere = new Date().toISOString();
    if (userStore.user?.id) {
        form.value.verificateurFinancier = `/api/personnels/${userStore.user.id}`;
        form.value.gestionnaire = `/api/personnels/${userStore.user.id}`;
    }
    form.value.statut = 'VISA_ENGAGEMENT';
    save();
}

function visaEngagement() {
    if (!isStep2Completed.value) {
        toast.add({ severity: 'error', summary: 'Étape bloquée', detail: 'La vérification financière SIFAC doit être validée.' });
        return;
    }
    form.value.dateVisaEngagement = new Date().toISOString();
    if (userStore.user?.id) {
        form.value.signataireVisaEngagement = `/api/personnels/${userStore.user.id}`;
    }
    form.value.statut = 'COMMANDE_VALIDEE';
    save();
}

function visaConstatationServiceFait() {
    if (!isStep3Completed.value) {
        toast.add({ severity: 'error', summary: 'Étape bloquée', detail: 'Le visa d\'engagement CSA est requis avant la réception.' });
        return;
    }
    currentReception.value.dateEffectiveReception = new Date().toISOString();
    currentReception.value.dateSignatureServiceFait = new Date().toISOString();
    form.value.statut = 'SERVICE_FAIT_CONSTATE';
    save();
}

function visaCertification() {
    if (!isStep4Completed.value) {
        toast.add({ severity: 'error', summary: 'Étape bloquée', detail: 'La constatation du service fait est requise.' });
        return;
    }
    form.value.dateCertificationServiceFait = new Date().toISOString();
    if (userStore.user?.id) {
        form.value.certificateurServiceFait = `/api/personnels/${userStore.user.id}`;
    }
    form.value.statut = 'SERVICE_FAIT_CERTIFIE';
    save();
}

// Gestion des PDF
async function loadModalPdf() {
    if (isNew.value || !route.params.id) return;
    modalPdfLoading.value = true;
    if (modalPdfBlobUrl.value) {
        window.URL.revokeObjectURL(modalPdfBlobUrl.value);
        modalPdfBlobUrl.value = '';
    }
    try {
        const res = await getFinancePdfBlobService(route.params.id, pdfModalStep.value);
        const blob = new Blob([res.data], { type: 'application/pdf' });
        modalPdfBlobUrl.value = window.URL.createObjectURL(blob);
    } catch (err) {
        console.error('Erreur lors du chargement de l\'aperçu PDF:', err);
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger l\'aperçu PDF.' });
    } finally {
        modalPdfLoading.value = false;
    }
}

function openPdfModal() {
    showPdfModal.value = true;
    loadModalPdf();
}

watch(pdfModalStep, () => {
    if (showPdfModal.value) {
        loadModalPdf();
    }
});

watch(showPdfModal, (val) => {
    if (!val && modalPdfBlobUrl.value) {
        window.URL.revokeObjectURL(modalPdfBlobUrl.value);
        modalPdfBlobUrl.value = '';
    }
});

async function downloadStepPdf(step) {
    if (isNew.value || !route.params.id) {
        toast.add({ severity: 'info', summary: 'Enregistrement en cours', detail: 'Enregistrement préalable de la demande avant génération du PDF...' });
        await save();
        return;
    }
    try {
        toast.add({ severity: 'info', summary: 'Génération en cours', detail: 'Génération du PDF par le serveur...' });
        await downloadFinancePdfService(route.params.id, step);
        toast.add({ severity: 'success', summary: 'Téléchargement réussi', detail: 'Le document PDF a été téléchargé avec succès.' });
    } catch (error) {
        console.error('Erreur lors du téléchargement du PDF:', error);
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de générer le document PDF.' });
    }
}

async function openSignedPdf() {
    if (!form.value.pdfFilePath) return;
    try {
        await openFinanceSignedPdfService(route.params.id);
    } catch (error) {
        console.error('Erreur lors de l\'ouverture du PDF signé:', error);
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible d\'ouvrir le document PDF signé.' });
    }
}

function triggerFileInput() {
    fileInputRef.value?.click();
}

async function onFileSelected(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
        toast.add({ severity: 'error', summary: 'Fichier invalide', detail: 'Le document déposé doit être au format PDF.' });
        return;
    }

    if (isNew.value) {
        toast.add({ severity: 'warn', summary: 'Action requise', detail: 'Veuillez enregistrer la demande avant de téléverser le document signé.' });
        return;
    }

    uploadingSignedPdf.value = true;
    const formData = new FormData();
    formData.append('file', file);

    try {
        const res = await uploadFinanceSignedPdfService(route.params.id, formData);
        form.value.pdfFilePath = res.data.pdfFilePath;
        if (res.data.statut) {
            form.value.statut = res.data.statut;
        }
        toast.add({ severity: 'success', summary: 'Document enregistré', detail: 'Le PDF signé a été téléversé avec succès.' });
        await loadCommande();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Échec de l\'envoi du document signé.' });
    } finally {
        uploadingSignedPdf.value = false;
        if (event.target) {
            event.target.value = '';
        }
    }
}

function handleSelectTimelineStep(stepId) {
    activeTab.value = String(stepId);
}

onMounted(async () => {
    try {
        const deptsRes = await getAllDepartementsService();
        allDepartements.value = deptsRes.data || [];
    } catch {
        allDepartements.value = [];
    }
    await loadCommande();
});
</script>

<template>
    <div class="space-y-6">
        <!-- 1. En-tête avec bouton retour au format Questionnaire -->
        <HeaderComponent
            icon="pi pi-wallet"
            :titre="isNew ? 'Nouvelle demande d\'achat' : `Demande d'achat : ${form.objetDemande || ('#' + route.params.id)}`"
            :description="isNew ? 'Renseignez l\'objet et les montants pour initier la demande d\'achat.' : `Cycle de vie de la commande, validations séquentielles et génération des PDF.`"
            :show-back="true"
            @back="router.push('/finance')"
        >
            <template #actions>
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        v-if="!isNew && form.statut"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold shadow-2xs mr-1"
                        :class="getStatusBadgeInfo(form.statut).badgeClass"
                    >
                        <span class="w-2 h-2 rounded-full shrink-0" :class="getStatusBadgeInfo(form.statut).dotClass"></span>
                        <i :class="getStatusBadgeInfo(form.statut).icon" class="text-xs"></i>
                        <span>{{ getStatusBadgeInfo(form.statut).label }}</span>
                    </span>
                    <Button
                        v-if="!isNew"
                        label="Aperçu / Export PDF"
                        icon="pi pi-file-pdf"
                        severity="danger"
                        outlined
                        size="small"
                        @click="openPdfModal"
                    />
                    <Button
                        label="Enregistrer"
                        icon="pi pi-save"
                        severity="primary"
                        size="small"
                        :loading="saving"
                        @click="save"
                    />
                </div>
            </template>
        </HeaderComponent>

        <!-- 2. Workflow Timeline interactive -->
        <CustomCard v-if="!isNew" class="shadow-xs border border-gray-200 dark:border-gray-700">
            <BonCommandeWorkflowTimeline :commande="form" @select-step="handleSelectTimelineStep" />
        </CustomCard>

        <!-- 3. Formulaire par Onglets / Étapes du Workflow -->
        <Tabs v-model:value="activeTab">
            <TabList class="mb-4">
                <Tab value="1">
                    <span class="flex items-center gap-2">
                        <i class="pi pi-file-edit text-blue-700"></i>
                        <span>1. Demande & Avis</span>
                        <i v-if="isStep1Completed" class="pi pi-check text-emerald-600 text-xs"></i>
                    </span>
                </Tab>
                <Tab value="2">
                    <span class="flex items-center gap-2">
                        <i class="pi pi-search text-amber-700"></i>
                        <span>2. Vérification SIFAC</span>
                        <i v-if="isStep2Completed" class="pi pi-check text-emerald-600 text-xs"></i>
                        <i v-else-if="!isStep2Unlocked" class="pi pi-lock text-gray-400 text-xs"></i>
                    </span>
                </Tab>
                <Tab value="3">
                    <span class="flex items-center gap-2">
                        <i class="pi pi-check-circle text-purple-700"></i>
                        <span>3. Visa Engagement</span>
                        <i v-if="isStep3Completed" class="pi pi-check text-emerald-600 text-xs"></i>
                        <i v-else-if="!isStep3Unlocked" class="pi pi-lock text-gray-400 text-xs"></i>
                    </span>
                </Tab>
                <Tab value="4">
                    <span class="flex items-center gap-2">
                        <i class="pi pi-box text-teal-700"></i>
                        <span>4. Service Fait</span>
                        <i v-if="isStep4Completed" class="pi pi-check text-emerald-600 text-xs"></i>
                        <i v-else-if="!isStep4Unlocked" class="pi pi-lock text-gray-400 text-xs"></i>
                    </span>
                </Tab>
                <Tab value="5">
                    <span class="flex items-center gap-2">
                        <i class="pi pi-wallet text-rose-700"></i>
                        <span>5. Facture & Paiement</span>
                        <i v-if="isStep5Completed" class="pi pi-check text-emerald-600 text-xs"></i>
                        <i v-else-if="!isStep5Unlocked" class="pi pi-lock text-gray-400 text-xs"></i>
                    </span>
                </Tab>
            </TabList>

            <TabPanels>
                <!-- ========================================== -->
                <!-- Onglet 1 : DEMANDE INITIALE & AVIS         -->
                <!-- ========================================== -->
                <TabPanel value="1">
                    <div class="space-y-6">
                        <!-- Bloc 1 : Données de la demande -->
                        <Card class="shadow-xs border-l-4 border-l-blue-700">
                            <template #title>
                                <div class="text-base font-bold text-blue-900 dark:text-blue-300 flex items-center justify-between">
                                    <span>1 — DEMANDE D'ACHAT INITIALE</span>
                                    <Tag v-if="form.dateDemande" severity="success" :value="'Soumise le ' + new Date(form.dateDemande).toLocaleDateString('fr-FR')" />
                                </div>
                            </template>
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Demandeur et Département -->
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Demandeur</label>
                                        <InputText :value="currentDemandeurDisplay" disabled class="bg-gray-50 dark:bg-gray-800" />
                                    </div>

                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Département d'imputation *</label>
                                        <Select
                                            v-if="allDepartements.length > 1"
                                            v-model="selectedDeptId"
                                            :options="departementSelectOptions"
                                            option-label="label"
                                            option-value="value"
                                            placeholder="Sélectionner le département"
                                        />
                                        <InputText v-else :value="currentDepartmentDisplay" disabled class="bg-gray-50 dark:bg-gray-800" />
                                    </div>

                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Type de prestation *</label>
                                        <Select v-model="form.prestation" :options="typePrestationOptions" option-label="label" option-value="value" />
                                    </div>

                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Centre Financier</label>
                                        <InputText v-model="form.centreFinancier" placeholder="Ex: ACFA9B5FGL" />
                                    </div>

                                    <div class="flex flex-col gap-1 md:col-span-2">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Objet de la dépense (Libellé) *</label>
                                        <InputText v-model="form.objetDemande" placeholder="Ex: Matériel informatique Salle 104, Fournitures pédagogiques..." />
                                    </div>

                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Montant HT (€) *</label>
                                        <InputNumber v-model="form.montantHt" mode="currency" currency="EUR" locale="fr-FR" placeholder="0,00 €" />
                                    </div>

                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Montant TTC (€)</label>
                                        <InputNumber v-model="form.montantTtc" mode="currency" currency="EUR" locale="fr-FR" placeholder="Calculé automatiquement" />
                                    </div>

                                    <!-- Fournisseur suggéré -->
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Fournisseur suggéré (Raison sociale)</label>
                                        <InputText v-model="form.fournisseur.nomFournisseur" placeholder="Ex: DELL, TOUSSAINT, AMAZON..." />
                                    </div>

                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">N° Fournisseur SIFAC (si connu)</label>
                                        <InputText v-model="form.fournisseur.numeroFournisseur" placeholder="Ex: 1650, 1471..." />
                                    </div>

                                    <div class="flex flex-col gap-1 md:col-span-2">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Note explicative synthétique (contexte, motivation, objectifs pédagogiques)</label>
                                        <Textarea v-model="form.noteExplicative" rows="3" auto-resize placeholder="Détailler le besoin et la justification de la dépense..." />
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                    <span class="text-xs text-gray-500">
                                        {{ form.statut === 'BROUILLON' ? 'Statut actuel : Brouillon' : `Statut : ${form.statut}` }}
                                    </span>
                                    <div class="flex gap-2">
                                        <Button
                                            label="Télécharger le PDF à signer"
                                            icon="pi pi-file-pdf"
                                            severity="danger"
                                            outlined
                                            size="small"
                                            @click="downloadStepPdf(1)"
                                        />
                                        <Button
                                            v-if="form.statut === 'BROUILLON' || isNew"
                                            label="Valider et soumettre la demande"
                                            icon="pi pi-send"
                                            severity="info"
                                            size="small"
                                            @click="visaDemande"
                                        />
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <!-- Bloc 2 : Document PDF Signé (Upload & Suivi) -->
                        <Card class="shadow-xs border-l-4 border-l-blue-600">
                            <template #title>
                                <div class="text-base font-bold text-blue-900 dark:text-blue-300 flex items-center justify-between">
                                    <span>DOCUMENT DE LA DEMANDE SIGNÉ (ÉMARGEMENT EXTERNE)</span>
                                    <Tag v-if="form.pdfFilePath" severity="success" value="PDF signé disponible" />
                                    <Tag v-else severity="warn" value="En attente de signature" />
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-4">
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        Générez le PDF de la demande ci-dessus, faites-le émarger/signer par le responsable ou demandeur (via votre outil habituel ou manuscritement), puis déposez le document signé ici pour instruire la demande.
                                    </p>

                                    <!-- Zone d'état et téléversement -->
                                    <div class="p-4 rounded-lg border-2 border-dashed border-blue-200 dark:border-blue-900/60 bg-blue-50/50 dark:bg-blue-950/20 flex flex-col sm:flex-row items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-700 dark:text-blue-300">
                                                <i class="pi pi-file-pdf text-lg"></i>
                                            </div>
                                            <div>
                                                <div v-if="form.pdfFilePath" class="text-xs font-semibold text-blue-900 dark:text-blue-200">
                                                    {{ form.pdfFilePath.split('/').pop() }}
                                                </div>
                                                <div v-else class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                    Aucun document signé n'a été déposé pour le moment.
                                                </div>
                                                <div class="text-[11px] text-gray-500">
                                                    Formats acceptés : PDF uniquement
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <input
                                                ref="fileInputRef"
                                                type="file"
                                                accept="application/pdf"
                                                class="hidden"
                                                @change="onFileSelected"
                                            />
                                            <Button
                                                v-if="form.pdfFilePath"
                                                label="Consulter le PDF signé"
                                                icon="pi pi-eye"
                                                severity="success"
                                                size="small"
                                                @click="openSignedPdf"
                                            />
                                            <Button
                                                :label="form.pdfFilePath ? 'Remplacer le document' : 'Déposer le PDF signé'"
                                                icon="pi pi-upload"
                                                severity="info"
                                                size="small"
                                                :loading="uploadingSignedPdf"
                                                @click="triggerFileInput"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Card>

                        <!-- Bloc 3 : Avis du Directeur / Chef des services -->
                        <Card class="shadow-xs border-l-4 border-l-blue-700">
                            <template #title>
                                <div class="text-base font-bold text-blue-900 dark:text-blue-300 flex items-center justify-between">
                                    <span>AVIS DU DIRECTEUR / CHEF DE SERVICE</span>
                                    <Tag
                                        v-if="form.avisDirecteur === 'FAVORABLE'"
                                        severity="success"
                                        value="Avis Favorable"
                                    />
                                    <Tag
                                        v-else-if="form.avisDirecteur === 'DEFAVORABLE'"
                                        severity="danger"
                                        value="Avis Défavorable"
                                    />
                                    <Tag
                                        v-else-if="form.avisDirecteur === 'DEMANDE_INFO'"
                                        severity="warn"
                                        value="Demande d'information"
                                    />
                                    <Tag
                                        v-else
                                        severity="secondary"
                                        value="En attente de décision"
                                    />
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-4">
                                    <!-- Avertissement si l'utilisateur n'est pas gestionnaire de décision -->
                                    <div
                                        v-if="!isDecisionManager"
                                        class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900 rounded-lg text-xs text-amber-900 dark:text-amber-200"
                                    >
                                        <i class="pi pi-info-circle mr-1"></i>
                                        L'avis officiel est saisi uniquement par le gestionnaire de la décision (Direction / Responsable financier).
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="flex flex-col gap-1">
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Décision</label>
                                            <Select
                                                v-if="isDecisionManager"
                                                v-model="form.avisDirecteur"
                                                :options="avisDirecteurOptions"
                                                option-label="label"
                                                option-value="value"
                                                placeholder="Sélectionner l'avis de la direction"
                                                show-clear
                                            />
                                            <InputText
                                                v-else
                                                :value="form.avisDirecteur ? (avisDirecteurOptions.find(o => o.value === form.avisDirecteur)?.label || form.avisDirecteur) : 'Non encore statué'"
                                                disabled
                                                class="bg-gray-50 dark:bg-gray-800"
                                            />
                                        </div>

                                        <div class="flex flex-col gap-1">
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Date de l'avis</label>
                                            <InputText :value="form.dateAvisDirecteur ? new Date(form.dateAvisDirecteur).toLocaleDateString('fr-FR') : 'Non visé'" disabled class="bg-gray-50 dark:bg-gray-800" />
                                        </div>

                                        <div class="flex flex-col gap-1 md:col-span-2" v-if="isDecisionManager || form.avisDirecteurCommentaire">
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Commentaire / Demande de précisions</label>
                                            <InputText
                                                v-model="form.avisDirecteurCommentaire"
                                                :disabled="!isDecisionManager"
                                                placeholder="Préciser les motivations ou les compléments d'informations nécessaires..."
                                                :class="{ 'bg-gray-50 dark:bg-gray-800': !isDecisionManager }"
                                            />
                                        </div>
                                    </div>

                                    <!-- Boutons réservés au gestionnaire de décision -->
                                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2" v-if="isDecisionManager">
                                        <Button
                                            label="Avis Favorable"
                                            icon="pi pi-thumbs-up"
                                            severity="success"
                                            size="small"
                                            @click="visaDirecteur(true)"
                                        />
                                        <Button
                                            label="Avis Défavorable"
                                            icon="pi pi-thumbs-down"
                                            severity="danger"
                                            outlined
                                            size="small"
                                            @click="visaDirecteur(false)"
                                        />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </TabPanel>

                <!-- ========================================== -->
                <!-- Onglet 2 : VÉRIFICATION SIFAC              -->
                <!-- ========================================== -->
                <TabPanel value="2">
                    <div class="space-y-4">
                        <!-- Message de verrouillage si Étape 1 non validée -->
                        <Message v-if="!isStep2Unlocked" severity="warn" :closable="false">
                            <span class="font-semibold">Étape 2 verrouillée :</span>
                            L'avis favorable du Directeur ou Chef de service sur la demande initiale (Étape 1) est obligatoire pour procéder à la vérification financière SIFAC.
                        </Message>

                        <Card class="shadow-xs border-l-4 border-l-amber-600">
                            <template #title>
                                <div class="text-base font-bold text-amber-900 dark:text-amber-300 flex items-center justify-between">
                                    <span>2 — VÉRIFICATION SERVICE FINANCIER (SIFAC)</span>
                                    <Tag v-if="form.dateVerificationFinanciere" severity="success" :value="'Vérifié le ' + new Date(form.dateVerificationFinanciere).toLocaleDateString('fr-FR')" />
                                </div>
                            </template>
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">N° Fournisseur SIFAC</label>
                                        <InputText v-model="form.fournisseur.numeroFournisseur" :disabled="!isStep2Unlocked" placeholder="Ex: 1650, 1471" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Nom du fournisseur</label>
                                        <InputText v-model="form.fournisseur.nomFournisseur" :disabled="!isStep2Unlocked" placeholder="Ex: ELITE SECURITE, TOUSSAINT" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">N° Bon de Commande SIFAC *</label>
                                        <InputText v-model="form.numeroBonCommande" :disabled="!isStep2Unlocked" placeholder="Ex: 4500264424" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Colis attendu</label>
                                        <InputText v-model="form.colisAttendu" :disabled="!isStep2Unlocked" placeholder="Informations livraison / colis" />
                                    </div>

                                    <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center gap-2">
                                            <Checkbox v-model="form.commandeSurMarche" :disabled="!isStep2Unlocked" :binary="true" input-id="csm" />
                                            <label for="csm" class="text-xs font-medium">Commande sur marché</label>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Checkbox v-model="form.dateLivraisonRenseignee" :disabled="!isStep2Unlocked" :binary="true" input-id="dlr" />
                                            <label for="dlr" class="text-xs font-medium">Date livraison renseignée</label>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Checkbox v-model="form.pjSurSifac" :disabled="!isStep2Unlocked" :binary="true" input-id="pjs" />
                                            <label for="pjs" class="text-xs font-medium">PJ rattachée SIFAC</label>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Checkbox v-model="form.bcTransmis" :disabled="!isStep2Unlocked" :binary="true" input-id="bct" />
                                            <label for="bct" class="text-xs font-medium">BC transmis fournisseur</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center">
                                    <span class="text-xs text-gray-500">
                                        {{ form.dateVerificationFinanciere ? ('Vérifié le ' + new Date(form.dateVerificationFinanciere).toLocaleDateString('fr-FR')) : 'Non encore visé par le service financier' }}
                                    </span>
                                    <div class="flex gap-2">
                                        <Button
                                            label="Télécharger le PDF (Étape 2)"
                                            icon="pi pi-file-pdf"
                                            severity="danger"
                                            outlined
                                            size="small"
                                            @click="downloadStepPdf(2)"
                                        />
                                        <Button
                                            v-if="isFinanceManager && isStep2Unlocked"
                                            label="Valider la vérification financière"
                                            icon="pi pi-check"
                                            severity="warn"
                                            size="small"
                                            @click="visaFinancier"
                                        />
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </TabPanel>

                <!-- ========================================== -->
                <!-- Onglet 3 : VISA ENGAGEMENT CSA             -->
                <!-- ========================================== -->
                <TabPanel value="3">
                    <div class="space-y-4">
                        <Message v-if="!isStep3Unlocked" severity="warn" :closable="false">
                            <span class="font-semibold">Étape 3 verrouillée :</span>
                            La vérification financière SIFAC (Étape 2) doit être préalablement validée pour accorder le visa d'engagement budgétaire.
                        </Message>

                        <Card class="shadow-xs border-l-4 border-l-purple-700">
                            <template #title>
                                <div class="text-base font-bold text-purple-900 dark:text-purple-300 flex items-center justify-between">
                                    <span>3 — VISA CHEF DES SERVICES ADMINISTRATIFS / RESPONSABLE FINANCIER</span>
                                    <Tag v-if="form.dateVisaEngagement" severity="success" :value="'Visa accordé le ' + new Date(form.dateVisaEngagement).toLocaleDateString('fr-FR')" />
                                </div>
                            </template>
                            <template #content>
                                <div class="p-4 bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-900 rounded-lg mb-4">
                                    <p class="text-xs text-purple-950 dark:text-purple-200 font-medium">
                                        Visa d'engagement budgétaire et d'autorisation formelle de passation de la commande vers le fournisseur.
                                    </p>
                                    <div class="mt-2 text-xs text-purple-800 dark:text-purple-300">
                                        Date du visa : <strong>{{ form.dateVisaEngagement ? new Date(form.dateVisaEngagement).toLocaleDateString('fr-FR') : 'En attente de visa' }}</strong>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center pt-2" v-if="isStep3Unlocked">
                                    <Button
                                        label="Télécharger le PDF (Étape 3)"
                                        icon="pi pi-file-pdf"
                                        severity="danger"
                                        outlined
                                        size="small"
                                        @click="downloadStepPdf(3)"
                                    />
                                    <Button
                                        v-if="isFinanceManager"
                                        label="Accorder le Visa d'engagement (CSA)"
                                        icon="pi pi-check-circle"
                                        severity="help"
                                        size="small"
                                        @click="visaEngagement"
                                    />
                                </div>
                            </template>
                        </Card>
                    </div>
                </TabPanel>

                <!-- ========================================== -->
                <!-- Onglet 4 : CONSTATATION DU SERVICE FAIT    -->
                <!-- ========================================== -->
                <TabPanel value="4">
                    <div class="space-y-4">
                        <Message v-if="!isStep4Unlocked" severity="warn" :closable="false">
                            <span class="font-semibold">Étape 4 verrouillée :</span>
                            Le visa d'engagement CSA (Étape 3) doit être accordé avant de procéder à la constatation du service fait ou à la réception des biens.
                        </Message>

                        <Card class="shadow-xs border-l-4 border-l-teal-600">
                            <template #title>
                                <div class="text-base font-bold text-teal-900 dark:text-teal-300 flex items-center justify-between">
                                    <span>4 — CONSTATATION SERVICE FAIT (RÉCEPTION)</span>
                                    <Tag v-if="currentReception.dateEffectiveReception" severity="success" :value="'Reçu le ' + new Date(currentReception.dateEffectiveReception).toLocaleDateString('fr-FR')" />
                                </div>
                            </template>
                            <template #content>
                                <div class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="flex flex-col gap-1">
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Qualité du signataire / réceptionnaire</label>
                                            <InputText v-model="currentReception.qualiteReceptionnaire" :disabled="!isStep4Unlocked" placeholder="Ex: Demandeur, Enseignant, Technicien..." />
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Type de réception</label>
                                            <Select v-model="currentReception.typeReception" :disabled="!isStep4Unlocked" :options="typeReceptionOptions" option-label="label" option-value="value" />
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Montant TTC constaté (€)</label>
                                            <InputNumber v-model="currentReception.montantTtcConstate" :disabled="!isStep4Unlocked" mode="currency" currency="EUR" locale="fr-FR" />
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Prestations conformes</label>
                                            <div class="flex items-center gap-2 mt-2">
                                                <Checkbox v-model="currentReception.prestationsConformes" :disabled="!isStep4Unlocked" :binary="true" input-id="conf" />
                                                <label for="conf" class="text-xs font-medium">Biens / prestations livrés conformes</label>
                                            </div>
                                        </div>

                                        <!-- Numéros MIGO -->
                                        <div class="md:col-span-2 flex flex-col gap-1">
                                            <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Numéros MIGO (réception SIFAC)</label>
                                            <div class="flex gap-2">
                                                <InputText v-model="newMigoInput" :disabled="!isStep4Unlocked" placeholder="Nouveau numéro MIGO (ex: 5000412345)" class="flex-1" @keyup.enter="addMigo" />
                                                <Button label="Ajouter MIGO" icon="pi pi-plus" size="small" :disabled="!isStep4Unlocked" @click="addMigo" />
                                            </div>
                                            <div class="flex flex-wrap gap-1 mt-2" v-if="currentReception.numerosMigo?.length">
                                                <Tag v-for="(migo, idx) in currentReception.numerosMigo" :key="idx" :value="migo" severity="info" class="cursor-pointer" @click="removeMigo(idx)" title="Cliquer pour supprimer" />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center" v-if="isStep4Unlocked">
                                        <Button
                                            label="Télécharger le PDF (Étape 4)"
                                            icon="pi pi-file-pdf"
                                            severity="danger"
                                            outlined
                                            size="small"
                                            @click="downloadStepPdf(4)"
                                        />
                                        <div class="flex gap-2">
                                            <Button label="Signer la constatation de Service Fait" icon="pi pi-box" severity="success" size="small" @click="visaConstatationServiceFait" />
                                            <Button v-if="isFinanceManager" label="Certifier le Service Fait (SF)" icon="pi pi-verified" severity="help" size="small" @click="visaCertification" />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Card>
                    </div>
                </TabPanel>

                <!-- ========================================== -->
                <!-- Onglet 5 : FACTURE & PAIEMENT              -->
                <!-- ========================================== -->
                <TabPanel value="5">
                    <div class="space-y-4">
                        <Message v-if="!isStep5Unlocked" severity="warn" :closable="false">
                            <span class="font-semibold">Étape 5 verrouillée :</span>
                            La constatation effective du service fait (Étape 4) doit être réalisée avant le traitement de la facture et le paiement.
                        </Message>

                        <Card class="shadow-xs border-l-4 border-l-rose-600">
                            <template #title>
                                <div class="text-base font-bold text-rose-900 dark:text-rose-300 flex items-center justify-between">
                                    <span>5 — FACTURE & PAIEMENT DU SERVICE FINANCIER</span>
                                    <Tag v-if="form.factureFinale" severity="success" value="Facture Soldée" />
                                </div>
                            </template>
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Date d'arrivée de la facture</label>
                                        <InputText v-model="form.dateArriveeFacture" :disabled="!isStep5Unlocked" type="date" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Date de paiement effectif</label>
                                        <InputText v-model="form.datePaiement" :disabled="!isStep5Unlocked" type="date" />
                                    </div>
                                    <div class="flex flex-col gap-1 md:col-span-2">
                                        <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">Motif de rejet (le cas échéant)</label>
                                        <InputText v-model="form.rejetFactureMotif" :disabled="!isStep5Unlocked" placeholder="Motif si la facture a été rejetée ou retournée au fournisseur..." />
                                    </div>
                                    <div class="md:col-span-2 flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <Checkbox v-model="form.factureFinale" :disabled="!isStep5Unlocked" :binary="true" input-id="ff" />
                                        <label for="ff" class="text-xs font-semibold text-gray-900 dark:text-white">Facture finale soldée (Clôture de la commande)</label>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                                    <Button
                                        label="Télécharger le PDF complet (Étape 5)"
                                        icon="pi pi-file-pdf"
                                        severity="danger"
                                        outlined
                                        size="small"
                                        @click="downloadStepPdf(5)"
                                    />
                                </div>
                            </template>
                        </Card>
                    </div>
                </TabPanel>
            </TabPanels>
        </Tabs>

        <!-- Modal Aperçu PDF avec sélecteur d'étape -->
        <Dialog
            v-model:visible="showPdfModal"
            header="Aperçu & Exportation PDF du Bon de Commande"
            modal
            class="w-full max-w-5xl h-[90vh]"
        >
            <div class="space-y-3 h-full flex flex-col">
                <div class="flex items-center justify-between gap-4 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Sélectionner l'étape à afficher :</span>
                        <Select
                            v-model="pdfModalStep"
                            :options="pdfStepOptions"
                            option-label="label"
                            option-value="value"
                            class="w-80 text-xs"
                        />
                    </div>
                    <Button
                        label="Télécharger ce PDF"
                        icon="pi pi-download"
                        severity="danger"
                        size="small"
                        outlined
                        @click="downloadStepPdf(pdfModalStep)"
                    />
                </div>

                <div v-if="modalPdfLoading" class="flex-1 w-full min-h-[500px] flex flex-col items-center justify-center gap-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <i class="pi pi-spin pi-spinner text-3xl text-primary"></i>
                    <span class="text-sm text-gray-500">Génération du PDF par le serveur...</span>
                </div>
                <div v-else-if="modalPdfBlobUrl" class="flex-1 w-full min-h-[500px]">
                    <iframe
                        :src="modalPdfBlobUrl"
                        class="w-full h-full min-h-[520px] border border-gray-200 dark:border-gray-700 rounded-lg"
                    ></iframe>
                </div>
                <div v-else class="flex-1 w-full min-h-[500px] flex items-center justify-center text-gray-400 border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                    Aucun aperçu disponible
                </div>
            </div>
            <template #footer>
                <div class="flex justify-end w-full">
                    <Button label="Fermer" severity="secondary" @click="showPdfModal = false" />
                </div>
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
</style>
