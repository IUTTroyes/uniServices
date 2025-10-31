<script setup>
import { ref, onMounted } from "vue";
import { getEtudiantScolaritesService, getEtudiantScolariteSemestresService, updateEtudiantScolariteSemestreGroupes } from "@requests/index.js";
import { updateEtudiantService, getUeService, getGroupesService } from "@requests/index.js";
import { ValidatedInput, ErrorView } from "@components/index.js";
import { useUsersStore } from "@stores/index.js";
import { SimpleSkeleton } from "@components/index.js";

const props = defineProps({
  isVisible: Boolean,
  etudiantId: Number,
});

const isLoadingScolarites = ref(true);
const etudiantScolarites = ref([]);
const currentScolarite = ref(null);
const activeTab = ref(null);
const typesGroupe = ref([]);
const ueLabels = ref({});

const hasError = ref(false);

onMounted(async () => {
  await getEtudiantScolarites();
});


const getEtudiantScolarites = async () => {
  isLoadingScolarites.value = true;
  try {
    const params = {
      etudiant: props.etudiantId,
    };
    etudiantScolarites.value = await getEtudiantScolaritesService(params);

    // Trier et définir l'onglet actif
    etudiantScolarites.value.sort((a, b) => b.anneeUniversitaire.annee - a.anneeUniversitaire.annee);
    activeTab.value = etudiantScolarites.value[0]?.anneeUniversitaire.libelle || null;

    for (const scolarite of etudiantScolarites.value) {
      try {
        const semestres = await getEtudiantScolariteSemestresService({ scolarite: scolarite.id });
        scolarite.scolariteSemestre = Array.isArray(semestres) ? semestres : (semestres?.data ?? []);
      } catch {
        scolarite.scolariteSemestre = [];
      }

      for (const scolariteSemestre of scolarite.scolariteSemestre) {
        try {
          const groupes = await getGroupesService({ semestre: scolariteSemestre.semestre.id }, '/mini');
          scolariteSemestre.semestre.groupes = Array.isArray(groupes) ? groupes : (groupes?.data ?? []);
        } catch {
          scolariteSemestre.semestre.groupes = [];
        }

        // Organiser les groupes par type
        typesGroupe.value = scolariteSemestre.semestre.typesGroupe ?? [];
        typesGroupe.value.forEach(type => {
          const groupesType = scolariteSemestre.semestre.groupes.filter(g => g.type === type);
          scolariteSemestre.semestre[`groupes${type}`] = groupesType.length > 0 ? groupesType : [];
        });
      }
    }
  } catch (error) {
    hasError.value = true;
    console.error("Erreur lors de la récupération :", error);
  } finally {
    isLoadingScolarites.value = false;
    currentScolarite.value = etudiantScolarites.value.find(sco => sco.actif) || null;
  }
};

const getUe = (ueId) => {
  if (ueLabels.value[ueId]) {
    return ueLabels.value[ueId];
  }

  ueLabels.value[ueId] = "Chargement...";

  getUeService(ueId)
      .then(ue => {
        ueLabels.value[ueId] = ue.numero;
      })
      .catch(error => {
        console.error("Erreur lors de la récupération de l'UE:", error);
        ueLabels.value[ueId] = "UE non trouvée";
      });

  return ueLabels.value[ueId];
}

const handleGroupSelection = async (scolariteSemestre, groupId, groupType) => {
  try {
    // retrouver le type de groupe sélectionné
    const group = scolariteSemestre.semestre.groupes.find(g => g.id === groupId);
    const groupType = group ? group.type : null;
    await updateEtudiantScolariteSemestreGroupes(scolariteSemestre.id, { id: groupId, type: groupType }, true);
  } catch (error) {
    console.error("Erreur lors de la sélection du groupe :", error);
  }
};
</script>

