<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {useAnneeUnivStore, useEnseignementsStore, useSemestreStore, useUsersStore} from '@stores';
import {ListSkeleton, SimpleSkeleton} from '@components';
import {
  getPersonnelsDepartementService,
  getSemestrePreviService,
  updatePreviEnseignementService,
  updatePreviPersonnelService,
  updatePreviService
} from '@requests';
import createApiService from "@requests/apiService.js";
const previService = createApiService('/api/previsionnels');
import PrevisionnelTable from '@/components/Previsionnel/PrevisionnelTable.vue';
import apiCall from "@helpers/apiCall.js";
import { showSuccess, showDanger } from '@helpers/toast.js';

const usersStore = useUsersStore();
const semestreStore = useSemestreStore();
const anneeUnivStore = useAnneeUnivStore();
const enseignementStore = useEnseignementsStore();
const departementId = usersStore.departementDefaut.id;

const semestresList = ref([]);
const selectedSemestre = ref(null);
const semestreDetails = ref(null);
const enseignementsList = ref([]);
const selectedEnseignement = ref(null);
const personnelsList = ref([]);
const selectedPersonnel = ref(null);


const anneesUnivList = ref([]);
const selectedAnneeUniv = ref(null);

const isLoadingSemestres = ref(false);
const isLoadingAnneesUniv = ref(false);
const isLoadingPrevisionnel = ref(true);

const previSemestre = ref(null);

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

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  await semestreStore.getSemestresByDepartement(departementId, true);
  semestresList.value = semestreStore.semestres;
  if (semestresList.value.length > 0) {
    selectedSemestre.value = semestresList.value[0];
  }
  isLoadingSemestres.value = false;
};

const getPrevi = async (semestreId) => {
  if (semestreId) {
    isLoadingPrevisionnel.value = true;

    try {
      await semestreStore.getSemestre(semestreId);
      semestreDetails.value = semestreStore.semestre;

      previSemestre.value = await getSemestrePreviService(selectedSemestre.value.id, selectedAnneeUniv.value.id);

      try {
        if (selectedSemestre.value) {
          await enseignementStore.getMatieresSemestre(selectedSemestre.value.id);
        }
        enseignementsList.value = enseignementStore.enseignements;
        if (enseignementsList.value.length > 0) {
          // construire chaque élément de la liste des matières avec d'abord le libellé de la matière puis le code
          enseignementsList.value = enseignementsList.value.map((enseignement) => ({
            ...enseignement,
            label: `${enseignement.codeEnseignement} - ${enseignement.libelle}`,
            value: enseignement
          }));
        }
      } catch (error) {
        console.error('Erreur lors du chargement des matières:', error);
      }
      try {
        personnelsList.value = await getPersonnelsDepartementService(departementId);
        personnelsList.value = personnelsList.value.map((personnel) => ({
          ...personnel,
          label: `${personnel.personnel.prenom} ${personnel.personnel.nom}`,
          value: personnel
        }));
      } catch (error) {
        console.error('Erreur lors du chargement des matières:', error);
      }

    } catch (error) {
      console.error('Erreur lors du chargement du prévisionnel:', error);
    } finally {
      console.log('previSemestre', previSemestre.value);
      isLoadingPrevisionnel.value = false;
    }
  }
};

const getAnneesUniv = async () => {
  isLoadingAnneesUniv.value = true;
  await anneeUnivStore.getAllAnneesUniv();
  anneesUnivList.value = anneeUnivStore.anneesUniv.sort((a, b) => b.id - a.id);
  await anneeUnivStore.getCurrentAnneeUniv();
  selectedAnneeUniv.value = anneeUnivStore.anneeUniv;
  isLoadingAnneesUniv.value = false;
};

onMounted(async () => {
  await getSemestres();
  await getAnneesUniv();
});

watch([selectedSemestre, selectedAnneeUniv], async ([newSemestre, newAnneeUniv]) => {
  if (newSemestre && newAnneeUniv) {
    await getPrevi(newSemestre.id, newAnneeUniv.id);
  }
});

watch(searchTerm, (newTerm) => {
  filters.value['libelleEnseignement'].value = newTerm;
});

