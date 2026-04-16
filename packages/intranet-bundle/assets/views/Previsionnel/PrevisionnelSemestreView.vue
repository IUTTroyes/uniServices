<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { SimpleSkeleton, ErrorView, ListSkeleton, ButtonDelete } from "@components";
import { useAnneeStore, useEnseignementsStore, useUsersStore } from "@stores";
import {
  createPreviService,
  deletePreviService,
  getPersonnelsService,
  getSemestrePreviService,
  getSemestresService,
  updatePreviEnseignementService,
  updatePreviPersonnelService,
  updatePreviService
} from "@requests";
import { showDanger } from "@helpers";
import PrevisionnelTable from "@/components/Previsionnel/PrevisionnelTable.vue";
import { useToast } from "primevue/usetoast";

const toast = useToast();
const hasError = ref(false);
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };
const selectedAnneeUniv = ref(anneeUniv);
const usersStore = useUsersStore();
const enseignementStore = useEnseignementsStore();
const departementId = usersStore.departementDefaut.id;
const anneeStore = useAnneeStore();
const annees = ref([]);
const isLoadingAnnees = ref(true);
const selectedAnnee = ref(null);
const semestres = ref([]);
const isLoadingSemestres = ref(true);
const selectedSemestre = ref(null);
const isLoadingPrevisionnel = ref(true);
const previSemestre = ref(null);
const enseignementsList = ref([]);
const selectedEnseignement = ref(null);
const personnelsList = ref([]);
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
    if (anneeStore.annees && Array.isArray(anneeStore.annees) && anneeStore.annees.length > 0) {
      annees.value = anneeStore.annees;
    } else {
      const params = {
        departement: departementId,
        actif: true,
        anneeUniversitaire: anneeUniv.id,
      };
      await anneeStore.getAnneesDepartement(params);
      annees.value = Array.isArray(anneeStore.annees) ? anneeStore.annees : [];
    }

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
  if (!selectedAnnee.value) return;

  isLoadingSemestres.value = true;
  hasError.value = false;
  try {
    const params = { annee: selectedAnnee.value.id };
    semestres.value = await getSemestresService(params, '/mini');

    if (semestres.value.length > 0) {
      selectedSemestre.value = semestres.value.find(s => s.actif) || semestres.value[0];
    }
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des semestres :", error);
  } finally {
    isLoadingSemestres.value = false;
  }
};

//watcher si selecedAnnee change
watch(selectedAnnee, async (newAnnee, oldAnnee) => {
  if (newAnnee) {
    await getSemestres();
  }
})

watch(selectedSemestre, async (newSemestre, oldSemestre) => {
  if (newSemestre) {
    await getPrevi(newSemestre.id);
  }
})

const getPrevi = async (semestreId) => {
  if (!semestreId) return;

  isLoadingPrevisionnel.value = true;
  try {
    // previSemestre est un tableau complexe renvoyé par le service :
    // [0] : Données pour le formulaire (heures par personnel/enseignement)
    // [1] : Données pour la synthèse (heures agrégées par enseignement)
    // [2] : Totaux généraux pour la synthèse
    // [3] : Totaux d'heures par étudiant (CM, TD, TP) avec comparaison attendu/saisi
    // [5] : Totaux d'heures (Classique et Équivalent TD)
    previSemestre.value = await getSemestrePreviService(semestreId, anneeUniv.id);

    // Chargement des listes pour les sélections dans le formulaire
    await Promise.all([
      fetchEnseignements(semestreId),
      fetchPersonnels()
    ]);

  } catch (error) {
    console.error('Erreur lors du chargement du prévisionnel:', error);
    hasError.value = true;
  } finally {
    isLoadingPrevisionnel.value = false;
  }
};

const fetchEnseignements = async (semestreId) => {
  try {
    await enseignementStore.getMatieresSemestre(semestreId);
    enseignementsList.value = (enseignementStore.enseignements || []).map((e) => ({
      ...e,
      label: `${e.codeEnseignement} - ${e.libelle}`,
      value: e
    }));
  } catch (error) {
    console.error('Erreur lors du chargement des matières:', error);
  }
};

const fetchPersonnels = async () => {
  try {
    const params = { departement: departementId, pagination: false };
    const personnels = await getPersonnelsService(params);
    personnelsList.value = (personnels || []).map((p) => ({
      ...p,
      label: p.display,
      value: p
    }));
  } catch (error) {
    console.error('Erreur lors du chargement des personnels:', error);
  }
};

