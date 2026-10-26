<script setup>
import { onMounted, ref, watch } from "vue";
import { getGroupesService, getSemestresService, updateGroupeService } from "@requests";
import { SimpleSkeleton, GlobalLoader, PermissionGuard, ButtonDelete, ButtonEdit, ButtonInfo, HeaderComponent } from "@components";
import { useUsersStore, useSemestreStore, useAnneeStore } from "@stores";
import {typesGroupes} from "@config/uniServices.js";
import {useRoute, useRouter} from "vue-router";

const route = useRoute();
const router = useRouter();
const userStore = useUsersStore();
const semestreStore = useSemestreStore();
const anneeStore = useAnneeStore();
const semestres = ref([]);
const annees = ref([]);
const annee = ref({});
const groupes = ref({});
const hasError = ref(false);
const isLoadingAnnees = ref(true);
const isLoadingAnnee = ref(true);
const isLoadingSemestres = ref(true);
const selectedGroupe = ref(null);
const isLoadingGroupes = ref(true);
const departementId = userStore.departementDefaut.id;

onMounted(async () => {
  await getAnnees();
  await getAnnee();
  await getSemestres();
});

// watcher pour relancer getSemestres quand annee change
watch(annee, async (newAnnee, oldAnnee) => {
  if (newAnnee.id !== oldAnnee.id) {
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
    await anneeStore.setSelectedAnnee(newAnnee)
  }
});


watch(groupes, (newVal) => {
  const types = Object.keys(newVal);
  if (types.length > 0 && !selectedGroupe.value) {
    selectedGroupe.value = types[0];
  }
});

const getAnnees = async () => {
  isLoadingAnnees.value = true;
  try {
    if (anneeStore.annees && Array.isArray(anneeStore.annees) && anneeStore.annees.length > 0) {
      annees.value = anneeStore.annees;
      return;
    }

    const params = {
      departement: departementId,
      actif: true,
    };
    await anneeStore.getAnneesDepartement(params);
    annees.value = Array.isArray(anneeStore.annees) ? anneeStore.annees : [];
  } catch (error) {
    console.error("Erreur lors de la récupération des années :", error);
    hasError.value = true;
  } finally {
    isLoadingAnnees.value = false;
  }
};

const getAnnee = async () => {
  isLoadingAnnee.value = true;
  hasError.value = false;
  // Récupération de l'id de l'année via l'URL
  try {
    const anneeId = Number.parseInt(String(route.params.anneeId), 10);
    if (!Number.isNaN(anneeId)) {
      annee.value = annees.value.find((item) => item.id === anneeId) || annees.value[0] || {};
    } else {
      annee.value = annees.value[0] || {};
    }
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
    await getGroupes();
    isLoadingSemestres.value = false;
  }
};

const getGroupes = async () => {
  isLoadingGroupes.value = true;
  hasError.value = false;
  try {
    const groupesParSemestre = {};

    for (const semestre of semestres.value) {
      const params = { semestre: semestre.id };
      const rawGroupes = await getGroupesService(params, '/structure');

      const groupesParType = {};
      typesGroupes.forEach(type => {
        groupesParType[type.value] = rawGroupes.filter(groupe => groupe.type === type.value);
      });

      // Supprimer les types sans groupes
      for (const type in groupesParType) {
        if (groupesParType[type].length === 0) {
          delete groupesParType[type];
        }
      }

      groupesParSemestre[semestre.id] = groupesParType;
    }

    groupes.value = groupesParSemestre;
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération des groupes :", error);
  } finally {
    isLoadingGroupes.value = false;
  }
};

const synchroApogee = async () => {
  isLoadingGroupes.value = true;
  hasError.value = false;
  try {

  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la synchronisation des groupes :", error);
  } finally {
    isLoadingGroupes.value = false;
  }
};

const deleteGroupeFromSemestre = async (groupeId, semestre) => {
  try {
    isLoadingGroupes.value = true;
    const data = {
      semestre: semestre
    }
    await updateGroupeService(groupeId, data, '/semestre');
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la modification du groupe :", error);
  } finally {
    isLoadingGroupes.value = false;
    await getGroupes();
  }
};
</script>

<template>
  <div class="card">
    <div class="flex flex-col md:flex-row justify-between items-start w-full card-header">
      <div>
        <p class="top-card-header">
          Organisation des groupes
        </p>
        <div class="flex flex-col items-start">
          <p class="uppercase text-xs font-bold mb-0! text-muted-color">
            {{annee.libelle}}
          </p>
          <h2 class="mt-0!">
            Listing des groupes importés depuis Apogée
          </h2>
        </div>
      </div>
      <SimpleSkeleton v-if="isLoadingAnnees" class="!w-60 !h-10"></SimpleSkeleton>
      <div v-else class="flex flex-col gap-2">
        <div class="flex gap-4 justify-end">
          <Select class="w-60" v-model="annee" option-label="libelle" :options="annees" placeholder="Changer d'année"/>
        </div>
        <Button
            @click="synchroApogee()"
            label="Synchronisation depuis Apogée"
            icon="pi pi-refresh"/>
      </div>
    </div>
    <div class="card-body flex items-start justify-center gap-2">
      <GlobalLoader v-if="isLoadingGroupes" class="w-full h-64" />
      <div v-else v-for="semestre in semestres" :key="semestre.id" class="p-4 w-full card">
        <div>
          <div class="uppercase text-xs font-bold mb-0! text-muted-color">semestre</div>
          <h3 class="text-xl! font-black mb-4 mt-0!">{{ semestre.libelle }}</h3>
        </div>
        <div v-for="(groupesType, type) in groupes[semestre.id]" :key="type" class="mb-4 bg-primary-300/20 rounded-md p-2">
          <h4 class="text-lg! font-bold">Type de groupe {{ type }}</h4>
          <div class="flex flex-wrap gap-4">
            <DataTable
                :value="groupesType"
                :empty-message="'Aucun groupe de type ' + type + ' pour ce semestre.'"
                striped-rows
                class="w-full">
              <Column field="ordre" header="Ordre">
                <template #body="slotProps">
                  <span class="text-muted-color">{{ slotProps.data.ordre !== null ? slotProps.data.ordre : '-' }}</span>
                </template>
              </Column>
              <Column field="libelle" header="Libellé" class="font-black"/>
              <Column field="codeApogee" header="Code Apogée" class="font-bold"/>
              <Column field="parent.libelle" header="Parent" class="text-muted-color"/>
              <Column header="Actions">
                <template #body="slotProps">
                  <ButtonInfo tooltip="Voir le groupe"/>
                  <PermissionGuard permission="isSuperAdmin">
                    <router-link :to="{ name: 'groupe-edit', params: { groupeId: slotProps.data.id } }">
                      <ButtonEdit tooltip="Éditer le groupe"/>
                    </router-link>
                    <ButtonDelete tooltip="Retirer le groupe du semestre" @confirm-delete="deleteGroupeFromSemestre(slotProps.data.id, semestre.id)"/>
                  </PermissionGuard>
                </template>
              </Column>
            </DataTable>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
