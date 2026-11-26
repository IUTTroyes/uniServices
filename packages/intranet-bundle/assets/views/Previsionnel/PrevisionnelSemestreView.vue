<script setup>
import ContexteSelection from '@components/components/Structure/ContexteSelection.vue';
import { computed, onMounted, ref, watch } from 'vue';
import { EmptyState, ErrorState, ListSkeleton, ButtonDelete } from "@components";
import { useUsersStore } from "@stores";
import {
  createPreviService,
  deletePreviService,
  getAnneesService,
  getEnseignementsService,
  getPersonnelsService,
  getSemestrePreviService,
  getSemestresService,
  updatePreviService
} from "@requests";
import { showDanger } from "@helpers";
import { getSelectedAnneeUniversitaireId } from "@helpers/contexteAnnuel.js";
import { viderPrevisionnelService } from "@/service/previsionnelActionsService.js";
import { construireHeures } from "@/service/utils/previsionnelHeures.js";
import PrevisionnelTable from "@/components/Previsionnel/PrevisionnelTable.vue";

const hasError = ref(false);
const anneeUnivId = getSelectedAnneeUniversitaireId();
const departementId = useUsersStore().departementDefaut?.id;
const isSaving = ref(false);
let requestId = 0;
const annees = ref([]);
const isLoadingAnnees = ref(true);
const selectedAnnee = ref(null);
const semestres = ref([]);
const isLoadingSemestres = ref(true);
const selectedSemestre = ref(null);
const isLoadingPrevisionnel = ref(true);
const previSemestre = ref(null);
const previSemestreOriginal = ref(null); // Copie pour annulation
const editingRowId = ref(null);
const enseignementsList = ref([]);
const selectedEnseignement = ref(null);
const personnels = ref([]);
const selectedPersonnel = ref(null);
const isEditing = ref(false);
const size = ref({ label: 'Petit', value: 'small' });
const sizeOptions = ref([
  { label: 'Petit', value: 'small' },
  { label: 'Normal', value: 'null' },
  { label: 'Large', value: 'large' }
]);
const searchTerm = ref('');
const filters = ref({
  'libelleEnseignement': { value: null, matchMode: 'contains' }
});
watch(searchTerm, (newTerm) => {
  filters.value['libelleEnseignement'].value = newTerm;
});

onMounted(async () => {
  await getAnnees();
})

const getAnnees = async () => {
  isLoadingAnnees.value = true;
  try {
    if (!departementId) return;
    annees.value = await getAnneesService({ departement: departementId }) ?? [];
    if (annees.value.length > 0) {
      selectedAnnee.value = annees.value[0];
    }
  } catch (error) {
    console.error("Erreur lors de la récupération des années :", error);
    hasError.value = true;
  } finally {
    isLoadingAnnees.value = false;
  }
};

const getSemestres = async () => {
  const anneeId = selectedAnnee.value?.id;
  selectedSemestre.value = null;
  semestres.value = [];
  hasError.value = false;
  if (!anneeId) return;

  isLoadingSemestres.value = true;
  try {
    const resultat = await getSemestresService({ annee: anneeId }, '/mini');
    if (selectedAnnee.value?.id !== anneeId) return;
    semestres.value = resultat ?? [];
    selectedSemestre.value = semestres.value[0] ?? null;
  } catch (error) {
    if (selectedAnnee.value?.id === anneeId) hasError.value = true;
  } finally {
    if (selectedAnnee.value?.id === anneeId) isLoadingSemestres.value = false;
  }
};

watch(selectedAnnee, getSemestres);
watch(selectedSemestre, (semestre) => getPrevi(semestre?.id));