const updateHeuresPrevi = async (previId, type, valeur) => {
  try {
    // Récupérer le prévisionnel à modifier
    let previForm = previSemestre.value[0].find(previ => previ.id === previId);

    if (previForm && parseFloat(valeur) !== previForm.heures[type] && !isNaN(parseFloat(valeur))) {
      // Mettre à jour le nombre d'heures du type concerné
      previForm.heures[type].NbHrGrp = parseFloat(valeur || previForm.heures[type].NbHrGrp || 0);

      // Calculer les nouvelles heures
      const newHeures = ['CM', 'TD', 'TP', 'Projet'].reduce((acc, key) => {
        acc[key] = parseFloat(previForm.heures[key].NbHrGrp * previForm.heures[key].NbGrp);
        return acc;
      }, {});

      // Mettre à jour le prévisionnel
      await updatePreviService(previId, {heures: newHeures});

      // Mettre à jour les heures dans le prévisionnel pour le calcul des séances
      previForm.heures = ['CM', 'TD', 'TP', 'Projet'].reduce((acc, key) => {
        acc[key] = {
          NbHrGrp: previForm.heures[key].NbHrGrp,
          NbGrp: previForm.heures[key].NbGrp,
          NbSeanceGrp: Math.round(previForm.heures[key].NbHrGrp * previForm.heures[key].NbGrp * 10) / 10,
        };
        return acc;
      }, {});

      previSemestre.value[3].CM.NbHrSaisi = Math.round(previSemestre.value[0].reduce((acc, previ) => acc + (previ.heures.CM.NbGrp > 0 ? previ.heures.CM.NbHrGrp : 0), 0) * 10) / 10;
      previSemestre.value[3].CM.Diff = Math.round((previSemestre.value[3].CM.NbHrSaisi - previSemestre.value[3].CM.NbHrAttendu) * 10) / 10;
      previSemestre.value[3].TD.NbHrSaisi = Math.round(previSemestre.value[0].reduce((acc, previ) => acc + (previ.heures.TD.NbGrp > 0 ? previ.heures.TD.NbHrGrp : 0), 0) * 10) / 10;
      previSemestre.value[3].TD.Diff = Math.round((previSemestre.value[3].TD.NbHrSaisi - previSemestre.value[3].TD.NbHrAttendu) * 10) / 10;
      previSemestre.value[3].TP.NbHrSaisi = Math.round(previSemestre.value[0].reduce((acc, previ) => acc + (previ.heures.TP.NbGrp > 0 ? previ.heures.TP.NbHrGrp : 0), 0) * 10) / 10;
      previSemestre.value[3].TP.Diff = Math.round((previSemestre.value[3].TP.NbHrSaisi - previSemestre.value[3].TP.NbHrAttendu) * 10) / 10;
    }
  } catch (error) {
    showDanger('Erreur lors de la mise à jour du prévisionnel', error);
    console.error('Erreur lors de la mise à jour du prévisionnel:', error);
  } finally {
  }
};

const updateGroupesPrevi = async (previId, type, valeur) => {
  try {
    // Récupérer le prévisionnel à modifier
    let previForm = previSemestre.value[0].find(previ => previ.id === previId);

    if (previForm && parseFloat(valeur) !== previForm.groupes[type] && !isNaN(parseFloat(valeur))) {
      // Mettre à jour le nombre de groupes du type concerné
      previForm.groupes[type] = parseInt(valeur || previForm.groupes[type]);

      // Mettre à jour le prévisionnel
      const newGroupes = ['CM', 'TD', 'TP', 'Projet'].reduce((acc, key) => {
        acc[key] = previForm.groupes[key];
        return acc;
      }, {});

      // Mettre à jour les heures dans le prévisionnel pour le calcul des séances
      previForm.heures = ['CM', 'TD', 'TP', 'Projet'].reduce((acc, key) => {
        acc[key] = {
          NbHrGrp: previForm.heures[key].NbHrGrp,
          NbGrp: previForm.groupes[key],
          NbSeanceGrp: previForm.heures[key].NbHrGrp * previForm.groupes[key],
        };
        return acc;
      }, {});

      // Recalculer les totaux
      previSemestre.value[3].CM.NbHrSaisi = Math.round(previSemestre.value[0].reduce((acc, previ) => acc + (previ.heures.CM.NbGrp > 0 ? previ.heures.CM.NbHrGrp : 0), 0) * 10) / 10;
      previSemestre.value[3].CM.Diff = Math.round((previSemestre.value[3].CM.NbHrSaisi - previSemestre.value[3].CM.NbHrAttendu) * 10) / 10;
      previSemestre.value[3].TD.NbHrSaisi = Math.round(previSemestre.value[0].reduce((acc, previ) => acc + (previ.heures.TD.NbGrp > 0 ? previ.heures.TD.NbHrGrp : 0), 0) * 10) / 10;
      previSemestre.value[3].TD.Diff = Math.round((previSemestre.value[3].TD.NbHrSaisi - previSemestre.value[3].TD.NbHrAttendu) * 10) / 10;
      previSemestre.value[3].TP.NbHrSaisi = Math.round(previSemestre.value[0].reduce((acc, previ) => acc + (previ.heures.TP.NbGrp > 0 ? previ.heures.TP.NbHrGrp : 0), 0) * 10) / 10;
      previSemestre.value[3].TP.Diff = Math.round((previSemestre.value[3].TP.NbHrSaisi - previSemestre.value[3].TP.NbHrAttendu) * 10) / 10;

      console.log('previForm', previForm);

      await updatePreviService(previId, {groupes: newGroupes});
    }
  } catch (error) {
    showDanger('Erreur lors de la mise à jour du prévisionnel', error);
    console.error('Erreur lors de la mise à jour du prévisionnel:', error);
  } finally {
  }
};

