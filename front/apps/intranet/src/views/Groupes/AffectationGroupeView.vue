<script setup>
import {computed, onMounted, ref, watch, nextTick} from 'vue'
import {ErrorView, SimpleSkeleton} from "@components/index.js";
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
    console.log(semestre.value);
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération du semestre :", error);
  } finally {
    isLoadingSemestre.value = false;
    console.log(semestre.value);
  }
};

const getSemestres = async () => {
  isLoadingSemestres.value = true;
  hasError.value = false;
  console.log(semestre.value)
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
    };
    etudiantsScolariteSemestre.value = await getEtudiantScolariteSemestresService(params, '/manage-groupes');

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

const registerHeaderCheckbox = (el, group) => {
  if (!el) {
    headerCheckboxRefs.delete(normalizeId(group.id));
    return;
  }
  headerCheckboxRefs.set(normalizeId(group.id), el);
  nextTick(() => {
    el.indeterminate = isSomeSelected(group);
    el.checked = isAllSelected(group);
  });
};

// Mettre à jour l'état des cases d'entête quand les étudiants changent
watch(etudiantsScolariteSemestre, () => {
  headerCheckboxRefs.forEach((el, gid) => {
    // retrouver l'objet groupe correspondant (si possible) pour recalculer les états
    const allGroupes = Object.values(groupes.value).flat();
    const group = allGroupes.find(g => normalizeId(g.id) === normalizeId(gid));
    if (group && el) {
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
</script>

<template>
  <div class="card min-h-full">
    <div class="flex justify-between items-end w-full">
      <div>
        <h2 class="text-2xl font-bold flex items-end gap-2">Composition des groupes du <SimpleSkeleton v-if="isLoadingSemestre" class="!w-32"></SimpleSkeleton><span v-else>{{semestre.libelle}}</span></h2>
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
      <Message severity="info" class="mb-4" icon="pi pi-info-circle">
        Vous pouvez ne remplir que le groupe de plus bas niveau (TP) et synchroniser pour remplir automatiquement les groupes parents. Si les groupes sont saisis dans Apogée, vous pouvez aussi les synchroniser (il faut attendre 24h entre la saisie dans Apogée et la possibilité de synchroniser).

      </Message>
      <Loader v-if="isLoadingGroupes" class="my-12"></Loader>
      <div v-else class="flex flex-col gap-4">
        <Tabs :value="selectedGroupe" scrollable>
          <TabList>
            <Tab v-for="type in typesList" :key="type" :value="type" @click="selectedGroupe = type">
              <span>{{ type }}</span>
            </Tab>
          </TabList>
        </Tabs>

        <DataTable v-if="selectedGroupe && groupes[selectedGroupe]" :value="etudiantsScolariteSemestre" responsiveLayout="scroll" class="w-full">
          <Column field="etudiant.numEtudiant" header="N° étudiant" />
          <Column field="etudiant.nom" header="Nom" />
          <Column field="etudiant.prenom" header="Prénom" />
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
