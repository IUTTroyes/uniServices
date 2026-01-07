<script setup>
import {computed, onMounted, ref, watch} from "vue";
import {ErrorView, ListSkeleton, PermissionGuard, SimpleSkeleton, ValidatedInput} from "@components";
import {useDiplomeStore, useUsersStore} from "@stores";
import {
  exportService,
  getAnneeUniversitaireService,
  getEdtEventsService,
  getEnseignementsService,
  getPersonnelsService,
  getPrevisService,
  getSallesService
} from "@requests";
import Loader from "@components/loader/GlobalLoader.vue";

// Aide: formater un objet Date en YYYY-MM-DD en heure locale (sans décalage de fuseau)
const formaterDateLocale = (d) => {
  if (!d) return null;
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const usersStore = useUsersStore();
const departement = usersStore.departementDefaut;
const hasError = ref(false);
const minDate = ref();
const maxDate = ref();
const periode = ref(null);
const diplomes = ref([]);
const diplomeStore = useDiplomeStore();
const isLoadingDiplomes = ref(true);
const selectedDiplome = ref(null);
const selectedAnnee = ref(null);
const selectedSemestre = ref(null);
const selectedAnneeId = ref(null);
const selectedSemestreId = ref(null);
const selectedEnseignantId = ref(null);
const selectedEnseignementId = ref(null);
const enseignements = ref([]);
const isLoadingEnseignements = ref(false);
const enseignants = ref([]);
const isLoadingEnseignants = ref(true);
const hasErrorEnseignants = ref(false);
const salles = ref([]);
const isLoadingSalles = ref(true);
const selectedSalleId = ref(null);
const isLoadingEventsData = ref(false);
const eventsData = ref(null);
const statsPreviData = ref(null);
const isLoadingStatsPreviData = ref(false);

const features = [
  { id: 'enseignant', libelle: 'Par enseignant' },
  { id: 'enseignement', libelle: 'Par enseignement' },
];
const selectedFeature = ref(features[0].id);


onMounted( async() => {
  isLoadingEventsData.value = true;
  await setPeriodeFromAnneeUniversitaire();
  await getDiplomes();
  await getEnseignements();
  await getEnseignants();
  await getSalles();
  await getEventsData();
  await getStatsPreviData();
  isLoadingEventsData.value = false;
});

const deriveBornesAnneeUniv = (au) => {
  // Calcul les dates de début et de fin de l'année universitaire à partir du libellé ou de la date actuelle
  const now = new Date();
  const match = au?.libelle ? au.libelle.match(/(\d{4})\s*[-/]\s*(\d{4})/) : null;
  let startYear;
  let endYear;
  if (match) {
    startYear = parseInt(match[1], 10);
    endYear = parseInt(match[2], 10);
  } else {
    const m = now.getMonth(); // 0=Jan, 8=Sep
    if (m >= 8) { // Sep..Dec → academic year starts this year
      startYear = now.getFullYear();
      endYear = now.getFullYear() + 1;
    } else { // Jan..Aug → academic year started last year
      startYear = now.getFullYear() - 1;
      endYear = now.getFullYear();
    }
  }
  const start = new Date(startYear, 8, 1, 0, 0, 0, 0); // 1 Sep startYear
  const end = new Date(endYear, 7, 31, 23, 59, 59, 999); // 31 Aug endYear
  return { start, end };
};

const bornerDate = (d, min, max) => {
  // Contraint une date entre min et max
  if (!d) return d;
  if (d < min) return new Date(min);
  if (d > max) return new Date(max);
  return d;
};

const setPeriodeFromAnneeUniversitaire = async () => {
  try {
    let anneeUniversitaire = anneeUniv;
    if (anneeUniversitaire?.id) {
      try {
        // Récupère l'AU complète au cas où d'autres infos seraient nécessaires plus tard ; pas indispensable pour les bornes
        anneeUniversitaire = await getAnneeUniversitaireService(anneeUniversitaire.id);
      } catch (e) {
        // ignore, we will still use libelle from localStorage
        anneeUniversitaire = anneeUniv;
      }
    }
    const { start, end } = deriveBornesAnneeUniv(anneeUniversitaire);
    minDate.value = start;
    maxDate.value = end;

    // Si l'année universitaire est active, utiliser la semaine en cours comme période par défaut sinon toute l'année
    const isActiveAu = anneeUniversitaire?.actif === true || anneeUniversitaire?.isActif === true;
    if (isActiveAu) {
      const now = new Date();
      // Calculer le lundi et vendredi de la semaine en cours
      const day = now.getDay();
      const diffToMonday = (day + 6) % 7;
      const monday = new Date(now);
      monday.setHours(0, 0, 0, 0);
      monday.setDate(now.getDate() - diffToMonday);
      const friday = new Date(monday);
      friday.setDate(monday.getDate() + 4);
      friday.setHours(23, 59, 59, 999);
      const clampedStart = bornerDate(monday, start, end);
      const clampedEnd = bornerDate(friday, start, end);
      periode.value = [clampedStart, clampedEnd];
    } else {
      // Utiliser toute l'année universitaire
      periode.value = [new Date(start), new Date(end)];
    }
  } catch (e) {
    // En cas d'erreur, définir une période par défaut de 30 jours avant/après aujourd'hui
    const today = new Date();
    const priorDate = new Date();
    priorDate.setDate(today.getDate() - 30);
    const futureDate = new Date();
    futureDate.setDate(today.getDate() + 30);
    minDate.value = priorDate;
    maxDate.value = futureDate;
    periode.value = [minDate.value, maxDate.value];
  }
};

const getDiplomes = async () => {
  isLoadingDiplomes.value = true;
  try {
    diplomes.value = await diplomeStore.diplomes;
    // retirer les diplomes inactifs
    diplomes.value = (diplomes.value || []).filter(diplome => diplome.actif);
  } catch (error) {
    hasError.value = true;
    console.error('Error fetching diplomes:', error);
  } finally {
    // Par défaut, aucun diplôme/année/semestre sélectionné
    selectedDiplome.value = null;
    selectedAnnee.value = null;
    selectedSemestre.value = null;
    selectedAnneeId.value = null;
    selectedSemestreId.value = null;
    isLoadingDiplomes.value = false;
  }
};

const getEnseignements = async () => {
  isLoadingEnseignements.value = true;
  try {
    console.log(selectedSemestre.value);
    console.log(selectedAnnee.value);
    const params = {
      semestre: selectedSemestre.value ? selectedSemestre.value.id : null,
      annee: selectedAnnee.value ? selectedAnnee.value.id : null,
      departement: selectedAnnee.value ? null : departement.id,
      actif: true,
    };
    enseignements.value = await getEnseignementsService(params);

    // reconstruire le libelle pour inclure le code_enseignement
    enseignements.value = enseignements.value.map(enseignement => ({
      ...enseignement,
      libelle: `${enseignement.codeEnseignement} - ${enseignement.libelle}`
    }));
  } catch (error) {
    console.error('Erreur lors du chargement des enseignements :', error);
  } finally {
    isLoadingEnseignements.value = false;
  }
};

const getEnseignants = async () => {
  isLoadingEnseignants.value = true;
  try {
    const params = {
      departement: departement.id,
    };
    enseignants.value = await getPersonnelsService(params);
  } catch (error) {
    hasErrorEnseignants.value = true;
    console.error('Erreur lors du chargement des enseignants :', error);
  } finally {
    isLoadingEnseignants.value = false;
  }
};

const getSalles = async () => {
  try {
    salles.value = await getSallesService();
  } catch (error) {
    console.error('Erreur lors du chargement des salles :', error);
  } finally {
    isLoadingSalles.value = false;
  }
};

watch(selectedAnneeId, (newId) => {
  if (selectedDiplome.value) {
    selectedAnnee.value = (selectedDiplome.value.annees || []).find(a => a.id === newId) || null;
  } else {
    selectedAnnee.value = null;
  }
  selectedSemestre.value = null;
  selectedSemestreId.value = null;
  getEventsData();
  getStatsPreviData();
  getEnseignements();
});

watch(selectedSemestreId, async (newId) => {
  if (selectedAnnee.value) {
    selectedSemestre.value = (selectedAnnee.value.semestres || []).find(s => s.id === newId) || null;
  }
  getEventsData();
  getStatsPreviData();
  getEnseignements();
});
const setDiplome = async (diplome) => {
  selectedDiplome.value = diplome;
  if (!diplome) {
    console.log(selectedDiplome.value);
    selectedAnnee.value = null;
    selectedAnneeId.value = null;
    selectedSemestre.value = null;
    selectedSemestreId.value = null;
    return;
  }
  selectedAnnee.value = diplome.annees?.[0] || null;
  selectedAnneeId.value = selectedAnnee.value?.id ?? null;
  selectedSemestre.value = null;
  selectedSemestreId.value = null;
};

const reinitialiserFiltres = () => {
  // Aucun diplôme/année/semestre sélectionné par défaut
  selectedDiplome.value = null;
  selectedAnnee.value = null;
  selectedAnneeId.value = null;
  selectedSemestre.value = null;
  selectedSemestreId.value = null;
  // reset period to full academic year bounds
  periode.value = [minDate.value, maxDate.value];
  selectedEnseignantId.value = null;
  selectedEnseignementId.value = null;
  selectedSalleId.value = null;
};

const getEventsData = async () => {
  isLoadingEventsData.value = true;
  try {
    let debutDate = Array.isArray(periode.value) ? periode.value[0] : null;
    let finDate = Array.isArray(periode.value) ? periode.value[1] : null;
    if (debutDate || finDate) {
      debutDate = bornerDate(debutDate || minDate.value, minDate.value, maxDate.value);
      finDate = bornerDate(finDate || maxDate.value, minDate.value, maxDate.value);
    }

    const params = {
      departement: departement.id,
      semestre: selectedSemestre.value ? selectedSemestre.value.id : null,
      annee: selectedAnnee.value ? selectedAnnee.value.id : null,
      enseignement: selectedEnseignementId.value,
      personnel: selectedEnseignantId.value,
      salle: selectedSalleId.value,
      debut: debutDate ? formaterDateLocale(debutDate) : null,
      fin: finDate ? formaterDateLocale(finDate) : null,
    }
    const resp = await getEdtEventsService(params, '/stats');
    eventsData.value = Array.isArray(resp) ? (resp[0] || null) : resp;

  } catch (error) {
    console.error('Erreur lors du chargement des événements :', error);
  } finally {
    isLoadingEventsData.value = false;
  }
};

const getStatsPreviData = async () => {
  isLoadingStatsPreviData.value = true;
  try {
    const params = {
      semestre: selectedSemestre.value ? selectedSemestre.value.id : null,
      departement: selectedSemestre.value ? null : departement.id,
      anneeUniversitaire: anneeUniv.id,
    }
    statsPreviData.value = await getPrevisService(params, '/stats_edt');
    console.log(statsPreviData.value);
  } catch (error) {
    console.error('Erreur lors du chargement des données prévisionnelles :', error);
  } finally {
    isLoadingStatsPreviData.value = false;
  }
};


// Préparer les données pour le chart
const chartDataTypes = computed(() => {
  const rep = eventsData.value?.repartitionTypes || null;
  if (!rep || !rep.length) return null;
  const labels = rep.map(r => r.type);
  const data = rep.map(r => Number(r.pourcentage) || 0);
  const palette = ['#4dc9f6','#f67019','#f53794','#537bc4','#acc236','#166a8f','#00a950','#58595b','#8549ba'];
  const backgroundColor = labels.map((_, i) => palette[i % palette.length]);
  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor,
      }
    ]
  };
});