const emptyPrevi = (previs) => {
  try {
    // pour chaque prévi on appelle deletePreviService
    previs.forEach(p => {
      deletePreviService(p.id, false);
    });
  } catch (error) {
    console.error('Erreur lors de la suppression des prévisions:', error);
  } finally {
    toast.add({
      severity: "success",
      summary: "Succès",
      detail: "Prévisionnel vidé avec succès",
      life: 5000,
    });
    getPrevi(selectedSemestre.value.id);
  }
}



//-------------------------
//-------------------------
//-------------------------
//-------------------------
//-------------------------





/**
 * Recalcule les totaux locaux pour la synthèse en bas du tableau après une modification d'heures ou de groupes.
 * previSemestre.value[0] contient les lignes individuelles par intervenant.
 * previSemestre.value[3] contient les totaux attendus/saisis par semestre.
 */
const refreshTotals = () => {
  const types = ['CM', 'TD', 'TP'];
  const prevs = previSemestre.value[0];
  const totals = previSemestre.value[3];

  types.forEach(type => {
    // Calcul de la somme des heures saisies (NbHrGrp * NbGrp) pour chaque type
    // Note: Dans ce contexte, on veut la somme des heures affectées (NbHrGrp) quand NbGrp > 0
    const totalSaisi = prevs.reduce((acc, p) => {
      const h = p.heures[type];
      return acc + (h.NbGrp > 0 ? (h.NbHrGrp || 0) : 0);
    }, 0);

    totals[type].NbHrSaisi = Math.round(totalSaisi * 10) / 10;
    totals[type].Diff = Math.round((totals[type].NbHrSaisi - totals[type].NbHrAttendu) * 10) / 10;
  });
};

const duplicatePrevi = async (previId) => {
  try {
    const previToDuplicate = previSemestre.value[0].find(p => p.id === previId);
    if (!previToDuplicate) return;

    const newPreviData = {
      personnel: `/api/personnels/${previToDuplicate.idPersonnel}`,
      anneeUniversitaire: `/api/structure_annee_universitaires/${selectedAnneeUniv.value.id}`,
      referent: false,
      heures: Object.keys(previToDuplicate.heures).reduce((acc, key) => {
        const h = previToDuplicate.heures[key];
        acc[key] = (h.NbHrGrp || 0) * (h.NbGrp || 0);
        return acc;
      }, {}),
      groupes: previToDuplicate.groupes,
      enseignement: `/api/scol_enseignements/${previToDuplicate.idEnseignement}`,
    };

    await createPreviService(newPreviData);
    await getPrevi(selectedSemestre.value.id); // Rechargement complet pour rafraîchir la synthèse
  } catch (error) {
    showDanger('Une erreur est survenue lors de la duplication du prévisionnel', error);
  }
};

const updateHeuresPrevi = async (previId, type, valeur) => {
  try {
    const previForm = previSemestre.value[0].find(p => p.id === previId);
    if (!previForm) return;

    const newValue = parseFloat(valeur);
    if (isNaN(newValue)) return;

    // Mise à jour locale immédiate pour réactivité de l'UI
    previForm.heures[type].NbHrGrp = newValue;
    previForm.heures[type].NbSeanceGrp = Math.round(newValue * previForm.heures[type].NbGrp * 10) / 10;
    refreshTotals();

    // Préparation de l'objet d'heures pour l'API (attends les totaux NbHrGrp * NbGrp)
    const newHeures = ['CM', 'TD', 'TP', 'Projet'].reduce((acc, key) => {
      const h = previForm.heures[key];
      acc[key] = (h.NbHrGrp || 0) * (h.NbGrp || 0);
      return acc;
    }, {});

    await updatePreviService(previId, { heures: newHeures });
  } catch (error) {
    showDanger('Une erreur est survenue lors de la mise à jour des heures', error);
    // Optionnel: Recharger les données en cas d'erreur pour resynchroniser
    await getPrevi(selectedSemestre.value.id);
  }
};

