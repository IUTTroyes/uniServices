<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {ErrorView, ListSkeleton, SimpleSkeleton} from "@components";
import {useToast} from "primevue/usetoast";
import {useUsersStore, useDiplomeStore} from "@stores";
import {
  getPersonnelPreviService,
  getAnneeUnivPreviService,
  getPersonnelsService,
  getEnseignementsService,
  getSemestresService, getPersonnelEnseignantHrsService, getPersonnelEnseignantTypesHrsService,
} from "@requests";
import PrevisionnelTable from "@/components/Previsionnel/PrevisionnelTable.vue";

const toast = useToast();
const hasError = ref(false);
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const usersStore = useUsersStore();
const diplomeStore = useDiplomeStore();

const enseignements = ref([]);
const semestres = ref([]);
const selectedSemestre = ref(null);
const departementId = usersStore.departementDefaut.id;
const isLoadingPrevisionnel = ref(true);
const previPersonnels = ref([]);
const previPersonnelsOriginal = ref([]);
const previPersonnel = ref([]);
const personnels = ref([]);
const selectedPersonnel = ref(null);
const isLoadingPersonnels = ref(true);
const isEditing = ref(false);
const personnelEnseignantHrs = ref([]);
const typeHrs = ref([]);
const selectedTypeHrs = ref(null);
const nbHeuresHrs = ref(0);
const libelleHrs = ref(null);
const selectedSemestreHrs = ref(null);
const selectedDiplomeHrs = ref(null);
const diplomes = ref([]);
const selectedDiplome = ref(null);
const editingRowId = ref(null);


const size = ref({ label: 'Petit', value: 'small' });
const sizeOptions = ref([
  { label: 'Petit', value: 'small' },
  { label: 'Normal', value: 'null' },
  { label: 'Large', value: 'large' }
]);
const searchTerm = ref('');
const filters = ref({
  'libelle': { value: null, matchMode: 'contains' }
});
watch(searchTerm, (newTerm) => {
  filters.value['libelle'].value = newTerm;
});

onMounted(async () => {
  await getPreviPersonnels();
});

const getPreviPersonnels = async () => {
  try {
    const params = {
      departement: departementId,
      anneeUniversitaire: anneeUniv.id,
    }
    previPersonnels.value = await getAnneeUnivPreviService(params);
    previPersonnelsOriginal.value = JSON.parse(JSON.stringify(previPersonnels.value));

  } catch (error) {
    hasError.value = true;
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors du chargement des données.' });
  } finally {
    console.log(previPersonnels.value)
    isLoadingPrevisionnel.value = false;
  }
}

watch(selectedPersonnel, async (newVal) => {
  if (newVal) {
    await getPreviPersonnel();
  }
})

const getPreviPersonnel = async () => {
  try {
    isLoadingPrevisionnel.value = true;
    const params = {
      departement: departementId,
      anneeUniversitaire: anneeUniv.id,
      personnel: selectedPersonnel.value ? selectedPersonnel.value.id : null,
    }
    previPersonnel.value = await getPersonnelPreviService(params);
    console.log(previPersonnel.value)
  } catch (error) {
    hasError.value = true;
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors du chargement des données.' });
  } finally {
    isLoadingPrevisionnel.value = false;
  }
}


watch(isEditing, async () => {
  if (isEditing.value) {
    await getPersonnels();
    // Charger les données du prévisionnel après avoir sélectionné le personnel
    await getPreviPersonnel();
    await getEnseignantHrs(selectedPersonnel.value.id);
    await getTypesHrs();
    await getSemestres();
    await getDiplomes();
    await getEnseignements();
  }})

const getPersonnels = async () => {
  try {
    const params = {
      departement: departementId,
      enseignant: true,
      pagination: false
    }
    personnels.value = await getPersonnelsService(params);
    selectedPersonnel.value = personnels.value[0];
  } catch (error) {
    hasError.value = true;
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors du chargement des données.' });
  } finally {
    console.log(personnels.value)
    isLoadingPersonnels.value = false;
  }
}