const chartDataSemestres = computed(() => {
  const rep = eventsData.value?.repartitionSemestres || null;
  if (!rep || !rep.length) return null;
  const labels = rep.map(r => r.semestre);
  const data = rep.map(r => Number(r.pourcentage) || 0);
  const palette = ['#4dc9f6','#f67019','#f53794','#537bc4','#acc236','#166a8f','#00a950','#58595b','#8549ba'];
  const backgroundColor = labels.map((_, i) => palette[i % palette.length]);
  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor,
      }
    ]
  };
});

const optionGraphTypes = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
};

const optionGraphSemestres = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
};

// Types de groupe affichés dans le comparatif (dynamiques selon le provider)
const typesList = computed(() => {
  const types = statsPreviData.value?.typesGroupes;
  return Array.isArray(types) && types.length > 0 ? types : ['CM', 'TD', 'TP', 'Projet'];
});

// Pivot des lignes (enseignement, type) en une ligne par enseignement avec sous-colonnes par type
const comparatifPreviRowsEnseignement = computed(() => {
  const rows = statsPreviData.value?.statPreviEdtEnseignement || [];
  if (!Array.isArray(rows) || rows.length === 0) return [];
  const map = new Map();
  for (const r of rows) {
    const key = r.enseignement;
    if (!map.has(key)) {
      const base = { enseignement: key };
      // init all sub-fields to 0 for predictable columns
      for (const t of typesList.value) {
        base[`previ_${t}`] = 0;
        base[`edt_${t}`] = 0;
      }
      map.set(key, base);
    }
    const row = map.get(key);
    const t = r.type;
    if (typesList.value.includes(t)) {
      row[`previ_${t}`] = Number(r.heures_previsionnel || 0);
      row[`edt_${t}`] = Number(r.heures_edt || 0);
    }
    row['heures_diff'] = r.heures_diff || 0;
  }
  return Array.from(map.values());
});