const getPrevi = async (semestreId) => {
  const id = ++requestId;
  editingRowId.value = null;
  previSemestre.value = null;
  isLoadingPrevisionnel.value = Boolean(semestreId);
  if (!semestreId) return;

  hasError.value = false;
  try {
    const [previsionnel, enseignements, intervenants] = await Promise.all([
      getSemestrePreviService(semestreId, anneeUnivId),
      getEnseignementsService({ semestre: semestreId, pagination: false }),
      getPersonnelsService({ departement: departementId, pagination: false }),
    ]);
    if (id !== requestId) return;

    previSemestre.value = previsionnel;
    previSemestreOriginal.value = JSON.parse(JSON.stringify(previsionnel));
    enseignementsList.value = (enseignements ?? []).map((e) => ({ ...e, label: e.display ?? `${e.codeEnseignement ?? ''} ${e.libelle}`.trim() }));
    personnels.value = (intervenants ?? []).map((p) => ({ ...p, label: p.display }));
    selectedEnseignement.value = null;
    selectedPersonnel.value = null;
  } catch (error) {
    if (id === requestId) hasError.value = true;
  } finally {
    if (id === requestId) isLoadingPrevisionnel.value = false;
  }
};

const modifierPrevi = async (action) => {
  if (isSaving.value) return;
  const semestreId = selectedSemestre.value?.id;
  if (!semestreId) return;

  isSaving.value = true;
  try {
    await action();
    if (selectedSemestre.value?.id === semestreId) await getPrevi(semestreId);
  } finally {
    isSaving.value = false;
  }
};

const deleteRow = async (id) => {
  try {
    await modifierPrevi(() => deletePreviService(id));
  } catch {
    // Le service affiche le détail de l'erreur.
  }
};

const emptyPrevi = async () => {
  const ids = previSemestre.value?.[0]?.map(({ id }) => id) ?? [];
  if (!ids.length) return;

  try {
    await modifierPrevi(() => viderPrevisionnelService(selectedSemestre.value.id, ids));
  } catch {
    // Le service affiche le motif du refus.
  }
};

const duplicatePrevi = async (id) => {
  const ligne = previSemestre.value?.[0].find((p) => p.id === id);
  if (!ligne) return;

  try {
    await modifierPrevi(() => createPreviService({
      ...construireHeures(ligne),
      personnel: `/api/personnels/${ligne.idPersonnel}`,
      enseignement: `/api/scol_enseignements/${ligne.idEnseignement}`,
      anneeUniversitaire: `/api/structure_annee_universitaires/${anneeUnivId}`,
      referent: false,
    }));
  } catch (error) {
    if (error instanceof RangeError) showDanger(error.message);
  }
};

const updateHeuresPrevi = (id, type, valeur) => {
  const ligne = previSemestre.value?.[0].find((p) => p.id === id);
  ligne.heures[type].NbHrGrp = valeur;
  const total = Number(String(valeur).replace(',', '.')) * ligne.heures[type].NbGrp;
  ligne.heures[type].NbSeanceGrp = Number.isFinite(total) ? Math.round(total * 10) / 10 : null;
};

const updateGroupesPrevi = (id, type, valeur) => {
  const ligne = previSemestre.value?.[0].find((p) => p.id === id);
  ligne.heures[type].NbGrp = valeur;
  updateHeuresPrevi(id, type, ligne.heures[type].NbHrGrp);
};

const saveRow = async (id) => {
  const ligne = previSemestre.value?.[0].find((p) => p.id === id);
  if (!ligne) return;

  try {
    await modifierPrevi(() => updatePreviService(id, {
      ...construireHeures(ligne),
      personnel: `/api/personnels/${ligne.idPersonnel}`,
      enseignement: `/api/scol_enseignements/${ligne.idEnseignement}`,
    }));
  } catch (error) {
    if (error instanceof RangeError) showDanger(error.message);
  }
};

const cancelRow = () => {
  previSemestre.value = JSON.parse(JSON.stringify(previSemestreOriginal.value));
  editingRowId.value = null;
};

const updateIntervenantPrevi = (previId, personnel) => {
  // Récupérer le prévisionnel à modifier
  let previForm = previSemestre.value[0].find(previ => previ.id === previId);
  if (!previForm || !personnel) return;

  previForm.idPersonnel = personnel.id;
  previForm.intervenant = typeof personnel.display === 'string' ? personnel.display : `${personnel.prenom} ${personnel.nom}`;
};

