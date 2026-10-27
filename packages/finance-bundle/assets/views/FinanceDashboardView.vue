<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import {
    HeaderComponent,
    Kpi,
    Card,
    EmptyState,
    ActionButtonVertical
} from '@components';
import {
    DocumentTextIcon,
    BanknotesIcon,
    ClockIcon,
    ShoppingBagIcon,
    CheckCircleIcon,
    PlusIcon,
    BuildingOfficeIcon,
    EyeIcon,
    PencilSquareIcon,
    ArrowPathIcon,
    FunnelIcon
} from '@heroicons/vue/24/outline';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Select from 'primevue/select';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Skeleton from 'primevue/skeleton';
import { useToast } from 'primevue/usetoast';
import { FilterMatchMode } from '@primevue/core/api';
import { useUsersStore } from '@stores';
import { hasPermission } from '@utils/permissions';
import { getAllDepartementsService } from '@requests';
import { getFinanceBonsCommandeService, getFinancePdfBlobService, downloadFinancePdfService } from '@/requests/financeService';
import BonCommandeWorkflowTimeline from '../components/BonCommandeWorkflowTimeline.vue';
import { STATUS_WORKFLOW_MAP, getStatusBadgeInfo, WORKFLOW_STEPS } from '../utils/workflowConstants';

const router = useRouter();
const toast = useToast();
const userStore = useUsersStore();

const loading = ref(false);
const bonsCommande = ref([]);
const selectedCommande = ref(null);
const showDetailDialog = ref(false);
const showPdfModal = ref(false);
const pdfModalId = ref(null);
const pdfBlobUrl = ref('');
const pdfLoading = ref(false);
const expandedRows = ref({});

// Scope & Role-based filtering
const selectedScope = ref('all'); // 'all' | 'dept' | 'my' | 'avis'
const selectedDeptFilter = ref(null);
const allDepartements = ref([]);

// Global search and status filters for DataTable
const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    statut: { value: null, matchMode: FilterMatchMode.EQUALS },
    prestation: { value: null, matchMode: FilterMatchMode.EQUALS },
});

// Roles determination
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

const isChefDepartement = computed(() => {
    return hasPermission('ROLE_CHEF_DEPARTEMENT') ||
        hasPermission('ROLE_CHEF_DEPT') ||
        hasPermission('isChefDepartement');
});

const userDepartmentId = computed(() => {
    return userStore?.departementDefaut?.id || null;
});

const userDepartmentName = computed(() => {
    return userStore?.departementDefaut?.libelle ||
        userStore?.departementDefaut?.sigle ||
        'Mon département';
});

const canViewAll = computed(() => {
    return isFinanceManager.value || isDirection.value;
});

// Default scope based on user role
function initDefaultScope() {
    if (canViewAll.value) {
        selectedScope.value = 'all';
    } else if (isChefDepartement.value && userDepartmentId.value) {
        selectedScope.value = 'dept';
    } else {
        selectedScope.value = 'my';
    }
}

// Department options for global managers
const departementOptions = computed(() => {
    const list = [
        { label: 'Tous les départements', value: null },
    ];
    allDepartements.value.forEach(d => {
        if (d && (d.id || d.libelle)) {
            list.push({
                label: d.libelle || `Département ${d.id}`,
                value: d.id
            });
        }
    });
    return list;
});

const statusOptions = Object.entries(STATUS_WORKFLOW_MAP).map(([key, val]) => ({
    label: val.fullLabel,
    value: key,
    severity: val.severity,
}));

const prestationOptions = [
    { label: 'Toutes prestations', value: null },
    { label: 'Fournitures', value: 'Fournitures' },
    { label: 'Services', value: 'Services' },
    { label: 'Travaux', value: 'Travaux' },
];

function getStatusBadge(statut) {
    return getStatusBadgeInfo(statut);
}