// Pivot des lignes (enseignant, type) en une ligne par enseignant avec sous-colonnes par type
const comparatifPreviRowsEnseignant = computed(() => {
  const rows = statsPreviData.value?.statPreviEdtEnseignant || [];
  if (!Array.isArray(rows) || rows.length === 0) return [];
  const map = new Map();
  for (const r of rows) {
    const key = r.enseignant;
    if (!map.has(key)) {
      const base = { enseignant: key };
      for (const t of typesList.value) {
        base[`previ_${t}`] = 0;
        base[`edt_${t}`] = 0;
      }
      map.set(key, base);
    }
    const row = map.get(key);
    const t = r.type;
    if (typesList.value.includes(t)) {
      row[`previ_${t}`] = Number(r.heures_previsionnel || 0);
      row[`edt_${t}`] = Number(r.heures_edt || 0);
    }
    row['heures_diff'] = r.heures_diff || 0;
  }
  return Array.from(map.values());
});

// Choix des lignes affichées selon la feature sélectionnée
const displayedComparatifRows = computed(() => {
  return selectedFeature.value === 'enseignant' ? comparatifPreviRowsEnseignant.value : comparatifPreviRowsEnseignement.value;
});

// Clé et entête de la première colonne selon la feature
const firstColKey = computed(() => selectedFeature.value === 'enseignant' ? 'enseignant' : 'enseignement');
const firstColHeader = computed(() => selectedFeature.value === 'enseignant' ? 'Enseignant' : 'Enseignement');