const updateIntervenantPrevi = async (previId, personnel) => {
  try {
    // Récupérer le prévisionnel à modifier
    let previForm = previSemestre.value[0].find(previ => previ.id === previId);
    // Mettre à jour l'intervenant du prévisionnel
    await updatePreviPersonnelService(previId, personnel.personnel.id);

    // Mettre à jour le prévisionnel
    previForm.idPersonnel = personnel.personnel.id;
    // Ensure intervenant is a string, not an object
    previForm.intervenant = typeof personnel.personnel.display === 'string' ? personnel.personnel.display : `${personnel.personnel.prenom} ${personnel.personnel.nom}`;

    console.log(previSemestre)
  } catch (error) {
    showDanger('Erreur lors de la mise à jour de l\'intervenant', error);
    console.error('Erreur lors de la mise à jour de l\'intervenant :', error);
  } finally {
    showSuccess('L\'intervenant a été mis à jour avec succès');
  }
};

watch(isEditing, async (newIsEditing) => {
  if (!newIsEditing) {
    isLoadingPrevisionnel.value === true;
    await getPrevi(selectedSemestre.value.id);
    isLoadingPrevisionnel.value === false;
  }
});

const addPrevi = async (personnel, enseignement) => {
  try {
    console.log(personnel, enseignement)
    const personnelIri = `/api/personnels/${personnel.personnel.id}`;
    const enseignementIri = `/api/scol_enseignements/${enseignement.id}`;
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
    await apiCall(previService.create,[dataNewPrevi], 'L\'élément a été créé avec succès', 'Une erreur est survenue lors de la création du prévisionnel');
  } catch (error) {
    showDanger('Erreur lors de la création du prévisionnel', error);
    console.error('Erreur lors de la création du prévisionnel:', error);
  } finally {
    getPrevi(selectedSemestre.value.id);
  }
};

// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------SYNTHESE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const columns = ref([
  { header: 'Code', field: 'codeEnseignement', sortable: true, colspan: 1 },
  { header: 'Nom', field: 'libelleEnseignement', sortable: true, colspan: 1 },
  { header: 'Type', field: 'typeEnseignement', sortable: true, colspan: 1 },
  { header: 'Nb profs', field: 'personnels.length', colspan: 1 },

  { header: 'Maq.', field: 'heures.CM.Maquette', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h'},
  { header: 'Prévi.', field: 'heures.CM.Previsionnel', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'Diff.', field: 'heures.CM.Diff', sortable: true, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },

  { header: 'Maq.', field: 'heures.TD.Maquette', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'Prévi.', field: 'heures.TD.Previsionnel', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'Diff.', field: 'heures.TD.Diff', sortable: true, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },

  { header: 'Maq.', field: 'heures.TP.Maquette', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'Prévi.', field: 'heures.TP.Previsionnel', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { header: 'Diff.', field: 'heures.TP.Diff', sortable: true, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },

  { header: 'Maq.', field: 'heures.Total.Maquette', colspan: 1, unit: ' h' },
  { header: 'Prévi.', field: 'heures.Total.Previsionnel', colspan: 1, unit: ' h' },
  { header: 'Diff.', field: 'heures.Total.Diff', sortable: true, colspan: 1, unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
]);

const additionalRows = ref ([
  [
    { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold'},
  ]
]);

const topHeaderCols = ref([
  { header: 'CM', colspan: 3, class: '!bg-purple-400 !bg-opacity-20' },
  { header: 'TD', colspan: 3, class: '!bg-green-400 !bg-opacity-20' },
  { header: 'TP', colspan: 3, class: '!bg-amber-400 !bg-opacity-20' },
  { header: 'Total', colspan: 3 }
]);

const footerCols = computed(() => [
  { footer: 'Total', colspan: 4 },
  { footer: previSemestre.value[2].CM.Maquette, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].CM.Previsionnel, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].CM.Diff, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { footer: previSemestre.value[2].TD.Maquette, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TD.Previsionnel, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TD.Diff, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { footer: previSemestre.value[2].TP.Maquette, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TP.Previsionnel, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].TP.Diff, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
  { footer: previSemestre.value[2].Total.Maquette, colspan: 1, class: '!text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].Total.Previsionnel, colspan: 1, class: '!text-nowrap', unit: ' h' },
  { footer: previSemestre.value[2].Total.Diff, colspan: 1, unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
]);

// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------FORMULAIRE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const columnsForm = ref([
  { header: 'Matière', field: 'libelleEnseignement', sortable: true, colspan: 1, class: '!overflow-hidden !truncate', form: true, formType: 'select', formOptions: enseignementsList, placeholder: "Sélectionner une matière", id: 'id', formAction: (previId, event) => { updatePreviEnseignementService(previId, event.id)} },

  { header: 'Intervenant', field: 'intervenant', sortable: true, colspan: 1, class: '!wrapper !text-wrap', form: true, formType: 'select', formOptions: personnelsList, placeholder: "Sélectionner un intervenant", id: 'id', formAction: (previId, event) => { updateIntervenantPrevi(previId, event)} },

  { header: 'Nb H/Gr.', field: 'heures.CM.NbHrGrp', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) } },

  { header: 'Nb Gr.', field: 'heures.CM.NbGrp', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) } },

  { header: 'Séances', field: 'heures.CM.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !max-w-20', form: false },

  { header: 'Nb H/Gr.', field: 'heures.TD.NbHrGrp', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) } },

  { header: 'Nb Gr.', field: 'heures.TD.NbGrp', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) } },

  { header: 'Séances', field: 'heures.TD.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !max-w-20', form: false },

  { header: 'Nb H/Gr.', field: 'heures.TP.NbHrGrp', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) } },

  { header: 'Nb Gr.', field: 'heures.TP.NbGrp', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) } },

  { header: 'Séances', field: 'heures.TP.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !max-w-20', form: false },

  { header: 'Dupliquer', field: '', colspan: 1, button: true, buttonIcon: 'pi pi-copy', id: 'id', buttonAction: (id) => {duplicatePrevi(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'warn', duplicate: true },
  { header: 'Supprimer', field: '', colspan: 1, button: true, buttonIcon: 'pi pi-trash', id: 'id', buttonAction: (id) => {deletePrevi(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'danger', delete: true },
]);

const topHeaderColsForm = ref([
  { header: 'CM', colspan: 3, class: '!bg-purple-400 !bg-opacity-20' },
  { header: 'TD', colspan: 3, class: '!bg-green-400 !bg-opacity-20' },
  { header: 'TP', colspan: 3, class: '!bg-amber-400 !bg-opacity-20' },
  { header: '', colspan: 2 },
]);

const additionalRowsForm = computed(() => [
  [
    { footer: 'Ajouter une entrée au prévisionnel', colspan: 2, class: '!text-center !font-bold'},
    { footer: enseignementsList.value, colspan: 4, form: true, formType: 'select', placeholder: "Sélectionner une matière", formAction: (enseignement) => {selectedEnseignement.value = enseignement} },
    { footer: personnelsList.value, colspan: 4, form: true, formType: 'select', placeholder: "Sélectionner un intervenant", formAction: (personnel) => {selectedPersonnel.value = personnel} },
    { footer: 'Ajouter', colspan: 3, button: true, buttonIcon: 'pi pi-plus', buttonAction: () => { addPrevi(selectedPersonnel.value, selectedEnseignement.value) }, buttonClass: () => '!w-full', buttonSeverity: () => 'success' },
  ],
  [
    { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold'},
  ],
  [
    { footer: '', colspan: 2, class: '!text-center !font-bold'},
    { footer: 'Nb hr attendu', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap !font-bold' },
    { footer: 'Nb hr saisi', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap !font-bold' },
    { footer: 'Diff', colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap !font-bold' },
    { footer: 'Nb hr attendu', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap !font-bold' },
    { footer: 'Nb hr saisi', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap !font-bold' },
    { footer: 'Diff', colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap !font-bold' },
    { footer: 'Nb hr attendu', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap !font-bold' },
    { footer: 'Nb hr saisi', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap !font-bold' },
    { footer: 'Diff', colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap !font-bold' },
    { footer: '', colspan: 2 },
  ],
  [
    { footer: 'Vérification du total d\'heures par étudiant', colspan: 2 },
    { footer: previSemestre.value[3].CM.NbHrAttendu, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestre.value[3].CM.NbHrSaisi, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestre.value[3].CM.Diff, colspan: 1, class: '!bg-purple-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
    { footer: previSemestre.value[3].TD.NbHrAttendu, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestre.value[3].TD.NbHrSaisi, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestre.value[3].TD.Diff, colspan: 1, class: '!bg-green-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
    { footer: previSemestre.value[3].TP.NbHrAttendu, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestre.value[3].TP.NbHrSaisi, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h' },
    { footer: previSemestre.value[3].TP.Diff, colspan: 1, class: '!bg-amber-400 !bg-opacity-20 !text-nowrap', unit: ' h', tag: true, tagClass: (value) => value === 0 ? '!bg-green-400 !text-white' : (value < 0 ? '!bg-amber-400 !text-white' : '!bg-red-400 !text-white'), tagSeverity: (value) => value === 0 ? 'success' : (value < 0 ? 'warn' : 'danger'), tagIcon: (value) => value === 0 ? 'pi pi-check' : (value < 0 ? 'pi pi-arrow-down' : 'pi pi-arrow-up') },
    { footer: '', colspan: 2 },
  ],
  [
    { footer: '', colspan: 2},
    { footer: 'Classique', colspan: 4, class: '!text-nowrap !text-center font-bold' },
    { footer: 'Équivalent TD', colspan: 5, class: '!text-nowrap !text-center font-bold' },
    { footer: '', colspan: 2 },
  ],
  [
    { footer: 'Total d\'heures', colspan: 2},
    { footer: previSemestre.value[5].TotalClassique, colspan: 4, class: '!text-nowrap !text-center', unit: ' h' },
    { footer: previSemestre.value[5].TotalTd, colspan: 5, class: '!text-nowrap !text-center', unit: ' h' },
    { footer: '', colspan: 2 },
  ],
]);

const footerColsForm = computed(() => [
]);
</script>

<template>
  <div class="px-4 flex flex-col">
    <div class="flex justify-between gap-10">
      <div class="flex gap-6 w-1/2">
        <SimpleSkeleton v-if="isLoadingSemestres" class="w-1/2" />
        <IftaLabel v-else class="w-1/2">
          <Select
              v-model="selectedSemestre"
              :options="semestresList"
              optionLabel="libelle"
              placeholder="Sélectionner un semestre"
              class="w-full"
          />
          <label for="semestre">Semestre</label>
        </IftaLabel>
        <SimpleSkeleton v-if="isLoadingAnneesUniv" class="w-1/2" />
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
      </div>
      <Button v-if="!isEditing" label="Saisir le prévisionnel" icon="pi pi-plus" @click="isEditing = !isEditing" />
      <Button v-else label="Afficher le prévisionnel" icon="pi pi-eye" @click="isEditing = !isEditing" />
    </div>
    <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
    <div v-else>
      <Message v-if="previSemestre < 1 || previSemestre[1].length < 1" severity="error" icon="pi pi-times-circle">
        Aucun prévisionnel pour cette année universitaire et ce semestre
      </Message>
      <div v-else>
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
          <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
          <PrevisionnelTable
              v-else
              origin="previSemestreForm"
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
  </div>
</template>

<style scoped>

</style>
