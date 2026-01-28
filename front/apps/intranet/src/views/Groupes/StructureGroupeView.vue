<script setup>
import {computed, onMounted, ref, watch, nextTick} from 'vue'
import {ErrorView, SimpleSkeleton} from "@components";
import Loader from '@components/loader/GlobalLoader.vue'
import {typesGroupes} from '@config/uniServices.js';
import {useSemestreStore, useUsersStore} from "@stores";
import {
  getEtudiantScolariteSemestresService,
  getGroupesService,
  getSemestreService,
  getSemestresService,
  updateEtudiantScolariteSemestreService
} from "@requests";
import {useRoute} from "vue-router";
import { useToast } from 'primevue/usetoast';
import { useEtudiantFilters } from '@composables/filters/usersFilters/useEtudiantFilters';

const toast = useToast();
const route = useRoute();
const hasError = ref(false);
const semestre = ref({});
const isLoadingSemestre = ref(true);
const groupes = ref({});
const isLoadingGroupes = ref(true);
const selectedGroupe = ref(null);
const semestreStore = useSemestreStore();
const semestres = ref(semestreStore.semestres);
const isLoadingSemestres = ref(true);
const typesList = computed(() => Object.keys(groupes.value));
const usersStore = useUsersStore();
const departementId = usersStore.departementDefaut.id;
const etudiantsScolariteSemestre = ref([]);
const isLoadingEtudiants = ref(true);
const anneeUniv = localStorage.getItem('selectedAnneeUniv') ? JSON.parse(localStorage.getItem('selectedAnneeUniv')) : { id: null };

const { filters, watchChanges } = useEtudiantFilters();
// Déclenche un rechargement serveur quand les filtres changent
watchChanges(async () => {
  page.value = 0;
  await getEtudiants();
});

const nbEtudiants = ref(0);
const page = ref(0);
const rowOptions = [30, 60, 120];
const limit = ref(rowOptions[0]);
const offset = computed(() => limit.value * page.value);



onMounted(async () => {
  await getSemestre();
  await getSemestres();
  await getGroupes();
  await getEtudiants();
});

watch(() => semestreStore.semestre, (newSemestre) => {
  semestre.value = newSemestre;
});

// watcher pour relancer getGroupes quand semestre change
watch(semestre, async (newSemestre, oldSemestre) => {
  if (newSemestre.id !== oldSemestre.id) {
    await getGroupes();
    await getEtudiants();
    syncPreselectedRadios();
  }
});

watch(groupes, (newVal) => {
  const types = Object.keys(newVal);
  if (types.length > 0 && !selectedGroupe.value) {
    selectedGroupe.value = types[0];
  }
  // dès que la liste des groupes par type évolue, recalcule la présélection
  syncPreselectedRadios();
});

// re-synchroniser lors du changement d'onglet/type sélectionné
watch(selectedGroupe, () => {
  syncPreselectedRadios();
});

const getSemestre = async () => {
  isLoadingSemestre.value = true;
  hasError.value = false;
  // Récupération de l'id du semestre dans l'url
  try {
    const semestreId = route.params.semestreId;
    semestre.value = await getSemestreService(semestreId, '/mini');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération du semestre :", error);
  } finally {
    isLoadingSemestre.value = false;
  }
};

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  hasError.value = false;
  try {
    const params = {
      departement: departementId,
    };
    semestres.value = await getSemestresService(params, '/mini');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des semestres :", error);
  } finally {
    isLoadingSemestres.value = false;
  }
};

const getGroupes = async () => {
  isLoadingGroupes.value = true;
  hasError.value = false;
  try {
    const params = {
      semestre: semestre.value.id,
    };
    const rawGroupes = await getGroupesService(params, '/mini');

    // Trier les groupes par type dans des tableaux séparés
    const groupesParType = {};
    typesGroupes.forEach(type => {
      groupesParType[type.value] = rawGroupes.filter(groupe => groupe.type === type.value);
    });
    // si un type n'a pas de groupe, on le supprime
    for (const type in groupesParType) {
      if (groupesParType[type].length === 0) {
        delete groupesParType[type];
      }
    }
    groupes.value = groupesParType;

    // initialiser selectedGroupe si nécessaire
    const types = Object.keys(groupes.value);
    if (types.length > 0 && !selectedGroupe.value) {
      selectedGroupe.value = types[0];
    }

    // synchroniser la présélection des radios dès que les groupes sont connus
    syncPreselectedRadios();
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des groupes :", error);
  } finally {
    isLoadingGroupes.value = false;
  }
};

