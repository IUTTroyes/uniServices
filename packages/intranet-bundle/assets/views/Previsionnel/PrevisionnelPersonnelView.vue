<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {useAnneeUnivStore, useUsersStore, useDiplomeStore} from '@stores';
import {ListSkeleton, SimpleSkeleton} from '@components';
import {
  getAnneeUnivPreviService,
  getDepartementSemestresService,
  getEnseignementsSemestreService,
  getEnseignementsDepartementService,
  getPersonnelPreviService,
  getPersonnelsService,
  getPersonnelEnseignantHrsService,
  getPersonnelEnseignantTypesHrsService,
  deletePersonnelEnseignantHrsService,
  updatePreviService,
} from '@requests';
import PrevisionnelTable from '@/components/Previsionnel/PrevisionnelTable.vue';
import createApiService from "@requests/apiService.js";
import apiCall from "@helpers/apiCall.js";
import { showSuccess, showDanger } from '@helpers/toast.js';
import {ErrorView} from "@components";

const previService = createApiService('/api/previsionnels');
const hrsService = createApiService('/api/personnel_enseignant_hrs');

const usersStore = useUsersStore();
const anneeUnivStore = useAnneeUnivStore();
const diplomeStore = useDiplomeStore();
const departementId = usersStore.departementDefaut.id;

const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingAnneesUniv = ref(false);
const isLoadingPrevisionnel = ref(true);
const isLoadingPrevisionnelForm = ref(true);
const isLoadingPersonnel = ref(false);

const personnelList = ref([]);
const selectedPersonnel = ref(null);

const personnelEnseignantHrs = ref([]);
const typeHrs = ref([]);
const selectedTypeHrs = ref(null);
const nbHeuresHrs = ref(0);
const libelleHrs = ref(null);
const selectedSemestreHrs = ref(null);
const selectedDiplomeHrs = ref(null);

const semestreList = ref([]);
const selectedSemestre = ref(null);

const diplomeList = ref([]);

const enseignementList = ref([]);
const selectedEnseignement = ref(null);

const previSemestreAnneeUniv = ref(null);
const previAnneeEnseignant = ref(null);

const size = ref({ label: 'Petit', value: 'small' });

const sizeOptions = ref([
  { label: 'Petit', value: 'small' },
  { label: 'Normal', value: 'null' },
  { label: 'Large', value: 'large' }
]);
const isEditing = ref(false);
const hasError = ref(false);

const getAnneesUniv = async () => {
  isLoadingAnneesUniv.value = true;
  try {
    await anneeUnivStore.getAllAnneesUniv();
    anneesUnivList.value = anneeUnivStore.anneesUniv.sort((a, b) => b.id - a.id);
    await anneeUnivStore.getCurrentAnneeUniv();
    selectedAnneeUniv.value = anneeUnivStore.anneeUniv;
  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des années universitaires:', error);
  } finally {
    isLoadingAnneesUniv.value = false;
  }
};

const getPersonnelsDepartement = async () => {
  isLoadingPersonnel.value = true;
  try {
    const params = {
      departement: departementId,
    };
    personnelList.value = await getPersonnelsService(params);
  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des personnels:', error);
  } finally {
    // sélectionner le premier personnel de la liste
    if (personnelList.value.length > 0) {
      selectedPersonnel.value = personnelList.value[0];
    } else {
      selectedPersonnel.value = null;
    }
    isLoadingPersonnel.value = false;

  }
};

const getPrevi = async () => {
  isLoadingPrevisionnel.value = true;
  try {
    previSemestreAnneeUniv.value = await getAnneeUnivPreviService(
        departementId,
        selectedAnneeUniv.value.id
    );
  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des prévisionnels:', error);
  } finally {
    isLoadingPrevisionnel.value = false;
  }
};

const getPreviEnseignant = async () => {
  isLoadingPrevisionnelForm.value = true;
  try {
    previAnneeEnseignant.value = await getPersonnelPreviService(
        departementId,
        selectedAnneeUniv.value.id,
        selectedPersonnel.value.personnel.id
    );
  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des prévisionnels :', error);
  } finally {
    isLoadingPrevisionnelForm.value = false;

  }
}

