<script setup>
import { ref, watch, onBeforeUnmount } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import { ListSkeleton, EmptyState } from '@components';
import { getFinancePdfBlobService, telechargerBlob } from '@/requests/financeService';
import { messageErreurApi } from '@/utils/finance';

/** Aperçu du formulaire PDF d'une demande (étape 1 à 5), avec téléchargement. */
const props = defineProps({
    commandeId: { type: [Number, String], default: null },
    etapeInitiale: { type: Number, default: 5 },
    choixEtape: { type: Boolean, default: true },
});
const visible = defineModel('visible', { type: Boolean, default: false });

const ETAPES = [
    { label: 'Étape 1 : demande à signer', value: 1 },
    { label: 'Étape 2 : vérification SIFAC', value: 2 },
    { label: 'Étape 3 : visa d\'engagement', value: 3 },
    { label: 'Étape 4 : constatation du service fait', value: 4 },
    { label: 'Document complet', value: 5 },
];

const toast = useToast();
const etape = ref(props.etapeInitiale);
const chargement = ref(false);
const url = ref('');
const blob = ref(null);

function liberer() {
    if (url.value) window.URL.revokeObjectURL(url.value);
    url.value = '';
    blob.value = null;
}

async function charger() {
    if (!props.commandeId) return;
    liberer();
    chargement.value = true;
    try {
        blob.value = await getFinancePdfBlobService(props.commandeId, etape.value);
        url.value = window.URL.createObjectURL(blob.value);
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Aperçu impossible', detail: messageErreurApi(e, 'Le document PDF n\'a pas pu être généré.'), life: 5000 });
    } finally {
        chargement.value = false;
    }
}

function telecharger() {
    if (blob.value) telechargerBlob(blob.value, `demande_achat_${props.commandeId}_etape_${etape.value}.pdf`);
}

watch(visible, (ouvert) => {
    if (ouvert) {
        etape.value = props.etapeInitiale;
        charger();
    } else {
        liberer();
    }
});
watch(etape, () => visible.value && charger());
onBeforeUnmount(liberer);
</script>

<template>
    <Dialog v-model:visible="visible" header="Aperçu du formulaire de demande d'achat" modal class="w-full max-w-5xl">
        <div class="flex flex-col gap-3">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div v-if="choixEtape" class="flex flex-col gap-1">
                    <label for="pdf-etape" class="q-label">Étape à afficher</label>
                    <select id="pdf-etape" v-model.number="etape" class="q-input">
                        <option v-for="e in ETAPES" :key="e.value" :value="e.value">{{ e.label }}</option>
                    </select>
                </div>
                <Button label="Télécharger ce PDF" icon="pi pi-download" severity="secondary" outlined :disabled="!blob" @click="telecharger" />
            </div>
            <ListSkeleton v-if="chargement" />
            <iframe v-else-if="url" :src="url" title="Aperçu du PDF" class="w-full h-[70vh] rounded-lg border border-gray-200 dark:border-gray-700" />
            <EmptyState v-else title="Aucun aperçu disponible" description="Le document n'a pas pu être généré." icon="pi pi-file-pdf" compact />
        </div>
        <template #footer>
            <Button label="Fermer" severity="secondary" @click="visible = false" />
        </template>
    </Dialog>
</template>