const getEtudiants = async () => {
  isLoadingEtudiants.value = true;
  hasError.value = false;
  try {
    const params = {
      anneeUniversitaire: anneeUniv.id,
      semestre: semestre.value.id,
      limit: limit.value,
      page: parseInt(page.value) + 1,
      filters: filters.value,
    };

    // 1) Récupération de la page courante via l'endpoint manage-groupes (DTO spécifique)
    const responsePage = await getEtudiantScolariteSemestresService(params, '/manage-groupes');
    etudiantsScolariteSemestre.value = responsePage.member ?? responsePage;

    // 2) Récupération d'un total fiable via l'endpoint standard (qui expose bien totalItems)
    const countParams = {
      anneeUniversitaire: anneeUniv.id,
      semestre: semestre.value.id,
      limit: 1,
      page: 1,
      filters: filters.value,
    };
    const responseCount = await getEtudiantScolariteSemestresService(countParams);
    nbEtudiants.value = responseCount.totalItems ?? (Array.isArray(responseCount.member) ? responseCount.member.length : 0);

    // Après chargement des étudiants, synchroniser la présélection
    syncPreselectedRadios();
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des étudiants :", error);
  } finally {
    isLoadingEtudiants.value = false;
  }
};

const syncPreselectedRadios = () => {
  try {
    const type = selectedGroupe.value;
    if (!type) return;

    const listeGroupesType = groupes.value?.[type] || [];

    etudiantsScolariteSemestre.value.forEach((sco) => {
      const assigned = (sco.groupes || []).find((g) => g?.type === type);
      if (!assigned) {
        sco.groupeAffecte = null;
        return;
      }

      // Normaliser l'ID si c'est une IRI
      let assignedId = assigned.id;
      if (typeof assignedId === 'string' && assignedId.startsWith('/')) {
        const parts = assignedId.split('/');
        const last = parts[parts.length - 1];
        const parsed = parseInt(last, 10);
        if (!isNaN(parsed)) assignedId = parsed;
      }

      const match = listeGroupesType.find((g) => g.id === assignedId);
      sco.groupeAffecte = match || null;
    });
  } catch (e) {
    console.error('Erreur lors de la synchronisation des présélections de radios :', e);
  }
};

const assignGroupe = async (etudiantScolariteSemestreId, groupeId) => {
  try {
    // Retrouver l'objet scolariteSemestre concerné
    const scolariteSemestre = etudiantsScolariteSemestre.value.find(e => e.id === etudiantScolariteSemestreId);
    if (!scolariteSemestre) return;

    const groupType = selectedGroupe.value; // ex: 'CM', 'TD', 'TP'

    // Copie de la liste actuelle des groupes affectés à l'étudiant pour ce semestre
    const currentGroupes = Array.isArray(scolariteSemestre.groupes) ? [...scolariteSemestre.groupes] : [];

    // L'ensemble des groupes du semestre (tous types confondus) pour retrouver l'objet complet
    const semGroupes = Object.values(groupes.value).flat();

    // Trouver l'objet groupe sélectionné (ou créer un placeholder avec id/type)
    const newGroup = groupeId != null
        ? (semGroupes.find(g => g.id === groupeId) || { id: groupeId, type: groupType })
        : null;

    // Remplacer/supprimer le groupe du même type dans la liste actuelle
    const idx = currentGroupes.findIndex(g => g.type === groupType);
    if (idx !== -1) {
      if (newGroup) {
        currentGroupes[idx] = newGroup;
      } else {
        currentGroupes.splice(idx, 1);
      }
    } else if (newGroup) {
      currentGroupes.push(newGroup);
    }

    // Mettre à jour le modèle local pour réactivité immédiate
    scolariteSemestre.groupes = currentGroupes;
    scolariteSemestre.groupeAffecte = newGroup;

    // Construire le payload en IRI
    const payloadGroupes = currentGroupes.map(g => {
      if (typeof g.id === 'number') {
        return `/api/structure_groupes/${g.id}`;
      }
      // si g.id est un objet (Hydra/IRI déjà), le laisser tel quel
      return g.id;
    });

    await updateEtudiantScolariteSemestreService(etudiantScolariteSemestreId, { groupes: payloadGroupes }, false);
  } catch (error) {
    console.error("Erreur lors de l'affectation du groupe :", error);
    // afficher un toast d'erreur' si aucun toast identique n'est présent
    const existingToast = Array.from(document.querySelectorAll('.p-toast-message')).some(el =>
        el.textContent && el.textContent.includes('Affectation mise à jour')
    );
    if (!existingToast) {
      toast.add({ severity: 'success', summary: 'Un problème est survenu', detail: 'Le groupe n\'a pas été mis à jour', life: 3000 });
    }
  } finally {
    // afficher un toast de succès si aucun toast identique n'est présent
    const existingToast = Array.from(document.querySelectorAll('.p-toast-message')).some(el =>
        el.textContent && el.textContent.includes('Affectation mise à jour')
    );
    if (!existingToast) {
      toast.add({ severity: 'success', summary: 'Affectation mise à jour', detail: 'Le groupe a été affecté avec succès.', life: 3000 });
    }
  }
};

// Ajout : refs pour cases d'entête et helpers de normalisation / états
const headerCheckboxRefs = new Map();