const updateEnseignementPrevi = (previId, enseignement) => {
  // Récupérer le prévisionnel à modifier
  let previForm = previSemestre.value[0].find(previ => previ.id === previId);
  if (!previForm || !enseignement) return;

  previForm.idEnseignement = enseignement.id;
  previForm.libelleEnseignement = enseignement.label;
  previForm.codeEnseignement = enseignement.codeEnseignement;
};

watch(isEditing, () => {
  if (editingRowId.value !== null) cancelRow();
});

const addPrevi = async () => {
  if (!selectedPersonnel.value || !selectedEnseignement.value) return;

  try {
    await modifierPrevi(() => createPreviService({
      personnel: `/api/personnels/${selectedPersonnel.value.id}`,
      anneeUniversitaire: `/api/structure_annee_universitaires/${anneeUnivId}`,
      referent: false,
      heures: { CM: 0, TD: 0, TP: 0, Projet: 0 },
      groupes: { CM: 0, TD: 0, TP: 0, Projet: 0 },
      enseignement: `/api/scol_enseignements/${selectedEnseignement.value.id}`,
    }));
  } catch {
    // Le service affiche le détail de l'erreur.
  }
};

// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------SYNTHESE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const tagClass = (value) => {
  if (value === 0) return '!bg-green-400 !text-white';
  return value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white';
};

const tagSeverity = (value) => {
  if (value === 0) return 'success';
  return value < 0 ? 'warn' : 'danger';
};

const tagIcon = (value) => {
  if (value === 0) return 'pi pi-check';
  return value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up';
};

