<script setup>
import { onMounted, ref, watch, computed } from "vue";
import {SimpleSkeleton, ValidatedInput, ListSkeleton, HeaderComponent, UserCard, EdtEventRow} from "@components";
import {useAnneeStore, useSemestreStore, useUsersStore} from "@stores";
import {getAnneeService, getSemestresService, getGroupesService, getEtudiantScolariteSemestresService, getEdtEventsService, createEtudiantAbsenceService, getEtudiantAbsencesService} from "@requests";
import {useRoute, useRouter} from "vue-router";
import {Button} from "primevue";
import { useToast } from "primevue/usetoast";

const route = useRoute();
const router = useRouter();
const toast = useToast();
const hasError = ref(false);
const usersStore = useUsersStore();
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const departementId = usersStore.departementDefaut.id;
const semestreStore = useSemestreStore();
const anneeStore = useAnneeStore();
const semestres = ref([]);
const isLoadingSemestres = ref(true);
const semestre = ref({});
const annees = ref([]);
const annee = ref({});
const isLoadingAnnee = ref(true);
const groupes = ref([]);
const isLoadingGroupes = ref(true);
const selectedGroupe = ref([]);
const typesGroupes = ref([]);
const isLoadingTypesGroupes = ref(true);
const selectedTypeGroupe = ref(null);
const etudiantsScolSemestre = ref([]);
const isLoadingEtudiants = ref(false);
const selectedEtudiants = ref([]);
const searchEtudiant = ref("");

const getLocalDateString = (inputDate = new Date()) => {
  const currentDate = inputDate instanceof Date ? inputDate : new Date(inputDate);
  const year = currentDate.getFullYear();
  const month = String(currentDate.getMonth() + 1).padStart(2, '0');
  const day = String(currentDate.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
};

const normalizeDayForApi = (inputDate) => {
  if (!inputDate) {
    return null;
  }

  if (typeof inputDate === 'string') {
    return inputDate;
  }

  return getLocalDateString(inputDate);
};

const date = ref(getLocalDateString());
const edtEvents = ref([]);
const isLoadingEvents = ref(false);
const selectedEvents = ref([]);
const firstEtudiants = ref(0);
const rowsEtudiants = ref(12);
const firstEvents = ref(0);
const rowsEvents = ref(10);
const showSummaryModal = ref(false);
const showEditModal = ref(false);
const isSavingAbsences = ref(false);
const isCheckingAbsences = ref(false);
const absencesSummary = ref([]);
const editingAbsenceIndex = ref(null);
const editedEtudiantId = ref(null);
const editedEventId = ref(null);

onMounted(async () => {
  await getAnnees();
  await getAnnee();
  await getSemestres();
  // Sélectionner le semestre actif par défaut
  if (semestres.value.length > 0 && !semestre.value.id) {
    semestre.value = semestres.value.find(s => s.actif) || semestres.value[0];
  }
});

const getAnnees = async () => {
  if (anneeStore.annees && Array.isArray(anneeStore.annees) && anneeStore.annees.length > 0) {
    annees.value = anneeStore.annees;
    return;
  }
  try {
    const params = {
      departement: departementId,
      actif: true,
    };
    await anneeStore.getAnneesDepartement(params);
    annees.value = Array.isArray(anneeStore.annees) ? anneeStore.annees : [];
  } catch (error) {
    console.error("Erreur lors de la récupération des années :", error);
    hasError.value = true;
  }
};

const getAnnee = async () => {
  isLoadingAnnee.value = true;
  hasError.value = false;
  try {
    const anneeId = route.params.anneeId;
    annee.value = await getAnneeService(anneeId);
    await anneeStore.setSelectedAnnee(annee.value);
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération de l'année :", error);
  } finally {
    isLoadingAnnee.value = false;
  }
};

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  hasError.value = false;
  try {
    const params = {
      annee: annee.value.id,
    };
    semestres.value = await getSemestresService(params, '/mini');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des semestres :", error);
  } finally {
    isLoadingSemestres.value = false;
  }
};

const getTypesGroupes = async () => {
  isLoadingTypesGroupes.value = true;
  try {
    typesGroupes.value = semestre.value.typesGroupe;
  } catch (error) {
    console.error('Erreur lors du chargement des types de groupes :', error);
    hasError.value = true;
  } finally {
    selectedTypeGroupe.value = typesGroupes.value?.[0];
    isLoadingTypesGroupes.value = false;
  }
};

