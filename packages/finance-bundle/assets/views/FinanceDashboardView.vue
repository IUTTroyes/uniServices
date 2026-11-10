<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Button from 'primevue/button';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { useToast } from 'primevue/usetoast';
import { DocumentTextIcon, BanknotesIcon, ClockIcon, ShoppingBagIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { HeaderComponent, Kpi, Card, EmptyState, ListSkeleton, ActionButtonVertical } from '@components';
import CustomDataTable from '@components/components/CustomDataTable.vue';
import { useUsersStore } from '@stores';
import { hasPermission } from '@utils/permissions';
import { getFinanceBonsCommandeService, exportFinanceBonsCommandeExcelService, telechargerBlob } from '@/requests/financeService';
import { formatDate, formatMontant, nomPersonne, nombre, estFinalisee, messageErreurApi } from '@/utils/finance';
import {
    STATUS_WORKFLOW_MAP,
    STATUTS_INSTRUCTION, STATUTS_ATTENTE_SERVICE_FAIT, STATUTS_ENGAGES, STATUTS_TERMINES,
} from '@/utils/workflowConstants';
import PdfApercuDialog from '@/components/PdfApercuDialog.vue';
import StatutTag from '@/components/StatutTag.vue';
import DetailLigneDemande from '@/components/DetailLigneDemande.vue';

const router = useRouter();
const toast = useToast();
const userStore = useUsersStore();

const chargement = ref(true);
const demandes = ref([]);
const perimetre = ref('toutes'); // 'toutes' | 'miennes' | 'en_cours'
const departement = ref(null);
const lignesOuvertes = ref({});
const apercu = ref(false);
const apercuId = ref(null);
const tableau = ref(null);
const exportEnCours = ref(false);
const recherche = ref(null);

const PERIMETRES = [
    { valeur: 'toutes', libelle: 'Toutes les demandes visibles' },
    { valeur: 'miennes', libelle: 'Mes demandes' },
    { valeur: 'en_cours', libelle: 'En cours' },
];

const peutCreer = computed(() => hasPermission('ROLE_FINANCE_DEMANDEUR'));
const monId = computed(() => userStore.user?.id ?? null);
const statutOptions = Object.entries(STATUS_WORKFLOW_MAP).map(([valeur, info]) => ({ valeur, libelle: info.label }));
const libelleFournisseur = (d) => d.fournisseur?.nomFournisseur || d.fournisseurPropose || '';
const libelleReference = (d) => [d.numeroBonCommande ? `BC ${d.numeroBonCommande}` : `Demande n° ${d.id}`, d.centreFinancier ? `CF ${d.centreFinancier}` : ''].join(' ').trim();

/** Départements présents dans les demandes visibles (le serveur ne renvoie que celles que l'on peut voir). */
const departements = computed(() => {
    const parId = new Map();
    demandes.value.forEach(d => d.departement?.id && parId.set(d.departement.id, d.departement.libelle));
    return [...parId].map(([id, libelle]) => ({ id, libelle })).sort((a, b) => a.libelle.localeCompare(b.libelle, 'fr'));
});

const visibles = computed(() => demandes.value.filter((d) => {
    if (departement.value && d.departement?.id !== departement.value) return false;
    if (perimetre.value === 'miennes') return d.demandeur?.id === monId.value;
    if (perimetre.value === 'en_cours') return !STATUTS_TERMINES.includes(d.statut) && d.statut !== 'REJETE';
    return true;
}));

/** Lignes du tableau : champs « à plat » pour les filtres d'en-tête (les données d'origine restent disponibles). */
const lignes = computed(() => visibles.value.map(d => ({
    ...d,
    reference: libelleReference(d),
    fournisseurLibelle: libelleFournisseur(d),
    demandeurNom: nomPersonne(d.demandeur),
    departementLibelle: d.departement?.libelle || '',
})));

const colonnes = computed(() => [
    { field: 'numeroBonCommande', key: 'reference', filterField: 'reference', header: 'Référence', sortable: true, filter: { type: 'text', placeholder: 'BC, n°, CF…' } },
    { field: 'objetDemande', header: 'Objet', sortable: true, filter: { type: 'text', placeholder: 'Objet…' } },
    { field: 'fournisseurLibelle', header: 'Fournisseur', sortable: true, filter: { type: 'text', placeholder: 'Fournisseur…' } },
    ...(departements.value.length > 1 ? [{ field: 'departementLibelle', header: 'Département', sortable: true }] : []),
    { field: 'demandeurNom', header: 'Demandeur', sortable: true, filter: { type: 'text', placeholder: 'Nom…' } },
    { field: 'montantTtc', header: 'Montant TTC', sortable: true, bodyClass: 'text-right', headerClass: 'text-right' },
    { field: 'statut', header: 'Statut', filter: { type: 'select', options: statutOptions, optionLabel: 'libelle', optionValue: 'valeur', placeholder: 'Tous' } },
    { field: 'dateDemande', header: 'Demandée le', sortable: true },
    { field: null, key: 'actions', header: 'Actions', bodyClass: 'text-center' },
]);
const champsRecherche = ['reference', 'objetDemande', 'prestation', 'fournisseurLibelle', 'demandeurNom'];

const nombreAvec = (statuts) => visibles.value.filter(d => statuts.includes(d.statut)).length;
const montantEngage = computed(() => visibles.value
    .filter(d => STATUTS_ENGAGES.includes(d.statut))
    .reduce((somme, d) => somme + (nombre(d.montantTtc ?? d.montantHt) ?? 0), 0));

async function charger() {
    chargement.value = true;
    try {
        demandes.value = await getFinanceBonsCommandeService();
    } catch (e) {
        demandes.value = [];
        toast.add({ severity: 'error', summary: 'Chargement impossible', detail: messageErreurApi(e, 'Impossible de charger les demandes d\'achat.'), life: 5000 });
    } finally {
        chargement.value = false;
    }
}

const ouvrir = (id) => router.push({ name: 'finance_detail', params: { id } });
const creer = () => router.push({ name: 'finance_demande_new' });
function voirPdf(id) {
    apercuId.value = id;
    apercu.value = true;
}

/** Export xlsx du tableau tel qu'affiché : périmètre, recherche, filtres d'en-tête et tri appliqués. */
async function exporter() {
    const ids = (tableau.value?.lignesAffichees() ?? []).map(d => d.id);
    if (!ids.length) {
        toast.add({ severity: 'info', summary: 'Export', detail: 'Aucune demande à exporter.', life: 3000 });
        return;
    }
    exportEnCours.value = true;
    try {
        const { blob, nom } = await exportFinanceBonsCommandeExcelService(ids);
        telechargerBlob(blob, nom);
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Export impossible', detail: messageErreurApi(e, 'Impossible de générer le fichier Excel.'), life: 5000 });
    } finally {
        exportEnCours.value = false;
    }
}

onMounted(charger);
</script>

<template>
    <div class="space-y-6">
        <HeaderComponent
            icon="pi pi-wallet"
            color="emerald"
            titre="Finances & Achats"
            description="Suivez les demandes d'achat : avis, engagement, service fait et paiement."
            :show-back="false"
        >
            <template v-if="peutCreer" #actions>
                <ActionButtonVertical :icon="PlusIcon" label="Nouvelle demande" severity="success" @click="creer" />
            </template>
        </HeaderComponent>

        <section aria-label="Indicateurs" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <Kpi label="Demandes" :value="visibles.length" :icon="DocumentTextIcon" color="blue" :loading="chargement" />
            <Kpi label="Montant engagé (TTC)" :value="formatMontant(montantEngage)" :icon="BanknotesIcon" color="teal" description="Commandes validées ou payées" :loading="chargement" />
            <Kpi label="En instruction" :value="nombreAvec(STATUTS_INSTRUCTION)" :icon="ClockIcon" color="amber" description="Avis, vérification, visa" :loading="chargement" />
            <Kpi label="Service fait à constater" :value="nombreAvec(STATUTS_ATTENTE_SERVICE_FAIT)" :icon="ShoppingBagIcon" color="purple" description="Commande validée, pas encore certifiée" :loading="chargement" />
        </section>

        <Card title="Demandes d'achat" :subtitle="`${visibles.length} demande(s)`" icon="pi pi-list" color="emerald">
            <template #header-actions>
                <div class="flex flex-wrap items-center gap-2">
                    <select v-model="perimetre" aria-label="Périmètre" class="q-input q-input-sm">
                        <option v-for="p in PERIMETRES" :key="p.valeur" :value="p.valeur">{{ p.libelle }}</option>
                    </select>
                    <select v-if="departements.length > 1" v-model="departement" aria-label="Département" class="q-input q-input-sm">
                        <option :value="null">Tous les départements</option>
                        <option v-for="d in departements" :key="d.id" :value="d.id">{{ d.libelle }}</option>
                    </select>
                    <IconField>
                        <InputIcon class="pi pi-search" />
                        <InputText v-model="recherche" aria-label="Rechercher" placeholder="Rechercher…" />
                    </IconField>
                    <Button icon="pi pi-file-excel" label="Exporter" severity="secondary" outlined size="small" aria-label="Exporter le tableau au format Excel" :loading="exportEnCours" :disabled="chargement" @click="exporter" />
                    <Button icon="pi pi-refresh" severity="secondary" text rounded aria-label="Actualiser" :loading="chargement" @click="charger" />
                </div>
            </template>

            <ListSkeleton v-if="chargement" />
            <CustomDataTable
                v-else
                ref="tableau"
                v-model:expanded-rows="lignesOuvertes"
                v-model:global-filter="recherche"
                :value="lignes"
                :columns="colonnes"
                :global-filter-fields="champsRecherche"
                :rows-per-page-options="[15, 30, 50]"
                filter-display="row"
                expandable
                :show-header="false"
                :show-footer="false"
                table-class="ui-table-compact"
            >
                <template #empty>
                    <EmptyState
                        title="Aucune demande d'achat"
                        description="Aucune demande ne correspond à ces critères."
                        icon="pi pi-inbox"
                        compact
                        :action-label="peutCreer ? 'Nouvelle demande' : ''"
                        action-icon="pi pi-plus"
                        @action="creer"
                    />
                </template>

                <template #body-reference="{ data }">
                    <div class="flex flex-col">
                        <span class="font-mono font-semibold">{{ data.numeroBonCommande ? `BC ${data.numeroBonCommande}` : `Demande n° ${data.id}` }}</span>
                        <span v-if="data.centreFinancier" class="text-sm text-gray-500 dark:text-gray-400">CF {{ data.centreFinancier }}</span>
                    </div>
                </template>
                <template #body-objetDemande="{ data }">
                    <div class="flex flex-col">
                        <span class="font-semibold line-clamp-1" :title="data.objetDemande">{{ data.objetDemande || 'Sans objet' }}</span>
                        <span v-if="data.prestation" class="text-sm text-gray-500 dark:text-gray-400">{{ data.prestation }}</span>
                    </div>
                </template>
                <template #body-fournisseurLibelle="{ data }">
                    <span v-if="data.fournisseur?.nomFournisseur">{{ data.fournisseur.nomFournisseur }}</span>
                    <span v-else-if="data.fournisseurPropose" class="italic text-gray-600 dark:text-gray-300" :title="'Fournisseur proposé, non encore référencé'">{{ data.fournisseurPropose }} <span class="text-xs">(proposé)</span></span>
                    <span v-else class="text-gray-400">—</span>
                </template>
                <template #body-departementLibelle="{ data }">{{ data.departementLibelle || '—' }}</template>
                <template #body-montantTtc="{ data }">
                    <div class="flex flex-col items-end">
                        <span class="font-semibold">{{ formatMontant(data.montantTtc ?? data.montantHt) }}</span>
                        <span v-if="data.montantHt" class="text-sm text-gray-500 dark:text-gray-400">{{ formatMontant(data.montantHt) }} HT</span>
                    </div>
                </template>
                <template #body-statut="{ data }">
                    <StatutTag :statut="data.statut" :finalisee="estFinalisee(data)" />
                </template>
                <template #body-dateDemande="{ data }">{{ formatDate(data.dateDemande) }}</template>
                <template #body-actions="{ data }">
                    <div class="flex justify-center gap-1">
                        <Button icon="pi pi-folder-open" severity="secondary" text rounded :aria-label="`Ouvrir la demande ${data.id}`" @click.stop="ouvrir(data.id)" />
                        <Button icon="pi pi-file-pdf" severity="secondary" text rounded :aria-label="`Aperçu PDF de la demande ${data.id}`" @click.stop="voirPdf(data.id)" />
                    </div>
                </template>
                <template #expansion="{ data }">
                    <DetailLigneDemande :commande="data" @ouvrir="ouvrir(data.id)" @apercu="voirPdf(data.id)" />
                </template>
            </CustomDataTable>
        </Card>

        <PdfApercuDialog v-model:visible="apercu" :commande-id="apercuId" :choix-etape="false" />
    </div>
</template>
