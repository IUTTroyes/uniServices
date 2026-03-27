<script setup>
import {computed, onMounted, ref, watch, nextTick} from 'vue'
import {ErrorView, SimpleSkeleton} from "@components";
import {typesGroupes} from '@config/uniServices.js';
import {useSemestreStore, useUsersStore, useAnneeStore, useAnneeUnivStore} from "@stores";
import {
  getEtudiantScolariteSemestresService,
  getGroupesService,
  getSemestresService,
  getAnneeService,
  updateEtudiantScolariteSemestreService
} from "@requests";
import {useRoute} from "vue-router";
import { useToast } from 'primevue/usetoast';
import { useEtudiantFilters } from '@composables/filters/usersFilters/useEtudiantFilters';

const toast = useToast();
const route = useRoute();
const hasError = ref(false);
const semestre = ref({});
const groupes = ref({});
const isLoadingGroupes = ref(true);
const selectedGroupe = ref(null);
const semestreStore = useSemestreStore();
const semestres = ref([]);
const isLoadingSemestres = ref(true);
const typesList = computed(() => Object.keys(groupes.value));
const usersStore = useUsersStore();
const departementId = usersStore.departementDefaut.id;
const etudiantsScolariteSemestre = ref([]);
const isLoadingEtudiants = ref(true);
const anneeUniv = useAnneeUnivStore().selectedAnneeUniv;
const anneeStore = useAnneeStore();
const annees = ref([]);
const annee = ref({});
const isLoadingAnnee = ref(true);
const isInitialLoading = ref(true);

const { filters, watchChanges } = useEtudiantFilters();
// Déclenche un rechargement serveur quand les filtres changent
watchChanges(async () => {
  if (isInitialLoading.value) return;
  page.value = 0;
  await getEtudiants();
});

const nbEtudiants = ref(0);
const page = ref(0);
const rowOptions = [30, 60, 120];
const limit = ref(rowOptions[0]);
const offset = computed(() => limit.value * page.value);



onMounted(async () => {
  isInitialLoading.value = true;
  try {
    await getAnnees();
    await getAnnee();
    await getSemestres();
    // Sélectionner le premier semestre de l'année par défaut
    if (semestres.value.length > 0 && !semestre.value.id) {
      semestre.value = semestres.value[0];
    }
    await getGroupes();
    await getEtudiants();
  } catch (error) {
    console.error("Erreur lors de l'initialisation :", error);
    hasError.value = true;
  } finally {
    isInitialLoading.value = false;
  }
});

watch(() => semestreStore.semestre, (newSemestre) => {
  semestre.value = newSemestre;
});

// watcher pour relancer getGroupes quand semestre change
watch(semestre, async (newSemestre, oldSemestre) => {
  if (isInitialLoading.value) return;
  if (newSemestre.id !== oldSemestre.id) {
    await getGroupes();
    await getEtudiants();
    syncPreselectedRadios();
  }
});

// watcher pour relancer getGroupes et getSemestres quand annee change
watch(annee, async (newAnnee, oldAnnee) => {
  if (isInitialLoading.value) return;
  if (newAnnee.id !== oldAnnee.id) {
    await getSemestres();
    // si le semestre sélectionné n'est pas dans la nouvelle liste, on sélectionne le premier de la liste
    if (!semestres.value.some(s => s.id === semestre.value.id)) {
      semestre.value = semestres.value[0] || {};
    }
    await anneeStore.setSelectedAnnee(newAnnee)
    await getGroupes();
    await getEtudiants();
    syncPreselectedRadios();
  }
});


watch(groupes, (newVal) => {
  const types = Object.keys(newVal);
  if (types.length > 0 && (!selectedGroupe.value || !types.includes(selectedGroupe.value))) {
    selectedGroupe.value = types[0];
  }
  // dès que la liste des groupes par type évolue, recalcule la présélection
  syncPreselectedRadios();
});

// re-synchroniser lors du changement d'onglet/type sélectionné
watch(selectedGroupe, () => {
  syncPreselectedRadios();
});

const getAnnees = async () => {
  if (anneeStore.annees && Array.isArray(anneeStore.annees) && anneeStore.annees.length > 0) {
    annees.value = anneeStore.annees;
  } else {
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
  }
};

