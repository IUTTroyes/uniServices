<script setup>
import {computed, onMounted, ref, watch} from 'vue';
import {EmptyState, ErrorState, ListSkeleton, SimpleSkeleton} from "@components";
import {useToast} from "primevue/usetoast";
import {useUsersStore} from "@stores";
import {
  getPersonnelPreviService,
  getAnneeUnivPreviService,
  getPersonnelsService,
  getDiplomesService,
  getEnseignementsService,
  getSemestresService,
  getPersonnelEnseignantHrsService,
  getPersonnelEnseignantTypesHrsService,
  createPreviService,
  updatePreviService,
  deletePreviService,
  deletePersonnelEnseignantHrsService,
} from "@requests";
import PrevisionnelTable from "@/components/Previsionnel/PrevisionnelTable.vue";
import api from '@helpers/axios';
import apiCall from '@helpers/apiCall';
import { getSelectedAnneeUniversitaireId } from '@helpers/contexteAnnuel.js';
import { construireHeures } from '@/service/utils/previsionnelHeures.js';

const props = defineProps({ gestionHrs: Boolean });

const toast = useToast();
const hasError = ref(false);
const anneeUnivId = getSelectedAnneeUniversitaireId();
const usersStore = useUsersStore();

const enseignements = ref([]);
const semestres = ref([]);
const selectedSemestre = ref(null);
const departementId = usersStore.departementDefaut?.id;
const isLoadingPrevisionnel = ref(true);
const previPersonnels = ref([]);
const previPersonnel = ref([]);
const previPersonnelOriginal = ref([]);
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
const selectedEnseignement = ref(null);


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
  if (props.gestionHrs) isEditing.value = true;
  else await getPreviPersonnels();
});

const getPreviPersonnels = async () => {
  try {
    const params = {
      departement: departementId,
      anneeUniversitaire: anneeUnivId,
    }
    previPersonnels.value = await getAnneeUnivPreviService(params);

  } catch (error) {
    hasError.value = true;
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors du chargement des données.' });
  } finally {
    isLoadingPrevisionnel.value = false;
  }
}

let chargementPersonnel = 0;
watch(selectedPersonnel, async (personnel) => {
  editingRowId.value = null;
  previPersonnel.value = [];
  personnelEnseignantHrs.value = [];
  typeHrs.value = [];
  selectedTypeHrs.value = null;
  ++chargementPersonnel;
  libelleHrs.value = null;
  nbHeuresHrs.value = 0;
  selectedSemestreHrs.value = null;
  selectedDiplomeHrs.value = null;
  if (!personnel) return;
  if (props.gestionHrs) isLoadingPrevisionnel.value = false;
  await Promise.all([
    props.gestionHrs ? Promise.resolve() : getPreviPersonnel(),
    getEnseignantHrs(personnel.id),
    getTypesHrs(personnel.id),
  ]);
});

const getPreviPersonnel = async () => {
  const personnel = selectedPersonnel.value?.id;
  if (!personnel) return;
  const requete = ++chargementPersonnel;
  try {
    isLoadingPrevisionnel.value = true;
    const resultat = await getPersonnelPreviService({
      departement: departementId,
      personnel,
    });
    if (requete !== chargementPersonnel) return;
    previPersonnel.value = resultat;
    previPersonnelOriginal.value = JSON.parse(JSON.stringify(resultat));
  } catch {
    if (requete === chargementPersonnel) {
      toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger le prévisionnel de cet intervenant.' });
    }
  } finally {
    if (requete === chargementPersonnel) isLoadingPrevisionnel.value = false;
  }
};

watch(isEditing, async (actif) => {
  editingRowId.value = null;
  if (actif) {
    await Promise.all([getPersonnels(), getSemestres(), getDiplomes()]);
  } else {
    ++chargementPersonnel;
    isLoadingPrevisionnel.value = false;
    await getPreviPersonnels();
  }
});

