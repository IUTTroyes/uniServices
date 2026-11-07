<script setup>
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import { moderationService, messagesErreur } from '../../requests/portfolioService';

const toast = useToast();
const publications = ref([]);
const chargement = ref(true);
const cible = ref(null);
const motif = ref('');
const envoi = ref(false);
const erreur = ref(null);

const charger = async () => {
  try {
    const { data } = await moderationService.liste();
    publications.value = data;
  } catch (e) {
    erreur.value = messagesErreur(e, 'Chargement impossible.')[0];
  } finally {
    chargement.value = false;
  }
};
onMounted(charger);

const moderer = async () => {
  envoi.value = true;
  try {
    await moderationService.moderer(cible.value.uuid, motif.value);
    cible.value = null;
    await charger();
    toast.add({ severity: 'success', summary: 'Publication retirée', life: 3000 });
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Modération impossible', detail: messagesErreur(e).join(' '), life: 6000 });
  } finally {
    envoi.value = false;
  }
};

const retablir = async (p) => {
  try {
    await moderationService.retablir(p.uuid);
    await charger();
    toast.add({ severity: 'success', summary: 'Publication rétablie', life: 3000 });
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Action impossible', detail: messagesErreur(e).join(' '), life: 6000 });
  }
};

const date = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) : '');
</script>

<template>
  <HeaderComponent
    icon="pi pi-eye"
    titre="Modération"
    description="Portfolios publiés de votre département. Un retrait est immédiat et motivé ; l'étudiant en est informé dans ses réglages de partage."
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  />

  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Erreur" :description="erreur" />
  <EmptyState v-else-if="publications.length === 0" icon="pi pi-eye" color="emerald" title="Rien à modérer" description="Aucun portfolio de votre périmètre n'est actuellement publié." />

  <div v-else class="flex flex-col gap-3">
    <div v-for="p in publications" :key="p.uuid" class="card p-4 m-0 flex flex-wrap items-center gap-3" :class="{ 'opacity-70': p.moderee }">
      <div class="flex flex-col flex-1 min-w-56">
        <span class="font-semibold">{{ p.portfolio.titre }}</span>
        <span class="text-sm text-muted-color">{{ p.portfolio.etudiant }} · version {{ p.numero }} · {{ date(p.genereLe) }}</span>
        <span v-if="p.moderee" class="text-sm text-red-600">Retirée : {{ p.motifModeration }}</span>
      </div>
      <Tag :value="p.portfolio.visibilite.libelle" severity="secondary" />
      <Tag :value="p.portfolio.type.libelle" :severity="p.portfolio.type.value === 'academique' ? 'success' : 'info'" />
      <a v-if="p.url" :href="p.url" target="_blank" rel="noopener"><Button label="Voir" icon="pi pi-external-link" size="small" severity="secondary" outlined /></a>
      <Button v-if="!p.moderee" label="Retirer" icon="pi pi-ban" size="small" severity="danger" outlined @click="cible = p; motif = ''" />
      <Button v-else label="Rétablir" icon="pi pi-undo" size="small" severity="secondary" outlined @click="retablir(p)" />
    </div>
  </div>

  <Dialog :visible="!!cible" header="Retirer cette publication" modal :style="{ width: '32rem' }" :breakpoints="{ '640px': '95vw' }" @update:visible="cible = null">
    <div class="flex flex-col gap-2">
      <p class="m-0 text-sm">« {{ cible?.portfolio.titre }} » ({{ cible?.portfolio.etudiant }}) ne sera plus accessible à ses lecteurs.</p>
      <label for="mod-motif" class="font-medium">Motif (visible par l'étudiant)</label>
      <Textarea id="mod-motif" v-model="motif" rows="3" maxlength="1000" auto-resize />
    </div>
    <template #footer>
      <Button label="Annuler" severity="secondary" outlined @click="cible = null" />
      <Button label="Retirer" icon="pi pi-ban" severity="danger" :disabled="!motif.trim()" :loading="envoi" @click="moderer" />
    </template>
  </Dialog>
</template>
