<script setup>
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { HeaderComponent, EmptyState } from '@components';
import ProgressBar from 'primevue/progressbar';
import { useUnifolioContexteStore } from '../stores/unifolioContexteStore';
import { unifolioSections } from '../navigation';

const router = useRouter();
const contexte = useUnifolioContexteStore();

onMounted(() => contexte.charger());

const sections = computed(() =>
  unifolioSections
    .map((section) => ({
      ...section,
      items: section.items.filter((item) => contexte.peut(item.capacite)),
    }))
    .filter((section) => section.items.length > 0),
);
</script>

<template>
  <HeaderComponent
    icon="pi pi-folder"
    titre="UniFolio"
    description="Portfolio de compétences du BUT : traces, auto-évaluation, évaluation et portfolios."
    color="emerald"
  />

  <ProgressBar v-if="contexte.isLoading" mode="indeterminate" style="height: 4px" />

  <EmptyState
    v-else-if="contexte.error"
    icon="pi pi-exclamation-triangle"
    color="rose"
    title="Espace indisponible"
    :description="contexte.error"
  />

  <EmptyState
    v-else-if="contexte.isLoaded && !contexte.peut('acces')"
    icon="pi pi-lock"
    color="gray"
    title="UniFolio n'est pas disponible pour votre profil"
    description="L'espace portfolio est réservé aux étudiants inscrits en BUT et à leurs enseignants."
  />

  <div v-else-if="contexte.isLoaded" class="flex flex-col gap-6">
    <div v-if="contexte.scolarite" class="card p-4 flex flex-wrap gap-x-8 gap-y-2 text-sm">
      <span><strong>Formation :</strong> {{ contexte.scolarite.diplome ?? '—' }}</span>
      <span><strong>Année :</strong> {{ contexte.scolarite.annee ?? '—' }}</span>
      <span><strong>Année universitaire :</strong> {{ contexte.scolarite.anneeUniversitaire ?? '—' }}</span>
    </div>

    <section v-for="section in sections" :key="section.titre" class="flex flex-col gap-3">
      <h2 class="text-lg font-semibold">{{ section.titre }}</h2>
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <button
          v-for="item in section.items"
          :key="item.to"
          type="button"
          class="card p-4 text-left flex gap-3 items-start hover:shadow-md transition-shadow cursor-pointer"
          @click="router.push(item.to)"
        >
          <i :class="item.icon" class="text-2xl text-emerald-600 mt-1" />
          <span class="flex flex-col gap-1">
            <span class="font-medium">{{ item.label }}</span>
            <span class="text-sm text-muted-color">{{ item.description }}</span>
          </span>
        </button>
      </div>
    </section>
  </div>
</template>