const getEnseignantHrs = async (enseignantId) => {
  try {
    personnelEnseignantHrs.value = await getPersonnelEnseignantHrsService(enseignantId, anneeUniv.id);

  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des heures de l\'enseignant :', error);
  }
};

const getTypesHrs = async () => {
  try {
    typeHrs.value = await getPersonnelEnseignantTypesHrsService(selectedPersonnel.value.id, anneeUniv.id);
    console.log(typeHrs.value)
    // construire chaque élément de la liste des types d'heures avec le libelle en label et le type en value
    typeHrs.value = typeHrs.value.map((type) => ({
      id: type.id,
      label: type.libelle,
      libelle: type.libelle,
      value: type.id
    }));

  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des types d\'heures :', error);
  }
};

const getEnseignements = async () => {
  try {
    const params = {
      semestre: selectedSemestre.value.id,
      anneeUniversitaire: anneeUniv.id,
    }
    const data = await getEnseignementsService(params);
    enseignements.value = (data || []).map((e) => ({
      ...e,
      label: `${e.codeEnseignement} - ${e.libelle}`,
      value: e
    }));
  } catch (error) {
    hasError.value = true;
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors du chargement des données.' });
  } finally {
    console.log(enseignements.value)
  }
}

const getSemestres = async () => {
  try {
    const params = {
      departement: departementId,
      anneeUniversitaire: anneeUniv.id,
    }
    semestres.value = await getSemestresService(params);
    semestres.value = semestres.value.map((semestre) => ({
      id: semestre.id,
      label: semestre.libelle,
      libelle: semestre.libelle,
      value: semestre.id
    }));
    selectedSemestre.value = semestres.value[0];
  } catch (error) {
    hasError.value = true;
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors du chargement des données.' });
  } finally {
    console.log(semestres.value)
  }
}

const getDiplomes = async () => {
  try {
    diplomes.value = diplomeStore.diplomes
    // construire chaque élément de la liste des diplômes avec le libelle en label et le diplôme en value
    diplomes.value = diplomes.value.map((diplome) => ({
      id: diplome.id,
      label: diplome.libelle,
      libelle: diplome.libelle,
      value: diplome.id
    }));
  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des diplômes :', error);
  }
}

// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------SYNTHESE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const columns = ref([
  { header: 'Intervenant', field: 'libelle', sortable: true, colspan: 1 },
  {
    header: 'Catégorie',
    field: 'personnel',
    sortable: false,
    colspan: 1,
    tag: true,
    tagClass: (value) => {
      return value.class;
    },
    tagSeverity: (value) => {
      return value.statutSeverity;
    },
    tagIcon: (value) => {
      return value.icon;
    },
    tagContent: (value) => {
      return value.statut;
    }
  },
  { header: 'Service', field: 'service', sortable: true, colspan: 1, unit: ' h' },
  { header: 'CM', field: 'heures.CM', sortable: true, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h' },
  { header: 'TD', field: 'heures.TD', sortable: true, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
  { header: 'TP', field: 'heures.TP', sortable: true, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
  { header: 'Total', field: 'heures.Total', sortable: true, colspan: 1, class: '!text-nowrap', unit: ' h' },
  {
    header: 'Diff.',
    field: 'heures.Diff',
    sortable: false,
    colspan: 1,
    class: '!text-nowrap',
    unit: ' h',
    tag: true,
    tagClass: (value) => {
      if (typeof value === 'string' && value.includes('autre département')) {
        return '!bg-gray-100 !text-gray-800';
      }
      if (typeof value === 'string' && value.includes('Dépassement')) {
        return '!bg-amber-400 !text-white';
      } else {
        return value === 0 ? '!bg-blue-400 !text-white' : (value < 0 ? '!bg-red-400 !text-white' : '!bg-green-400 !text-white');
      }
    },
    tagSeverity: (value) => {
      if (typeof value === 'string' && value.includes('autre département')) {
        return 'secondary';
      }
      if (typeof value === 'string' && value.includes('Dépassement')) {
        return 'warn';
      } else {
        return value === 0 ? 'success' : (value < 0 ? 'warn' : 'success');
      }
    },
    tagIcon: (value) => {
      if (typeof value === 'string' && value.includes('autre département')) {
        return '';
      }
      if (typeof value === 'string' && value.includes('Dépassement')) {
        return 'pi pi-exclamation-triangle';
      } if (typeof value === 'string' && value.includes('Peut rester')) {
        return 'pi pi-check';
      } else {
        return value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up');
      }
    }
  },
]);

const topHeaderCols = ref([
  { header: '', colspan: 7 }
]);

const additionalRows = computed(() => [
  [
    { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold'},
  ],
  [
    { footer: 'Total', colspan: 3, class: 'font-bold' },
    { footer: previPersonnels.value[1].TotalCM, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnels.value[1].TotalTD, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnels.value[1].TotalTP, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnels.value[1].TotalTotal, colspan: 1, class: '!text-nowrap', unit: ' h' },
    { footer: '', colspan: 1 },
  ],
  [
    { footer: 'Répartition', colspan: 19, class: '!text-center !font-bold'},
  ],
  [
    { footer: '', colspan: 3 },
    { footer: 'Permanent', colspan: 2, class: 'font-bold' },
    { footer: 'Vacataire', colspan: 2, class: 'font-bold' },
    { footer: 'Autre', colspan: 2, class: 'font-bold' },
  ],
  [
    { footer: 'Répartition du total d\'heures entre les catégories', colspan: 3 },
    { footer: previPersonnels.value[2].Permanent, colspan: 2, unit: ' %' },
    { footer: previPersonnels.value[2].Vacataire, colspan: 2, unit: ' %' },
    { footer: previPersonnels.value[2].Autre, colspan: 2, unit: ' %' },
  ],
]);

const footerCols = computed(() => [
]);


// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------FORMULAIRE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const columnsForm = ref([
  { header: 'Matière/ressource/SAE', field: 'libelleEnseignement', sortable: true, colspan: 1, form: true, formType: 'select', formOptions: enseignements, id: 'id', placeholder: 'Sélectionner un enseignement', formAction: (previId, type, valeur) => {updateEnseignementPrevi(previId, valeur)}, class: '!text-wrap !min-w-64' },

  { header: 'Nb H/Gr.', name: 'nbHrGrpCM', field: 'heures.CM.NbHrGrp', colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, valeur) => { updateHeuresPrevi(previId, type, valeur) } },
  { header: 'Nb Gr.', name: 'nbGrpCM', field: 'heures.CM.NbGrp', colspan: 1, class: '!bg-purple-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, valeur) => { updateGroupesPrevi(previId, type, valeur) } },

  { header: 'Nb H/Gr.', name: 'nbHrGrpTD', field: 'heures.TD.NbHrGrp', colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, valeur) => { updateHeuresPrevi(previId, type, valeur) } },
  { header: 'Nb Gr.', name: 'nbGrpTD', field: 'heures.TD.NbGrp', colspan: 1, class: '!bg-green-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, valeur) => { updateGroupesPrevi(previId, type, valeur) } },

  { header: 'Nb H/Gr.', name: 'nbHrGrpTP', field: 'heures.TP.NbHrGrp', colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, valeur) => { updateHeuresPrevi(previId, type, valeur) } },
  { header: 'Nb Gr.', name: 'nbGrpTP', field: 'heures.TP.NbGrp', colspan: 1, class: '!bg-amber-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, valeur) => { updateGroupesPrevi(previId, type, valeur) } },

  { header: 'Actions', field: 'id', colspan: 1, actions: true, saveRow: (id) => saveRow(id), cancelRow: (id) => cancelRow(id), duplicateRow: (id) => duplicatePrevi(id), deleteRow: (id) => deletePrevi(id) },
]);