const getGroupes = async () => {
  isLoadingGroupes.value = true;
  try {
    const params = {
      semestre: semestre.value.id,
      type: selectedTypeGroupe.value,
    };
    groupes.value = await getGroupesService(params, '/mini');
  } catch (error) {
    console.error('Erreur lors du chargement des groupes :', error);
    hasError.value = true;
  } finally {
    isLoadingGroupes.value = false;
    // préselectionner le premier groupe
    selectedGroupe.value = groupes.value?.[0];
  }
};

const getEtudiantsScolSemestre = async () => {
  isLoadingEtudiants.value = true;
  try {
    const params = {
      groupe: selectedGroupe.value?.id,
      anneeUniversitaire: anneeUniv.id,
      pagination: false,
    };
    const response = await getEtudiantScolariteSemestresService(params, '/absence');
    etudiantsScolSemestre.value = Array.isArray(response) ? response : [];
    selectedEtudiants.value = selectedEtudiants.value.filter(selectedEtudiant =>
        etudiantsScolSemestre.value.some(etudiant => etudiant.id === selectedEtudiant.id)
    );
  } catch (error) {
    console.error('Erreur lors du chargement des étudiants scolarité semestre :', error);
    hasError.value = true;
    etudiantsScolSemestre.value = [];
  } finally {
    isLoadingEtudiants.value = false;
  }
};

const getEdtEvents = async () => {
  isLoadingEvents.value = true;
  try {
    if (!date.value || !selectedGroupe.value?.id) {
      edtEvents.value = [];
      selectedEvents.value = [];
      return;
    }

    const params = {
      groupe: selectedGroupe.value.id,
      anneeUniversitaire: anneeUniv.id,
      day: normalizeDayForApi(date.value),
    };

    const response = await getEdtEventsService(params);
    edtEvents.value = Array.isArray(response) ? response : [];
    selectedEvents.value = selectedEvents.value.filter(selectedEvent =>
        edtEvents.value.some(event => event.id === selectedEvent.id)
    );
  } catch (error) {
    console.error('Erreur lors du chargement des événements EDT :', error);
    hasError.value = true;
    edtEvents.value = [];
    selectedEvents.value = [];
  } finally {
    isLoadingEvents.value = false;
  }
};

const filteredEtudiants = computed(() => {
  const searchValue = searchEtudiant.value?.trim().toLowerCase();
  if (!searchValue) {
    return etudiantsScolSemestre.value;
  }

  return etudiantsScolSemestre.value.filter(etudiant => {
    const prenom = etudiant?.scolarite?.etudiant?.prenom?.toLowerCase() || '';
    const nom = etudiant?.scolarite?.etudiant?.nom?.toLowerCase() || '';
    const numeroEtudiant = etudiant?.scolarite?.etudiant?.numeroEtudiant?.toLowerCase() || '';
    const fullName = `${prenom} ${nom}`.trim();

    return (
        prenom.includes(searchValue)
        || nom.includes(searchValue)
        || fullName.includes(searchValue)
        || numeroEtudiant.includes(searchValue)
    );
  });
});

const paginatedEtudiants = computed(() => {
  return filteredEtudiants.value.slice(firstEtudiants.value, firstEtudiants.value + rowsEtudiants.value);
});

const paginatedEdtEvents = computed(() => {
  return edtEvents.value.slice(firstEvents.value, firstEvents.value + rowsEvents.value);
});

const formattedPaginatedEdtEvents = computed(() => {
  return paginatedEdtEvents.value.map(event => {
    const debutEvent = event?.debut ? new Date(event.debut).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '--:--';
    const finEvent = event?.fin ? new Date(event.fin).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '--:--';

    return {
      id: event.id,
      heure: `${debutEvent} - ${finEvent}`,
      groupe: event?.libGroupe || event?.codeGroupe || selectedGroupe.value?.libelle || '-',
      cours: event?.codeModule ? `${event.codeModule} - ${event?.libModule || ''}` : (event?.libModule || 'Cours'),
      salle: event?.salle || '-',
      color: event?.couleur,
      intervenant: event?.personnel.display,
      rawEvent: event,
    };
  });
});

const isFormValid = computed(() => {
  return selectedEtudiants.value.length > 0 && selectedEvents.value.length > 0;
});