// Current scope labels
const currentScopeLabel = computed(() => {
    switch (selectedScope.value) {
        case 'my':
            return 'Mes demandes personnelles';
        case 'dept':
            return `Département ${userDepartmentName.value}`;
        case 'avis':
            return 'Commandes en attente de visa / validation';
        case 'all':
        default:
            return selectedDeptFilter.value
                ? `Département ${departementOptions.value.find(d => d.value === selectedDeptFilter.value)?.label || ''}`
                : 'Vue Globale IUT — Toutes les commandes';
    }
});

const currentScopeBadge = computed(() => {
    switch (selectedScope.value) {
        case 'my':
            return 'Mes demandes';
        case 'dept':
            return 'Mon département';
        case 'avis':
            return 'À viser';
        case 'all':
        default:
            return 'Tous départements';
    }
});

// Data Loading
async function loadData() {
    loading.value = true;
    try {
        const params = {};

        // API-side filtering according to scope
        if (selectedScope.value === 'my') {
            if (userStore.user?.id) {
                params['demandeur.id'] = userStore.user.id;
            }
        } else if (selectedScope.value === 'dept') {
            if (userDepartmentId.value) {
                params['departement.id'] = userDepartmentId.value;
            }
        } else if (selectedScope.value === 'all' && selectedDeptFilter.value) {
            params['departement.id'] = selectedDeptFilter.value;
        }

        const response = await getFinanceBonsCommandeService(params);
        const raw = response?.data;
        let list = [];
        if (Array.isArray(raw)) {
            list = raw;
        } else if (raw && Array.isArray(raw['hydra:member'])) {
            list = raw['hydra:member'];
        } else if (raw && Array.isArray(raw['member'])) {
            list = raw['member'];
        } else if (raw && Array.isArray(raw['data'])) {
            list = raw['data'];
        }

        // Client-side fallback / refinement (e.g. for scope 'avis')
        if (selectedScope.value === 'avis') {
            list = list.filter(item =>
                ['SOUMIS', 'AVIS_DIRECTION', 'VERIFICATION_FINANCIERE', 'VISA_ENGAGEMENT'].includes(item.statut)
            );
        } else if (selectedScope.value === 'my' && userStore.user?.id) {
            list = list.filter(item => {
                const dId = item.demandeur?.id ?? (typeof item.demandeur === 'string' ? parseInt(item.demandeur.split('/').pop(), 10) : null);
                return dId === userStore.user.id;
            });
        }

        bonsCommande.value = list;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Erreur',
            detail: 'Impossible de charger les bons de commande',
            life: 4000,
        });
        bonsCommande.value = [];
    } finally {
        loading.value = false;
    }
}

function setScope(scope) {
    selectedScope.value = scope;
    loadData();
}

watch(selectedDeptFilter, () => {
    if (selectedScope.value === 'all') {
        loadData();
    }
});

// KPIs computation
const totalEngageTtc = computed(() => {
    if (!Array.isArray(bonsCommande.value)) return 0;
    return bonsCommande.value.reduce((sum, item) => sum + parseFloat(item.montantTtc || item.montantHt || 0), 0);
});

const countAViser = computed(() => {
    if (!Array.isArray(bonsCommande.value)) return 0;
    return bonsCommande.value.filter(b =>
        ['SOUMIS', 'AVIS_DIRECTION', 'VERIFICATION_FINANCIERE', 'VISA_ENGAGEMENT'].includes(b.statut)
    ).length;
});

const countEnAttenteSf = computed(() => {
    if (!Array.isArray(bonsCommande.value)) return 0;
    return bonsCommande.value.filter(b =>
        ['COMMANDE_VALIDEE', 'COMMANDE_TRANSMISE', 'SERVICE_FAIT_CONSTATE'].includes(b.statut)
    ).length;
});

const countPayes = computed(() => {
    if (!Array.isArray(bonsCommande.value)) return 0;
    return bonsCommande.value.filter(b =>
        ['PAYE', 'CLOTURE'].includes(b.statut)
    ).length;
});

// Helpers
function formatDate(val) {
    if (!val) return '-';
    const d = new Date(val);
    return isNaN(d.getTime()) ? '-' : d.toLocaleDateString('fr-FR');
}