const updateGroupesPrevi = async (previId, type, valeur) => {
  try {
    const previForm = previSemestre.value[0].find(p => p.id === previId);
    if (!previForm) return;

    const newValue = parseInt(valeur);
    if (isNaN(newValue)) return;

    // Mise à jour locale immédiate
    previForm.groupes[type] = newValue;
    previForm.heures[type].NbGrp = newValue;
    previForm.heures[type].NbSeanceGrp = previForm.heures[type].NbHrGrp * newValue;
    refreshTotals();

    // Si le nombre de groupes devient 0, on pourrait aussi vouloir mettre à jour l'API pour les heures
    // car le total NbHrGrp * NbGrp change
    const newHeures = ['CM', 'TD', 'TP', 'Projet'].reduce((acc, key) => {
      const h = previForm.heures[key];
      acc[key] = (h.NbHrGrp || 0) * (h.NbGrp || 0);
      return acc;
    }, {});

    // Mise à jour groupes ET heures (car elles dépendent du NbGrp pour le calcul côté API)
    await updatePreviService(previId, {
      groupes: { ...previForm.groupes },
      heures: newHeures
    });
  } catch (error) {
    showDanger('Une erreur est survenue lors de la mise à jour des groupes', error);
    await getPrevi(selectedSemestre.value.id);
  }
};

const updateIntervenantPrevi = async (previId, personnel) => {
  try {
    // Récupérer le prévisionnel à modifier
    let previForm = previSemestre.value[0].find(previ => previ.id === previId);
    // Mettre à jour l'intervenant du prévisionnel
    await updatePreviPersonnelService(previId, personnel.id);
    // Mettre à jour le prévisionnel
    previForm.idPersonnel = personnel.id;
    // Ensure intervenant is a string, not an object
    previForm.intervenant = typeof personnel.display === 'string' ? personnel.display : `${personnel.prenom} ${personnel.nom}`;
  } catch (error) {
    console.error('Erreur lors de la mise à jour de l\'intervenant :', error);
  }
};

watch(isEditing, async (newIsEditing) => {
  if (!newIsEditing) {
    await getPrevi(selectedSemestre.value?.id);
  }
});