const etudiantOptions = computed(() => {
  return etudiantsScolSemestre.value.map(etudiantScolSemestre => ({
    label: `${etudiantScolSemestre?.scolarite?.etudiant?.prenom || ''} ${etudiantScolSemestre?.scolarite?.etudiant?.nom || ''}`.trim(),
    value: etudiantScolSemestre.id,
  }));
});

const eventOptions = computed(() => {
  return edtEvents.value.map(event => {
    const debutEvent = event?.debut ? new Date(event.debut).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '--:--';
    const finEvent = event?.fin ? new Date(event.fin).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '--:--';
    const moduleLabel = event?.codeModule ? `${event.codeModule} - ${event?.libModule || ''}` : (event?.libModule || 'Cours');

    return {
      label: `${debutEvent} - ${finEvent} • ${moduleLabel}`,
      value: event.id,
    };
  });
});

const formatEventLabel = (event) => {
  const debutEvent = event?.debut ? new Date(event.debut).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '--:--';
  const finEvent = event?.fin ? new Date(event.fin).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '--:--';
  const moduleLabel = event?.codeModule ? `${event.codeModule} - ${event?.libModule || ''}` : (event?.libModule || 'Cours');

  return `${debutEvent} - ${finEvent} • ${moduleLabel}`;
};

const buildAbsenceItem = (etudiantScolSemestre, event) => {
  return {
    etudiantScolariteSemestreId: etudiantScolSemestre.id,
    eventId: event.id,
    etudiantLabel: etudiantScolSemestre?.scolarite?.etudiant?.display,
    eventLabel: formatEventLabel(event),
    personnelLabel: usersStore.user.display,
    payload: {
      scolariteSemestre: `/api/etudiant_scolarite_semestres/${etudiantScolSemestre.id}`,
      event: `/api/edt_events/${event.id}`,
      personnel: `/api/personnels/${usersStore.user.id}`,
    },
    alreadyExists: false,
    statusLabel: 'À créer',
  };
};

const updateAbsenceStatus = async (absenceItem) => {
  const params = {
    scolariteSemestre: absenceItem.payload.scolariteSemestre,
    event: absenceItem.payload.event,
  };

  const existingAbsences = await getEtudiantAbsencesService(params, '/administration');
  const alreadyExists = Array.isArray(existingAbsences) && existingAbsences.length > 0;

  return {
    ...absenceItem,
    alreadyExists,
    statusLabel: alreadyExists ? 'Existe déjà' : 'À créer',
  };
};

const refreshAbsencesStatuses = async () => {
  if (absencesSummary.value.length === 0) {
    return;
  }

  isCheckingAbsences.value = true;

  try {
    const updatedSummary = await Promise.all(
        absencesSummary.value.map(absenceSummaryItem => updateAbsenceStatus(absenceSummaryItem))
    );
    absencesSummary.value = updatedSummary;
  } catch (error) {
    console.error('Erreur lors de la vérification des absences existantes :', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Impossible de vérifier les absences existantes.',
      life: 4000,
    });
  } finally {
    isCheckingAbsences.value = false;
  }
};

const prepareAbsencesSummary = () => {
  absencesSummary.value = selectedEtudiants.value.flatMap(etudiantScolSemestre =>
      selectedEvents.value.map(event => buildAbsenceItem(etudiantScolSemestre, event))
  );
};

const removeAbsenceSummaryItem = (index) => {
  absencesSummary.value = absencesSummary.value.filter((_, currentIndex) => currentIndex !== index);
};

const openEditAbsenceModal = (index) => {
  const selectedAbsence = absencesSummary.value[index];
  if (!selectedAbsence) {
    return;
  }

  editingAbsenceIndex.value = index;
  editedEtudiantId.value = selectedAbsence.etudiantScolariteSemestreId;
  editedEventId.value = selectedAbsence.eventId;
  showEditModal.value = true;
};

const saveEditedAbsence = () => {
  if (editingAbsenceIndex.value === null || !editedEtudiantId.value || !editedEventId.value) {
    return;
  }

  const etudiant = etudiantsScolSemestre.value.find(etudiantScolSemestre => etudiantScolSemestre.id === editedEtudiantId.value);
  const event = edtEvents.value.find(edtEvent => edtEvent.id === editedEventId.value);

  if (!etudiant || !event) {
    return;
  }

  absencesSummary.value[editingAbsenceIndex.value] = buildAbsenceItem(etudiant, event);
  showEditModal.value = false;
  editingAbsenceIndex.value = null;
  editedEtudiantId.value = null;
  editedEventId.value = null;

  refreshAbsencesStatuses();
};

