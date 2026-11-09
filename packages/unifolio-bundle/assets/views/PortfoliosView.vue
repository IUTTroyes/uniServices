<script setup>
import { showDanger } from '@helpers/toast';
import { formatDateFr } from '@helpers/date';
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent, EmptyState, Card, LoadingBar } from '@components';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { useChargement } from '../composables/useChargement';
import { portfolioService, messagesErreur } from '../requests/portfolioService';

const router = useRouter();
const creation = ref(false);
const { donnees: portfolios, chargement, erreur } = useChargement(() => portfolioService.liste(), { initial: [], messageParDefaut: 'Impossible de charger vos portfolios.' });

const aVitrine = computed(() => portfolios.value.some((p) => p.type.value === 'vitrine'));
const creerVitrine = async () => {
  creation.value = true;
  try {
    const { data } = await portfolioService.creerVitrine();
    router.push(`/unifolio/portfolios/${data.uuid}`);
  } catch (e) {
    showDanger(messagesErreur(e).join(' '), 'Création impossible', 6000);
  } finally {
    creation.value = false;
  }
};

const date = (iso) => formatDateFr(iso);
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
      <Button v-if="!aVitrine && !chargement && !erreur" label="Créer ma vitrine" icon="pi pi-star" :loading="creation" @click="creerVitrine" />
      <Button label="Mon CV" icon="pi pi-id-card" severity="secondary" outlined @click="router.push('/unifolio/cv')" />
    </template>
  </HeaderComponent>

  <LoadingBar v-if="chargement" />
  <EmptyState v-else-if="erreur" icon="pi pi-exclamation-triangle" color="rose" title="Erreur" :description="erreur" />
  <EmptyState
    v-else-if="portfolios.length === 0"
    icon="pi pi-book"
    color="emerald"
    title="Pas encore de portfolio"
    description="Votre portfolio académique est créé dès l'ouverture d'une campagne pour votre année de BUT. En attendant, vous pouvez déposer vos traces, compléter votre CV et créer votre vitrine : un portfolio libre et durable, que vous pouvez partager."
  />

  <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    <Card bare tag="button"
 v-for="p in portfolios"
 :key="p.uuid"
 type="button"
 class="p-4 m-0 text-left flex flex-col gap-3 hover:shadow-md transition-shadow cursor-pointer"
 @click="router.push(`/unifolio/portfolios/${p.uuid}`)">
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
    </Card>
  </div>
</template>
