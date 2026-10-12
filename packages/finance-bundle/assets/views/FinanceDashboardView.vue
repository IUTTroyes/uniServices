<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useFinanceFilters } from '@/composables/filters/useFinanceFilters';
import { getFinanceBonsCommandeService } from '@/requests/financeService';
import BonCommandeWorkflowTimeline from '../components/BonCommandeWorkflowTimeline.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Skeleton from 'primevue/skeleton';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const toast = useToast();
const loading = ref(false);
const bonsCommande = ref([]);
const selectedCommande = ref(null);
const showDetailDialog = ref(false);
const showPdfModal = ref(false);
const pdfModalId = ref(null);

const { filters, resetFilters, watchChanges } = useFinanceFilters();

async function loadData() {
    loading.value = true;
    try {
        const response = await getFinanceBonsCommandeService();
        const raw = response?.data;
        if (Array.isArray(raw)) {
            bonsCommande.value = raw;
        } else if (raw && Array.isArray(raw['hydra:member'])) {
            bonsCommande.value = raw['hydra:member'];
        } else if (raw && Array.isArray(raw['member'])) {
            bonsCommande.value = raw['member'];
        } else if (raw && Array.isArray(raw['data'])) {
            bonsCommande.value = raw['data'];
        } else {
            bonsCommande.value = [];
        }
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

watchChanges(() => {
    // Rechargement ou filtrage dynamique
});

onMounted(() => {
    loadData();
});

const totalHt = computed(() => {
    if (!Array.isArray(bonsCommande.value)) return 0;
    return bonsCommande.value.reduce((sum, item) => sum + parseFloat(item.montantHt || 0), 0);
});

const totalTtc = computed(() => {
    if (!Array.isArray(bonsCommande.value)) return 0;
    return bonsCommande.value.reduce((sum, item) => sum + parseFloat(item.montantTtc || 0), 0);
});

const enAttenteServiceFait = computed(() => {
    if (!Array.isArray(bonsCommande.value)) return 0;
    return bonsCommande.value.filter(b => b.statut === 'COMMANDE_VALIDEE' || b.statut === 'COMMANDE_TRANSMISE').length;
});

function formatDate(val) {
    if (!val) return '-';
    const d = new Date(val);
    return d.toLocaleDateString('fr-FR');
}

function formatCurrency(val) {
    if (!val && val !== 0) return '-';
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(val);
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

function previewPdf(id) {
    pdfModalId.value = id;
    showPdfModal.value = true;
}

function openPdfDirect(id) {
    window.open(`/api/finance_bon_commandes/${id}/pdf`, '_blank');
}
</script>

<template>
    <div class="finance-dashboard-container p-4">
        <!-- En-tête avec bouton Nouvelle Demande -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tableau de bord financier</h1>
                <p class="text-sm text-gray-500">Suivi global des demandes d'achats, bons de commande, réceptions et factures</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button label="Nouvelle demande d'achat" icon="pi pi-plus-circle" severity="primary" @click="createNewDemande" />
                <Button label="Réinitialiser filtres" icon="pi pi-filter-slash" severity="secondary" outlined @click="resetFilters" />
                <Button label="Rafraîchir" icon="pi pi-refresh" severity="info" outlined @click="loadData" />
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="kpi-card p-3 rounded-lg shadow-sm border border-gray-200 bg-white">
                <div class="text-xs font-semibold text-gray-500 uppercase">Commandes totales</div>
                <div class="text-2xl font-bold text-gray-800 mt-1">{{ bonsCommande.length }}</div>
            </div>
            <div class="kpi-card p-3 rounded-lg shadow-sm border border-gray-200 bg-white">
                <div class="text-xs font-semibold text-gray-500 uppercase">Total HT engagé</div>
                <div class="text-2xl font-bold text-blue-600 mt-1">{{ formatCurrency(totalHt) }}</div>
            </div>
            <div class="kpi-card p-3 rounded-lg shadow-sm border border-gray-200 bg-white">
                <div class="text-xs font-semibold text-gray-500 uppercase">Total TTC</div>
                <div class="text-2xl font-bold text-emerald-600 mt-1">{{ formatCurrency(totalTtc) }}</div>
            </div>
            <div class="kpi-card p-3 rounded-lg shadow-sm border border-gray-200 bg-white">
                <div class="text-xs font-semibold text-gray-500 uppercase">En attente Service Fait</div>
                <div class="text-2xl font-bold text-amber-600 mt-1">
                    {{ enAttenteServiceFait }}
                </div>
            </div>
        </div>

        <!-- Tableau Principal avec regroupement par colonnes exactes -->
        <div class="card shadow-sm rounded-lg overflow-hidden border border-gray-200 bg-white">
            <DataTable
                v-model:filters="filters"
                :value="bonsCommande"
                :loading="loading"
                data-key="id"
                filter-display="row"
                striped-rows
                show-gridlines
                responsive-layout="scroll"
                class="p-datatable-sm finance-table"
            >
                <template #empty>
                    <div class="p-6 text-center text-gray-500">
                        <i class="pi pi-inbox text-3xl text-gray-400 mb-2 block"></i>
                        Aucun bon de commande trouvé.
                        <div class="mt-2">
                            <Button label="Créer la première demande" icon="pi pi-plus" size="small" @click="createNewDemande" />
                        </div>
                    </div>
                </template>

                <template #loading>
                    <div class="p-4">
                        <Skeleton height="2rem" class="mb-2" />
                        <Skeleton height="2rem" class="mb-2" />
                        <Skeleton height="2rem" />
                    </div>
                </template>

                <!-- Header Groupement (COMMANDE / SERVICE FAIT / FACTURE / SERVICE FINANCIER) -->
                <ColumnGroup type="header">
                    <Row>
                        <Column header="Actions" :rowspan="2" style="width: 80px" />
                        <Column header="COMMANDE" :colspan="12" class="header-group group-commande" />
                        <Column header="SERVICE FAIT" :colspan="4" class="header-group group-service-fait" />
                        <Column header="FACTURE / PAIEMENT" :colspan="3" class="header-group group-facture" />
                        <Column header="FINANCE" :colspan="1" class="header-group group-finance" />
                    </Row>
                    <Row>
                        <!-- COMMANDE (bleu) -->
                        <Column header="Centre financier" filter-field="centreFinancier" sortable field="centreFinancier" style="min-width: 130px;" />
                        <Column header="Nom du gestionnaire" filter-field="gestionnaire" sortable style="min-width: 140px;" class="col-gestionnaire" />
                        <Column header="Date du BC" sortable field="dateBonCommande" style="min-width: 110px;" />
                        <Column header="N° bon de commande" filter-field="numeroBonCommande" sortable field="numeroBonCommande" style="min-width: 140px;" />
                        <Column header="N° Fournisseur" filter-field="fournisseur.numeroFournisseur" sortable style="min-width: 110px;" />
                        <Column header="Nom Fournisseur" filter-field="fournisseur.nomFournisseur" sortable style="min-width: 160px;" />
                        <Column header="Libellé" filter-field="objetDemande" sortable field="objetDemande" style="min-width: 180px;" />
                        <Column header="Montant HT" sortable field="montantHt" style="min-width: 110px;" />
                        <Column header="Montant TTC" sortable field="montantTtc" style="min-width: 110px;" />
                        <Column header="PJ SIFAC" style="min-width: 80px; text-align: center;" />
                        <Column header="BC transmis ?" style="min-width: 90px; text-align: center;" />
                        <Column header="Colis attendu" style="min-width: 100px;" />

                        <!-- SERVICE FAIT (ocre) -->
                        <Column header="Date livraison" style="min-width: 110px;" />
                        <Column header="Date MIGO 103" style="min-width: 110px;" />
                        <Column header="Date MIGO 105" style="min-width: 110px;" />
                        <Column header="PJ SIFAC" style="min-width: 80px; text-align: center;" />

                        <!-- FACTURE / PAIEMENT (vert) -->
                        <Column header="Date arrivée facture" style="min-width: 120px;" />
                        <Column header="Rejet de facture" style="min-width: 110px;" />
                        <Column header="Date de paiement" style="min-width: 110px;" />

                        <!-- SERVICE FINANCIER (bordeaux) -->
                        <Column header="Facture finale" style="min-width: 90px; text-align: center;" />
                    </Row>
                </ColumnGroup>

                <!-- Actions -->
                <Column style="text-align: center;">
                    <template #body="{ data }">
                        <div class="flex gap-1 justify-center">
                            <Button icon="pi pi-eye" size="small" text rounded severity="secondary" @click="openDetail(data)" title="Voir détails et étapes" />
                            <Button icon="pi pi-pencil" size="small" text rounded severity="info" @click="openEditView(data.id)" title="Éditer / Visas" />
                            <Button icon="pi pi-file-pdf" size="small" text rounded severity="danger" @click="previewPdf(data.id)" title="Aperçu PDF formulaire" />
                        </div>
                    </template>
                </Column>

                <!-- COMMANDE fields -->
                <Column field="centreFinancier" filter-field="centreFinancier">
                    <template #body="{ data }">
                        <span class="font-mono text-xs font-semibold text-gray-700">{{ data.centreFinancier || '-' }}</span>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-if="filterModel" v-model="filterModel.value" type="text" class="p-column-filter p-inputtext-sm" placeholder="Centre" @input="filterCallback()" />
                    </template>
                </Column>

                <Column field="gestionnaire">
                    <template #body="{ data }">
                        <span>{{ data.gestionnaire ? (data.gestionnaire.nom + ' ' + data.gestionnaire.prenom) : '-' }}</span>
                    </template>
                </Column>

                <Column field="dateBonCommande">
                    <template #body="{ data }">
                        <span>{{ formatDate(data.dateBonCommande) }}</span>
                    </template>
                </Column>

                <Column field="numeroBonCommande" filter-field="numeroBonCommande">
                    <template #body="{ data }">
                        <span class="font-bold text-gray-900 cursor-pointer hover:text-blue-600" @click="openDetail(data)">{{ data.numeroBonCommande || '-' }}</span>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-if="filterModel" v-model="filterModel.value" type="text" class="p-column-filter p-inputtext-sm" placeholder="N° BC" @input="filterCallback()" />
                    </template>
                </Column>

                <Column field="fournisseur.numeroFournisseur" filter-field="fournisseur.numeroFournisseur">
                    <template #body="{ data }">
                        <span>{{ data.fournisseur?.numeroFournisseur || '-' }}</span>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-if="filterModel" v-model="filterModel.value" type="text" class="p-column-filter p-inputtext-sm" placeholder="N° Fournisseur" @input="filterCallback()" />
                    </template>
                </Column>

                <Column field="fournisseur.nomFournisseur" filter-field="fournisseur.nomFournisseur">
                    <template #body="{ data }">
                        <span class="font-medium text-gray-800">{{ data.fournisseur?.nomFournisseur || '-' }}</span>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-if="filterModel" v-model="filterModel.value" type="text" class="p-column-filter p-inputtext-sm" placeholder="Fournisseur" @input="filterCallback()" />
                    </template>
                </Column>

                <Column field="objetDemande" filter-field="objetDemande">
                    <template #body="{ data }">
                        <span class="truncate block max-w-xs font-medium cursor-pointer hover:underline text-blue-900" :title="data.objetDemande" @click="openDetail(data)">
                            {{ data.objetDemande }}
                        </span>
                    </template>
                    <template #filter="{ filterModel, filterCallback }">
                        <InputText v-if="filterModel" v-model="filterModel.value" type="text" class="p-column-filter p-inputtext-sm" placeholder="Libellé" @input="filterCallback()" />
                    </template>
                </Column>

                <Column field="montantHt" style="text-align: right;">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ formatCurrency(data.montantHt) }}</span>
                    </template>
                </Column>

                <Column field="montantTtc" style="text-align: right;">
                    <template #body="{ data }">
                        <span class="font-semibold text-emerald-700">{{ formatCurrency(data.montantTtc) }}</span>
                    </template>
                </Column>

                <Column field="pjSurSifac" style="text-align: center;">
                    <template #body="{ data }">
                        <span v-if="data.pjSurSifac" class="badge-check">x</span>
                        <span v-else class="text-gray-300">-</span>
                    </template>
                </Column>

                <Column field="bcTransmis" style="text-align: center;">
                    <template #body="{ data }">
                        <span v-if="data.bcTransmis" class="badge-check">x</span>
                        <span v-else class="text-gray-300">-</span>
                    </template>
                </Column>

                <Column field="colisAttendu">
                    <template #body="{ data }">
                        <span>{{ data.colisAttendu || '-' }}</span>
                    </template>
                </Column>

                <!-- SERVICE FAIT fields -->
                <Column field="reception.dateEffectiveReception">
                    <template #body="{ data }">
                        <span>{{ formatDate(data.receptions?.[0]?.dateEffectiveReception) }}</span>
                    </template>
                </Column>

                <Column field="reception.dateMigo103">
                    <template #body="{ data }">
                        <span>{{ formatDate(data.receptions?.[0]?.dateMigo103) }}</span>
                    </template>
                </Column>

                <Column field="reception.dateMigo105">
                    <template #body="{ data }">
                        <span>{{ formatDate(data.receptions?.[0]?.dateMigo105) }}</span>
                    </template>
                </Column>

                <Column field="reception.blRattacheSifac" style="text-align: center;">
                    <template #body="{ data }">
                        <span v-if="data.receptions?.[0]?.blRattacheSifac" class="badge-check">x</span>
                        <span v-else class="text-gray-300">-</span>
                    </template>
                </Column>

                <!-- FACTURE / PAIEMENT fields -->
                <Column field="dateArriveeFacture">
                    <template #body="{ data }">
                        <span>{{ formatDate(data.dateArriveeFacture) }}</span>
                    </template>
                </Column>

                <Column field="rejetFactureMotif">
                    <template #body="{ data }">
                        <Tag v-if="data.rejetFactureMotif" severity="danger" :value="data.rejetFactureMotif" />
                        <span v-else class="text-gray-300">-</span>
                    </template>
                </Column>

                <Column field="datePaiement">
                    <template #body="{ data }">
                        <span class="font-semibold text-emerald-800">{{ formatDate(data.datePaiement) }}</span>
                    </template>
                </Column>

                <!-- SERVICE FINANCIER fields -->
                <Column field="factureFinale" style="text-align: center;">
                    <template #body="{ data }">
                        <span v-if="data.factureFinale" class="badge-check">x</span>
                        <span v-else class="text-gray-300">-</span>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Dialogue Détail Complet avec Timeline des étapes -->
        <Dialog v-model:visible="showDetailDialog" modal header="Détail complet du Bon de Commande" :style="{ width: '65vw', maxWidth: '1000px' }">
            <div v-if="selectedCommande" class="space-y-4">
                <!-- Timeline Workflow -->
                <div class="p-3 bg-gray-50 rounded-lg border">
                    <BonCommandeWorkflowTimeline :commande="selectedCommande" />
                </div>

                <!-- 1 - Demande & Avis -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-3">
                    <div>
                        <strong class="text-xs text-blue-900 block font-bold uppercase mb-1">1. Demande</strong>
                        <div class="text-sm"><strong>Objet :</strong> {{ selectedCommande.objetDemande }}</div>
                        <div class="text-sm"><strong>Prestation :</strong> {{ selectedCommande.prestation }}</div>
                        <div class="text-sm"><strong>Montant :</strong> {{ formatCurrency(selectedCommande.montantHt) }} HT / <span class="text-emerald-700 font-bold">{{ formatCurrency(selectedCommande.montantTtc) }} TTC</span></div>
                        <div class="text-sm text-gray-600 mt-1 bg-white p-2 rounded border"><strong>Note :</strong> {{ selectedCommande.noteExplicative || 'Aucune note' }}</div>
                    </div>
                    <div>
                        <strong class="text-xs text-indigo-900 block font-bold uppercase mb-1">Avis Direction</strong>
                        <div class="text-sm">
                            <strong>Avis :</strong>
                            <Tag v-if="selectedCommande.avisDirecteur" :severity="selectedCommande.avisDirecteur === 'FAVORABLE' ? 'success' : 'danger'" :value="selectedCommande.avisDirecteur" class="ml-2" />
                            <span v-else class="text-gray-400 ml-2">En attente</span>
                        </div>
                        <div class="text-sm mt-1" v-if="selectedCommande.avisDirecteurCommentaire">
                            <strong>Commentaire :</strong> {{ selectedCommande.avisDirecteurCommentaire }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Date avis : {{ formatDate(selectedCommande.dateAvisDirecteur) }}</div>
                    </div>
                </div>

                <!-- 2 & 3 - Vérification & Visa Engagement -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-3">
                    <div>
                        <strong class="text-xs text-amber-900 block font-bold uppercase mb-1">2. Vérification Service Financier</strong>
                        <div class="text-sm"><strong>N° Bon de commande :</strong> {{ selectedCommande.numeroBonCommande || 'Non attribué' }}</div>
                        <div class="text-sm"><strong>Fournisseur :</strong> {{ selectedCommande.fournisseur?.nomFournisseur ? (selectedCommande.fournisseur.numeroFournisseur + ' - ' + selectedCommande.fournisseur.nomFournisseur) : '-' }}</div>
                        <div class="text-xs text-gray-500 mt-1">Date vérification : {{ formatDate(selectedCommande.dateVerificationFinanciere) }}</div>
                    </div>
                    <div>
                        <strong class="text-xs text-purple-900 block font-bold uppercase mb-1">3. Visa Engagement (CSA)</strong>
                        <div class="text-sm">
                            <strong>Visa :</strong>
                            <Tag v-if="selectedCommande.dateVisaEngagement" severity="success" value="Accordé" class="ml-2" />
                            <span v-else class="text-gray-400 ml-2">En attente</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Date visa : {{ formatDate(selectedCommande.dateVisaEngagement) }}</div>
                    </div>
                </div>

                <!-- 4 & 5 - Service Fait & Facturation -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t pt-3">
                    <div>
                        <strong class="text-xs text-emerald-900 block font-bold uppercase mb-1">4. Service Fait</strong>
                        <div class="text-sm"><strong>Date réception :</strong> {{ formatDate(selectedCommande.receptions?.[0]?.dateEffectiveReception) }}</div>
                        <div class="text-sm"><strong>MIGO :</strong> {{ selectedCommande.receptions?.[0]?.numerosMigo?.join(', ') || 'Aucun MIGO' }}</div>
                        <div class="text-sm"><strong>BL rattaché SIFAC :</strong> {{ selectedCommande.receptions?.[0]?.blRattacheSifac ? 'OUI' : 'NON' }}</div>
                    </div>
                    <div>
                        <strong class="text-xs text-rose-900 block font-bold uppercase mb-1">5. Facture & Paiement</strong>
                        <div class="text-sm"><strong>Date arrivée facture :</strong> {{ formatDate(selectedCommande.dateArriveeFacture) }}</div>
                        <div class="text-sm"><strong>Date de paiement :</strong> {{ formatDate(selectedCommande.datePaiement) }}</div>
                        <div class="text-sm"><strong>Facture finale :</strong> {{ selectedCommande.factureFinale ? 'Soldée' : 'Non soldée' }}</div>
                    </div>
                </div>

                <div class="flex justify-between items-center border-t pt-4">
                    <Button label="Gérer les étapes & visas" icon="pi pi-external-link" severity="primary" @click="openEditView(selectedCommande.id)" />
                    <div class="flex gap-2">
                        <Button label="Aperçu PDF (Émargement)" icon="pi pi-file-pdf" severity="danger" outlined @click="previewPdf(selectedCommande.id)" />
                        <Button label="Fermer" severity="secondary" @click="showDetailDialog = false" />
                    </div>
                </div>
            </div>
        </Dialog>

        <!-- Modal Visualiseur PDF Intégré -->
        <Dialog v-model:visible="showPdfModal" modal header="Formulaire Bon de Commande (Émargement)" :style="{ width: '80vw', height: '85vh' }">
            <div class="w-full h-[70vh]">
                <iframe v-if="pdfModalId" :src="`/api/finance_bon_commandes/${pdfModalId}/pdf`" class="w-full h-full border-0 rounded"></iframe>
            </div>
            <template #footer>
                <Button label="Fermer" severity="secondary" @click="showPdfModal = false" />
                <Button label="Télécharger PDF" icon="pi pi-download" severity="primary" @click="openPdfDirect(pdfModalId)" />
            </template>
        </Dialog>
    </div>
</template>

<style scoped>
.finance-table :deep(.group-commande) {
    background-color: #1e3a8a !important;
    color: #ffffff !important;
    text-align: center;
    font-weight: bold;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.finance-table :deep(.group-service-fait) {
    background-color: #b45309 !important;
    color: #ffffff !important;
    text-align: center;
    font-weight: bold;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.finance-table :deep(.group-facture) {
    background-color: #047857 !important;
    color: #ffffff !important;
    text-align: center;
    font-weight: bold;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.finance-table :deep(.group-finance) {
    background-color: #881337 !important;
    color: #ffffff !important;
    text-align: center;
    font-weight: bold;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

.finance-table :deep(.col-gestionnaire) {
    background-color: #fee2e2 !important;
}

.badge-check {
    display: inline-block;
    font-weight: bold;
    color: #0f172a;
    background: #f1f5f9;
    border-radius: 4px;
    padding: 1px 6px;
    font-size: 0.8rem;
}
</style>
