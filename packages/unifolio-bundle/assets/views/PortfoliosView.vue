<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent, EmptyState } from '@components';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import { portfolioService, messagesErreur } from '../requests/portfolioService';

const router = useRouter();
const portfolios = ref([]);
const chargement = ref(true);
const erreur = ref(null);

onMounted(async () => {
  try {
    const { data } = await portfolioService.liste();
    portfolios.value = data;
  } catch (e) {
    erreur.value = messagesErreur(e, 'Impossible de charger vos portfolios.')[0];
  } finally {
    chargement.value = false;
  }
});

const date = (iso) => (iso ? new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }) : null);
</script>

<template>
  <HeaderComponent
    icon="pi pi-book"
    titre="Mes portfolios"
    description="Composez vos pages avec des blocs : texte, médias, traces, progression, CV…"
    :show-back="true"
    back-url="/unifolio"
    color="emerald"
  >
    <template #actions>
      <Button label="Mon CV" icon="pi pi-id-card" severity="secondary" outlined @click="router.push('/unifolio/cv')" />
    </template>
  </HeaderComponent>

  <ProgressBar v-if="chargement" mode="indeterminate" style="height: 4px" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Erreur" :description="erreur" />
  <EmptyState
    v-else-if="portfolios.length === 0"
    icon="pi pi-book"
    color="emerald"
    title="Pas encore de portfolio"
    description="Votre portfolio académique est créé dès l'ouverture d'une campagne pour votre année de BUT. En attendant, vous pouvez déposer vos traces et compléter votre CV."
  />

  <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    <button
      v-for="p in portfolios"
      :key="p.uuid"
      type="button"
      class="card p-4 m-0 text-left flex flex-col gap-3 hover:shadow-md transition-shadow cursor-pointer"
      @click="router.push(`/unifolio/portfolios/${p.uuid}`)"
    >
      <div class="flex items-start justify-between gap-2">
        <span class="font-semibold">{{ p.titre }}</span>
        <Tag :value="p.type.libelle" :severity="p.type.value === 'academique' ? 'success' : 'info'" />
      </div>
      <span class="text-sm text-muted-color">
        {{ [p.scolarite?.annee, p.scolarite?.anneeUniversitaire].filter(Boolean).join(' · ') }}
      </span>
      <div class="flex flex-wrap gap-2 text-xs">
        <Tag :value="`${p.nbPages} page(s)`" severity="secondary" />
        <Tag v-if="p.campagne" :value="p.campagne.phase.libelle" :severity="p.campagne.phase.badge" />
        <Tag v-if="!p.modifiable" value="Lecture seule" severity="warn" icon="pi pi-lock" />
        <Tag :value="p.visibilite.libelle" severity="secondary" />
      </div>
      <span class="text-xs text-muted-color">Modifié le {{ date(p.misAJourLe) }}<template v-if="p.modele"> · modèle « {{ p.modele }} »</template></span>
    </button>
  </div>
</template>
