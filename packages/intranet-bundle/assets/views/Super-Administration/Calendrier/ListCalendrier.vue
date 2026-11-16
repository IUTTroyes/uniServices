<script setup>
import { ref, onMounted, watch } from 'vue';
import { getAnneesUniversitairesService, getCalendrierUniversitaireService, updateAnneeUniversitaireService } from '@requests';
import { HeaderComponent, ErrorState, ListSkeleton } from '@components';

const annees = ref([]);
const selectedAnnee = ref(null);
const dateDebut = ref('');
const semaines = ref([]);
const loading = ref(false);
const saving = ref(false);
const error = ref('');
const load = async () => {
  const annee = annees.value.find(a => a.id === selectedAnnee.value);
  dateDebut.value = annee?.dateDebutSemaineFormation?.slice(0, 10) ?? '';
  semaines.value = [];
  error.value = '';
  if (!annee || !dateDebut.value) return;
  loading.value = true;
  try {
    semaines.value = (await getCalendrierUniversitaireService(annee.id)).semaines;
  } catch (e) {
    error.value = e.response?.data?.message ?? 'Impossible de calculer le calendrier.';
  } finally {
    loading.value = false;
  }
};
const save = async () => {
  saving.value = true;
  error.value = '';
  try {
    await updateAnneeUniversitaireService(selectedAnnee.value, { dateDebutSemaineFormation: dateDebut.value || null });
    const annee = annees.value.find(a => a.id === selectedAnnee.value);
    annee.dateDebutSemaineFormation = dateDebut.value || null;
    await load();
  } catch (e) {
    error.value = e.response?.data?.detail ?? e.response?.data?.message ?? 'Impossible d’enregistrer la date de début. Vérifiez qu’il s’agit d’un lundi.';
  } finally {
    saving.value = false;
  }
};
watch(selectedAnnee, load);
onMounted(async () => {
  loading.value = true;
  try {
    annees.value = await getAnneesUniversitairesService();
    selectedAnnee.value = (annees.value.find(a => a.actif) ?? annees.value[0])?.id ?? null;
  } catch (e) {
    error.value = 'Impossible de charger les années universitaires.';
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="space-y-6">
    <HeaderComponent titre="Calendrier universitaire" icon="pi pi-calendar" description="Semaines continues jusqu’au 31 août de l’année suivante, vacances comprises." />
    <div class="flex flex-wrap items-end gap-4">
      <div>
        <label for="annee-calendrier" class="block mb-2">Année universitaire</label>
        <select id="annee-calendrier" v-model="selectedAnnee" class="border rounded p-2" :disabled="saving">
          <option v-for="annee in annees" :key="annee.id" :value="annee.id">{{ annee.libelle }}</option>
        </select>
      </div>
      <div>
        <label for="debut-calendrier" class="block mb-2">Lundi de la semaine n°1</label>
        <input id="debut-calendrier" v-model="dateDebut" type="date" class="border rounded p-2" :disabled="saving" />
      </div>
      <Button label="Enregistrer" :loading="saving" :disabled="!selectedAnnee || loading" @click="save" />
    </div>
    <ListSkeleton v-if="loading" />
    <ErrorState v-else-if="error" :message="error" />
    <Message v-else-if="!semaines.length" severity="info">Renseignez le lundi de la semaine n°1 pour configurer le calendrier.</Message>
    <DataTable v-else :value="semaines" paginator :rows="15" data-key="dateLundi">
      <Column field="semaineFormation" header="Semaine de formation" />
      <Column field="dateLundi" header="Lundi" />
      <Column field="dateFin" header="Fin de semaine dans l’année universitaire" />
      <Column field="semaineReelle" header="Semaine ISO" />
      <Column field="anneeIso" header="Année ISO" />
    </DataTable>
  </div>
</template>