const getEnseignantHrs = async (enseignantId) => {
  try {
    personnelEnseignantHrs.value = await getPersonnelEnseignantHrsService(enseignantId, selectedAnneeUniv.value.id);

  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des heures de l\'enseignant :', error);
  }
};

const getTypesHrs = async () => {
  try {
    typeHrs.value = await getPersonnelEnseignantTypesHrsService(selectedPersonnel.value.personnel.id, selectedAnneeUniv.value.id);
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

const getSemestres = async () => {
  try {
    semestreList.value = await getDepartementSemestresService(departementId, true);
    // construire chaque élément de la liste des semestres avec le libelle en label et le semestre en value
    semestreList.value = semestreList.value.map((semestre) => ({
      id: semestre.id,
      label: semestre.libelle,
      libelle: semestre.libelle,
      value: semestre.id
    }));

  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des semestres :', error);
  }
};

const getDiplomes = async () => {
  try {
    diplomeList.value = diplomeStore.diplomes
    // construire chaque élément de la liste des diplômes avec le libelle en label et le diplôme en value
    diplomeList.value = diplomeList.value.map((diplome) => ({
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

const getEnseignementsSemestre = async (semestreId) => {
  try {
    const enseignements = await getEnseignementsSemestreService(semestreId);

    // Crée une nouvelle référence pour enseignementList
    enseignementList.value = [...enseignements.map((enseignement) => ({
      id: enseignement.id,
      label: `${enseignement.codeEnseignement} - ${enseignement.libelle}`,
      value: enseignement,
    }))];
  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des enseignements :', error);
  }
};

const getAllEnseignementsDepartement = async () => {
  try {
    const enseignements = await getEnseignementsDepartementService(departementId);
    // Crée une nouvelle référence pour enseignementList
    enseignementList.value = [...enseignements.map((enseignement) => ({
      id: enseignement.id,
      label: `${enseignement.codeEnseignement} - ${enseignement.libelle}`,
      value: enseignement,
    }))];
  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des enseignements :', error);
  }
}

onMounted(async () => {
  await getAnneesUniv();
  await getAllEnseignementsDepartement();
});

watch(selectedAnneeUniv, async (newAnneeUniv) => {
  if (!newAnneeUniv) return;
  await getPrevi(newAnneeUniv.id);
  await getPersonnelsDepartement();
  await getPreviEnseignant();
  await getTypesHrs();
  await getEnseignantHrs(selectedPersonnel.value.personnel.id);
  await getSemestres();
  await getDiplomes();
});

watch(selectedPersonnel , async (newPersonnel) => {
  if (!newPersonnel) return;
  await getPreviEnseignant(newPersonnel.id);

});

watch(selectedSemestre, async (newSemestre) => {
  if (!newSemestre) return;
  await getEnseignementsSemestre(newSemestre.id);
});

const addPrevi = async () => {
  try {
    const personnelIri = `/api/personnels/${selectedPersonnel.value.personnel.id}`;
    const enseignementIri = `/api/scol_enseignements/${selectedEnseignement.value.id}`;
    const anneeUnivIri = `/api/structure_annee_universitaires/${selectedAnneeUniv.value.id}`;
    const dataNewPrevi = {
      personnel: personnelIri,
      anneeUniversitaire: anneeUnivIri,
      referent: false,
      heures: {
        CM: 0,
        TD: 0,
        TP: 0,
        Projet: 0,
      },
      groupes: {
        CM: 0,
        TD: 0,
        TP: 0,
        Projet: 0,
      },
      enseignement: enseignementIri,
    };

    await apiCall(previService.create,[dataNewPrevi], 'Prévisionnel créé', 'Une erreur est survenue lors de la création du prévisionnel');
  } catch (error) {
    console.error('Erreur lors de la création du prévisionnel:', error);
  } finally {
    await getPreviEnseignant();
  }
}

const duplicatePrevi = async (previId) => {
  try {
    const previToDuplicate = previAnneeEnseignant.value[0].find(previ => previ.id === previId);

    if (previToDuplicate) {
      const newPreviData = {
        personnel: `/api/personnels/${previToDuplicate.idPersonnel}`,
        anneeUniversitaire: `/api/structure_annee_universitaires/${selectedAnneeUniv.value.id}`,
        referent: false,
        heures: {
          CM: previToDuplicate.heures.CM,
          TD: previToDuplicate.heures.TD,
          TP: previToDuplicate.heures.TP,
          Projet: previToDuplicate.heures.Projet,
        },
        groupes: {
          CM: previToDuplicate.groupes.CM,
          TD: previToDuplicate.groupes.TD,
          TP: previToDuplicate.groupes.TP,
          Projet: previToDuplicate.groupes.Projet,
        },
        enseignement: `/api/scol_enseignements/${previToDuplicate.idEnseignement}`,
      };
      await apiCall(previService.create, [newPreviData], 'Le prévisionnel a été dupliqué avec succès', 'Une erreur est survenue lors de la duplication du prévisionnel');
    }
  } catch (error) {
    showDanger('Une erreur est survenue lors de la duplication du prévisionnel', error);
    console.error('Erreur lors de la duplication du prévisionnel:', error);
  } finally {
    await getPreviEnseignant();
  }
}

const updateHeuresPrevi = async (previId, type, valeur) => {
  try {
    console.log('updateHeures')
    const previ = previAnneeEnseignant.value[0].find((previ) => previ.id === previId);
    if (previ && parseFloat(valeur) !== previ.heures[type] && !isNaN(parseFloat(valeur))) {
      previ.heures[type] = parseFloat(valeur || previ.heures[type] || 0);

      const newHeures = ['CM', 'TD', 'TP', 'Projet'].reduce((acc, key) => {
        acc[key] = previ.groupes[key] > 0 ? previ.heures[key] * previ.groupes[key] : 0;
        return acc;
      }, {});

      previAnneeEnseignant.value[1]['CM'] = Math.round(previAnneeEnseignant.value[0].reduce((acc, previ) =>
          acc + (previ.groupes.CM > 0 ? previ.heures.CM * previ.groupes.CM : 0), 0) * 10) / 10;
      previAnneeEnseignant.value[1]['TD'] = Math.round(previAnneeEnseignant.value[0].reduce((acc, previ) =>
          acc + (previ.groupes.TD > 0 ? previ.heures.TD * previ.groupes.TD : 0), 0) * 10) / 10;
      previAnneeEnseignant.value[1]['TP'] = Math.round(previAnneeEnseignant.value[0].reduce((acc, previ) =>
          acc + (previ.groupes.TP > 0 ? previ.heures.TP * previ.groupes.TP : 0), 0) * 10) / 10;
      previAnneeEnseignant.value[1]['Total'] = previAnneeEnseignant.value[1]['CM'] + previAnneeEnseignant.value[1]['TD'] + previAnneeEnseignant.value[1]['TP'];

      await updatePreviService(previ.id, { heures: { ...previ.heures, [type]: previ.heures[type] } });
    }
  } catch (error) {
    showDanger('Erreur lors de la mise à jour des heures', error);
    console.error('Erreur lors de la mise à jour des heures:', error);
  }
};

const updateGroupesPrevi = async (previId, type, valeur) => {
  try {
    console.log('updateGroupes')
    const previ = previAnneeEnseignant.value[0].find((previ) => previ.id === previId);

    if (previ && parseInt(valeur) !== previ.groupes[type] && !isNaN(parseInt(valeur))) {
      previ.groupes[type] = parseInt(valeur || previ.groupes[type] || 0);

      const newGroupes = ['CM', 'TD', 'TP', 'Projet'].reduce((acc, key) => {
        acc[key] = previ.groupes[key];
        return acc;
      }, {});

      previAnneeEnseignant.value[1]['CM'] = Math.round(previAnneeEnseignant.value[0].reduce((acc, previ) =>
          acc + (previ.groupes.CM > 0 ? previ.heures.CM * previ.groupes.CM : 0), 0) * 10) / 10;
      previAnneeEnseignant.value[1]['TD'] = Math.round(previAnneeEnseignant.value[0].reduce((acc, previ) =>
          acc + (previ.groupes.TD > 0 ? previ.heures.TD * previ.groupes.TD : 0), 0) * 10) / 10;
      previAnneeEnseignant.value[1]['TP'] = Math.round(previAnneeEnseignant.value[0].reduce((acc, previ) =>
          acc + (previ.groupes.TP > 0 ? previ.heures.TP * previ.groupes.TP : 0), 0) * 10) / 10;
      previAnneeEnseignant.value[1]['Total'] = previAnneeEnseignant.value[1]['CM'] + previAnneeEnseignant.value[1]['TD'] + previAnneeEnseignant.value[1]['TP'];

      await updatePreviService(previ.id, { groupes: { ...previ.groupes, [type]: previ.groupes[type] } });
    }
  } catch (error) {
    showDanger('Erreur lors de la mise à jour des groupes', error);
    console.error('Erreur lors de la mise à jour des groupes:', error);
  }
};

const addHrs = async (personnelId) => {
  try {
    const personnelIri = `/api/personnels/${personnelId}`;
    const anneeUnivIri = `/api/structure_annee_universitaires/${selectedAnneeUniv.value.id}`;
    const hrsTypeIri = `/api/personnel_enseignant_type_hrs/${selectedTypeHrs.value.id}`;
    const dataNewHrs = {
      personnel: personnelIri,
      anneeUniversitaire: anneeUnivIri,
      enseignantTypeHrs: hrsTypeIri,
      libelle: libelleHrs.value,
      semestre: selectedSemestreHrs.value ? `/api/structure_semestres/${selectedSemestreHrs.value.id}` : null,
      diplome: selectedDiplomeHrs.value ? `/api/structure_diplomes/${selectedDiplomeHrs.value.id}` : null,
      nbHeuresTd: parseFloat(nbHeuresHrs.value) || 0,
    };

    // await apiCall(hrsService.create,[dataNewHrs], 'HRS/prime créée', 'Une erreur est survenue lors de la création de l\'HRS/prime');

    // Reset form fields after successful creation
    selectedTypeHrs.value = null;
    libelleHrs.value = null;
    selectedSemestreHrs.value = null;
    selectedDiplomeHrs.value = null;
    nbHeuresHrs.value = 0;
  } catch (error) {
    console.error('Erreur lors de la création de l\'heure/HRS:', error);
  } finally {
    await getEnseignantHrs(personnelId);
  }
};

watch(isEditing, async (newIsEditing) => {
  if (!newIsEditing) {
    isLoadingPrevisionnel.value = true;
    await getPrevi(selectedAnneeUniv.value.id);
    isLoadingPrevisionnel.value = false;
  }
})

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
  { header: 'CM', field: 'heures.CM', sortable: true, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'TD', field: 'heures.TD', sortable: true, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'TP', field: 'heures.TP', sortable: true, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
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
    { footer: previSemestreAnneeUniv.value[1].TotalCM, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreAnneeUniv.value[1].TotalTD, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreAnneeUniv.value[1].TotalTP, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestreAnneeUniv.value[1].TotalTotal, colspan: 1, class: '!text-nowrap', unit: ' h' },
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
    { footer: previSemestreAnneeUniv.value[2].Permanent, colspan: 2, unit: ' %' },
    { footer: previSemestreAnneeUniv.value[2].Vacataire, colspan: 2, unit: ' %' },
    { footer: previSemestreAnneeUniv.value[2].Autre, colspan: 2, unit: ' %' },
  ],
]);

const footerCols = computed(() => [
]);

// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------FORMULAIRE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const columnsForm = ref([
  { header: 'Matière/ressource/SAE', field: 'libelleEnseignement', sortable: true, colspan: 1, form: true, formType: 'select', formOptions: enseignementList, id: 'id', placeholder: 'Sélectionner un enseignement', formAction: (enseignement) => {selectedEnseignement.value = enseignement}, class: '!text-wrap !w-20' },

  { header: 'Nb H/Gr.', name: 'nbHrGrpCM', field: 'heures.CM', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, valeur) => { updateHeuresPrevi(previId, type, valeur) } },
  { header: 'Nb Gr.', name: 'nbGrpCM', field: 'groupes.CM', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, valeur) => { updateGroupesPrevi(previId, type, valeur) } },

  { header: 'Nb H/Gr.', name: 'nbHrGrpTD', field: 'heures.TD', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, valeur) => { updateHeuresPrevi(previId, type, valeur) } },
  { header: 'Nb Gr.', name: 'nbGrpTD', field: 'groupes.TD', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, valeur) => { updateGroupesPrevi(previId, type, valeur) } },

  { header: 'Nb H/Gr.', name: 'nbHrGrpTP', field: 'heures.TP', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, valeur) => { updateHeuresPrevi(previId, type, valeur) } },
  { header: 'Nb Gr.', name: 'nbGrpTP', field: 'groupes.TP', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, valeur) => { updateGroupesPrevi(previId, type, valeur) } },

  { header: 'Dupliquer', field: '', colspan: 1, button: true, buttonIcon: 'pi pi-copy', id: 'id', buttonAction: (id) => {duplicatePrevi(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'warn', duplicate: true },
  { header: 'Supprimer', field: '', colspan: 1, button: true, buttonIcon: 'pi pi-trash', id: 'id', buttonAction: (id) => {deletePrevi(id)}, buttonClass: () => '!w-fit', buttonSeverity: () => 'danger', delete: true },
]);