const columns = ref([
  { header: 'Code', field: 'codeEnseignement', sortable: true, colspan: 1 },
  { header: 'Nom', field: 'libelleEnseignement', sortable: true, colspan: 1 },
  { header: 'Type', field: 'typeEnseignement', sortable: true, colspan: 1 },
  { header: 'Nb profs', field: 'personnels.length', colspan: 1 },

  { header: 'Maq.', field: 'heures.CM.Maquette', colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h'},
  { header: 'Prévi.', field: 'heures.CM.Previsionnel', colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h' },
  { header: 'Diff.', field: 'heures.CM.Diff', sortable: true, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h', tag: true, tagClass, tagSeverity, tagIcon },

  { header: 'Maq.', field: 'heures.TD.Maquette', colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
  { header: 'Prévi.', field: 'heures.TD.Previsionnel', colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
  { header: 'Diff.', field: 'heures.TD.Diff', sortable: true, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h', tag: true, tagClass, tagSeverity, tagIcon },

  { header: 'Maq.', field: 'heures.TP.Maquette', colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
  { header: 'Prévi.', field: 'heures.TP.Previsionnel', colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
  { header: 'Diff.', field: 'heures.TP.Diff', sortable: true, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h', tag: true, tagClass, tagSeverity, tagIcon },

  { header: 'Maq.', field: 'heures.Total.Maquette', colspan: 1, unit: ' h' },
  { header: 'Prévi.', field: 'heures.Total.Previsionnel', colspan: 1, unit: ' h' },
  { header: 'Diff.', field: 'heures.Total.Diff', sortable: true, colspan: 1, unit: ' h', tag: true, tagClass, tagSeverity, tagIcon },
]);

const additionalRows = ref ([
  [
    { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold'},
  ]
]);

const topHeaderCols = ref([
  { header: 'CM', colspan: 3, class: '!bg-purple-400/20' },
  { header: 'TD', colspan: 3, class: '!bg-green-400/20' },
  { header: 'TP', colspan: 3, class: '!bg-amber-400/20' },
  { header: 'Total', colspan: 3 }
]);

const footerCols = computed(() => {
  if (!previSemestre.value || !previSemestre.value[2]) return [];

  const totals = previSemestre.value[2];
  return [
    { footer: 'Total', colspan: 4 },
    { footer: totals.CM.Maquette, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h' },
    { footer: totals.CM.Previsionnel, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h' },
    { footer: totals.CM.Diff, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h', tag: true, tagClass, tagSeverity, tagIcon },
    { footer: totals.TD.Maquette, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
    { footer: totals.TD.Previsionnel, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
    { footer: totals.TD.Diff, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h', tag: true, tagClass, tagSeverity, tagIcon },
    { footer: totals.TP.Maquette, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
    { footer: totals.TP.Previsionnel, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
    { footer: totals.TP.Diff, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h', tag: true, tagClass, tagSeverity, tagIcon },
    { footer: totals.Total.Maquette, colspan: 1, class: '!text-nowrap', unit: ' h' },
    { footer: totals.Total.Previsionnel, colspan: 1, class: '!text-nowrap', unit: ' h' },
    { footer: totals.Total.Diff, colspan: 1, unit: ' h', tag: true, tagClass, tagSeverity, tagIcon },
  ];
});

// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------FORMULAIRE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const columnsForm = ref([
  { header: 'Matière', field: 'libelleEnseignement', sortable: true, colspan: 1, class: '!overflow-hidden !truncate', form: true, formType: 'select', formOptions: enseignementsList, placeholder: "Sélectionner une matière", id: 'id', formAction: (previId, event) => { updateEnseignementPrevi(previId, event)}, disabled: () => false, style: 'min-width: 250px' },

  { header: 'Intervenant', field: 'intervenant', sortable: true, colspan: 1, class: '!wrapper !text-wrap', form: true, formType: 'select', formOptions: personnels, placeholder: "Sélectionner un intervenant", id: 'id', formAction: (previId, event) => { updateIntervenantPrevi(previId, event)}, disabled: () => false, style: 'min-width: 200px' },

{ header: 'Nb H/Gr.', name: 'nbHrGrpCM', field: 'heures.CM.NbHrGrp', colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) }, disabled: false },

  { header: 'Nb Gr.', name: 'NbGrpCM', field: 'heures.CM.NbGrp', colspan: 1, class: '!bg-purple-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) }, disabled: () => false },

  { header: 'Séances', field: 'heures.CM.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-purple-400/20 !max-w-20', form: false },

{ header: 'Nb H/Gr.', name: 'nbHrGrpTD', field: 'heures.TD.NbHrGrp', colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) }, disabled: false },

  { header: 'Nb Gr.', name: 'nbGrpTD', field: 'heures.TD.NbGrp', colspan: 1, class: '!bg-green-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) }, disabled: () => false },

  { header: 'Séances', field: 'heures.TD.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-green-400/20 !max-w-20', form: false },

{ header: 'Nb H/Gr.', name: 'nbHrGrpTD', field: 'heures.TP.NbHrGrp', colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) }, disabled: false },

  { header: 'Nb Gr.', name: 'nbGrpTD', field: 'heures.TP.NbGrp', colspan: 1, class: '!bg-amber-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) }, disabled: () => false },

  { header: 'Séances', field: 'heures.TP.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-amber-400/20 !max-w-20', form: false },

  { header: 'Actions', field: 'id', colspan: 1, actions: true, duplicate: true, duplicateAction: (id) => {duplicatePrevi(id)}, delete: true, class: 'w-24' },
]);

const topHeaderColsForm = ref([
  { header: 'CM', colspan: 3, class: '!bg-purple-400/20' },
  { header: 'TD', colspan: 3, class: '!bg-green-400/20' },
  { header: 'TP', colspan: 3, class: '!bg-amber-400/20' },
  { header: '', colspan: 1 },
]);

const additionalRowsForm = computed(() => {
  if (!previSemestre.value || !previSemestre.value[3]) return [];

  const totals = previSemestre.value[3];
  const totalEquiv = previSemestre.value[5];

  return [
    [
      { footer: 'Ajouter une entrée au prévisionnel', colspan: 2, class: '!text-center !font-bold'},
      { footer: enseignementsList.value, colspan: 4, form: true, formType: 'select', placeholder: "Sélectionner une matière", formAction: (e) => { selectedEnseignement.value = e } },
      { footer: personnels.value, colspan: 4, form: true, formType: 'select', placeholder: "Sélectionner un intervenant", formAction: (p) => { selectedPersonnel.value = p } },
      { footer: 'Ajouter', colspan: 2, button: true, buttonIcon: 'pi pi-plus', buttonAction: () => { addPrevi(selectedPersonnel.value, selectedEnseignement.value) }, buttonClass: () => '!w-full', buttonSeverity: () => 'success' },
    ],
    [
      { footer: 'Synthèse', colspan: 17, class: '!text-center !font-bold'},
    ],
    [
      { footer: '', colspan: 2, class: '!text-center !font-bold'},
      { footer: 'Nb hr attendu', colspan: 1, class: '!bg-purple-400/20 !text-nowrap !font-bold' },
      { footer: 'Nb hr saisi', colspan: 1, class: '!bg-purple-400/20 !text-nowrap !font-bold' },
      { footer: 'Diff', colspan: 1, class: '!bg-purple-400/20 !text-nowrap !font-bold' },
      { footer: 'Nb hr attendu', colspan: 1, class: '!bg-green-400/20 !text-nowrap !font-bold' },
      { footer: 'Nb hr saisi', colspan: 1, class: '!bg-green-400/20 !text-nowrap !font-bold' },
      { footer: 'Diff', colspan: 1, class: '!bg-green-400/20 !text-nowrap !font-bold' },
      { footer: 'Nb hr attendu', colspan: 1, class: '!bg-amber-400/20 !text-nowrap !font-bold' },
      { footer: 'Nb hr saisi', colspan: 1, class: '!bg-amber-400/20 !text-nowrap !font-bold' },
      { footer: 'Diff', colspan: 1, class: '!bg-amber-400/20 !text-nowrap !font-bold' },
      { footer: '', colspan: 1 },
    ],
    [
      { footer: 'Vérification du total d\'heures par étudiant', colspan: 2 },
      { footer: totals.CM.NbHrAttendu, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h' },
      { footer: totals.CM.NbHrSaisi, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h' },
      { footer: totals.CM.Diff, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h', tag: true, tagClass, tagSeverity, tagIcon },
      { footer: totals.TD.NbHrAttendu, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
      { footer: totals.TD.NbHrSaisi, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
      { footer: totals.TD.Diff, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h', tag: true, tagClass, tagSeverity, tagIcon },
      { footer: totals.TP.NbHrAttendu, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
      { footer: totals.TP.NbHrSaisi, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
      { footer: totals.TP.Diff, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h', tag: true, tagClass, tagSeverity, tagIcon },
      { footer: '', colspan: 1 },
    ],
    [
      { footer: '', colspan: 2},
      { footer: 'Classique', colspan: 4, class: '!text-nowrap !text-center font-bold' },
      { footer: 'Équivalent TD', colspan: 5, class: '!text-nowrap !text-center font-bold' },
      { footer: '', colspan: 1 },
    ],
    [
      { footer: 'Total d\'heures', colspan: 2},
      { footer: totalEquiv?.TotalClassique, colspan: 4, class: '!text-nowrap !text-center', unit: ' h' },
      { footer: totalEquiv?.TotalTd, colspan: 5, class: '!text-nowrap !text-center', unit: ' h' },
      { footer: '', colspan: 1 },
    ],
  ];
});

const footerColsForm = computed(() => {
  if (!previSemestre.value || !previSemestre.value[4]) return [];
  return [];
});
</script>

<template>
  <ContexteSelection titre="Semestre de travail" :valeur="[selectedAnnee?.libelle, selectedSemestre?.libelle].filter(Boolean).join(' · ')">
    <div class="flex flex-col gap-2"><label for="contexte-selectedAnnee" class="text-sm font-semibold text-muted-color">Année de formation</label>
      <Select :loading="isLoadingAnnees" :disabled="isLoadingAnnees || isSaving" input-id="contexte-selectedAnnee" class="w-full sm:w-60" v-model="selectedAnnee" option-label="libelle" :options="annees">
          <template #value>
            {{ selectedAnnee?.libelle || "Sélectionner une année" }}
          </template>
        </Select>
    </div>
    <div class="flex flex-col gap-2"><label for="contexte-selectedSemestre" class="text-sm font-semibold text-muted-color">Semestre</label>
      <Select :loading="isLoadingSemestres" :disabled="isLoadingSemestres || isSaving" input-id="contexte-selectedSemestre" class="w-full sm:w-60" v-model="selectedSemestre" option-label="libelle" :options="semestres">
          <template #value>
            {{ selectedSemestre?.libelle || "Sélectionner un semestre" }}
          </template>
        </Select>
    </div>
  </ContexteSelection>

  <ErrorState v-if="hasError" />
  <ListSkeleton v-else-if="isLoadingAnnees || isLoadingSemestres || (selectedSemestre && isLoadingPrevisionnel)" class="mt-6" />
  <EmptyState v-else-if="!previSemestre" title="Aucun semestre disponible pour cette sélection." />
  <div v-else class="px-4 flex flex-col">
    <div class="flex justify-between gap-10">

      <div class="flex items-center gap-4">
        <Button v-if="!isEditing" label="Saisir le prévisionnel" icon="pi pi-plus" :disabled="isSaving" @click="isEditing = !isEditing" />
        <Button v-else label="Afficher le prévisionnel" icon="pi pi-eye" :disabled="isSaving" @click="isEditing = !isEditing" />
        <ButtonDelete v-if="isEditing && !isSaving && previSemestre[0].length" tooltip="Vider le prévisionnel" label="Vider le prévisionnel" @confirm-delete="emptyPrevi"/>
      </div>
    </div>

    <div>
      <div class="flex w-full justify-between my-6">
        <SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
        <div class="flex justify-end">
          <IconField>
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="searchTerm" placeholder="Rechercher par matière" />
          </IconField>
        </div>
      </div>
      <div v-if="!isEditing">
        <PrevisionnelTable
            origin="previSemestreSynthese"
            :columns="columns"
            :topHeaderCols="topHeaderCols"
            :additionalRows="additionalRows"
            :footerCols="footerCols"
            :data="previSemestre[1]"
            :filters="filters"
            :size="size.value"
            :headerTitle="`Prévisionnel du semestre ${selectedSemestre?.libelle}`"
            :headerTitlecolspan="4"/>
      </div>
      <div v-else>
        <Message severity="info" class="my-6 flex justify-center" icon="pi pi-info-circle">
          Cliquer sur une ligne pour l'éditer, enregistrer les modifications ligne par ligne
        </Message>
        <div class="flex flex-wrap items-end gap-3 mb-4">
          <div class="flex flex-col gap-1">
            <label for="previ-enseignement">Enseignement</label>
            <Select v-model="selectedEnseignement" input-id="previ-enseignement" :options="enseignementsList" option-label="label" filter />
          </div>
          <div class="flex flex-col gap-1">
            <label for="previ-intervenant">Intervenant</label>
            <Select v-model="selectedPersonnel" input-id="previ-intervenant" :options="personnels" option-label="label" filter />
          </div>
          <Button label="Ajouter une ligne" icon="pi pi-plus" :loading="isSaving" :disabled="!selectedEnseignement || !selectedPersonnel" @click="addPrevi" />
        </div>
        <PrevisionnelTable
            origin="previSemestreForm"
            v-model:editingRowId="editingRowId"
            @save-row="saveRow"
            @delete-row="deleteRow"
            @cancel-row="cancelRow"
            :columns="columnsForm"
            :topHeaderCols="topHeaderColsForm"
            :additionalRows="additionalRowsForm"
            :footerCols="footerColsForm"
            :data="previSemestre[0]"
            :filters="filters"
            :size="size.value"
            :headerTitle="`Prévisionnel du semestre ${selectedSemestre?.libelle}`"
            :headerTitlecolspan="2"/>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