const isEtudiantSelected = (etudiantScolSemestre) => {
  return selectedEtudiants.value.some(selectedEtudiant => selectedEtudiant.id === etudiantScolSemestre.id);
};

const toggleEtudiantSelection = (etudiantScolSemestre) => {
  const etudiantAlreadySelected = isEtudiantSelected(etudiantScolSemestre);
  if (etudiantAlreadySelected) {
    selectedEtudiants.value = selectedEtudiants.value.filter(selectedEtudiant => selectedEtudiant.id !== etudiantScolSemestre.id);
    return;
  }

  selectedEtudiants.value.push(etudiantScolSemestre);
};

const toggleEventSelection = (event) => {
  const eventAlreadySelected = selectedEvents.value.some(selectedEvent => selectedEvent.id === event.id);
  if (eventAlreadySelected) {
    selectedEvents.value = selectedEvents.value.filter(selectedEvent => selectedEvent.id !== event.id);
    return;
  }

  selectedEvents.value.push(event);
};

// --- Watchers ---

watch(() => semestreStore.semestre, (newSemestre) => {
  semestre.value = newSemestre;
});

watch(semestre, async (newSemestre, oldSemestre) => {
  if (newSemestre?.id !== oldSemestre?.id) {
    await getTypesGroupes();
    await getGroupes();

    if (!selectedGroupe.value?.id) {
      etudiantsScolSemestre.value = [];
    }
  }
});

watch(annee, async (newAnnee, oldAnnee) => {
  if (newAnnee?.id === oldAnnee?.id) return;

  if (newAnnee?.id && String(route.params.anneeId) !== String(newAnnee.id)) {
    await router.replace({
      name: route.name,
      params: {
        ...route.params,
        anneeId: String(newAnnee.id),
      },
      query: route.query,
    });
  }

  await getSemestres();

  // Changer automatiquement de semestre lors d'un changement d'année
  semestre.value = semestres.value.find(s => s.actif) || semestres.value[0] || {};
  semestreStore.setSelectedSemestre(semestre.value);

  await anneeStore.setSelectedAnnee(newAnnee);
});

watch(selectedTypeGroupe, async (newTypeGroupe, oldTypeGroupe) => {
  if (newTypeGroupe !== oldTypeGroupe) {
    await getGroupes();
  }
});

watch(selectedGroupe, async (newGroupe, oldGroupe) => {
  if (newGroupe !== oldGroupe) {
    await getEtudiantsScolSemestre();
    await getEdtEvents();
  }
});

watch(date, async (newDate, oldDate) => {
  if (newDate !== oldDate) {
    await getEdtEvents();
  }
});

watch(filteredEtudiants, () => {
  firstEtudiants.value = 0;
});

watch(edtEvents, () => {
  firstEvents.value = 0;
});

const saveAbsences = async () => {
  if (!isFormValid.value) {
    return;
  }

  prepareAbsencesSummary();
  await refreshAbsencesStatuses();
  showSummaryModal.value = absencesSummary.value.length > 0;
};

const confirmAbsencesCreation = async () => {
  try {
    if (absencesSummary.value.length === 0) {
      return;
    }

    isSavingAbsences.value = true;

    const absencesToCreate = absencesSummary.value.filter(absenceSummaryItem => !absenceSummaryItem.alreadyExists);
    const skippedAbsencesCount = absencesSummary.value.length - absencesToCreate.length;

    for (const absenceSummaryItem of absencesToCreate) {
      await createEtudiantAbsenceService(absenceSummaryItem.payload);
    }

    toast.add({
      severity: 'success',
      summary: 'Succès',
      detail: `${absencesToCreate.length} absence(s) créée(s)${skippedAbsencesCount > 0 ? `, ${skippedAbsencesCount} déjà existante(s)` : ''}.`,
      life: 3000,
    });

    showSummaryModal.value = false;
    selectedEtudiants.value = [];
    selectedEvents.value = [];
    absencesSummary.value = [];
  } catch (error) {
    console.error('Erreur lors de la création des absences :', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'La création des absences a échoué.',
      life: 4000,
    });
  } finally {
    isSavingAbsences.value = false;
  }
};
</script>

