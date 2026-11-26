<script setup>
import { ref, onMounted, watch } from 'vue';
import { EmptyState, ErrorState, ListSkeleton } from '@components';
import { getAnneesUniversitairesService, getSemestresService } from '@requests';
import { useUsersStore } from '@stores';
import { getSelectedAnneeUniversitaireId } from '@helpers/contexteAnnuel.js';
import ContexteSelection from '@components/components/Structure/ContexteSelection.vue';
import { usePrevisionnelSemestres } from '@/composables/usePrevisionnelSemestres.js';
import { copierPrevisionnelService, importerPrevisionnelService } from '@/service/previsionnelActionsService.js';
import { colonnesPrevisionnel, lireCsvPrevisionnel } from '@/service/utils/previsionnelCsv.js';

const { semestres, semestre, chargementSemestres, erreurSemestres } = usePrevisionnelSemestres();
const departementId = useUsersStore().departementDefaut?.id;
const ancienneAnnee = ref(null);
const annees = ref([]);
const source = ref(null);
const sources = ref([]);
const fichier = ref(null);
const fichierInput = ref(null);
const chargementSource = ref(false);
const chargementAnnees = ref(true);
const enregistrement = ref(false);
const erreurAnnees = ref(false);
const erreurSource = ref(false);
const erreur = ref('');
const resultat = ref('');

onMounted(async () => {
  try {
    const disponibles = await getAnneesUniversitairesService();
    annees.value = (disponibles ?? []).filter(({ id }) => id !== getSelectedAnneeUniversitaireId());
  } catch {
    erreurAnnees.value = true;
  } finally {
    chargementAnnees.value = false;
  }
});

watch(ancienneAnnee, async (annee, _, onCleanup) => {
  let courant = true;
  onCleanup(() => { courant = false; });
  source.value = null;
  sources.value = [];
  erreurSource.value = false;
  chargementSource.value = Boolean(annee);
  if (!annee || !departementId) return;

  try {
    const liste = await getSemestresService({ departement: departementId, anneeUniversitaire: annee.id, pagination: false }, '/annee-light');
    if (courant) {
      sources.value = (liste ?? []).map((item) => ({
        ...item, label: [item.annee?.libelle, item.libelle].filter(Boolean).join(' · '),
      }));
    }
  } catch {
    if (courant) erreurSource.value = true;
  } finally {
    if (courant) chargementSource.value = false;
  }
});

watch(semestre, () => { resultat.value = ''; erreur.value = ''; });

const executer = async (action) => {
  enregistrement.value = true;
  resultat.value = '';
  erreur.value = '';
  try {
    const reponse = await action();
    resultat.value = `${reponse.ajoutees} ligne(s) ajoutée(s).`;
  } catch (exception) {
    erreur.value = exception.response?.data?.detail ?? exception.message;
  } finally {
    enregistrement.value = false;
  }
};

const importer = () => executer(async () => {
  const lignes = await lireCsvPrevisionnel(fichier.value);
  const resultat = await importerPrevisionnelService(semestre.value.id, lignes);
  fichier.value = null;
  if (fichierInput.value) fichierInput.value.value = '';
  return resultat;
});
const copier = () => executer(() => copierPrevisionnelService(semestre.value.id, source.value.id));

const telechargerModele = () => {
  const url = URL.createObjectURL(new Blob(['\ufeff', colonnesPrevisionnel.join(';'), '\r\n'], { type: 'text/csv;charset=utf-8' }));
  const lien = document.createElement('a');
  lien.href = url;
  lien.download = 'modele-import-previsionnel.csv';
  lien.click();
  setTimeout(() => URL.revokeObjectURL(url), 0);
};
</script>

<template>
  <ContexteSelection titre="Semestre de destination" :valeur="semestre?.libelle">
    <div class="flex flex-col gap-2">
      <label for="previ-destination">Semestre</label>
      <Select v-model="semestre" input-id="previ-destination" :options="semestres" option-label="libelle" :loading="chargementSemestres" :disabled="enregistrement" />
    </div>
  </ContexteSelection>
  <ErrorState v-if="erreurSemestres" />
  <ListSkeleton v-else-if="chargementSemestres" />
  <EmptyState v-else-if="!semestre" title="Aucun semestre de destination disponible" />
  <div v-else class="grid gap-4 lg:grid-cols-2">
    <Message v-if="resultat" severity="success" class="lg:col-span-2">{{ resultat }}</Message>
    <Message v-if="erreur" severity="error" class="lg:col-span-2">{{ erreur }}</Message>
    <section class="flex flex-col gap-3 rounded-border border border-surface p-4">
      <h3 class="font-semibold">Importer des affectations</h3>
      <p class="text-muted-color">Renseignez l’identifiant du personnel, le code de l’enseignement, les heures totales et le nombre de groupes.</p>
      <Button label="Télécharger le modèle CSV" icon="pi pi-download" outlined class="self-start" @click="telechargerModele" />
      <label for="previ-import">Fichier CSV</label>
      <input id="previ-import" ref="fichierInput" type="file" accept=".csv,text/csv" :disabled="enregistrement" @change="fichier = $event.target.files[0] ?? null">
      <Button label="Importer" icon="pi pi-upload" :disabled="!fichier || enregistrement" class="self-start" @click="importer" />
    </section>
    <section class="flex flex-col gap-3 rounded-border border border-surface p-4">
      <h3 class="font-semibold">Copier les affectations d’un semestre</h3>
      <p class="text-muted-color">Choisissez un semestre du même diplôme. La copie associe les enseignements par code et type. Un conflit avec une affectation existante bloque la copie.</p>
      <Message v-if="erreurAnnees || erreurSource" severity="error">Impossible de charger les semestres source.</Message>
      <label for="previ-source-annee">Année universitaire source</label>
      <Select v-model="ancienneAnnee" input-id="previ-source-annee" :options="annees" option-label="libelle" :loading="chargementAnnees" :disabled="enregistrement" />
      <label for="previ-source-semestre">Semestre source</label>
      <Select v-model="source" input-id="previ-source-semestre" :options="sources" option-label="label" :loading="chargementSource" :disabled="!ancienneAnnee || enregistrement" />
      <Button label="Copier les affectations" icon="pi pi-copy" :disabled="!source || enregistrement" class="self-start" @click="copier" />
    </section>
  </div>
</template>
