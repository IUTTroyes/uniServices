<script setup>
import { ref, watch } from 'vue';
import { EmptyState, ErrorState, ListSkeleton } from '@components';
import { getSemestrePreviService } from '@requests';
import ContexteSelection from '@/components/Structure/ContexteSelection.vue';
import { usePrevisionnelSemestres } from '@/composables/usePrevisionnelSemestres.js';

const { semestres, semestre, chargementSemestres, erreurSemestres } = usePrevisionnelSemestres();
const donnees = ref(null);
const chargement = ref(false);
const erreur = ref(false);
const types = ['CM', 'TD', 'TP'];
const format = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 });

watch(semestre, async (selection, _, onCleanup) => {
  let courant = true;
  onCleanup(() => { courant = false; });
  donnees.value = null;
  erreur.value = false;
  chargement.value = Boolean(selection);
  if (!selection) return;

  try {
    const resultat = await getSemestrePreviService(selection.id);
    if (courant) donnees.value = resultat;
  } catch {
    if (courant) erreur.value = true;
  } finally {
    if (courant) chargement.value = false;
  }
});

const celluleCsv = (valeur) => {
  const texte = String(valeur ?? '');
  const contenu = typeof valeur === 'string' && /^[\s]*[=+\-@]/.test(texte) ? "'" + texte : texte;
  return '"' + contenu.replaceAll('"', '""') + '"';
};

const exporter = () => {
  const mesures = ['Maquette', 'Previsionnel', 'Diff'];
  const entete = ['Code', 'Enseignement', 'Intervenants', ...types.flatMap((type) => mesures.map((mesure) => `${type} ${mesure}`))];
  const lignes = donnees.value[1].map((ligne) => [
    ligne.codeEnseignement,
    ligne.libelleEnseignement,
    ligne.personnels.length,
    ...types.flatMap((type) => mesures.map((mesure) => ligne.heures[type][mesure])),
  ]);
  const contenu = [entete, ...lignes].map((ligne) => ligne.map(celluleCsv).join(';')).join('\r\n');
  const url = URL.createObjectURL(new Blob(['\ufeff', contenu], { type: 'text/csv;charset=utf-8' }));
  const lien = document.createElement('a');
  lien.href = url;
  lien.download = `previsionnel-semestre-${semestre.value.id}.csv`;
  lien.click();
  setTimeout(() => URL.revokeObjectURL(url), 0);
};
</script>

<template>
  <ContexteSelection titre="Semestre de travail" :valeur="semestre?.libelle">
    <div class="flex flex-col gap-2">
      <label for="previ-synthese-semestre">Semestre</label>
      <Select v-model="semestre" input-id="previ-synthese-semestre" :options="semestres" option-label="libelle" :loading="chargementSemestres" />
    </div>
  </ContexteSelection>
  <ErrorState v-if="erreurSemestres || erreur" />
  <ListSkeleton v-else-if="chargementSemestres || chargement" />
  <EmptyState v-else-if="!donnees" title="Aucun semestre disponible" />
  <div v-else class="flex flex-col gap-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <p class="m-0">Groupes du semestre · {{ types.map((type) => type + ' ' + (donnees[6]?.[type] ?? 0)).join(' · ') }}</p>
      <Button label="Exporter la synthèse CSV" icon="pi pi-download" outlined :disabled="!donnees[1].length" @click="exporter" />
    </div>
    <DataTable :value="donnees[1]" data-key="id" scrollable striped-rows size="small">
      <template #empty>Aucun enseignement dans ce semestre.</template>
      <Column field="libelleEnseignement" header="Enseignement" sortable />
      <Column header="Intervenants">
        <template #body="{ data }">{{ data.personnels.length }}</template>
      </Column>
      <Column v-for="type in types" :key="type" :header="`${type} · prévues / maquette`">
        <template #body="{ data }">{{ format.format(data.heures[type].Previsionnel) }} / {{ format.format(data.heures[type].Maquette) }} h</template>
      </Column>
      <Column field="heures.Total.Diff" header="Écart total" sortable>
        <template #body="{ data }">
          <Tag :value="`${format.format(data.heures.Total.Diff)} h`" :severity="data.heures.Total.Diff === 0 ? 'success' : 'warn'" />
        </template>
      </Column>
    </DataTable>
    <p class="font-semibold">{{ format.format(donnees[5].TotalClassique) }} h de service · {{ format.format(donnees[5].TotalTd) }} h équivalent TD</p>
  </div>
</template>