<template>
  <HeaderComponent
      icon="pi pi-calendar"
      titre="Absences"
      description="Saisir des absences"
  />

  <section class="card">
    <!-- En-tête : titre + sélecteurs année/semestre -->
    <header class="card-header flex justify-between items-center w-full mb-6">
      <div>
        <p class="top-card-header">
          Contrôle des présences
        </p>
        <div class="flex flex-col items-start">
          <p class="uppercase text-xs font-bold mb-0! text-muted-color">
            Semestre
          </p>
          <h2 class="mt-0!">
            {{ semestre.libelle }}
          </h2>
        </div>
      </div>

      <SimpleSkeleton v-if="isLoadingSemestres" class="!w-60 !h-10" />
      <div v-else class="flex gap-4">
        <Select
            v-model="annee"
            class="w-60"
            option-label="libelle"
            :options="annees"
        >
          <template #value>
            {{ annee?.libelle || "Changer d'année" }}
          </template>
        </Select>

        <Select
            v-model="semestre"
            class="w-60"
            option-label="libelle"
            :options="semestres"
        >
          <template #value>
            {{ semestre?.libelle || "Changer de semestre" }}
          </template>
        </Select>
      </div>
    </header>

    <div class="card-body">

      <!-- Formulaire de recherche -->
      <fieldset class="flex flex-row gap-4 border-0 p-0 m-0">
        <legend class="sr-only">Recherche des absences</legend>
        <ValidatedInput
            v-model="date"
            name="date"
            label="Date"
            type="date"
            help-text="Date obligatoire"
            class="w-full"
            @validation=""
        />
        <ValidatedInput
            v-model="searchEtudiant"
            name="etudiant"
            label="Etudiant"
            type="text"
            help-text="Rechercher un étudiant"
            class="w-full"
            @validation=""
        />
      </fieldset>

      <!-- Sélection du type et du groupe -->
      <section class="groupes-section">
        <ListSkeleton v-if="isLoadingTypesGroupes" class="flex items-center gap-4 w-1/2" />
        <Tabs v-else :value="selectedTypeGroupe" scrollable>
          <TabList>
            <Tab
                v-for="typeGroupe in typesGroupes"
                :key="typeGroupe"
                :value="typeGroupe"
                @click="selectedTypeGroupe = typeGroupe"
            >
              {{ typeGroupe }}
            </Tab>
          </TabList>
        </Tabs>

        <SimpleSkeleton v-if="isLoadingGroupes" class="!w-60 !h-10 my-2" />
        <div v-else class="flex gap-4 my-2">
          <Button
              v-for="groupe in groupes"
              :key="groupe.id"
              @click="selectedGroupe = groupe"
          >
            {{ groupe.libelle }}
          </Button>
        </div>
      </section>

      <section class="w-full grid grid-cols-1 xl:grid-cols-3 gap-6 mt-4">
        <div class="flex flex-col justify-between xl:col-span-2">
          <div>
            <h3 class="m-0 mb-3">Etudiants</h3>
            <ListSkeleton v-if="isLoadingEtudiants" class="!w-full mt-4" />
            <div
                v-else-if="filteredEtudiants.length > 0"
                class="grid grid-cols-3 md:grid-cols-4 2xl:grid-cols-5 gap-4 w-full"
            >
              <UserCard
                  v-for="etudiantScolSemestre in paginatedEtudiants"
                  :key="etudiantScolSemestre.id ?? etudiantScolSemestre.scolarite.etudiant.id"
                  :user="etudiantScolSemestre.scolarite.etudiant"
                  class="cursor-pointer hover:shadow-lg hover:scale-105 transition-all text-xs!"
                  :class="{ 'bg-primary-500/20!': isEtudiantSelected(etudiantScolSemestre) }"
                  @click="toggleEtudiantSelection(etudiantScolSemestre)"
              />
            </div>
          </div>
          <div v-if="filteredEtudiants.length > 0" class="flex justify-center mt-4">
            <Paginator
                v-model:first="firstEtudiants"
                v-model:rows="rowsEtudiants"
                :totalRecords="filteredEtudiants.length"
                :rowsPerPageOptions="[12, 24, 48]"
            />
          </div>
          <Message v-else severity="warn" class="mb-4 w-fit" icon="pi pi-info-circle">
            Aucun étudiant trouvé.
          </Message>
        </div>

        <div class="flex flex-col justify-between xl:col-span-1">
          <div>
            <h3 class="m-0 mb-3">Cours du jour</h3>
            <Message v-if="edtEvents.length === 0" severity="warn" class="mb-4 w-fit" icon="pi pi-info-circle">
              Aucun cours trouvé pour cette date et ce groupe.
            </Message>
            <ListSkeleton v-if="isLoadingEvents" class="!w-full mt-4" />
            <div v-else-if="edtEvents.length > 0" class="flex flex-col gap-2">
              <EdtEventRow
                  v-for="eventItem in formattedPaginatedEdtEvents"
                  :key="eventItem.id"
                  :item="eventItem"
                  selectable
                  :selected="selectedEvents.some(selectedEvent => selectedEvent.id === eventItem.id)"
                  @select="toggleEventSelection(eventItem.rawEvent)"
              />
            </div>
          </div>
          <div v-if="edtEvents.length > 0" class="flex justify-center mt-4">
            <Paginator
                v-model:first="firstEvents"
                v-model:rows="rowsEvents"
                :totalRecords="edtEvents.length"
                :rowsPerPageOptions="[5, 10, 20]"
            />
          </div>
        </div>
      </section>

      <div class="w-full flex justify-center mt-4">
        <Button
            label="Enregistrer les absences"
            severity="primary"
            :disabled="!isFormValid"
            @click="saveAbsences"
        />
      </div>

      <Message v-if="!isFormValid" severity="info" class="mt-3 w-fit mx-auto" icon="pi pi-info-circle">
        Sélectionnez au moins un étudiant et au moins un cours pour créer une absence.
      </Message>

      <Dialog
          v-model:visible="showSummaryModal"
          modal
          header="Récapitulatif des absences à créer"
          :style="{ width: '75vw' }"
          :breakpoints="{ '1199px': '90vw', '575px': '95vw' }"
      >
        <DataTable :value="absencesSummary" striped-rows>
          <Column field="etudiantLabel" header="Étudiant" />
          <Column field="eventLabel" header="Cours" />
          <Column field="personnelLabel" header="Personnel" />
          <Column field="statusLabel" header="Statut" />
          <Column header="Actions" style="width: 10rem">
            <template #body="slotProps">
              <div class="flex gap-2">
                <Button
                    icon="pi pi-pencil"
                    size="small"
                    severity="secondary"
                    outlined
                    @click="openEditAbsenceModal(slotProps.index)"
                />
                <Button
                    icon="pi pi-trash"
                    size="small"
                    severity="danger"
                    outlined
                    @click="removeAbsenceSummaryItem(slotProps.index)"
                />
              </div>
            </template>
          </Column>
        </DataTable>

        <Message v-if="isCheckingAbsences" severity="info" class="mt-4" icon="pi pi-spin pi-spinner">
          Vérification des absences existantes en cours...
        </Message>

        <Message v-if="absencesSummary.length === 0" severity="warn" class="mt-4" icon="pi pi-info-circle">
          Aucune absence à créer. Ajoutez des lignes ou fermez la fenêtre.
        </Message>

        <template #footer>
          <div class="flex justify-end gap-2 w-full">
            <Button
                label="Annuler"
                severity="secondary"
                outlined
                @click="showSummaryModal = false"
            />
            <Button
                label="Confirmer la création"
                severity="primary"
                :disabled="absencesSummary.length === 0 || isCheckingAbsences"
                :loading="isSavingAbsences"
                @click="confirmAbsencesCreation"
            />
          </div>
        </template>
      </Dialog>

      <Dialog
          v-model:visible="showEditModal"
          modal
          header="Modifier une absence"
          :style="{ width: '40rem' }"
      >
        <div class="flex flex-col gap-4">
          <Select
              v-model="editedEtudiantId"
              :options="etudiantOptions"
              option-label="label"
              option-value="value"
              placeholder="Sélectionner un étudiant"
              class="w-full"
          />
          <Select
              v-model="editedEventId"
              :options="eventOptions"
              option-label="label"
              option-value="value"
              placeholder="Sélectionner un cours"
              class="w-full"
          />
        </div>

        <template #footer>
          <div class="flex justify-end gap-2 w-full">
            <Button
                label="Annuler"
                severity="secondary"
                outlined
                @click="showEditModal = false"
            />
            <Button
                label="Enregistrer"
                severity="primary"
                :disabled="!editedEtudiantId || !editedEventId"
                @click="saveEditedAbsence"
            />
          </div>
        </template>
      </Dialog>
    </div>
  </section>
</template>

<style scoped>
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
