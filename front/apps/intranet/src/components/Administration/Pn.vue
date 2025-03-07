<script setup>
              import { onMounted, ref } from 'vue'
              import { useSemestreStore } from '@stores'
              import { useUsersStore, useDiplomeStore } from "@stores";

              const usersStore = useUsersStore();
              const diplomeStore = useDiplomeStore();
              const departementId = usersStore.departementDefaut.id;

              const diplomes = ref([])
              const selectedDiplome = ref(null)
              const selectedPn = ref(null)

              const getDiplomes = async (departementId) => {
                await diplomeStore.getDiplomesActifsDepartement(departementId);
                diplomes.value = diplomeStore.diplomes;
              }

              onMounted(async () => {
                await getDiplomes(departementId)

                if (selectedDiplome.value === null) {
                  selectedDiplome.value = diplomes.value[0]
                }

                if (selectedDiplome.value) {
                  selectedPn.value = selectedDiplome.value.structurePns.find(pn => pn.structureAnneeUniversitaires.some(annee => annee.actif === true))
                  console.log(selectedDiplome.value.structurePns)
                  console.log(selectedPn.value)
                }
              })

              const changeDiplome = (diplome) => {
                selectedDiplome.value = diplome
                console.log(diplome)
                // sélectionner le PN de l'année universitaire en cours
                selectedPn.value = diplome.structurePns.find(pn => pn.structureAnneeUniversitaires.some(annee => annee.actif === true))
              }

              </script>

              <template>
                <div class="card">
                  <Tabs :value="selectedDiplome ? selectedDiplome.id : diplomes[0]?.id" scrollable>
                    <TabList>
                      <Tab v-for="diplome in diplomes" :key="diplome.libelle" :value="diplome.id" @click="changeDiplome(diplome)">
                        <span>{{ diplome.sigle }}</span>
                      </Tab>
                    </TabList>
                  </Tabs>

                  <div class="flex justify-between gap-10 mt-6">
                    <Select v-if="selectedDiplome" v-model="selectedPn"
                            :options="selectedDiplome.structurePns"
                            optionLabel="libelle"
                            placeholder="Selectionner un PN"
                            class="w-full md:w-56"/>

                    <Button label="Créer un nouveau pn" icon="pi pi-plus" />
                  </div>
                </div>
              </template>

              <style scoped>
              </style>
