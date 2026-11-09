<script setup>
import { ref } from 'vue';
import AutoComplete from 'primevue/autocomplete';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import { searchFinanceFournisseursService, createFinanceFournisseurService } from '@/requests/financeService';
import { messageErreurApi } from '@/utils/finance';

/**
 * Fournisseur d'une demande : choix dans le référentiel (recherche) ; à défaut, le demandeur propose un nom en clair
 * que le service financier rattache ou ajoute au référentiel (sans doublon, contrôlé par le serveur).
 */
const props = defineProps({
    idPrefixe: { type: String, default: 'fournisseur' },
    modifiable: { type: Boolean, default: false },
    proposable: { type: Boolean, default: false },
    /** Le service financier peut ajouter le fournisseur proposé au référentiel. */
    peutCreer: { type: Boolean, default: false },
});
const fournisseur = defineModel({ type: Object, default: null });
const propose = defineModel('propose', { type: String, default: null });

const toast = useToast();
const suggestions = ref([]);
const creation = ref(false);
const numero = ref('');

const libelle = (f) => (f ? `${f.numeroFournisseur ? f.numeroFournisseur + ' - ' : ''}${f.nomFournisseur}` : '');

async function rechercher(event) {
    try {
        suggestions.value = await searchFinanceFournisseursService(event.query);
    } catch {
        suggestions.value = [];
    }
}

async function ajouterAuReferentiel() {
    creation.value = true;
    try {
        fournisseur.value = await createFinanceFournisseurService({ nomFournisseur: propose.value, numeroFournisseur: numero.value || null });
        toast.add({ severity: 'success', summary: 'Fournisseur ajouté', detail: libelle(fournisseur.value), life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Ajout impossible', detail: messageErreurApi(e), life: 6000 });
    } finally {
        creation.value = false;
    }
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex flex-col gap-1">
            <label :for="`${idPrefixe}-referentiel`" class="q-label">Fournisseur (référentiel)</label>
            <AutoComplete
                v-model="fournisseur"
                :input-id="`${idPrefixe}-referentiel`"
                :suggestions="suggestions"
                :option-label="libelle"
                :disabled="!modifiable"
                force-selection
                dropdown
                placeholder="Rechercher par raison sociale"
                class="w-full"
                @complete="rechercher"
            />
        </div>
        <div v-if="proposable || propose" class="flex flex-col gap-1">
            <label :for="`${idPrefixe}-propose`" class="q-label">Fournisseur proposé (s'il n'est pas dans la liste)</label>
            <input :id="`${idPrefixe}-propose`" v-model="propose" type="text" maxlength="255" :disabled="!proposable" class="q-input" placeholder="Raison sociale, site, contact…" />
        </div>
        <div v-if="peutCreer && modifiable && propose && !fournisseur" class="flex flex-wrap items-end gap-2">
            <div class="flex flex-col gap-1">
                <label :for="`${idPrefixe}-numero`" class="q-label">N° fournisseur SIFAC</label>
                <input :id="`${idPrefixe}-numero`" v-model="numero" type="text" maxlength="50" class="q-input" />
            </div>
            <Button label="Ajouter au référentiel" icon="pi pi-plus" size="small" severity="secondary" outlined :loading="creation" @click="ajouterAuReferentiel" />
        </div>
    </div>
</template>