const topHeaderColsForm = ref([
  { header: 'Matière', colspan: 1 },
  { header: 'CM', colspan: 2, class: '!bg-purple-400/20' },
  { header: 'TD', colspan: 2, class: '!bg-green-400/20' },
  { header: 'TP', colspan: 2, class: '!bg-amber-400/20' },
  { header: 'Actions', colspan: 1 },
]);

const additionalRowsForm = computed(() => [
  [
    { footer: 'Ajouter une entrée au prévisionnel', colspan: 1, class: '!text-center !font-bold'},

    { footer: semestres.value, colspan: 2, form: true, formType: 'select', placeholder: 'Sélectionner un semestre', formAction: (semestre) => {selectedSemestre.value = semestre} },

    { footer: enseignements.value, colspan: 3, form: true, formType: 'select', placeholder: 'Sélectionner un enseignement', formAction: (enseignement) => {selectedEnseignement.value = enseignement} },

    { footer: 'Ajouter', colspan: 2, button: true, buttonIcon: 'pi pi-plus', buttonAction: () => { addPrevi(selectedPersonnel.value, selectedEnseignement.value) }, buttonClass: () => '!w-fit', buttonSeverity: () => 'success' },

  ],
  [
    { footer: '', colspan: 1 },
    { footer: 'Total CM', colspan: 2, class: '!bg-purple-400/20 !text-nowrap font-bold' },
    { footer: 'Total TD', colspan: 2, class: '!bg-green-400/20 !text-nowrap font-bold' },
    { footer: 'Total TP', colspan: 2, class: '!bg-amber-400/20 !text-nowrap font-bold' },
    { footer: 'Total', colspan: 1, class: '!text-nowrap font-bold' },
  ],
  [
    { footer: 'Total heures saisies', colspan: 1, tooltip: "Somme du produit du nombre d'heures par le nombre de groupes" },
    { footer: previPersonnel.value?.[1]?.['CM'], colspan: 2, class: '!bg-purple-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnel.value?.[1]?.['TD'], colspan: 2, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnel.value?.[1]?.['TP'], colspan: 2, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnel.value?.[1]?.['Total'], colspan: 1, class: '!text-nowrap', unit: ' h' },
  ],



  [
    { footer: 'Primes/HRS', colspan: 8, class: '!text-center !font-bold'},
  ],
  [
    { footer: '', colspan: 1, class: '!text-center !font-bold'},
    { footer: 'Type d\'HRS, prime ou suivi', colspan: 1, class: '!text-center !font-bold'},
    { footer: 'Libellé', colspan: 1, class: '!text-center !font-bold'},
    { footer: 'Semestre', colspan: 1, class: '!text-center !font-bold'},
    { footer: 'Diplôme', colspan: 1, class: '!text-center !font-bold'},
    { footer: 'Nb heures équivalent TD', colspan: 1, class: '!text-center !font-bold'},
    { footer: '', colspan: 2, class: '!text-center !font-bold'},
  ],
  ...personnelEnseignantHrs.value.map(hrs => [
    { footer: '', colspan: 1 },

    { footer: typeHrs.value, colspan: 1, form: true, formType: 'select', placeholder: hrs.enseignantTypeHrs ? hrs.enseignantTypeHrs.libelle : 'Pas de type renseigné', formAction: (type) => {hrs.enseignantTypeHrs = type}, tooltip: 'Sélectionner un type de HRS, prime ou suivi', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: hrs.libelle, colspan: 1, form: true, formType: 'text', placeholder: hrs.libelle || 'Pas de libellé renseigné', class: '!max-w-52', formAction: (libelle) => {hrs.libelle = libelle} },

    { footer: semestres.value, colspan: 1, form: true, formType: 'select', placeholder: hrs.semestre ? hrs.semestre.libelle : 'Pas de semestre renseigné', formAction: (semestre) => {hrs.semestre = semestre}, tooltip: 'Sélectionner un semestre', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: diplomes.value, colspan: 1, form: true, formType: 'select', placeholder: hrs.diplome ? hrs.diplome.libelle : 'Pas de diplôme renseigné', formAction: (diplome) => {hrs.diplome = diplome}, tooltip: 'Sélectionner un diplôme', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: hrs.nbHeuresTd, colspan: 1, form: true, formType: 'text', placeholder: hrs.nbHeuresTd || 'Nombre d\'heures', formAction: (nbHrs) => {hrs.nbHeuresTd = nbHrs} },

    { footer: [
        { button: true, buttonIcon: 'pi pi-save', buttonAction: (id) => {saveHrs(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'primary' },
        { button: true, buttonIcon: 'pi pi-copy', buttonAction: (id) => {duplicateHrs(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'warn' },
        { button: true, buttonIcon: 'pi pi-trash', buttonAction: (id) => {deleteHrs(id)}, buttonClass: () => '!w-fit', buttonSeverity: () => 'danger' }
      ], colspan: 2, id: hrs.id },
  ]),
  [
    { footer: 'Ajouter une prime/HRS', colspan: 1, class: '!text-center !font-bold'},

    { footer: typeHrs.value, colspan: 1, form: true, formType: 'select', placeholder: 'Sélectionner un type', formAction: (type) => {selectedTypeHrs.value = type}, tooltip: 'Sélectionner un type de HRS, prime ou suivi', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: libelleHrs.value, colspan: 1, form: true, formType: 'text', placeholder: 'Libellé', class: '!max-w-52', formAction: (libelle) => {libelleHrs.value = libelle} },

    { footer: semestres.value, colspan: 1, form: true, formType: 'select', placeholder: 'Sélectionner un semestre', formAction: (semestre) => {selectedSemestreHrs.value = semestre}, tooltip: 'Sélectionner un semestre', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: diplomes.value, colspan: 1, form: true, formType: 'select', placeholder: 'Sélectionner un diplôme', formAction: (diplome) => {selectedDiplomeHrs.value = diplome}, tooltip: 'Sélectionner un diplôme', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: nbHeuresHrs.value, colspan: 1, form: true, formType: 'text', placeholder: 'Nombre d\'heures', formAction: (nbHrs) => {nbHeuresHrs.value = nbHrs} },
    { footer: 'Ajouter', colspan: 2, button: true, buttonIcon: 'pi pi-plus', buttonAction: () => { addHrs(selectedPersonnel.value.id) }, buttonClass: () => '!w-fit', buttonSeverity: () => 'success' },
  ],



  [
    { footer: 'Synthèse', colspan: 8, class: '!text-center !font-bold'},
  ],
  [
    { footer: '', colspan: 1 },
    { footer: 'Nb heures saisies', colspan: 3, class: 'font-bold' },
    { footer: 'Nb heures de service', colspan: 1, class: 'font-bold' },
    {
      footer: previPersonnel.value?.[3],
      sortable: false,
      colspan: 1,
      tag: true,
      tagClass: (value) => {
        return value?.class;
      },
      tagSeverity: (value) => {
        return value?.statutSeverity;
      },
      tagIcon: (value) => {
        return value?.icon;
      },
      tagContent: (value) => {
        return value?.statut;
      }
    },
    { footer: 'Différence', colspan: 2, class: 'font-bold' },
  ],
  [
    { footer: '', colspan: 1 },
    { footer: 'Classique', colspan: 1, class: 'font-bold' },
    { footer: 'Équivalent TD *', colspan: 2, class: 'font-bold' },
    { footer: previPersonnel.value?.[2]?.['Service'], colspan: 2, rowspan: 2, class: '!text-center', unit: ' h' },
    { footer: previPersonnel.value?.[2]?.['Diff'], colspan: 2, rowspan: 2, class: '!text-center', unit: ' h',
      tag: true,
      tagClass: (value) => {
        if (typeof value === 'string' && value.includes('autre département')) {
          return '!bg-gray-100 !text-gray-800';
        }
        if (typeof value === 'string' && value.includes('Dépassement')) {
          return '!bg-amber-400 !text-white';
        } else {
          return value === 0 ? '!bg-blue-400 !text-white' : (value < 0 ? '!bg-red-400 !text-white' : '!bg-green-400 !text-white');
        }
      },
      tagSeverity: (value) => {
        if (typeof value === 'string' && value.includes('autre département')) {
          return 'secondary';
        }
        if (typeof value === 'string' && value.includes('Dépassement')) {
          return 'warn';
        } else {
          return value === 0 ? 'success' : (value < 0 ? 'warn' : 'success');
        }
      },
      tagIcon: (value) => {
        if (typeof value === 'string' && value.includes('autre département')) {
          return '';
        }
        if (typeof value === 'string' && value.includes('Dépassement')) {
          return 'pi pi-exclamation-triangle';
        } if (typeof value === 'string' && value.includes('Peut rester')) {
          return 'pi pi-check';
        } else {
          return value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up');
        }
      }
    },
  ],
  [
    { footer: 'Total d\'heures', colspan: 1, class: 'font-bold' },
    { footer: previPersonnel.value?.[2]?.['TotalClassique'], colspan: 1, unit: ' h' },
    { footer: previPersonnel.value?.[2]?.['TotalTd'], colspan: 2, unit: ' h' },
  ],
  [
    { footer: '* Les heures équivalent TD majorée sont calculées avec les CM * 1.5', colspan: 8, class: '!text-center !text-muted-color' },
  ]
]);

const footerColsForm = computed(() => [

]);
</script>

<template>
  <ErrorView v-if="hasError" />
  <div v-else class="px-4 flex flex-col">
    <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
    <div v-else>
      <div class="flex justify-end gap-10">
        <Button v-if="!isEditing" label="Saisir le prévisionnel" icon="pi pi-plus" @click="isEditing = !isEditing" />
        <Button v-else label="Afficher le prévisionnel" icon="pi pi-eye" @click="isEditing = !isEditing" />
      </div>
      <div v-if="previPersonnels[0].length > 0">
        <div class="flex w-full justify-between my-6">
          <SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
          <div v-if="isEditing">
            <SimpleSkeleton v-if="isLoadingPersonnels" width="100%" />
            <Select
                v-model="selectedPersonnel"
                :options="personnels"
                optionLabel="display"
                placeholder="Sélectionner un enseignant"
                class="w-80"
            />
          </div>
          <div v-else>
            <IconField>
              <InputIcon>
                <i class="pi pi-search" />
              </InputIcon>
              <InputText v-model="searchTerm" placeholder="Rechercher par enseignant" />
            </IconField>
          </div>
        </div>
        <div v-if="!isEditing">
          <PrevisionnelTable
              origin="previEnseignantsSynthese"
              :columns="columns"
              :topHeaderCols="topHeaderCols"
              :additionalRows="additionalRows"
              :footerCols="footerCols"
              :filters="filters"
              :data="previPersonnels[0]"
              :size="size.value"
              :headerTitle="`Prévisionnel de l'année`"
              :headerTitlecolspan="1"/>
        </div>
         <div v-else>
           <PrevisionnelTable
               v-if="previPersonnel.length > 0 && previPersonnel[0]"
               origin="previEnseignantForm"
               :columns="columnsForm"
               :topHeaderCols="topHeaderColsForm"
               :additionalRows="additionalRowsForm"
               :footerCols="footerColsForm"
               :data="previPersonnel[0]"
               :size="size.value"
               v-model:editingRowId="editingRowId"
               :headerTitle="`Prévisionnel de ${selectedPersonnel.display}`"
               :headerTitlecolspan="1"/>
           <SimpleSkeleton v-else width="100%" />
         </div>
      </div>
    </div>
  </div>
</template>