const addPrevi = async (personnel, enseignement) => {
  if (!personnel || !enseignement) {
    showDanger('Veuillez sélectionner un intervenant et une matière');
    return;
  }

  try {
    const dataNewPrevi = {
      personnel: `/api/personnels/${personnel.id}`,
      anneeUniversitaire: `/api/structure_annee_universitaires/${selectedAnneeUniv.value.id}`,
      referent: false,
      heures: { CM: 0, TD: 0, TP: 0, Projet: 0 },
      groupes: { CM: 0, TD: 0, TP: 0, Projet: 0 },
      enseignement: `/api/scol_enseignements/${enseignement.id}`,
    };
    await createPreviService(dataNewPrevi);
    await getPrevi(selectedSemestre.value.id);
  } catch (error) {
    console.error('Erreur lors de la création du prévisionnel:', error);
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
  { header: 'Matière', field: 'libelleEnseignement', sortable: true, colspan: 1, class: '!overflow-hidden !truncate', form: true, formType: 'select', formOptions: enseignementsList, placeholder: "Sélectionner une matière", id: 'id', formAction: (previId, event) => { updatePreviEnseignementService(previId, event.id)}, disabled: () => false },

  { header: 'Intervenant', field: 'intervenant', sortable: true, colspan: 1, class: '!wrapper !text-wrap', form: true, formType: 'select', formOptions: personnelsList, placeholder: "Sélectionner un intervenant", id: 'id', formAction: (previId, event) => { updateIntervenantPrevi(previId, event)}, disabled: () => false },

  { header: 'Nb H/Gr.', name: 'nbHrGrpCM', field: 'heures.CM.NbHrGrp', colspan: 1, class: '!bg-purple-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) }, disabled: (data) => (data.heures.CM.NbGrp || 0) <= 0 },

  { header: 'Nb Gr.', name: 'NbGrpCM', field: 'heures.CM.NbGrp', colspan: 1, class: '!bg-purple-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'CM', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) }, disabled: () => false },

  { header: 'Séances', field: 'heures.CM.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-purple-400/20 !max-w-20', form: false },

  { header: 'Nb H/Gr.', name: 'nbHrGrpTD', field: 'heures.TD.NbHrGrp', colspan: 1, class: '!bg-green-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) }, disabled: (data) => (data.heures.TD.NbGrp || 0) <= 0 },

  { header: 'Nb Gr.', name: 'nbGrpTD', field: 'heures.TD.NbGrp', colspan: 1, class: '!bg-green-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TD', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) }, disabled: () => false },

  { header: 'Séances', field: 'heures.TD.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-green-400/20 !max-w-20', form: false },

  { header: 'Nb H/Gr.', name: 'nbHrGrpTD', field: 'heures.TP.NbHrGrp', colspan: 1, class: '!bg-amber-400/20 !text-nowrap', unit: ' h', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, event) => { updateHeuresPrevi(previId, type, event) }, disabled: (data) => (data.heures.TP.NbGrp || 0) <= 0 },

  { header: 'Nb Gr.', name: 'nbGrpTD', field: 'heures.TP.NbGrp', colspan: 1, class: '!bg-amber-400/20 !text-nowrap', form: true, formType:'text', id: 'id', type: 'TP', formAction: (previId, type, event) => { updateGroupesPrevi(previId, type, event) }, disabled: () => false },

  { header: 'Séances', field: 'heures.TP.NbSeanceGrp', sortable: false, colspan: 1, class: '!bg-amber-400/20 !max-w-20', form: false },

  { header: 'Dupliquer', field: 'id', colspan: 1, button: true, buttonIcon: 'pi pi-copy', id: 'id', buttonAction: (id) => {duplicatePrevi(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'warn', duplicate: true },
  { header: 'Supprimer', field: '', colspan: 1, button: true, buttonIcon: 'pi pi-trash', id: 'id', buttonAction: (id) => {deletePrevi(id)}, buttonClass: () => '!w-full', buttonSeverity: () => 'danger', delete: true },
]);

const topHeaderColsForm = ref([
  { header: 'CM', colspan: 3, class: '!bg-purple-400/20' },
  { header: 'TD', colspan: 3, class: '!bg-green-400/20' },
  { header: 'TP', colspan: 3, class: '!bg-amber-400/20' },
  { header: '', colspan: 2 },
]);

const additionalRowsForm = computed(() => {
  if (!previSemestre.value || !previSemestre.value[3]) return [];

  const totals = previSemestre.value[3];
  const totalEquiv = previSemestre.value[5];

  return [
    [
      { footer: 'Ajouter une entrée au prévisionnel', colspan: 2, class: '!text-center !font-bold'},
      { footer: enseignementsList.value, colspan: 4, form: true, formType: 'select', placeholder: "Sélectionner une matière", formAction: (e) => { selectedEnseignement.value = e } },
      { footer: personnelsList.value, colspan: 4, form: true, formType: 'select', placeholder: "Sélectionner un intervenant", formAction: (p) => { selectedPersonnel.value = p } },
      { footer: 'Ajouter', colspan: 3, button: true, buttonIcon: 'pi pi-plus', buttonAction: () => { addPrevi(selectedPersonnel.value, selectedEnseignement.value) }, buttonClass: () => '!w-full', buttonSeverity: () => 'success' },
    ],
    [
      { footer: 'Synthèse', colspan: 19, class: '!text-center !font-bold'},
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
      { footer: '', colspan: 2 },
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
      { footer: totalEquiv?.TotalClassique, colspan: 4, class: '!text-nowrap !text-center', unit: ' h' },
      { footer: totalEquiv?.TotalTd, colspan: 5, class: '!text-nowrap !text-center', unit: ' h' },
      { footer: '', colspan: 2 },
    ],
  ];
});

const footerColsForm = computed(() => {
  if (!previSemestre.value || !previSemestre.value[4]) return [];
  return [];
});
</script>

<template>
  <ErrorView v-if="hasError" />
  <div v-else class="px-4 flex flex-col">
    <div class="flex justify-between gap-10">
      <div class="flex gap-6 w-1/2">
        <SimpleSkeleton v-if="isLoadingAnnees" class="!w-60 !h-10"></SimpleSkeleton>
        <Select v-else class="w-60" v-model="selectedAnnee" option-label="libelle" :options="annees">
          <template #value>
            {{ selectedAnnee?.libelle || "Sélectionner une année" }}
          </template>
        </Select>
        <SimpleSkeleton v-if="isLoadingSemestres" class="!w-60 !h-10"></SimpleSkeleton>
        <Select v-else class="w-60" v-model="selectedSemestre" option-label="libelle" :options="semestres">
          <template #value>
            {{ selectedSemestre?.libelle || "Sélectionner un semestre" }}
          </template>
        </Select>
      </div>
      <div class="flex items-center gap-4">
        <Button v-if="!isEditing" label="Saisir le prévisionnel" icon="pi pi-plus" @click="isEditing = !isEditing" />
        <Button v-else label="Afficher le prévisionnel" icon="pi pi-eye" @click="isEditing = !isEditing" />
        <ButtonDelete tooltip="Vider le prévisionnel" label="Vider le prévisionnel" @confirm-delete="emptyPrevi(previSemestre[0])"/>
      </div>
    </div>

    <ListSkeleton v-if="isLoadingPrevisionnel" class="mt-6" />
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
</template>

<style scoped>

</style>