// Helpers: totaux et différence
const sumByPrefix = (line, prefix) => typesList.value.reduce((acc, t) => acc + Number(line[`${prefix}_${t}`] || 0), 0);
const previTotal = (line) => sumByPrefix(line, 'previ');
const edtTotal = (line) => sumByPrefix(line, 'edt');
const diffTotal = (line) => {
  return edtTotal(line) - previTotal(line);
};
const diffSeverity = (line) => {
  const d = diffTotal(line);
  if (d === 0) return 'success'; // égal
  if (d > 0) return 'danger';    // trop programmé
  return 'warn';               // pas assez
};

const applyFilters = async () => {
  await getEventsData();
};

const exportDataPrevi = async () => {
  try {
    await exportService(statsPreviData.value, '/previ', 'export_previsionnel')
  } catch (error) {
    console.error('Erreur lors de l\'export des données :', error);
  }
}

const exportDataHeures = async () => {
  try {
    console.log(eventsData.value);
    await exportService(eventsData.value, '/edt-heures', 'export_heures_edt')
  } catch (error) {
    console.error('Erreur lors de l\'export des données :', error);
  }
}
</script>

<template>
  <ErrorView v-if="hasError"></ErrorView>
  <div v-else class="border border-gray-300 dark:border-gray-700 rounded-lg p-6 mb-6 w-full bg-neutral-100/20">
    <div class="text-lg font-bold mb-4">Filtres</div>

    <div>
      <SimpleSkeleton v-if="isLoadingDiplomes" class="w-full"/>
      <Tabs v-else :value="selectedDiplome ? selectedDiplome.id : null" scrollable>
        <TabList>
          <Tab :key="0" :value="null" @click="setDiplome(null)">
            <span>Tous les diplômes</span>
          </Tab>
          <Tab v-for="diplome in diplomes" :key="diplome.libelle" :value="diplome.id" @click="setDiplome(diplome)">
        <span>
          <span>{{ diplome.typeDiplome.sigle }}</span> | <span>{{ diplome.sigle }}</span> <Tag v-if="!diplome.actif" severity="danger">Inactif</Tag>
        </span>
          </Tab>
        </TabList>
      </Tabs>
      <div v-if="isLoadingDiplomes" class="flex items-center gap-4 w-full">
        <SimpleSkeleton class="w-1/2"/>
        <SimpleSkeleton class="w-1/2"/>
      </div>
      <div v-else class="mt-8 flex items-center gap-4 w-full">
        <ValidatedInput
            v-model="selectedAnneeId"
            :options="(selectedDiplome?.annees || []).map(annee => ({...annee, label: annee.libelle, value: annee.id}))"
            name="annee"
            label="Années"
            type="select"
            :rules="[]"
            class="w-full"
            :show-clear="false"
            :disabled="!selectedDiplome"
        />
        <ValidatedInput
            v-model="selectedSemestreId"
            :options="(selectedAnnee?.semestres || []).map(semestre => ({...semestre, label: semestre.libelle, value: semestre.id}))"
            name="semestre"
            label="Semestres"
            type="select"
            :rules="[]"
            class="w-full"
            :show-clear="true"
            :disabled="!selectedAnnee"
        />
      </div>
    </div>
    <Divider></Divider>
    <div class="flex items-end flex-wrap gap-4 mb-6 h-full">
      <ValidatedInput
          v-model="periode"
          name="date"
          label="Période"
          type="date"
          :rules="[]"
          selectionMode="range"
          :manualInput="false"
          :minDate="minDate"
          :maxDate="maxDate"
          placeholder="Sélectionner une période"
          class="!mb-0"
      />

      <div v-if="isLoadingEnseignements" class="w-1/4">
        <div>Enseignements</div>
        <SimpleSkeleton class="w-full"/>
      </div>
      <ValidatedInput
          v-else
          v-model="selectedEnseignementId"
          :options="enseignements.map(enseignement => ({...enseignement, label: enseignement.libelle, value: enseignement.id}))"
          name="enseignement"
          label="Enseignements"
          type="select"
          :rules="[]"
          class="w-1/4 !mb-0"
          placeholder="Sélectionner un enseignement"
          :show-clear="true"
      />

      <div v-if="isLoadingSalles" class="w-1/4">
        <div>Salles</div>
        <SimpleSkeleton class="w-full"/>
      </div>
      <ValidatedInput
          v-else
          v-model="selectedSalleId"
          :options="salles.map(salle => ({...salle, label: salle.libelle, value: salle.id}))"
          name="salle"
          label="Salles"
          type="select"
          :rules="[]"
          class="w-1/4 !mb-0"
          placeholder="Sélectionner une salle"
          :showClear="true"
      />

      <PermissionGuard :permission="'canViewPersonnelDetails'" :showFallback="true">
        <div v-if="isLoadingEnseignants" class="w-1/4">
          <div>Enseignants</div>
          <SimpleSkeleton class="w-full"/>
        </div>
        <ValidatedInput
            v-else
            v-model="selectedEnseignantId"
            :options="enseignants.map(enseignant => ({...enseignant, label: enseignant.display, value: enseignant.id}))"
            name="enseignant"
            label="Enseignants"
            type="select"
            :rules="[]"
            class="w-1/4 !mb-0"
            placeholder="Sélectionner un enseignant"
            :show-clear="true"
        />

        <template #fallback>
          <div>
            <div>Mes statistiques</div>
            <ToggleSwitch
                v-model="selectedEnseignantId"
                @change="!selectedEnseignantId ? selectedEnseignantId = null : selectedEnseignantId = usersStore.user.id"
            />
          </div>
        </template>
      </PermissionGuard>

    </div>

    <div class="flex items-center justify-end gap-4 w-full">
      <Button label="Appliquer les filtres" icon="pi pi-filter" severity="primary" @click="applyFilters"/>
      <Button label="Réinitialiser les filtres" icon="pi pi-filter" severity="secondary" @click="reinitialiserFiltres"/>
    </div>
  </div>

  <Loader v-if="isLoadingEventsData" class="my-12"/>
  <div v-else class="flex flex-col gap-6 w-full">
    <div class="text-xl font-bold">
      Statistiques pour
      <span v-if="selectedAnnee"> - {{ selectedAnnee.libelle }}
          <span v-if="selectedSemestre" class="font-medium text-muted-color"> - {{ selectedSemestre.libelle }}</span>
        </span>
      <span v-else>Tous les diplômes</span>
    </div>
    <div class="flex items-stretch gap-4 h-full">
      <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-6 w-full">
        <div class="text-lg font-bold mb-4">Nombre d'heures programmées</div>
        <div>
          <div v-if="eventsData">
            <div class="mb-4 flex flex-col justify-center items-center w-full border border-gray-300 dark:border-gray-700 rounded-lg p-6 bg-white/10">
              <div class="text-lg">Total d'heures programmées</div>
              <div class="text-2xl font-bold">{{ eventsData.totalHeures }} h</div>
            </div>
            <Divider></Divider>
            <div v-if="eventsData.heuresParType && Object.keys(eventsData.heuresParType).length" class="mb-4 border border-gray-300 dark:border-gray-700 rounded-lg p-6 bg-white/10">
              <div class="text-center text-lg">Heures par type</div>
              <DataTable :value="Object.entries(eventsData.heuresParType).map(([type, heures]) => ({ type, heures }))" class="w-full">
                <Column field="type" header="Type d'activité" />
                <Column field="heures" header="Heures programmées">
                  <template #body="slotProps">
                    {{ slotProps.data.heures }} h
                  </template>
                </Column>
              </DataTable>
            </div>
            <Button label="Exporter en xlsx" icon="pi pi-file" severity="secondary" class="w-full" @click="exportDataHeures()" />
          </div>
          <div v-else class="text-gray-500">Aucune donnée à afficher pour les filtres sélectionnés.</div>
        </div>
      </div>
      <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-6 w-full flex flex-col">
        <div class="text-lg font-bold">Répartition par types d'activités</div>
        <div class="text-sm text-gray-600 mb-2">Pourcentage par types</div>
        <div v-if="eventsData" class="flex flex-col justify-start gap-4 h-full">
          <Chart v-if="chartDataTypes" type="pie" :data="chartDataTypes" :options="optionGraphTypes" />
          <DataTable :value="eventsData.repartitionTypes" >
            <Column field="type" header="Type d'activité" />
            <Column field="pourcentage" header="Pourcentage (%)">
              <template #body="slotProps">
                {{ slotProps.data.pourcentage }} %
              </template>
            </Column>
          </DataTable>
        </div>
        <div v-else class="text-gray-500">Aucune répartition disponible.</div>
      </div>
    </div>
    <div v-if="!selectedAnnee" class="border border-gray-300 dark:border-gray-700 rounded-lg p-6 w-full">
      <div class="text-lg font-bold">Répartition par semestres</div>
      <div v-if="eventsData">
        <div class="text-sm text-gray-600 mb-2">Pourcentage par semestres</div>
        <Chart v-if="chartDataSemestres" type="pie" :data="chartDataSemestres" :options="optionGraphSemestres" class="w-full h-full" />

        <DataTable :value="eventsData.repartitionSemestres" class="mt-4">
          <Column field="semestre" header="Semestre" />
          <Column field="heures" header="Heures programmées">
            <template #body="slotProps">
              {{ slotProps.data.heures }} h
            </template>
          </Column>
          <Column field="pourcentage" header="Pourcentage (%)">
            <template #body="slotProps">
              {{ slotProps.data.pourcentage }} %
            </template>
          </Column>
        </DataTable>
      </div>
      <div v-else class="text-gray-500">Aucune répartition disponible.</div>
    </div>
    <Divider/>
    <ListSkeleton v-if="isLoadingStatsPreviData" class="w-full"/>
    <PermissionGuard :permission="'canViewPersonnelDetails'">
      <div class="border border-gray-300 dark:border-gray-700 rounded-lg p-6 w-full">
        <div class="mb-4">
          <div class="text-lg font-bold">Comparatif prévisionnel</div>
          <em>Cette section compare les heures programmées avec les heures prévues selon les enseignements associés.</em>
        </div>
        <Message severity="info" class="mb-4" icon="pi pi-question-circle">
          Les filtres ne sont pas pris en compte dans ce comparatif, qui se base uniquement sur le diplôme, l'année et le semestre sélectionnés.
        </Message>
        <div class="text-xl font-bold">
          Comparatif pour
          <span v-if="selectedAnnee"> - {{ selectedAnnee.libelle }}
          <span v-if="selectedSemestre" class="font-medium text-muted-color"> - {{ selectedSemestre.libelle }}</span>
        </span>
          <span v-else>Tous les diplômes</span>
        </div>
        <div v-if="displayedComparatifRows">
          <Message v-if="displayedComparatifRows.length < 1" severity="warn" class="mb-4 w-fit mx-auto" icon="pi pi-info-circle">
            Aucune donnée de comparatif prévisionnel disponible pour les filtres sélectionnés.
          </Message>
          <div class="flex flex-col gap-4 w-full items-center justify-center" v-else>
            <SelectButton
                :options="features"
                v-model="selectedFeature"
                class="w-full justify-center"
                optionLabel="libelle"
                optionValue="id"
            />

            <DataTable :value="displayedComparatifRows" class="mt-4 w-full" show-gridlines paginator :rows="10" :rowsPerPageOptions="[10, 25, 50]">
              <ColumnGroup type="header">
                <Row>
                  <Column :header="firstColHeader" :rowspan="2" />
                  <Column header="Heures prévisionnel" :colspan="typesList.length + 1" />
                  <Column header="Heures edt" :colspan="typesList.length + 1" />
                  <Column header="Différence" :rowspan="2" />
                </Row>
                <Row>
                  <Column v-for="t in typesList" :key="'previ-h-' + t" :header="t" :class="t === 'CM' ? '!bg-purple-400' : t === 'TD' ? '!bg-green-400' : t === 'TP' ? '!bg-yellow-400' : t === 'Projet' ? '!bg-blue-400' : ''" class="!bg-opacity-40 !text-nowrap"/>
                  <Column header="Total" />
                  <Column v-for="t in typesList" :key="'edt-h-' + t" :header="t" :class="t === 'CM' ? '!bg-purple-400' : t === 'TD' ? '!bg-green-400' : t === 'TP' ? '!bg-yellow-400' : t === 'Projet' ? '!bg-blue-400' : ''" class="!bg-opacity-40 !text-nowrap"/>
                  <Column header="Total" />
                </Row>
              </ColumnGroup>

              <Column>
                <template #body="slotProps">
                  {{ slotProps.data[firstColKey] }}
                </template>
              </Column>

              <Column v-for="t in typesList" :key="'previ-' + t" :class="t === 'CM' ? 'bg-purple-400' : t === 'TD' ? 'bg-green-400' : t === 'TP' ? 'bg-yellow-400' : t === 'Projet' ? 'bg-blue-400' : ''" class="bg-opacity-20 text-nowrap">
                <template #body="slotProps">
                  {{ (slotProps.data['previ_' + t] || 0) }} h
                </template>
              </Column>
              <Column>
                <template #body="slotProps">
                  <strong>{{ previTotal(slotProps.data) }} h</strong>
                </template>
              </Column>

              <Column v-for="t in typesList" :key="'edt-' + t" :class="t === 'CM' ? 'bg-purple-400' : t === 'TD' ? 'bg-green-400' : t === 'TP' ? 'bg-yellow-400' : t === 'Projet' ? 'bg-blue-400' : ''" class="bg-opacity-20 text-nowrap">
                <template #body="slotProps">
                  {{ (slotProps.data['edt_' + t] || 0) }} h
                </template>
              </Column>
              <Column>
                <template #body="slotProps">
                  <strong>{{ edtTotal(slotProps.data) }} h</strong>
                </template>
              </Column>

              <Column>
                <template #body="slotProps">
                  <Tag :severity="diffSeverity(slotProps.data)" rounded>
                    <i v-if="diffTotal(slotProps.data) > 0" class="pi pi-arrow-up"></i>
                    <i v-else-if="diffTotal(slotProps.data) < 0" class="pi pi-arrow-down"></i>
                    {{ (slotProps.data['heures_diff'] || 0) }} h
                  </Tag>
                </template>
              </Column>
            </DataTable>

            <div class="flex items-center justify-end w-full gap-4">
              <router-link :to="`/administration/previsionnel/semestre`">
                <Button label="Accéder au prévisionnel" icon="pi pi-arrow-right" severity="primary" @click="" />
              </router-link>
              <Button label="Exporter en xlsx" icon="pi pi-file" severity="secondary" @click="exportDataPrevi()" />
            </div>
          </div>
        </div>
      </div>
    </PermissionGuard>
  </div>
</template>

<style scoped>
</style>