<template>
  <ErrorView v-if="hasError" message="Une erreur est survenue lors du chargement des données."></ErrorView>
  <SimpleSkeleton v-else-if="isLoadingScolarites" />
  <div v-else>
    <div class="md:px-12 px-4">
      <div class="flex justify-between gap-4 w-full items-center">
        <div>
          <h2 class="text-2xl font-bold">Scolarité</h2>
          <p class="text-sm text-muted-color">Années universitaires dans lesquelles l'étudiant est ou a été inscrit</p>
        </div>

        <div>
          <Button v-if="currentScolarite" label="Plus d'infos sur la scolarité de l'étudiant" />
          <p class="text-sm text-muted-color">Bilans, notes, absences ...</p>
        </div>

      </div>
      <Tabs v-if="etudiantScolarites.length > 0" :value="activeTab">
        <TabList>
          <Tab v-for="scolarite in etudiantScolarites"
               :key="scolarite.anneeUniversitaire.libelle"
               :value="scolarite.anneeUniversitaire.libelle">
            {{ scolarite.anneeUniversitaire.libelle }}
          </Tab>
        </TabList>
        <TabPanels>
          <TabPanel v-for="scolarite in etudiantScolarites"
                    :key="scolarite.anneeUniversitaire.libelle"
                    :value="scolarite.anneeUniversitaire.libelle">
            <div class="flex md:flex-row flex-col justify-between gap-2 w-full h-full">
              <div v-for="scoSemestre in scolarite.scolariteSemestre"
                   class="card mb-0 w-full md:w-1/2">
                <div class="font-bold text-lg">
                  {{ scoSemestre.semestre.annee.libelle }} |
                  <span class="text-muted-color font-normal">{{ scoSemestre.semestre.libelle }}</span>
                </div>
                <Divider/>
                <div class="text-lg font-medium opacity-70 mb-4">Groupes</div>
                <div class="flex md:flex-row flex-col gap-2 w-full">
                  <div v-for="type in typesGroupe" :key="type" class="flex-1 min-w-0">
                    <div class="border p-2 rounded-md flex flex-col gap-2 h-full">
                      <Tag :severity="type === 'CM' ? 'info' : type === 'TD' ? 'warning' : type === 'TP' ? 'success' : 'info'">
                        {{ type }}
                      </Tag>
                      <ValidatedInput
                          type="select"
                          v-model="scoSemestre.semestre['selectedGroup' + type]"
                          :name="'groupe' + type"
                          label=""
                          :options="scoSemestre.semestre['groupes' + type]?.map(groupe => ({ label: groupe.libelle, value: groupe.id })) ?? []"
                          :modelValue="scoSemestre.groupes.find(g => g.type === type)?.id || scoSemestre.semestre['selectedGroup' + type]"
                          :placeholder="scoSemestre.groupes?.find(g => g.type === type)?.libelle ?? (`Sélectionner un groupe ${type}`)"
                          @validation="result => handleValidation('groupe' + type, result)"
                          @update:modelValue="groupId => handleGroupSelection(scoSemestre, groupId, type)"
                          class="!m-0 w-full"
                      />
                    </div>
                  </div>
                </div>
                <Divider></Divider>
                <div class="text-lg font-medium opacity-70 mb-4">Bilan du semestre</div>
                <div class="flex">
                  <table class="table-auto w-full">
                    <thead>
                    <tr>
                      <th class="text-left px-2 py-1">UE</th>
                      <th class="text-left px-2 py-1">Moyenne</th>
                      <th class="text-left px-2 py-1">Décision</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(moyenneUe, key) in scoSemestre.moyennesUe" :key="key">
                      <td class="px-2 py-1">UE {{ getUe(key) }}</td>
                      <td class="px-2 py-1">{{ moyenneUe.moyenne || 'Non renseigné' }}</td>
                      <td class="px-2 py-1">
                        <span v-if="moyenneUe.decision === 'V'"><Tag severity="success">V</Tag></span>
                        <span v-else-if="moyenneUe.decision === 'NV'"><Tag severity="danger">NV</Tag></span>
                        <span v-else><Tag severity="warning">En attente</Tag></span>
                      </td>
                    </tr>
                    </tbody>
                  </table>
                  <table class="table-auto w-full">
                    <thead>
                    <tr>
                      <th class="text-left px-2 py-1">Décision semestre</th>
                      <th class="text-left px-2 py-1">Proposition</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                      <td class="px-2 py-1">
                        <span v-if="scoSemestre.decision === true"><Tag severity="success">V</Tag></span>
                        <span v-else-if="scoSemestre.decision === false"><Tag severity="danger">NV</Tag></span>
                        <span v-else><Tag severity="warning">En attente</Tag></span>
                      </td>
                      <td class="px-2 py-1">{{ scoSemestre.proposition?.libelle || 'Non renseigné' }}</td>
                    </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>
  </div>

</template>

<style scoped>
</style>
