<script setup>
import { ref, watch } from 'vue';
import { EmptyState, ErrorState, ListSkeleton } from '@components';
import { getEnseignementsService, getSemestreEnseignementPreviService } from '@requests';
import ContexteSelection from '@/components/Structure/ContexteSelection.vue';
import { usePrevisionnelSemestres } from '@/composables/usePrevisionnelSemestres.js';

const { semestres, semestre, chargementSemestres, erreurSemestres } = usePrevisionnelSemestres();
const enseignements = ref([]);
const enseignement = ref(null);
const chargementEnseignements = ref(false);
const chargement = ref(false);
const erreurEnseignements = ref(false);
const erreur = ref(false);
const donnees = ref(null);
const types = ['CM', 'TD', 'TP'];
const format = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 });

watch(semestre, async (selection, _, onCleanup) => {
  let courant = true;
  onCleanup(() => { courant = false; });
  enseignement.value = null;
  enseignements.value = [];
  erreurEnseignements.value = false;
  chargementEnseignements.value = Boolean(selection);
  if (!selection) return;

  try {
    const liste = await getEnseignementsService({ semestre: selection.id, pagination: false });
    if (!courant) return;
    enseignements.value = (liste ?? []).map((item) => ({
      ...item, label: [item.codeEnseignement, item.libelle].filter(Boolean).join(' · '),
    }));
    enseignement.value = enseignements.value[0] ?? null;
  } catch {
    if (courant) erreurEnseignements.value = true;
  } finally {
    if (courant) chargementEnseignements.value = false;
  }
});

watch(enseignement, async (selection, _, onCleanup) => {
  let courant = true;
  onCleanup(() => { courant = false; });
  donnees.value = null;
  erreur.value = false;
  chargement.value = Boolean(selection);
  if (!selection) return;

  try {
    const resultat = await getSemestreEnseignementPreviService(semestre.value.id, selection.id);
    if (courant) donnees.value = resultat;
  } catch {
    if (courant) erreur.value = true;
  } finally {
    if (courant) chargement.value = false;
  }
});
</script>

<template>
  <ContexteSelection titre="Enseignement de travail" :valeur="[semestre?.libelle, enseignement?.label].filter(Boolean).join(' · ')">
    <div class="flex flex-col gap-2">
      <label for="previ-matiere-semestre">Semestre</label>
      <Select v-model="semestre" input-id="previ-matiere-semestre" :options="semestres" option-label="libelle" :loading="chargementSemestres" />
    </div>
    <div class="flex flex-col gap-2">
      <label for="previ-matiere-enseignement">Enseignement</label>
      <Select v-model="enseignement" input-id="previ-matiere-enseignement" :options="enseignements" option-label="label" :loading="chargementEnseignements" filter />
    </div>
  </ContexteSelection>
  <ErrorState v-if="erreurSemestres || erreurEnseignements || erreur" />
  <ListSkeleton v-else-if="chargementSemestres || chargementEnseignements || chargement" />
  <EmptyState v-else-if="!donnees" title="Aucun enseignement disponible pour cette sélection." />
  <div v-else class="flex flex-col gap-4">
    <div class="grid gap-3 sm:grid-cols-3">
      <section v-for="type in types" :key="type" class="rounded-border border border-surface p-4">
        <h3 class="mb-2 font-semibold">{{ type }}</h3>
        <p>{{ format.format(donnees[1][type].NbHrSaisi) }} h prévues sur {{ format.format(donnees[1][type].NbHrAttendu) }} h par étudiant</p>
        <p class="text-muted-color">{{ format.format(donnees[2]['Total' + type]) }} h de service</p>
      </section>
    </div>
    <DataTable :value="donnees[0]" scrollable striped-rows size="small">
      <template #empty>Aucun intervenant affecté à cet enseignement.</template>
      <Column field="personnel.display" header="Intervenant" sortable />
      <template v-for="type in types" :key="type">
        <Column :field="`heures.${type}.NbHrGrp`" :header="`${type} · h/groupe`">
          <template #body="{ data }">{{ format.format(data.heures[type].NbHrGrp) }}</template>
        </Column>
        <Column :field="`heures.${type}.NbGrp`" :header="`${type} · groupes`" />
      </template>
    </DataTable>
    <p class="font-semibold">{{ format.format(donnees[3].TotalClassique) }} h de service · {{ format.format(donnees[3].TotalTd) }} h équivalent TD</p>
  </div>
</template>