const getPersonnels = async () => {
  try {
    const params = {
      departement: departementId,
      pagination: false
    }
    personnels.value = await getPersonnelsService(params);
    const selection = personnels.value.find((p) => p.id === selectedPersonnel.value?.id) ?? personnels.value[0] ?? null;
    if (selectedPersonnel.value?.id === selection?.id && selection) {
      if (!props.gestionHrs) await getPreviPersonnel();
    }
    else selectedPersonnel.value = selection;
    if (!selection || props.gestionHrs) isLoadingPrevisionnel.value = false;
  } catch (error) {
    hasError.value = true;
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors du chargement des données.' });
  } finally {
    isLoadingPersonnels.value = false;
  }
}

const getEnseignantHrs = async (enseignantId) => {
  try {
    const heures = await getPersonnelEnseignantHrsService(enseignantId, anneeUnivId);
    if (selectedPersonnel.value?.id === enseignantId) personnelEnseignantHrs.value = heures;

  } catch (error) {
    hasError.value = true;
    console.error('Erreur lors du chargement des heures de l\'enseignant :', error);
  }
};

const getTypesHrs = async (personnelId) => {
  try {
    const types = await getPersonnelEnseignantTypesHrsService(personnelId, anneeUnivId);
    if (selectedPersonnel.value?.id !== personnelId) return;
    // construire chaque élément de la liste des types d'heures avec le libelle en label et le type en value
    typeHrs.value = (types ?? []).map((type) => ({
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
  if (!selectedSemestre.value?.id) {
    enseignements.value = [];
    return;
  }
  selectedEnseignement.value = null;
  const semestreId = selectedSemestre.value.id;
  try {
    const data = await getEnseignementsService({ semestre: semestreId, pagination: false });
    if (selectedSemestre.value?.id !== semestreId) return;
    enseignements.value = (data || []).map((e) => ({
      ...e,
      label: `${e.codeEnseignement ?? ''} ${e.libelle}`.trim(),
    }));
  } catch (error) {
    hasError.value = true;
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Une erreur est survenue lors du chargement des données.' });
  } finally {
  }
}

watch(selectedSemestre, getEnseignements);

const getSemestres = async () => {
  try {
    const params = {
      departement: departementId,
      anneeUniversitaire: anneeUnivId,
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
  }
}

const getDiplomes = async () => {
  try {
    diplomes.value = await getDiplomesService({ departement: departementId, pagination: false }) ?? [];
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
    header: 'Écart service (éq. TD)',
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
    { footer: previPersonnels.value?.[1]?.TotalCM, colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnels.value?.[1]?.TotalTD, colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnels.value?.[1]?.TotalTP, colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h' },
    { footer: previPersonnels.value?.[1]?.TotalTotal, colspan: 1, class: '!text-nowrap', unit: ' h' },
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
    { footer: previPersonnels.value?.[2]?.Permanent, colspan: 2, unit: ' %' },
    { footer: previPersonnels.value?.[2]?.Vacataire, colspan: 2, unit: ' %' },
    { footer: previPersonnels.value?.[2]?.Autre, colspan: 2, unit: ' %' },
  ],
]);

const footerCols = computed(() => [
]);


// ------------------------------------------------------------------------------------------------------------
// ---------------------------------------FORMULAIRE------------------------------------------------
// ------------------------------------------------------------------------------------------------------------

const columnsForm = ref([
  { header: 'Matière/ressource/SAE', field: 'libelleEnseignement', sortable: true, colspan: 1, form: true, formType: 'select', formOptions: enseignements, id: 'id', placeholder: 'Sélectionner un enseignement', formAction: (id, valeur) => updateEnseignementPrevi(id, valeur), class: '!text-wrap !min-w-64' },

  { header: 'Nb H/Gr.', name: 'nbHrGrpCM', field: 'heures.CM.NbHrGrp', colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, valeur) => { updateHeuresPrevi(previId, type, valeur) } },
  { header: 'Nb Gr.', name: 'nbGrpCM', field: 'heures.CM.NbGrp', colspan: 1, class: '!bg-purple-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, valeur) => { updateGroupesPrevi(previId, type, valeur) } },

  { header: 'Nb H/Gr.', name: 'nbHrGrpTD', field: 'heures.TD.NbHrGrp', colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, valeur) => { updateHeuresPrevi(previId, type, valeur) } },
  { header: 'Nb Gr.', name: 'nbGrpTD', field: 'heures.TD.NbGrp', colspan: 1, class: '!bg-green-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, valeur) => { updateGroupesPrevi(previId, type, valeur) } },

  { header: 'Nb H/Gr.', name: 'nbHrGrpTP', field: 'heures.TP.NbHrGrp', colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, valeur) => { updateHeuresPrevi(previId, type, valeur) } },
  { header: 'Nb Gr.', name: 'nbGrpTP', field: 'heures.TP.NbGrp', colspan: 1, class: '!bg-amber-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, valeur) => { updateGroupesPrevi(previId, type, valeur) } },

  { header: 'Actions', field: 'id', colspan: 1, actions: true, duplicate: true, duplicateAction: (id) => duplicatePrevi(id), delete: true },
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

const additionalRowsHrs = computed(() => [
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
        { label: 'Enregistrer', buttonIcon: 'pi pi-save', buttonAction: (id) => {saveHrs(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'primary' },
        { label: 'Dupliquer', buttonIcon: 'pi pi-copy', buttonAction: (id) => {duplicateHrs(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'warn' },
        { label: 'Supprimer', delete: true, buttonIcon: 'pi pi-trash', buttonAction: (id) => {deleteHrs(id)}, buttonClass: () => '!w-fit', buttonSeverity: () => 'danger' }
      ], colspan: 2, id: hrs.id },
  ]),
  [
    { footer: 'Ajouter une prime/HRS', colspan: 1, class: '!text-center !font-bold'},

    { footer: typeHrs.value, colspan: 1, form: true, formType: 'select', placeholder: 'Sélectionner un type', formAction: (type) => {selectedTypeHrs.value = type}, tooltip: 'Sélectionner un type de HRS, prime ou suivi', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: libelleHrs.value, colspan: 1, form: true, formType: 'text', placeholder: 'Libellé', class: '!max-w-52', formAction: (libelle) => {libelleHrs.value = libelle} },

    { footer: semestres.value, colspan: 1, form: true, formType: 'select', placeholder: 'Sélectionner un semestre', formAction: (semestre) => {selectedSemestreHrs.value = semestre}, tooltip: 'Sélectionner un semestre', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: diplomes.value, colspan: 1, form: true, formType: 'select', placeholder: 'Sélectionner un diplôme', formAction: (diplome) => {selectedDiplomeHrs.value = diplome}, tooltip: 'Sélectionner un diplôme', class: '!max-w-52 !truncate !overflow-hidden' },

    { footer: nbHeuresHrs.value, colspan: 1, form: true, formType: 'text', placeholder: 'Nombre d\'heures', formAction: (nbHrs) => {nbHeuresHrs.value = nbHrs} },
    { footer: 'Ajouter', colspan: 2, button: true, buttonIcon: 'pi pi-plus', buttonAction: () => { addHrs(selectedPersonnel.value?.id) }, buttonClass: () => '!w-fit', buttonSeverity: () => 'success' },
  ],
]);

const footerColsForm = computed(() => [

]);

const updateHeuresPrevi = (previId, type, valeur) => {
  const previ = previPersonnel.value?.[0]?.find((p) => p.id === previId);
  if (!previ?.heures?.[type]) return;

  previ.heures[type].NbHrGrp = valeur;
};

const updateGroupesPrevi = (previId, type, valeur) => {
  const previ = previPersonnel.value?.[0]?.find((p) => p.id === previId);
  if (!previ?.heures?.[type]) return;

  previ.heures[type].NbGrp = valeur;
};

const updateEnseignementPrevi = (previId, enseignement) => {
  const previ = previPersonnel.value?.[0]?.find((p) => p.id === previId);
  if (!previ || !enseignement) return;

  previ.idEnseignement = enseignement.id;
  previ.libelleEnseignement = enseignement.label || enseignement.libelle;
};

const saveRow = async (previId) => {
  try {
    const previ = previPersonnel.value?.[0]?.find((p) => p.id === previId);
    if (!previ) return;

    const payload = {
      ...construireHeures(previ),
      enseignement: `/api/scol_enseignements/${previ.idEnseignement}`,
    };

    await updatePreviService(previId, payload);
    editingRowId.value = null;
    await getPreviPersonnel();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: error instanceof RangeError ? error.message : 'Erreur lors de la sauvegarde de la ligne.' });
  }
};

const cancelRow = (previId) => {
  const originalRow = previPersonnelOriginal.value?.[0]?.find((p) => p.id === previId);
  if (originalRow) {
    const index = previPersonnel.value?.[0]?.findIndex((p) => p.id === previId);
    if (index != null && index !== -1) {
      previPersonnel.value[0][index] = JSON.parse(JSON.stringify(originalRow));
    }
  }
  editingRowId.value = null;
};

const addPrevi = async (personnel, enseignement) => {
  if (!personnel?.id || !enseignement?.id) {
    toast.add({ severity: 'warn', summary: 'Attention', detail: 'Sélectionnez un enseignant et un enseignement.' });
    return;
  }

  try {
    await createPreviService({
      personnel: `/api/personnels/${personnel.id}`,
      anneeUniversitaire: `/api/structure_annee_universitaires/${anneeUnivId}`,
      referent: false,
      heures: { CM: 0, TD: 0, TP: 0, Projet: 0 },
      groupes: { CM: 0, TD: 0, TP: 0, Projet: 0 },
      enseignement: `/api/scol_enseignements/${enseignement.id}`,
    });

    await getPreviPersonnel();
    await getPreviPersonnels();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de l\'ajout de la ligne.' });
  }
};

const duplicatePrevi = async (previId) => {
  try {
    const previ = previPersonnel.value?.[0]?.find((p) => p.id === previId);
    if (!previ) return;

    const data = {
      personnel: `/api/personnels/${previ.idPersonnel}`,
      anneeUniversitaire: `/api/structure_annee_universitaires/${anneeUnivId}`,
      referent: false,
      ...construireHeures(previ),
      enseignement: `/api/scol_enseignements/${previ.idEnseignement}`,
    };

    await createPreviService(data);
    await getPreviPersonnel();
    await getPreviPersonnels();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de la duplication.' });
  }
};

const deletePrevi = async (previId) => {
  try {
    await deletePreviService(previId);
    await getPreviPersonnel();
    await getPreviPersonnels();
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de la suppression.' });
  }
};

const addHrs = async (personnelId) => {
  if (!personnelId || !selectedTypeHrs.value?.id) {
    toast.add({ severity: 'warn', summary: 'Attention', detail: 'Sélectionnez au minimum un type de HRS.' });
    return;
  }

  try {
    await apiCall(
        api.post,
        ['/api/personnel_enseignant_hrs', {
          personnel: `/api/personnels/${personnelId}`,
          anneeUniversitaire: `/api/structure_annee_universitaires/${anneeUnivId}`,
          enseignantTypeHrs: `/api/personnel_enseignant_type_hrs/${selectedTypeHrs.value.id}`,
          libelle: libelleHrs.value,
          semestre: selectedSemestreHrs.value ? `/api/structure_semestres/${selectedSemestreHrs.value.id}` : null,
          diplome: selectedDiplomeHrs.value ? `/api/structure_diplomes/${selectedDiplomeHrs.value.id}` : null,
          nbHeuresTd: parseFloat(nbHeuresHrs.value) || 0,
        }, { headers: { 'Content-Type': 'application/ld+json' } }],
        'HRS ajoutée avec succès',
        'Erreur lors de la création de la HRS',
        true
    );

    selectedTypeHrs.value = null;
    libelleHrs.value = null;
    selectedSemestreHrs.value = null;
    selectedDiplomeHrs.value = null;
    nbHeuresHrs.value = 0;
    await getEnseignantHrs(personnelId);
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de l\'ajout de la HRS.' });
  }
};

const saveHrs = async (hrsId) => {
  const hrs = personnelEnseignantHrs.value.find((h) => h.id === hrsId);
  if (!hrs) return;

  try {
    await apiCall(
        api.patch,
        [`/api/personnel_enseignant_hrs/${hrsId}`, {
          enseignantTypeHrs: hrs.enseignantTypeHrs?.id ? `/api/personnel_enseignant_type_hrs/${hrs.enseignantTypeHrs.id}` : null,
          libelle: hrs.libelle,
          semestre: hrs.semestre?.id ? `/api/structure_semestres/${hrs.semestre.id}` : null,
          diplome: hrs.diplome?.id ? `/api/structure_diplomes/${hrs.diplome.id}` : null,
          nbHeuresTd: parseFloat(hrs.nbHeuresTd) || 0,
        }, { headers: { 'Content-Type': 'application/merge-patch+json' } }],
        'HRS mise à jour',
        'Erreur lors de la mise à jour de la HRS',
        true
    );

    await getEnseignantHrs(selectedPersonnel.value?.id);
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de la sauvegarde de la HRS.' });
  }
};

const duplicateHrs = async (hrsId) => {
  const hrs = personnelEnseignantHrs.value.find((h) => h.id === hrsId);
  if (!hrs || !selectedPersonnel.value?.id) return;

  try {
    await apiCall(
        api.post,
        ['/api/personnel_enseignant_hrs', {
          personnel: `/api/personnels/${selectedPersonnel.value.id}`,
          anneeUniversitaire: `/api/structure_annee_universitaires/${anneeUnivId}`,
          enseignantTypeHrs: hrs.enseignantTypeHrs?.id ? `/api/personnel_enseignant_type_hrs/${hrs.enseignantTypeHrs.id}` : null,
          libelle: hrs.libelle,
          semestre: hrs.semestre?.id ? `/api/structure_semestres/${hrs.semestre.id}` : null,
          diplome: hrs.diplome?.id ? `/api/structure_diplomes/${hrs.diplome.id}` : null,
          nbHeuresTd: parseFloat(hrs.nbHeuresTd) || 0,
        }, { headers: { 'Content-Type': 'application/ld+json' } }],
        'HRS dupliquée',
        'Erreur lors de la duplication de la HRS',
        true
    );

    await getEnseignantHrs(selectedPersonnel.value.id);
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de la duplication de la HRS.' });
  }
};

const deleteHrs = async (hrsId) => {
  try {
    await deletePersonnelEnseignantHrsService(hrsId, true);
    await getEnseignantHrs(selectedPersonnel.value?.id);
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erreur', detail: 'Erreur lors de la suppression de la HRS.' });
  }
};
</script>

<template>
  <ErrorState v-if="hasError" />
  <div v-else class="px-4 flex flex-col">
    <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
    <div v-else>
      <div class="flex justify-end gap-10">
        <Button v-if="!gestionHrs && !isEditing" label="Saisir le prévisionnel" icon="pi pi-plus" @click="isEditing = !isEditing" />
        <Button v-else-if="!gestionHrs" label="Afficher le prévisionnel" icon="pi pi-eye" @click="isEditing = !isEditing" />
      </div>
      <div v-if="isEditing || previPersonnels?.[0]?.length > 0">
        <div class="flex w-full justify-between my-6">
          <SelectButton v-model="size" :options="sizeOptions" optionLabel="label" dataKey="label" />
          <div v-if="isEditing">
            <SimpleSkeleton v-if="isLoadingPersonnels" width="100%" />
            <Select
                v-model="selectedPersonnel"
                :options="personnels"
                option-label="display"
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
              v-if="gestionHrs && selectedPersonnel"
              origin="previHrs"
              :columns="[]"
              :additionalRows="additionalRowsHrs"
              :data="[]"
              :size="size.value"
              :headerTitle="`HRS et primes de ${selectedPersonnel.display}`"
              :headerTitlecolspan="8"/>
          <PrevisionnelTable
              v-else-if="previPersonnel.length > 0 && previPersonnel[0]"
              origin="previEnseignantForm"
              @save-row="saveRow"
              @delete-row="deletePrevi"
              @cancel-row="cancelRow"
              :columns="columnsForm"
              :topHeaderCols="topHeaderColsForm"
              :additionalRows="[...additionalRowsForm, ...additionalRowsHrs]"
              :footerCols="footerColsForm"
              :data="previPersonnel[0]"
              :size="size.value"
              v-model:editingRowId="editingRowId"
              :headerTitle="`Prévisionnel de ${selectedPersonnel?.display || ''}`"
              :headerTitlecolspan="1"/>
          <EmptyState v-else-if="!selectedPersonnel" title="Aucun intervenant disponible" />
          <p v-else>Prévisionnel indisponible pour cet intervenant.</p>
        </div>
      </div>
      <EmptyState v-else title="Aucun service prévisionnel saisi" />
    </div>
  </div>
</template>