const getAnnee = async () => {
  isLoadingAnnee.value = true;
  hasError.value = false;
  // Récupération de l'id de l'année via l'URL
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
    if (types.length > 0 && (!selectedGroupe.value || !types.includes(selectedGroupe.value))) {
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

    // Récupération de la page courante via l'endpoint manage-groupes (DTO spécifique)
    const responsePage = await getEtudiantScolariteSemestresService(params, '/manage-groupes');
    etudiantsScolariteSemestre.value = responsePage.member ?? responsePage;

    // Récupération du total directement depuis la réponse Hydra si disponible
    if (responsePage.totalItems !== undefined) {
      nbEtudiants.value = responsePage.totalItems;
    } else {
      // Fallback si l'endpoint manage-groupes ne renvoie pas le totalItems
      const countParams = {
        anneeUniversitaire: anneeUniv.id,
        semestre: semestre.value.id,
        limit: 1,
        page: 1,
        filters: filters.value,
      };
      const responseCount = await getEtudiantScolariteSemestresService(countParams);
      nbEtudiants.value = responseCount.totalItems ?? (Array.isArray(responseCount.member) ? responseCount.member.length : 0);
    }

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

    // Afficher un toast de succès seulement s'il n'y en a pas déjà un (évite les spams en sélection multiple)
    const existingSuccessToast = Array.from(document.querySelectorAll('.p-toast-message-success')).length > 0;
    if (!existingSuccessToast) {
      toast.add({ severity: 'success', summary: 'Affectation mise à jour', detail: 'Le groupe a été affecté avec succès.', life: 3000 });
    }
  } catch (error) {
    console.error("Erreur lors de l'affectation du groupe :", error);
    toast.add({ severity: 'error', summary: 'Un problème est survenu', detail: 'Le groupe n\'a pas été mis à jour', life: 3000 });
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

const isSynchroLoading = ref(false);

const synchroParents = async () => {
  isSynchroLoading.value = true;
  try {
    // Récupérer tous les groupes du semestre pour avoir les relations parent
    const allGroupes = Object.values(groupes.value).flat();

    // Construire un map pour accès rapide par ID
    const groupeMap = new Map();
    allGroupes.forEach(g => {
      groupeMap.set(g.id, g);
      // Si l'ID est une IRI, on ajoute aussi la version numérique
      if (typeof g.id === 'string') {
        const numId = parseInt(g.id.split('/').pop(), 10);
        if (!isNaN(numId)) groupeMap.set(numId, g);
      }
    });

    // Fonction pour remonter la hiérarchie et récupérer tous les parents
    const getParentChain = (groupe) => {
      const parents = [];
      let current = groupe;
      while (current?.parent) {
        // Normaliser l'ID du parent
        let parentId = current.parent.id ?? current.parent;
        if (typeof parentId === 'string' && parentId.startsWith('/')) {
          const parts = parentId.split('/');
          parentId = parseInt(parts[parts.length - 1], 10);
        }
        const parentGroupe = groupeMap.get(parentId);
        if (parentGroupe) {
          parents.push(parentGroupe);
          current = parentGroupe;
        } else {
          break;
        }
      }
      return parents;
    };

    // Ordre de priorité des types (du plus bas au plus haut niveau)
    const typePriority = { 'TP': 1, 'LV': 2, 'PROJET': 2, 'AUTRE': 2, 'TD': 3, 'CM': 4 };

    // Compteur pour le feedback
    let updatedCount = 0;
    let errorCount = 0;

    // Traiter chaque étudiant
    for (const sco of etudiantsScolariteSemestre.value) {
      try {
        // Récupérer les groupes actuels de l'étudiant
        const currentGroupes = Array.isArray(sco.groupes) ? [...sco.groupes] : [];

        // Trouver le groupe de plus bas niveau dans la hiérarchie
        let lowestGroupe = null;
        let lowestPriority = Infinity;

        for (const g of currentGroupes) {
          const groupeObj = groupeMap.get(normalizeId(g.id));
          if (groupeObj) {
            const priority = typePriority[groupeObj.type] || 99;
            if (priority < lowestPriority) {
              lowestPriority = priority;
              lowestGroupe = groupeObj;
            }
          }
        }

        if (!lowestGroupe) {
          // Pas de groupe assigné, rien à synchroniser
          continue;
        }

        // Récupérer tous les parents
        const parentChain = getParentChain(lowestGroupe);

        if (parentChain.length === 0) {
          // Pas de parents à ajouter
          continue;
        }

        // Construire la nouvelle liste de groupes
        // On garde le groupe de plus bas niveau et on ajoute les parents manquants
        const currentTypes = new Set(currentGroupes.map(g => {
          const obj = groupeMap.get(normalizeId(g.id));
          return obj?.type;
        }));

        const newGroupes = [...currentGroupes];
        let hasChanges = false;

        for (const parent of parentChain) {
          // Vérifier si ce type de groupe n'est pas déjà présent
          if (!currentTypes.has(parent.type)) {
            newGroupes.push(parent);
            currentTypes.add(parent.type);
            hasChanges = true;
          } else {
            // Remplacer le groupe existant par le parent correct si différent
            const existingIndex = newGroupes.findIndex(g => {
              const obj = groupeMap.get(normalizeId(g.id));
              return obj?.type === parent.type;
            });
            if (existingIndex !== -1) {
              const existingId = normalizeId(newGroupes[existingIndex].id);
              if (existingId !== parent.id) {
                newGroupes[existingIndex] = parent;
                hasChanges = true;
              }
            }
          }
        }

        if (!hasChanges) {
          continue;
        }

        // Mettre à jour l'étudiant via l'API
        const payloadGroupes = newGroupes.map(g => {
          const gid = normalizeId(g.id);
          if (typeof gid === 'number') {
            return `/api/structure_groupes/${gid}`;
          }
          return g.id;
        });

        await updateEtudiantScolariteSemestreService(sco.id, { groupes: payloadGroupes }, false);

        // Mettre à jour le modèle local
        sco.groupes = newGroupes;
        updatedCount++;
      } catch (error) {
        console.error(`Erreur lors de la synchronisation pour l'étudiant ${sco.id}:`, error);
        errorCount++;
      }
    }

    // Rafraîchir les données
    await getEtudiants();
    syncPreselectedRadios();

    // Afficher le résultat
    if (errorCount > 0) {
      toast.add({
        severity: 'warn',
        summary: 'Synchronisation partielle',
        detail: `${updatedCount} étudiant(s) mis à jour, ${errorCount} erreur(s).`,
        life: 5000
      });
    } else if (updatedCount > 0 && errorCount === 0) {
      toast.add({
        severity: 'success',
        summary: 'Synchronisation réussie',
        detail: `Les groupes ont été mis à jour.`,
        life: 3000
      });
    } else {
      toast.add({
        severity: 'info',
        summary: 'Aucune modification',
        detail: 'Tous les groupes parents étaient déjà correctement assignés.',
        life: 3000
      });
    }
  } catch (error) {
    console.error('Erreur lors de la synchronisation des parents:', error);
    toast.add({
      severity: 'error',
      summary: 'Erreur',
      detail: 'Une erreur est survenue lors de la synchronisation.',
      life: 5000
    });
  } finally {
    isSynchroLoading.value = false;
  }
};
</script>

<template>
  <div class="card min-h-full">
    <div class="flex justify-between items-end w-full">
      <div>
        <h2 class="text-2xl! mb-0! font-bold flex items-end gap-2">Composition des groupes de <span>{{annee.libelle}}</span></h2>
        <em>Répartir les étudiants dans les groupes</em>
      </div>
      <SimpleSkeleton v-if="isLoadingSemestres" class="!w-60 !h-10"></SimpleSkeleton>
      <div v-else class="flex gap-4">
        <Select class="w-60" v-model="annee" option-label="libelle" :options="annees">
          <template #value>
            {{ annee?.libelle || "Changer d'année" }}
          </template>
        </Select>
        <Select class="w-60" v-model="semestre" option-label="libelle" :options="semestres">
          <template #value>
            {{ semestre?.libelle || "Changer de semestre" }}
          </template>
        </Select>
        <div class="flex items-center gap-1">
            <Button
                label="Synchroniser les parents"
                icon="pi pi-sync"
                @click="synchroParents()"
                class="p-button"
                :loading="isSynchroLoading"
                :disabled="isLoadingEtudiants || isLoadingGroupes"
            />
          <i class="pi pi-question-circle text-primary font-black" v-tooltip.top="`Permet de remplir automatiquement les groupes parents (CM, TD...) en fonction des affectations des groupes enfants (TP...).`"></i>
        </div>
      </div>
    </div>
    <Divider/>
    <ErrorView v-if="hasError"></ErrorView>
    <div v-else>
      <Message severity="info" class="mb-4" icon="pi pi-info-circle">
        Vous pouvez ne remplir que le groupe de plus bas niveau (TP) et synchroniser pour remplir automatiquement les groupes parents. Si les groupes sont saisis dans Apogée, vous pouvez aussi les synchroniser (il faut attendre 24h entre la saisie dans Apogée et la possibilité de synchroniser).

      </Message>
      <div v-if="isLoadingGroupes" class="flex items-center justify-center my-12">
        <ProgressSpinner style="width: 50px; height: 50px" />
      </div>
      <div v-else class="flex flex-col gap-4">
        <Tabs :value="selectedGroupe" scrollable>
          <TabList>
            <Tab v-for="type in typesList" :key="type" :value="type" @click="selectedGroupe = type">
              <span>{{ type }}</span>
            </Tab>
          </TabList>
        </Tabs>

        <DataTable
            v-if="selectedGroupe && groupes[selectedGroupe]"
            v-model:filters="filters"
            :value="etudiantsScolariteSemestre"
            responsiveLayout="scroll"
            class="w-full"
            scrollable
            scroll-height="60vh"
            lazy
            striped-rows
            paginator
            filterDisplay="row"
            dataKey="id"
            :first="offset"
            :rows="limit"
            :rowsPerPageOptions="rowOptions"
            :totalRecords="nbEtudiants"
            :loading="isLoadingEtudiants"
            @page="onPageChange($event)"
            @update:rows="limit = $event"
        >
          <Column field="numEtudiant" header="N° étudiant">
            <template #body="{ data }">
              <div class="p-1">
                {{ data.etudiant.numEtudiant }}
              </div>
            </template>
            <template #filter="{ filterModel, filterCallback }">
              <InputText
                  v-model="filterModel.value"
                  placeholder="Filtrer par n° étudiant"
                  class="w-full"
                  @input="filterCallback()"
              />
            </template>
          </Column>
          <Column field="nom" header="Nom" frozen>
            <template #body="{ data }">
              <div class="p-1">
                {{ data.etudiant.nom }}
              </div>
            </template>
            <template #filter="{ filterModel, filterCallback }">
              <InputText
                  v-model="filterModel.value"
                  placeholder="Filtrer par nom"
                  class="w-full"
                  @input="filterCallback()"
              />
            </template>
          </Column>
          <Column field="prenom" header="Prénom" frozen>
            <template #body="{ data }">
              <div class="p-1">
                {{ data.etudiant.prenom }}
              </div>
            </template>
            <template #filter="{ filterModel, filterCallback }">
              <InputText
                  v-model="filterModel.value"
                  placeholder="Filtrer par prénom"
                  class="w-full"
                  @input="filterCallback()"
              />
            </template>
          </Column>
          <Column field="etudiant.bac" header="Bac" />
          <Column v-for="g in groupes[selectedGroupe]" :key="g.id">
            <!-- Entête : checkbox pour cocher/décocher ce groupe pour toutes les lignes -->
            <template #header>
              <div class="flex items-center gap-2">
                <input
                    type="checkbox"
                    :ref="el => registerHeaderCheckbox(el, g)"
                    @change="toggleSelectAllForGroup(g, $event)"
                    :aria-label="`Tout sélectionner ${g.libelle}`"
                />
                <span>{{ g.libelle }}</span>
              </div>
            </template>

            <template #body="{ data }">
              <div class="p-1">
                <RadioButton
                    v-model="data.groupeAffecte"
                    :value="g"
                    :name="`groupe-${data.id}`"
                    :inputId="`groupe-${data.id}-${g.id}`"
                    @change="assignGroupe(data.id, g.id)"
                />
              </div>
            </template>
          </Column>
          <!--         colonne pour aucun groupe -->
          <Column>
            <template #header>
              <div class="flex items-center gap-2">
                <input
                    type="checkbox"
                    :ref="el => registerHeaderCheckbox(el, { id: 'none' })"
                    @change="toggleSelectAllNone($event)"
                    :aria-label="`Tout désélectionner`"
                />
                <span>Sans grp.</span>
              </div>
            </template>
            <template #body="{ data }">
              <div class="p-1">
                <RadioButton
                    v-model="data.groupeAffecte"
                    :value="null"
                    :name="`groupe-${data.id}`"
                    :inputId="`groupe-${data.id}-none`"
                    @change="assignGroupe(data.id, null)"
                />
              </div>
            </template>
          </Column>
          <template #footer> {{ nbEtudiants }} résultat(s).</template>
        </DataTable>

        <div v-else-if="!isLoadingGroupes" class="flex items-center justify-center gap-2">
          <Message severity="warn" class="w-fit" icon="pi pi-exclamation-triangle">
            Aucun groupe pour le semestre ou le type sélectionné.
          </Message>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>
