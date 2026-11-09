<script setup>
import { ref, computed, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import { useToast } from 'primevue/usetoast';
import { HeaderComponent, Card, EmptyState, ListSkeleton, ErrorView } from '@components';
import { useConfirmation } from '@composables/useConfirmation';
import {
    getFinanceTauxTvaService,
    createFinanceTauxTvaService,
    updateFinanceTauxTvaService,
    deleteFinanceTauxTvaService,
} from '@/requests/financeService';
import { messageErreurApi } from '@/utils/finance';

/**
 * Configuration du package : taux de TVA proposés à la saisie d'une demande (gérés par le service financier).
 * Une demande recopie le taux choisi : modifier, désactiver ou supprimer un taux ne change pas les demandes existantes.
 */
const toast = useToast();
const { confirmer } = useConfirmation();

const chargement = ref(true);
const erreur = ref('');
const taux = ref([]);
const dialogue = ref(false);
const enregistrement = ref(false);
const edition = ref(null); // taux en cours de modification (null : création)
const form = ref({ taux: null, libelle: '', actif: true, parDefaut: false });

const valide = computed(() => form.value.taux !== null && form.value.taux !== '' && form.value.libelle.trim() !== '');
const formatTaux = (t) => `${Number(t).toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 })} %`;

async function charger() {
    erreur.value = '';
    chargement.value = true;
    try {
        taux.value = await getFinanceTauxTvaService();
    } catch (e) {
        erreur.value = messageErreurApi(e, 'Impossible de charger les taux de TVA.');
    } finally {
        chargement.value = false;
    }
}

function ouvrir(t = null) {
    edition.value = t;
    form.value = t
        ? { taux: Number(t.taux), libelle: t.libelle, actif: t.actif, parDefaut: t.parDefaut }
        : { taux: null, libelle: '', actif: true, parDefaut: taux.value.length === 0 };
    dialogue.value = true;
}

async function enregistrer() {
    if (!valide.value) return;
    enregistrement.value = true;
    const donnees = { taux: String(form.value.taux), libelle: form.value.libelle.trim(), actif: form.value.actif, parDefaut: form.value.parDefaut };
    try {
        if (edition.value) await updateFinanceTauxTvaService(edition.value.id, donnees);
        else await createFinanceTauxTvaService(donnees);
        toast.add({ severity: 'success', summary: 'Taux enregistré', life: 2500 });
        dialogue.value = false;
        await charger();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Enregistrement impossible', detail: messageErreurApi(e), life: 6000 });
    } finally {
        enregistrement.value = false;
    }
}

async function basculer(t) {
    try {
        await updateFinanceTauxTvaService(t.id, { actif: !t.actif });
        await charger();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Modification impossible', detail: messageErreurApi(e), life: 6000 });
    }
}

async function supprimer(t) {
    if (!(await confirmer({
        titre: 'Supprimer le taux',
        message: `Supprimer le taux « ${t.libelle} » ? Les demandes déjà saisies gardent leur taux.`,
        libelle: 'Supprimer',
        danger: true,
    }))) return;
    try {
        await deleteFinanceTauxTvaService(t.id);
        toast.add({ severity: 'success', summary: 'Taux supprimé', life: 2500 });
        await charger();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Suppression impossible', detail: messageErreurApi(e), life: 6000 });
    }
}

onMounted(charger);
</script>

<template>
    <div class="space-y-6">
        <HeaderComponent
            icon="pi pi-sliders-h"
            color="emerald"
            titre="Configuration — Finances & Achats"
            description="Taux de TVA proposés à la saisie d'une demande d'achat."
            :back-url="{ name: 'finance_dashboard' }"
        />

        <ListSkeleton v-if="chargement" />
        <ErrorView v-else-if="erreur" :message="erreur" />

        <Card v-else title="Taux de TVA" subtitle="Seuls les taux actifs sont proposés ; le taux par défaut est présélectionné" icon="pi pi-percentage" color="emerald">
            <template #header-actions>
                <Button label="Ajouter un taux" icon="pi pi-plus" size="small" @click="ouvrir()" />
            </template>
            <DataTable :value="taux" data-key="id" class="ui-table-compact">
                <template #empty>
                    <EmptyState title="Aucun taux de TVA" description="Ajoutez les taux applicables (20 %, 10 %, 5,5 %…)." icon="pi pi-percentage" compact action-label="Ajouter un taux" action-icon="pi pi-plus" @action="ouvrir()" />
                </template>
                <Column field="libelle" header="Libellé" />
                <Column field="taux" header="Taux" body-class="text-right" header-class="text-right">
                    <template #body="{ data }">{{ formatTaux(data.taux) }}</template>
                </Column>
                <Column header="État">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Tag :value="data.actif ? 'Actif' : 'Désactivé'" :severity="data.actif ? 'success' : 'secondary'" />
                            <Tag v-if="data.parDefaut" value="Par défaut" severity="info" />
                        </div>
                    </template>
                </Column>
                <Column header="Actions" body-class="text-right" header-class="text-right">
                    <template #body="{ data }">
                        <div class="flex justify-end gap-1">
                            <Button :label="data.actif ? 'Désactiver' : 'Activer'" severity="secondary" text size="small" @click="basculer(data)" />
                            <Button icon="pi pi-pencil" severity="secondary" text rounded :aria-label="`Modifier ${data.libelle}`" @click="ouvrir(data)" />
                            <Button icon="pi pi-trash" severity="danger" text rounded :aria-label="`Supprimer ${data.libelle}`" @click="supprimer(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </Card>

        <Dialog v-model:visible="dialogue" :header="edition ? 'Modifier le taux' : 'Nouveau taux de TVA'" modal class="w-full max-w-md">
            <form id="form-taux" class="q-form" @submit.prevent="enregistrer">
                <div class="flex flex-col gap-1">
                    <label for="taux-valeur" class="q-label q-label-required">Taux (%)</label>
                    <input id="taux-valeur" v-model.number="form.taux" type="number" min="0" max="100" step="0.01" required class="q-input" />
                </div>
                <div class="flex flex-col gap-1">
                    <label for="taux-libelle" class="q-label q-label-required">Libellé</label>
                    <input id="taux-libelle" v-model="form.libelle" type="text" maxlength="100" required class="q-input" placeholder="Ex. : Taux normal (20 %)" />
                </div>
                <label for="taux-actif" class="flex items-center gap-2 text-sm">
                    <input id="taux-actif" v-model="form.actif" type="checkbox" class="q-check" />
                    <span>Proposé à la saisie</span>
                </label>
                <label for="taux-defaut" class="flex items-center gap-2 text-sm">
                    <input id="taux-defaut" v-model="form.parDefaut" type="checkbox" class="q-check" />
                    <span>Taux par défaut d'une nouvelle demande</span>
                </label>
            </form>
            <template #footer>
                <Button label="Annuler" severity="secondary" outlined @click="dialogue = false" />
                <Button type="submit" form="form-taux" label="Enregistrer" :disabled="!valide" :loading="enregistrement" />
            </template>
        </Dialog>
    </div>
</template>