function formatCurrency(val) {
    if (!val && val !== 0) return '-';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val);
}

function getDemandeurLabel(data) {
    if (!data.demandeur) return '-';
    if (typeof data.demandeur === 'object') {
        if (data.demandeur.prenom || data.demandeur.nom) {
            return `${data.demandeur.prenom || ''} ${data.demandeur.nom || ''}`.trim();
        }
        return `Personnel #${data.demandeur.id}`;
    }
    return String(data.demandeur);
}

function openDetail(data) {
    selectedCommande.value = data;
    showDetailDialog.value = true;
}

function openEditView(id) {
    router.push({ name: 'finance_detail', params: { id } });
}

function createNewDemande() {
    router.push({ name: 'finance_demande_new' });
}

async function previewPdf(id) {
    pdfModalId.value = id;
    showPdfModal.value = true;
    pdfLoading.value = true;
    if (pdfBlobUrl.value) {
        window.URL.revokeObjectURL(pdfBlobUrl.value);
        pdfBlobUrl.value = '';
    }
    try {
        const res = await getFinancePdfBlobService(id, 5);
        const blob = new Blob([res.data], { type: 'application/pdf' });
        pdfBlobUrl.value = window.URL.createObjectURL(blob);
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger le document PDF' });
    } finally {
        pdfLoading.value = false;
    }
}

watch(showPdfModal, (val) => {
    if (!val && pdfBlobUrl.value) {
        window.URL.revokeObjectURL(pdfBlobUrl.value);
        pdfBlobUrl.value = '';
    }
});

onMounted(async () => {
    initDefaultScope();
    try {
        const deptsRes = await getAllDepartementsService();
        allDepartements.value = deptsRes.data || [];
    } catch {
        allDepartements.value = [];
    }
    await loadData();
});
</script>

