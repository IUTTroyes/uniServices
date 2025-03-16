<script setup>
      import { ref, onMounted, computed, watch } from 'vue'
      import { FilterMatchMode } from '@primevue/core/api'
      import api from '@helpers/axios.js'
      import ButtonInfo from '@components/components/ButtonInfo.vue'
      import ButtonEdit from '@components/components/ButtonEdit.vue'
      import ButtonDelete from '@components/components/ButtonDelete.vue'

      import ViewEtudiantDialog from '@/dialogs/etudiants/ViewEtudiantDialog.vue'
      import EditEtudiantDialog from '@/dialogs/etudiants/EditEtudiantDialog.vue'
      import AccessEtudiantDialog from '@/dialogs/etudiants/AccessEtudiantDialog.vue'

      import { getEtudiantsDepartementService } from "@requests";

      import {useAnneeUnivStore, useUsersStore} from "@stores";
      import {SimpleSkeleton} from "@components";
      const usersStore = useUsersStore();
      const anneeUnivStore = useAnneeUnivStore();

      const departementId = ref(null);
      const anneesUnivList = ref([]);
      const selectedAnneeUniv = ref(null);

      const isLoadingAnneesUniv = ref(false);

      const etudiants = ref([])
      const nbEtudiants = ref(0)
      const loading = ref(true)
      const page = ref(0)
      const rowOptions = [30, 60, 120]

      const limit = ref(rowOptions[0])
      const offset = computed(() => limit.value * page.value)

      const filters = ref({
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        nom: { value: null, matchMode: FilterMatchMode.CONTAINS },
        prenom: { value: null, matchMode: FilterMatchMode.CONTAINS },
        mailUniv: { value: null, matchMode: FilterMatchMode.EQUALS },
      })

      const showViewDialog = ref(false)
      const showEditDialog = ref(false)
      const showAccessEditDialog = ref(false)
      const selectedEtudiant = ref(null)

      const getAnneesUniv = async () => {
        isLoadingAnneesUniv.value = true;
        await anneeUnivStore.getAllAnneesUniv();
        anneesUnivList.value = anneeUnivStore.anneesUniv.sort((a, b) => b.id - a.id);
        await anneeUnivStore.getCurrentAnneeUniv();
        selectedAnneeUniv.value = anneeUnivStore.anneeUniv;
        isLoadingAnneesUniv.value = false;
      };

      const loadEtudiants = async () => {
        loading.value = true
        const response = await getEtudiantsDepartementService(departementId.value, selectedAnneeUniv.value.id, limit.value, parseInt(page.value) + 1)
        etudiants.value = response.member
        nbEtudiants.value = response.totalItems
        loading.value = false

        // récupérer l'etudiantScolarite parmi les etudiantScolarites de l'etudiant celle dont l'anneeUniv est celle selectionnée
        // l'ajouter à l'etudiant pour l'afficher dans le tableau
        etudiants.value.forEach(etudiant => {
          etudiant.etudiantScolarite = etudiant.etudiantScolarites.find(es => es.structureAnneeUniversitaire.id === selectedAnneeUniv.value.id)
        })
        // ajouter tous les etudiantScolarite.scolarite_semestre à l'etudiant pour l'afficher dans le tableau
        etudiants.value.forEach(etudiant => {
          etudiant.semestres = etudiant.etudiantScolarite.scolarite_semestre.map(es => es.structure_semestre)
        })

        console.log(etudiants.value)
      }

      onMounted(async () => {
        if (usersStore.departementDefaut) {
          departementId.value = usersStore.departementDefaut.id;
          await getAnneesUniv();
          await loadEtudiants();
        } else {
          console.error('departementDefaut is not defined');
        }
      })

      const onPageChange = async (event) => {
        page.value = event.page
        limit.value = event.rows
        await loadEtudiants();
      }

      const viewEtudiant = (etudiant) => {
        selectedEtudiant.value = etudiant
        showViewDialog.value = true
      }

      const editEtudiant = (etudiant) => {
        selectedEtudiant.value = etudiant
        showEditDialog.value = true
      }

      const deleteEtudiant = (etudiant) => {
        console.log(etudiant)
      }

      const editAccessEtudiant = (etudiant) => {
        selectedEtudiant.value = etudiant
        showAccessEditDialog.value = true
      }

      //wtach filters
      watch([filters, selectedAnneeUniv], async () => {
        await loadEtudiants();
      })

      </script>

      <template>
        <div class="card">
          <h2 class="text-2xl font-bold mb-4">Tous les étudiants du département</h2>

          <DataTable v-model:filters="filters" :value="etudiants"
                     lazy
                     stripedRows
                     paginator
                     :first="offset"
                     :rows="limit"
                     :rowsPerPageOptions="rowOptions"
                     :totalRecords="nbEtudiants"
                     dataKey="id" filterDisplay="row" :loading="loading"
                     @page="onPageChange($event)"
                     @update:rows="limit = $event"
                     :globalFilterFields="['nom', 'prenom']">
            <template #header>
              <div class="flex justify-between items-end">
                <SimpleSkeleton v-if="isLoadingAnneesUniv" class="w-1/3" />
                <IftaLabel v-else class="w-1/3">
                  <Select
                      v-model="selectedAnneeUniv"
                      :options="anneesUnivList"
                      optionLabel="libelle"
                      placeholder="Sélectionner une année universitaire"
                      class="w-full"
                  />
                  <label for="anneeUniversitaire">Année universitaire</label>
                </IftaLabel>

                <IconField class="h-full">
                  <InputIcon>
                    <i class="pi pi-search"/>
                  </InputIcon>
                  <InputText v-model="filters['global'].value" placeholder="Keyword Search"/>
                </IconField>
              </div>
            </template>
            <template #empty> No customers found.</template>
            <template #loading> Loading customers data. Please wait.</template>
            <Column field="nom" :showFilterMenu="false" header="Nom" style="min-width: 12rem">
              <template #body="{ data }">
                {{ data.nom }}
              </template>
              <template #filter="{ filterModel, filterCallback }">
                <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par nom"/>
              </template>
            </Column>
            <Column field="prenom" :showFilterMenu="false" header="Prénom" style="min-width: 12rem">
              <template #body="{ data }">
                {{ data.prenom }}
              </template>
              <template #filter="{ filterModel, filterCallback }">
                <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par prénom"/>
              </template>
            </Column>
            <Column field="semestres" header="semestres" style="min-width: 12rem">
              <template #body="{ data }">
                <div v-for="semestre in data.semestres">{{semestre.libelle}}</div>
              </template>
            </Column>
            <Column field="mailUniv" :showFilterMenu="false" header="Email" style="min-width: 12rem">
              <template #body="{ data }">
                {{ data.mailUniv }}
              </template>
              <template #filter="{ filterModel, filterCallback }">
                <InputText v-model="filterModel.value" type="text" @input="filterCallback()" placeholder="Filtrer par email"/>
              </template>
            </Column>
            <Column :showFilterMenu="false" style="min-width: 12rem">
              <template #body="slotProps">
                <ButtonInfo tooltip="Voir les détails" @click="viewEtudiant(slotProps.data)"/>
                <Button icon="pi pi-key"
                        v-tooltip.bottom="'Gérer les droits'"
                        outlined severity="warn" rounded class="mr-2" @click="editAccessEtudiant(slotProps.data)"/>
                <ButtonEdit
                    tooltip="Modifier le personnel"
                    @click="editEtudiant(slotProps.data)"/>
                <ButtonDelete
                    tooltip="Supprimer le personnel du département"
                    @confirm-delete="deleteEtudiant(slotProps.data)"/>
              </template>
            </Column>
            <template #footer> {{ nbEtudiants }} résultat(s).</template>

          </DataTable>

          <ViewEtudiantDialog
              :isVisible="showViewDialog"
              :personnel="selectedEtudiant"
              @update:visible="showViewDialog = $event"/>
          <EditEtudiantDialog
              :isVisible="showEditDialog"
              :personnel="selectedEtudiant"
              @update:visible="showEditDialog = $event"/>
          <AccessEtudiantDialog
              :isVisible="showAccessEditDialog"
              :personnel="selectedEtudiant"
              @update:visible="showAccessEditDialog = $event"/>
        </div>
      </template>

      <style scoped>

      </style>