const topHeaderColsForm = ref([
  { header: 'CM', colspan: 2, class: '!bg-purple-400 !bg-opacity-20' },
  { header: 'TD', colspan: 2, class: '!bg-green-400 !bg-opacity-20' },
  { header: 'TP', colspan: 2, class: '!bg-amber-400 !bg-opacity-20' },
  { header: '', colspan: 2 },
]);

const additionalRowsForm = computed(() => [
  [
    { footer: 'Ajouter une entrée au prévisionnel', colspan: 1, class: '!text-center !font-bold'},

    { footer: semestreList.value, colspan: 3, form: true, formType: 'select', placeholder: 'Sélectionner un semestre', formAction: (semestre) => {selectedSemestre.value = semestre} },

    { footer: enseignementList.value, colspan: 3, form: true, formType: 'select', placeholder: 'Sélectionner un enseignement', formAction: (enseignement) => {selectedEnseignement.value = enseignement} },

    { footer: 'Ajouter', colspan: 2, button: true, buttonIcon: 'pi pi-plus', buttonAction: () => { addPrevi(selectedPersonnel.value, selectedEnseignement.value) }, buttonClass: () => '!w-fit', buttonSeverity: () => 'success' },

  ],
  [
    { footer: '', colspan: 1 },
    { footer: 'Total CM', colspan: 2, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap font-bold' },
    { footer: 'Total TD', colspan: 2, class: '!bg-green-400 !bg-opacity-20 !text-nowrap font-bold' },
    { footer: 'Total TP', colspan: 2, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap font-bold' },
    { footer: 'Total', colspan: 2, class: '!text-nowrap font-bold' },
  ],
  [
    { footer: 'Total heures saisies', colspan: 1, tooltip: "Somme du produit du nombre d'heures par le nombre de groupes" },
    { footer: previAnneeEnseignant.value[1]['CM'], colspan: 2, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previAnneeEnseignant.value[1]['TD'], colspan: 2, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previAnneeEnseignant.value[1]['TP'], colspan: 2, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previAnneeEnseignant.value[1]['Total'], colspan: 2, class: '!text-nowrap', unit: ' h' },
  ],



  [
    { footer: 'Primes/HRS', colspan: 9, class: '!text-center !font-bold'},
  ],
  [
    { footer: '', colspan: 1, class: '!text-center !font-bold'},
    { footer: 'Type d\'HRS, prime ou suivi', colspan: 1, class: '!text-center !font-bold'},
    { footer: 'Libellé', colspan: 1, class: '!text-center !font-bold'},
    { footer: 'Semestre', colspan: 1, class: '!text-center !font-bold'},
    { footer: 'Diplôme', colspan: 1, class: '!text-center !font-bold'},
    { footer: 'Nb heures équivalent TD', colspan: 1, class: '!text-center !font-bold'},
    { footer: '', colspan: 3, class: '!text-center !font-bold'},
  ],
  ...personnelEnseignantHrs.value.map(hrs => [
    { footer: '', colspan: 1 },

    { footer: typeHrs.value, colspan: 1, form: true, formType: 'select', placeholder: hrs.enseignantTypeHrs ? hrs.enseignantTypeHrs.libelle : 'Pas de type renseigné', formAction: (type) => {hrs.enseignantTypeHrs = type}, tooltip: 'Sélectionner un type de HRS, prime ou suivi', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: hrs.libelle, colspan: 1, form: true, formType: 'text', placeholder: hrs.libelle || 'Pas de libellé renseigné', class: '!max-w-52', formAction: (libelle) => {hrs.libelle = libelle} },

    { footer: semestreList.value, colspan: 1, form: true, formType: 'select', placeholder: hrs.semestre ? hrs.semestre.libelle : 'Pas de semestre renseigné', formAction: (semestre) => {hrs.semestre = semestre}, tooltip: 'Sélectionner un semestre', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: diplomeList.value, colspan: 1, form: true, formType: 'select', placeholder: hrs.diplome ? hrs.diplome.libelle : 'Pas de diplôme renseigné', formAction: (diplome) => {hrs.diplome = diplome}, tooltip: 'Sélectionner un diplôme', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: hrs.nbHeuresTd, colspan: 1, form: true, formType: 'text', placeholder: hrs.nbHeuresTd || 'Nombre d\'heures', formAction: (nbHrs) => {hrs.nbHeuresTd = nbHrs} },

    { header: 'Enregistrer', field: '', colspan: 1, button: true, buttonIcon: 'pi pi-save', id: hrs.id, buttonAction: (id) => {duplicateHrs(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'primary', save: true },

    { header: 'Dupliquer', field: '', colspan: 1, button: true, buttonIcon: 'pi pi-copy', id: hrs.id, buttonAction: (id) => {duplicateHrs(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'warn', duplicate: true },

    { header: 'Supprimer', field: '', colspan: 1, button: true, buttonIcon: 'pi pi-trash', id: hrs.id, buttonAction: (id) => {deleteHrs(id)}, buttonClass: () => '!w-fit', buttonSeverity: () => 'danger', delete: true },
  ]),
  [
    { footer: 'Ajouter une prime/HRS', colspan: 1, class: '!text-center !font-bold'},

    { footer: typeHrs.value, colspan: 1, form: true, formType: 'select', placeholder: 'Sélectionner un type', formAction: (type) => {selectedTypeHrs.value = type}, tooltip: 'Sélectionner un type de HRS, prime ou suivi', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: libelleHrs.value, colspan: 1, form: true, formType: 'text', placeholder: 'Libellé', class: '!max-w-52', formAction: (libelle) => {libelleHrs.value = libelle} },

    { footer: semestreList.value, colspan: 1, form: true, formType: 'select', placeholder: 'Sélectionner un semestre', formAction: (semestre) => {selectedSemestreHrs.value = semestre}, tooltip: 'Sélectionner un semestre', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: diplomeList.value, colspan: 1, form: true, formType: 'select', placeholder: 'Sélectionner un diplôme', formAction: (diplome) => {selectedDiplomeHrs.value = diplome}, tooltip: 'Sélectionner un diplôme', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: nbHeuresHrs.value, colspan: 1, form: true, formType: 'text', placeholder: 'Nombre d\'heures', formAction: (nbHrs) => {nbHeuresHrs.value = nbHrs} },
    { footer: 'Ajouter', colspan: 3, button: true, buttonIcon: 'pi pi-plus', buttonAction: () => { addHrs(selectedPersonnel.value.personnel.id) }, buttonClass: () => '!w-fit', buttonSeverity: () => 'success' },
  ],



  [
    { footer: 'Synthèse', colspan: 9, class: '!text-center !font-bold'},
  ],
  [
    { footer: '', colspan: 1 },
    { footer: 'Nb heures saisies', colspan: 3, class: 'font-bold' },
    { footer: 'Nb heures de service', colspan: 2, class: 'font-bold' },
    {
      footer: previAnneeEnseignant.value[3],
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
    { footer: 'Différence', colspan: 2, class: 'font-bold' },
  ],
  [
    { footer: '', colspan: 1 },
    { footer: 'Classique', colspan: 1, class: 'font-bold' },
    { footer: 'Équivalent TD *', colspan: 2, class: 'font-bold' },
    { footer: previAnneeEnseignant.value[2]['Service'], colspan: 3, rowspan: 2, class: '!text-center', unit: ' h' },
    { footer: previAnneeEnseignant.value[2]['Diff'], colspan: 2, rowspan: 2, class: '!text-center', unit: ' h',
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
    { footer: previAnneeEnseignant.value[2]['TotalClassique'], colspan: 1, unit: ' h' },
    { footer: previAnneeEnseignant.value[2]['TotalTd'], colspan: 2, unit: ' h' },
  ],
  [
    { footer: '* Les heures équivalent TD majorée sont calculées avec les CM * 1.5', colspan: 9, class: '!text-center !text-muted-color' },
  ]
]);

const footerColsForm = computed(() => [

]);
</script>

<template>
  <ErrorView v-if="hasError" />
  <div v-else class="px-4 flex flex-col">
    <div class="flex justify-between gap-10">
      <div class="flex gap-6 w-1/2">
        <SimpleSkeleton v-if="isLoadingAnneesUniv" width="50%" />
        <IftaLabel v-else class="w-1/2">
          <Select
              v-model="selectedAnneeUniv"
              :options="anneesUnivList"
              optionLabel="libelle"
              placeholder="Sélectionner une année universitaire"
              class="w-full"
          />
          <label for="anneeUniversitaire">Année universitaire</label>
        </IftaLabel>

        <div v-if="isEditing" class="w-1/2">
          <SimpleSkeleton v-if="isLoadingPersonnel" width="100%" />
          <IftaLabel v-else class="w-full">
            <Select
                v-model="selectedPersonnel"
                :options="personnelList"
                optionLabel="personnel.display"
                placeholder="Sélectionner un enseignant"
                class="w-full"
            />
            <label for="anneeUniversitaire">Enseignant</label>
          </IftaLabel>
        </div>
      </div>
      <Button v-if="!isEditing" label="Saisir le prévisionnel" icon="pi pi-plus" @click="isEditing = !isEditing" />
      <Button v-else label="Afficher le prévisionnel" icon="pi pi-eye" @click="isEditing = !isEditing" />
    </div>
    <ListSkeleton v-if="isLoadingPrevisionnel" width="100%" class="mt-6" />
    <div v-else>

      <div v-if="previSemestreAnneeUniv[0].length > 0">
        <div class="flex w-full justify-between my-6">
          <SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
        </div>
        <div v-if="!isEditing">
          <PrevisionnelTable
              origin="previEnseignantsSynthese"
              :columns="columns"
              :topHeaderCols="topHeaderCols"
              :additionalRows="additionalRows"
              :footerCols="footerCols"
              :data="previSemestreAnneeUniv[0]"
              :size="size.value"
              :headerTitle="`Prévisionnel de l'année ${selectedAnneeUniv?.libelle}`"
              :headerTitlecolspan="1"/>
        </div>
        <div v-else>
          <ListSkeleton v-if="isLoadingPrevisionnelForm" width="100%" class="mt-6" />
          <PrevisionnelTable
              v-else
              origin="previEnseignantForm"
              :columns="columnsForm"
              :topHeaderCols="topHeaderColsForm"
              :additionalRows="additionalRowsForm"
              :footerCols="footerColsForm"
              :data="previAnneeEnseignant[0]"
              :size="size.value"
              :headerTitle="`${selectedPersonnel.personnel.display} | Prévisionnel de l'année ${selectedAnneeUniv?.libelle}`"
              :headerTitlecolspan="1"/>
        </div>
      </div>
      <Message v-else-if="previAnneeEnseignant[0] < 1" severity="error" icon="pi pi-times-circle">
        Aucun prévisionnel pour cette année universitaire.
      </Message>
    </div>
  </div>
</template>