<template>
    <div class="space-y-6">
        <!-- 1. En-tête principal au format Questionnaire -->
        <HeaderComponent
            icon="pi pi-wallet"
            titre="Finances & Achats"
            description="Pilotez les commandes, suivez les engagements budgétaires, les visas et le service fait."
        >
            <template #actions>
                <ActionButtonVertical
                    :to="{ name: 'finance_demande_new' }"
                    :icon="PlusIcon"
                    label="Nouvelle demande"
                    severity="success"
                />
            </template>
        </HeaderComponent>

        <!-- 2. Barre de Périmètre & Rôle (alignée sur le style du Dashboard Questionnaire) -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-4.5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                    <BuildingOfficeIcon class="w-6 h-6" />
                </div>
                <div>
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Périmètre d'affichage
                    </div>
                    <div class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2 mt-0.5">
                        <span>{{ currentScopeLabel }}</span>
                        <Tag :value="currentScopeBadge" severity="info" class="text-xs font-semibold" />
                    </div>
                </div>
            </div>

            <!-- Boutons de bascule de vue selon le rôle -->
            <div class="flex flex-wrap items-center gap-2.5">
                <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-800 p-1 border border-slate-200 dark:border-slate-700">
                    <button
                        v-if="canViewAll"
                        type="button"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                            selectedScope === 'all'
                                ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 shadow-xs'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
                        ]"
                        @click="setScope('all')"
                    >
                        Toutes les commandes
                    </button>
                    <button
                        v-if="userDepartmentId"
                        type="button"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                            selectedScope === 'dept'
                                ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 shadow-xs'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
                        ]"
                        @click="setScope('dept')"
                    >
                        Mon département
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                            selectedScope === 'my'
                                ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 shadow-xs'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
                        ]"
                        @click="setScope('my')"
                    >
                        Mes demandes
                    </button>
                    <button
                        v-if="canViewAll || isDirection"
                        type="button"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all',
                            selectedScope === 'avis'
                                ? 'bg-white dark:bg-slate-900 text-amber-700 dark:text-amber-400 shadow-xs'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
                        ]"
                        @click="setScope('avis')"
                    >
                        À viser ({{ countAViser }})
                    </button>
                </div>

                <!-- Dropdown pour filtrer par département lorsque vue globale -->
                <Select
                    v-if="canViewAll && selectedScope === 'all' && departementOptions.length > 1"
                    v-model="selectedDeptFilter"
                    :options="departementOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Tous départements"
                    class="w-56 text-xs"
                />

                <Button
                    icon="pi pi-refresh"
                    size="small"
                    text
                    rounded
                    severity="secondary"
                    :loading="loading"
                    title="Actualiser les données"
                    @click="loadData"
                />
            </div>
        </div>

        <!-- 3. KPI Cards pertinentes (composant Kpi unifié) -->
        <section aria-label="Indicateurs financiers clés" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <Kpi
                label="Commandes dans le périmètre"
                :value="bonsCommande.length"
                :icon="DocumentTextIcon"
                color="blue"
            />
            <Kpi
                label="Montant total engagé (TTC)"
                :value="formatCurrency(totalEngageTtc)"
                :icon="BanknotesIcon"
                color="teal"
            />
            <Kpi
                label="Demandes à valider / viser"
                :value="countAViser"
                :icon="ClockIcon"
                color="orange"
            />
            <Kpi
                label="En attente Service Fait"
                :value="countEnAttenteSf"
                :icon="ShoppingBagIcon"
                color="yellow"
            />
        </section>

        <!-- 4. Tableau Principal Compact et Responsive -->
        <Card body-class="overflow-hidden">
            <DataTable
                v-model:filters="filters"
                v-model:expandedRows="expandedRows"
                :value="bonsCommande"
                :loading="loading"
                data-key="id"
                filter-display="row"
                striped-rows
                paginator
                :rows="15"
                :rows-per-page-options="[10, 15, 30, 50]"
                responsive-layout="scroll"
                class="ui-table-compact"
            >
                <template #header>
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-white">Registre des Commandes</h3>
                            <p class="text-xs text-gray-550 dark:text-gray-400">Cliquez sur une ligne pour déplier le détail (SIFAC, MIGO, factures) sans encombrer la vue.</p>
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <IconField class="w-full sm:w-64">
                                <InputIcon>
                                    <i class="pi pi-search" />
                                </InputIcon>
                                <InputText
                                    v-model="filters['global'].value"
                                    placeholder="Rechercher..."
                                    class="w-full py-1.5 px-3 text-sm rounded-lg border border-gray-300 dark:border-gray-700"
                                />
                            </IconField>
                        </div>
                    </div>
                </template>

                <template #empty>
                    <div class="text-center py-10">
                        <DocumentTextIcon class="w-12 h-12 text-gray-400 mx-auto mb-2" />
                        <p class="text-gray-600 dark:text-gray-400 font-medium">Aucun bon de commande trouvé pour ce périmètre.</p>
                        <div class="mt-3">
                            <Button label="Créer une nouvelle demande" icon="pi pi-plus" size="small" @click="createNewDemande" />
                        </div>
                    </div>
                </template>

                <template #loading>
                    <div class="p-4 space-y-3">
                        <Skeleton height="2.5rem" />
                        <Skeleton height="2.5rem" />
                        <Skeleton height="2.5rem" />
                    </div>
                </template>

                <!-- Expander pour voir les détails SIFAC / MIGO sans colonne supplémentaire -->
                <Column expander style="width: 3rem" />

                <!-- Réf / N° BC SIFAC -->
                <Column field="numeroBonCommande" header="Réf. / N° BC" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <div class="flex flex-col">
                            <span v-if="data.numeroBonCommande" class="font-mono text-xs font-bold text-blue-700 dark:text-blue-400">
                                #{{ data.numeroBonCommande }}
                            </span>
                            <span v-else class="text-xs text-gray-400 italic">
                                Demande #{{ data.id }}
                            </span>
                            <span v-if="data.centreFinancier" class="text-[10px] text-gray-500 font-mono">
                                CF: {{ data.centreFinancier }}
                            </span>
                        </div>
                    </template>
                </Column>

                <!-- Objet & Prestation -->
                <Column field="objetDemande" header="Objet de la dépense" sortable style="min-width: 14rem">
                    <template #body="{ data }">
                        <div class="flex flex-col">
                            <span class="font-semibold text-gray-900 dark:text-white line-clamp-1" :title="data.objetDemande">
                                {{ data.objetDemande || 'Sans objet' }}
                            </span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[11px] px-1.5 py-0.2 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium">
                                    {{ data.prestation || 'Fournitures' }}
                                </span>
                                <span v-if="data.fournisseur?.nomFournisseur" class="text-xs text-gray-500 truncate max-w-[150px]" :title="data.fournisseur.nomFournisseur">
                                    {{ data.fournisseur.nomFournisseur }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Column>

                <!-- Département (badge avec couleur) -->
                <Column field="departement" header="Département" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span
                            v-if="data.departement"
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                            :style="{
                                backgroundColor: data.departement.couleur ? `${data.departement.couleur}15` : '#f0fdf4',
                                color: data.departement.couleur || '#16a34a',
                                borderColor: data.departement.couleur ? `${data.departement.couleur}40` : '#bbf7d0'
                            }"
                        >
                            <span
                                class="w-2 h-2 rounded-full shrink-0"
                                :style="{ backgroundColor: data.departement.couleur || '#16a34a' }"
                            ></span>
                            <span class="truncate max-w-[110px]">{{ data.departement.libelle || `Dpt ${data.departement.id}` }}</span>
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium text-slate-600 bg-slate-100 dark:bg-slate-800 border border-slate-200"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            <span>IUT</span>
                        </span>
                    </template>
                </Column>

                <!-- Demandeur -->
                <Column field="demandeur" header="Demandeur" sortable style="min-width: 9rem">
                    <template #body="{ data }">
                        <span class="text-xs text-gray-700 dark:text-gray-300">
                            {{ getDemandeurLabel(data) }}
                        </span>
                    </template>
                </Column>

                <!-- Montant TTC (avec HT) -->
                <Column field="montantTtc" header="Montant" sortable style="min-width: 8rem; text-align: right">
                    <template #body="{ data }">
                        <div class="flex flex-col items-end">
                            <span class="font-bold text-xs text-gray-900 dark:text-white">
                                {{ formatCurrency(data.montantTtc || data.montantHt) }}
                            </span>
                            <span v-if="data.montantHt" class="text-[10px] text-gray-400">
                                {{ formatCurrency(data.montantHt) }} HT
                            </span>
                        </div>
                    </template>
                </Column>

                <!-- Statut / Étape -->
                <Column field="statut" header="Statut / Avancement" :show-filter-menu="false" style="min-width: 13rem">
                    <template #body="{ data }">
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shadow-2xs"
                            :class="getStatusBadge(data.statut).badgeClass"
                        >
                            <span class="w-2 h-2 rounded-full shrink-0" :class="getStatusBadge(data.statut).dotClass"></span>
                            <i :class="getStatusBadge(data.statut).icon" class="text-xs"></i>
                            <span>{{ getStatusBadge(data.statut).label }}</span>
                        </span>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <Select
                            v-model="filterModel.value"
                            :options="statusOptions"
                            option-label="label"
                            option-value="value"
                            placeholder="Statut..."
                            show-clear
                            class="w-full text-xs"
                            @change="filterCallback()"
                        />
                    </template>
                </Column>

                <!-- Date -->
                <Column field="dateDemande" header="Date" sortable style="min-width: 6.5rem">
                    <template #body="{ data }">
                        <span class="text-xs text-gray-500">
                            {{ formatDate(data.dateBonCommande || data.dateDemande) }}
                        </span>
                    </template>
                </Column>

                <!-- Actions directes -->
                <Column header="Actions" style="min-width: 7rem; text-align: center">
                    <template #body="{ data }">
                        <div class="flex gap-1 justify-center items-center">
                            <Button
                                icon="pi pi-eye"
                                size="small"
                                text
                                rounded
                                severity="secondary"
                                title="Voir la synthèse complète"
                                @click="openDetail(data)"
                            />
                            <Button
                                icon="pi pi-pencil"
                                size="small"
                                text
                                rounded
                                severity="info"
                                title="Éditer / Visas"
                                @click="openEditView(data.id)"
                            />
                            <Button
                                icon="pi pi-file-pdf"
                                size="small"
                                text
                                rounded
                                severity="danger"
                                title="Aperçu du bon de commande (PDF)"
                                @click="previewPdf(data.id)"
                            />
                        </div>
                    </template>
                </Column>

                <!-- Extension de ligne : Affiche la timeline et les 5 blocs d'étapes cohérents -->
                <template #expansion="{ data }">
                    <div class="p-4 bg-slate-50/80 dark:bg-slate-900/60 border-y border-slate-200 dark:border-slate-700 space-y-4">
                        <!-- Stepper visuel de la commande -->
                        <div class="bg-white dark:bg-slate-800 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xs">
                            <BonCommandeWorkflowTimeline :commande="data" />
                        </div>

                        <!-- 5 Blocs d'étapes cohérents (numéro, libellé, icône, couleur) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
                            <!-- Étape 1 : Demande & Avis (Bleu) -->
                            <div class="bg-white dark:bg-slate-800 p-3 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 border-l-blue-600 shadow-2xs flex flex-col justify-between">
                                <div>
                                    <div class="text-xs font-bold text-blue-900 dark:text-blue-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <i class="pi pi-file-edit text-blue-600"></i>
                                        <span>1. Demande & Avis</span>
                                    </div>
                                    <dl class="text-xs space-y-1">
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Demandeur :</dt>
                                            <dd class="font-medium truncate max-w-[120px]" :title="getDemandeurLabel(data)">{{ getDemandeurLabel(data) }}</dd>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Date :</dt>
                                            <dd class="font-medium">{{ formatDate(data.dateDemande) }}</dd>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Avis Dir. :</dt>
                                            <dd class="font-semibold" :class="data.avisDirecteur === 'FAVORABLE' ? 'text-emerald-600' : (data.avisDirecteur === 'DEFAVORABLE' ? 'text-red-600' : 'text-gray-400')">
                                                {{ data.avisDirecteur || 'En attente' }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between py-0.5">
                                            <dt class="text-gray-500">PDF signé :</dt>
                                            <dd class="font-medium" :class="data.pdfFilePath ? 'text-blue-600 font-bold' : 'text-gray-400'">
                                                {{ data.pdfFilePath ? 'Déposé' : 'Non' }}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Étape 2 : Vérification SIFAC (Ambre) -->
                            <div class="bg-white dark:bg-slate-800 p-3 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 border-l-amber-600 shadow-2xs flex flex-col justify-between">
                                <div>
                                    <div class="text-xs font-bold text-amber-900 dark:text-amber-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <i class="pi pi-search text-amber-600"></i>
                                        <span>2. Vérif. SIFAC</span>
                                    </div>
                                    <dl class="text-xs space-y-1">
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Fournisseur :</dt>
                                            <dd class="font-medium truncate max-w-[120px]" :title="data.fournisseur?.nomFournisseur || 'Non renseigné'">
                                                {{ data.fournisseur?.nomFournisseur || 'Non renseigné' }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">N° BC :</dt>
                                            <dd class="font-mono font-bold text-amber-700 dark:text-amber-400">
                                                {{ data.numeroBonCommande ? `#${data.numeroBonCommande}` : 'Non saisi' }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Marché :</dt>
                                            <dd class="font-medium">{{ data.commandeSurMarche ? 'Oui' : 'Hors marché' }}</dd>
                                        </div>
                                        <div class="flex justify-between py-0.5">
                                            <dt class="text-gray-500">Vérifié le :</dt>
                                            <dd class="font-medium">{{ formatDate(data.dateVerificationFinanciere) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Étape 3 : Visa Engagement (Violet) -->
                            <div class="bg-white dark:bg-slate-800 p-3 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 border-l-purple-600 shadow-2xs flex flex-col justify-between">
                                <div>
                                    <div class="text-xs font-bold text-purple-900 dark:text-purple-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <i class="pi pi-check-circle text-purple-600"></i>
                                        <span>3. Visa Engagement</span>
                                    </div>
                                    <dl class="text-xs space-y-1">
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Visa CSA :</dt>
                                            <dd class="font-semibold" :class="data.dateVisaEngagement ? 'text-purple-600 font-bold' : 'text-gray-400'">
                                                {{ data.dateVisaEngagement ? 'Accordé' : 'En attente' }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Date Visa :</dt>
                                            <dd class="font-medium">{{ formatDate(data.dateVisaEngagement) }}</dd>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">BC transmis :</dt>
                                            <dd class="font-medium" :class="data.bcTransmis ? 'text-emerald-600 font-bold' : 'text-amber-600'">
                                                {{ data.bcTransmis ? 'Transmis' : 'Non' }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between py-0.5">
                                            <dt class="text-gray-500">PJ SIFAC :</dt>
                                            <dd class="font-medium">{{ data.pjSurSifac ? 'Oui' : 'Non' }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Étape 4 : Constatation Service Fait (Teal) -->
                            <div class="bg-white dark:bg-slate-800 p-3 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 border-l-teal-600 shadow-2xs flex flex-col justify-between">
                                <div>
                                    <div class="text-xs font-bold text-teal-900 dark:text-teal-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <i class="pi pi-box text-teal-600"></i>
                                        <span>4. Service Fait</span>
                                    </div>
                                    <dl class="text-xs space-y-1">
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Réceptionné :</dt>
                                            <dd class="font-medium">{{ formatDate(data.receptions?.[0]?.dateEffectiveReception) }}</dd>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Conforme :</dt>
                                            <dd class="font-medium" :class="data.receptions?.[0]?.prestationsConformes ? 'text-teal-600 font-bold' : 'text-gray-400'">
                                                {{ data.receptions?.[0]?.prestationsConformes ? 'Oui' : (data.receptions?.[0]?.dateEffectiveReception ? 'Non' : '-') }}
                                            </dd>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">MIGO :</dt>
                                            <dd class="font-medium">{{ data.receptions?.[0]?.numerosMigo?.length ? data.receptions[0].numerosMigo.join(', ') : '-' }}</dd>
                                        </div>
                                        <div class="flex justify-between py-0.5">
                                            <dt class="text-gray-500">Certifié SF :</dt>
                                            <dd class="font-medium">{{ formatDate(data.dateCertificationServiceFait) }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Étape 5 : Facture & Paiement (Rose) -->
                            <div class="bg-white dark:bg-slate-800 p-3 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 border-l-rose-600 shadow-2xs flex flex-col justify-between">
                                <div>
                                    <div class="text-xs font-bold text-rose-900 dark:text-rose-300 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                        <i class="pi pi-wallet text-rose-600"></i>
                                        <span>5. Facture & Paiement</span>
                                    </div>
                                    <dl class="text-xs space-y-1">
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Arrivée fac. :</dt>
                                            <dd class="font-medium">{{ formatDate(data.dateArriveeFacture) }}</dd>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Paiement :</dt>
                                            <dd class="font-bold text-emerald-600">{{ formatDate(data.datePaiement) }}</dd>
                                        </div>
                                        <div class="flex justify-between py-0.5 border-b border-slate-100 dark:border-slate-700/50">
                                            <dt class="text-gray-500">Facture finale :</dt>
                                            <dd class="font-semibold" :class="data.factureFinale ? 'text-emerald-600 font-bold' : 'text-gray-400'">
                                                {{ data.factureFinale ? 'Soldée' : 'Non soldée' }}
                                            </dd>
                                        </div>
                                        <div v-if="data.rejetFactureMotif" class="flex justify-between py-0.5 text-rose-600">
                                            <dt>Rejet :</dt>
                                            <dd class="font-medium truncate max-w-[100px]" :title="data.rejetFactureMotif">{{ data.rejetFactureMotif }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-1">
                            <Button label="Ouvrir le formulaire complet" icon="pi pi-external-link" size="small" outlined @click="openEditView(data.id)" />
                            <Button label="Aperçu PDF" icon="pi pi-file-pdf" size="small" severity="danger" outlined @click="previewPdf(data.id)" />
                        </div>
                    </div>
                </template>
            </DataTable>
        </Card>

        <!-- 5. Dialog de Détail / Workflow complet -->
        <Dialog
            v-model:visible="showDetailDialog"
            header="Détails du Bon de Commande"
            modal
            class="w-full max-w-4xl"
        >
            <div v-if="selectedCommande" class="space-y-4">
                <BonCommandeWorkflowTimeline :commande="selectedCommande" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mt-4">
                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Informations Générales</div>
                        <p><strong>Objet :</strong> {{ selectedCommande.objetDemande }}</p>
                        <p><strong>Prestation :</strong> {{ selectedCommande.prestation }}</p>
                        <p><strong>Centre Financier :</strong> {{ selectedCommande.centreFinancier || '-' }}</p>
                        <p><strong>Montant HT :</strong> {{ formatCurrency(selectedCommande.montantHt) }}</p>
                        <p><strong>Montant TTC :</strong> {{ formatCurrency(selectedCommande.montantTtc) }}</p>
                    </div>

                    <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Visas & Décisions</div>
                        <p><strong>Avis Direction :</strong> {{ selectedCommande.avisDirecteur || 'En attente' }}</p>
                        <p><strong>Date Visa Engagement :</strong> {{ formatDate(selectedCommande.dateVisaEngagement) }}</p>
                        <p><strong>Date Certification SF :</strong> {{ formatDate(selectedCommande.dateCertificationServiceFait) }}</p>
                        <p><strong>Date Paiement :</strong> {{ formatDate(selectedCommande.datePaiement) }}</p>
                    </div>
                </div>

                <div v-if="selectedCommande.noteExplicative" class="p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 rounded-lg text-xs">
                    <strong class="text-blue-900 dark:text-blue-300 block mb-1">Note explicative :</strong>
                    <p class="text-blue-950 dark:text-blue-200 whitespace-pre-line">{{ selectedCommande.noteExplicative }}</p>
                </div>
            </div>

            <template #footer>
                <div class="flex justify-between w-full">
                    <Button label="Aperçu PDF" icon="pi pi-file-pdf" severity="danger" outlined @click="previewPdf(selectedCommande.id)" />
                    <div class="flex gap-2">
                        <Button label="Fermer" severity="secondary" text @click="showDetailDialog = false" />
                        <Button label="Modifier la commande" icon="pi pi-pencil" severity="primary" @click="openEditView(selectedCommande.id)" />
                    </div>
                </div>
            </template>
        </Dialog>

        <!-- 6. Modal Aperçu PDF -->
        <Dialog
            v-model:visible="showPdfModal"
            header="Aperçu du Bon de Commande (Formulaire émargé)"
            modal
            class="w-full max-w-5xl h-[85vh]"
        >
            <div v-if="pdfLoading" class="w-full h-full min-h-[500px] flex flex-col items-center justify-center gap-3">
                <i class="pi pi-spin pi-spinner text-3xl text-primary"></i>
                <span class="text-sm text-gray-500">Chargement du document PDF...</span>
            </div>
            <div v-else-if="pdfBlobUrl" class="w-full h-full min-h-[500px]">
                <iframe
                    :src="pdfBlobUrl"
                    class="w-full h-[70vh] border-0 rounded-lg"
                ></iframe>
            </div>
            <div v-else class="w-full h-full min-h-[500px] flex items-center justify-center text-gray-400">
                Aucun document disponible
            </div>
            <template #footer>
                <Button label="Fermer" severity="secondary" @click="showPdfModal = false" />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
</style>