const normalizeId = (id) => {
  if (typeof id === 'string' && id.startsWith('/')) {
    const parts = id.split('/');
    const last = parts[parts.length - 1];
    const parsed = parseInt(last, 10);
    if (!isNaN(parsed)) return parsed;
  }
  return id;
};

const isAllSelected = (group) => {
  if (!etudiantsScolariteSemestre.value.length) return false;
  const gid = normalizeId(group.id);
  return etudiantsScolariteSemestre.value.every(sco => {
    return sco.groupeAffecte && normalizeId(sco.groupeAffecte.id) === gid;
  });
};

const isSomeSelected = (group) => {
  const gid = normalizeId(group.id);
  const some = etudiantsScolariteSemestre.value.some(sco => {
    return sco.groupeAffecte && normalizeId(sco.groupeAffecte.id) === gid;
  });
  return some && !isAllSelected(group);
};

// Helpers pour la colonne "Sans grp." (aucun groupe)
const isAllNoneSelected = () => {
  if (!etudiantsScolariteSemestre.value.length) return false;
  return etudiantsScolariteSemestre.value.every(sco => !sco.groupeAffecte);
};

const isSomeNoneSelected = () => {
  const some = etudiantsScolariteSemestre.value.some(sco => !sco.groupeAffecte);
  return some && !isAllNoneSelected();
};

const registerHeaderCheckbox = (el, group) => {
  if (!el) {
    headerCheckboxRefs.delete(normalizeId(group.id));
    return;
  }
  const gid = normalizeId(group.id);
  headerCheckboxRefs.set(gid, el);
  nextTick(() => {
    if (gid === 'none') {
      el.indeterminate = isSomeNoneSelected();
      el.checked = isAllNoneSelected();
    } else {
      el.indeterminate = isSomeSelected(group);
      el.checked = isAllSelected(group);
    }
  });
};

// Mettre à jour l'état des cases d'entête quand les étudiants changent
watch(etudiantsScolariteSemestre, () => {
  headerCheckboxRefs.forEach((el, gid) => {
    if (!el) return;
    if (gid === 'none') {
      el.indeterminate = isSomeNoneSelected();
      el.checked = isAllNoneSelected();
      return;
    }
    // retrouver l'objet groupe correspondant (si possible) pour recalculer les états
    const allGroupes = Object.values(groupes.value).flat();
    const group = allGroupes.find(g => normalizeId(g.id) === normalizeId(gid));
    if (group) {
      el.indeterminate = isSomeSelected(group);
      el.checked = isAllSelected(group);
    }
  });
}, { deep: true });

// Action déclenchée par la case d'entête : cocher/décocher pour tous
const toggleSelectAllForGroup = async (group, event) => {
  const checked = event.target.checked;
  // Appliquer pour tous les étudiants (parallèle)
  await Promise.all(etudiantsScolariteSemestre.value.map(sco => {
    // si décoché => groupeId null pour supprimer l'affectation de ce type
    return assignGroupe(sco.id, checked ? normalizeId(group.id) : null);
  }));
  // Mettre à jour l'état visuel
  const el = headerCheckboxRefs.get(normalizeId(group.id));
  if (el) {
    el.indeterminate = false;
    el.checked = checked;
  }
};

// Action pour la colonne "Sans grp." => définit tout le monde sans groupe pour le type sélectionné
const toggleSelectAllNone = async (event) => {
  const checked = event.target.checked;
  await Promise.all(etudiantsScolariteSemestre.value.map(sco => {
    // checked => tout le monde sans groupe; uncheck => on ne force pas de groupe (état inchangé)
    return assignGroupe(sco.id, checked ? null : (sco.groupeAffecte ? normalizeId(sco.groupeAffecte.id) : null));
  }));
  const el = headerCheckboxRefs.get('none');
  if (el) {
    el.indeterminate = false;
    el.checked = checked;
  }
};

const onPageChange = async (event) => {
  limit.value = event.rows;
  page.value = event.page;
  await getEtudiants();
};
</script>

<template>
  <div class="card min-h-full">
    <div class="flex justify-between items-end w-full">
      <div>
        <h2 class="text-2xl font-bold flex items-end gap-2">Structure des groupes du <SimpleSkeleton v-if="isLoadingSemestre" class="!w-32"></SimpleSkeleton><span v-else>{{semestre.libelle}}</span></h2>
        <em>Répartir les étudiants dans les groupes</em>
      </div>
      <SimpleSkeleton v-if="isLoadingSemestres" class="!w-60 !h-10"></SimpleSkeleton>
      <Select v-else class="w-60" v-model="semestre" option-label="libelle" :options="semestres" placeholder="Sélectionner un semestre">
        <template #value>
          Changer de semestre
        </template>
      </Select>
    </div>
    <Divider/>
    <ErrorView v-if="hasError"></ErrorView>
    <div v-else>

      <Loader v-if="isLoadingGroupes" class="my-12"></Loader>
      <div v-else class="flex flex-col gap-4">

      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
