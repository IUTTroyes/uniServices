<script setup>
import {computed, onMounted, ref} from "vue";
import {useRoute, useRouter} from "vue-router";
import {Access, ErrorView, ListSkeleton, PermissionGuard} from "@components";
import {getAnneeUniversitaireService, getPns, deletePnService, deleteDiplomeFromAnneeUnivService} from "@requests";
import ButtonInfo from "@components/components/Buttons/ButtonInfo.vue";
import ButtonDelete from "@components/components/Buttons/ButtonDelete.vue";

const route = useRoute();
const router = useRouter();

const hasError = ref(false);
const isLoading = ref(true);

const anneeUnivId = computed(() => route.params.id);
const anneeUniv = ref(null);
const pns = ref([]);
const activeTabIndex = ref(0);

const page = ref(0);
const rowOptions = [5, 10, 20];
const offset = computed(() => limit.value * page.value);
const limit = ref(rowOptions[0]);

onMounted(async () => {
  await loadAnneeUniv();
  await getPnsAnneeUniv();
});

const getPnsAnneeUniv = async () => {
  try {
    isLoading.value = true;
    const params = {
      anneeUniversitaire: anneeUnivId.value,
    };
    pns.value = await getPns(params);
  } catch (error) {
    console.error("Erreur lors de la récupération des PNs:", error);
    hasError.value = true;
  } finally {
    isLoading.value = false;
  }
};

// Grouper les diplômes par département
const diplomesByDepartement = computed(() => {
  const grouped = {};
  pns.value.forEach(pn => {
    const diplome = pn.diplome;
    const deptName = diplome?.departement?.libelle || 'Sans département';
    const deptId = diplome?.departement?.id || 'none';
    if (!grouped[deptId]) {
      grouped[deptId] = {
        id: deptId,
        libelle: deptName,
        pns: []
      };
    }
    grouped[deptId].pns.push(pn);
  });
  // Trier les départements par nom
  return Object.values(grouped).sort((a, b) => a.libelle.localeCompare(b.libelle));
});

const loadAnneeUniv = async () => {
  try {
    isLoading.value = true;
    anneeUniv.value = await getAnneeUniversitaireService(anneeUnivId.value);
  } catch (error) {
    console.error("Erreur lors de la récupération de l'année universitaire:", error);
    hasError.value = true;
  } finally {
    isLoading.value = false;
  }
};

const viewDiplome = (diplome) => {
  router.push({ path: `/administration/diplome/${diplome.id}` });
};

const goBack = () => {
  router.push('/super-administration/annees-universitaires');
};

const deletePn = async (pnId) => {
  try {
    await deletePnService(pnId);
    await getPns();
  } catch (error) {
    console.error("Erreur lors de la suppression du PN:", error);
    hasError.value = true;
  }
}

const editAnneeUniv = (anneeUniv) => {
  // Redirection vers la page d'édition de l'année universitaire
  router.push({ path: `/super-administration/annee-universitaire/${anneeUniv.id}/edit` });
}
</script>

<template>
  <div class="card">
    <div class="card-title mb-8">
      <div class="flex items-center gap-4 justify-between">
        <div class="flex items-center gap-4">
          <Button icon="pi pi-arrow-left" severity="secondary" text rounded @click="goBack" v-tooltip.top="'Retour'" />
          <div>
            <h1 class="text-2xl font-bold">Diplômes de l'Année Universitaire</h1>
            <p class="text-muted-color" v-if="anneeUniv">
              Année universitaire <strong>{{ anneeUniv.libelle }}</strong>
            </p>
          </div>
        </div>
        <Button severity="primary" @click="editAnneeUniv(anneeUniv)">
        Ajouter des diplômes
        </Button>
      </div>
    </div>

    <ErrorView v-if="hasError"/>
    <ListSkeleton v-else-if="isLoading" :count="5" />
    <template v-else>
      <PermissionGuard permission="isSuperAdmin" :showFallback="true">
        <div class="mb-4 flex items-center gap-4">
          <Tag :severity="anneeUniv.actif ? 'success' : 'danger'" :value="anneeUniv.actif ? 'Active' : 'Inactive'" />
          <span class="text-muted-color">Année : {{ anneeUniv.annee }}</span>
        </div>

        <Message v-if="pns.length === 0" severity="info" class="mb-4">
          Aucun diplôme n'est associé à cette année universitaire.
        </Message>

        <template v-else>
          <Tabs v-model:value="activeTabIndex">
            <TabList>
              <Tab v-for="(dept, index) in diplomesByDepartement" :key="dept.id" :value="index">
                {{ dept.libelle }}
                <Badge :value="dept.pns.length" severity="secondary" class="ml-2" />
              </Tab>
            </TabList>
            <TabPanels>
              <TabPanel v-for="(dept, index) in diplomesByDepartement" :key="dept.id" :value="index">
                <DataTable :value="dept.pns" striped-rows>
                  <Column field="diplome.libelle" header="Libellé">
                    <template #body="slotProps">
                      <span class="font-semibold">{{ slotProps.data.diplome?.libelle }}</span>
                    </template>
                  </Column>
                  <Column field="diplome.typeDiplome" header="Type">
                    <template #body="slotProps">
                      <Tag v-if="slotProps.data.diplome?.typeDiplome" :value="slotProps.data.diplome.typeDiplome.sigle" severity="info" />
                      <span v-else>-</span>
                    </template>
                  </Column>
                  <Column header="Actions">
                    <template #body="slotProps">
                      <ButtonInfo tooltip="Voir le diplôme" icon="pi pi-eye" @click="viewDiplome(slotProps.data.diplome)" />
                      <ButtonDelete tooltip="Supprimer l'association" icon="pi pi-trash" class="ml-2" @confirm-delete="deletePn(slotProps.data.id)" />
                    </template>
                  </Column>
                </DataTable>
              </TabPanel>
            </TabPanels>
          </Tabs>
        </template>

        <template #fallback>
          <Access></Access>
        </template>
      </PermissionGuard>
    </template>
  </div>
</template>

<style scoped></style>

