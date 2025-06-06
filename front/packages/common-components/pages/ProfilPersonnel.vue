<script setup>
import { ref, onMounted } from 'vue';
import { useUsersStore } from "@stores";
import {ArticleSkeleton} from "@components";

const userStore = useUsersStore();
const statuts = ref([]);
const user = ref(null);
const isEditMode = ref(false);
const selectedStatut = ref(null);
const OTHER_STATUT_LABEL = 'Autre';

const getStatuts = async () => {
  await userStore.getStatuts();
  if (userStore.statuts) {
    statuts.value = Object.entries(userStore.statuts).map(([key, value]) => ({ label: value, value: key }));
    selectedStatut.value = statuts.value.find(statut => statut.value === userStore.user?.statut);
  } else {
    console.warn('Aucun statut trouvé pour l’utilisateur.');
  }
};

const toggleEditMode = () => {
  isEditMode.value = !isEditMode.value;
};

const saveChanges = async () => {
  try {
    userStore.user.statut = selectedStatut.value?.value === OTHER_STATUT_LABEL ? null : selectedStatut.value?.value;
    await userStore.updateUser(userStore.user);
  } catch (err) {
    console.error('Error saving changes:', err);
  } finally {
    toggleEditMode();
  }
};

const redirectTo = (url) => {
  if (url) window.open(url, '_blank');
};

// Mounted hook
onMounted(getStatuts);
</script>

<template>
  <Card class="p-6">
    <template #header>
      <Fieldset class="w-full h-full relative">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-user bg-primary-400 bg-opacity-20 rounded-full p-4 text-primary"/>
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">Mon profil</span>
            </div>
          </div>
          <Button label="Modifier" icon="pi pi-pencil" class="!absolute top-3 right-4" severity="secondary" @click="toggleEditMode" />
        </template>
        <div v-if="userStore.isLoading || !userStore.isLoaded">
          <ArticleSkeleton/>
        </div>
        <div v-else class="mt-4 gap-4 flex flex-col md:flex-row items-center">
          <div class="w-full md:w-1/6 flex justify-center">
            <img :src="userStore.userPhoto" alt="photo de profil" class="rounded-full w-24 h-24 md:w-auto md:h-auto">
          </div>
          <div class="w-full md:w-3/6 flex flex-col gap-4">
            <div v-if="!isEditMode" class="flex flex-col gap-4">
              <div class="flex flex-col gap-2">
                <div class="flex flex-col md:flex-row gap-2">
                  <div class="title text text-2xl font-bold">
                    {{ userStore.user.prenom }} {{ userStore.user.nom }}
                  </div>
                  <Tag v-if="userStore.user.statut" :value="userStore.user.displayStatut" severity="info" rounded/>
                  <Tag v-for="domaine in userStore.user.domaines" v-if="userStore.user.domaines && userStore.user.domaines.length > 0" :value="domaine" severity="secondary" rounded class="capitalize"/>
                </div>
                <div v-if="userStore.user.responsabilites" class="text-lg border-b w-fit pr-6 pb-1">{{userStore.user.responsabilites}}</div>
                <div class="text-sm opacity-80 pt-1 flex flex-row w-full flex-wrap gap-2">
                  <span v-if="userStore.user.numeroHarpege">Numéro Harpege : {{userStore.user.numeroHarpege}} </span>
                  <span v-if="userStore.user.mailUniv">•</span>
                  <span v-if="userStore.user.mailUniv">{{userStore.user.mailUniv}} </span>
                  <template v-if="isEditMode">
                    <InputText v-model="userStore.user.mailUniv" placeholder="Mail Universitaire" />
                  </template>
                  <span v-if="userStore.user.telBureau || userStore.user.posteInterne">•</span>
                  <div>
                    <span v-if="userStore.user.telBureau">{{userStore.user.telBureau}} </span>
                    <span v-if="userStore.user.posteInterne"> ({{userStore.user.posteInterne}})</span>
                  </div>
                  <span v-if="userStore.user.bureau">•</span>
                  <span v-if="userStore.user.bureau">Bureau  : {{userStore.user.bureau}}</span>
                </div>
              </div>
              <div class="flex flex-col md:flex-row gap-2">
                <Button label="Contacter" icon="pi pi-envelope" severity="contrast"/>
                <Button v-if="userStore.user.sitePerso" label="Site Personnel" icon="pi pi-external-link" iconPos="right" severity="contrast" @click="redirectTo(userStore.user.sitePerso)"/>
                <Button v-if="userStore.user.siteUniv" label="Site Universitaire" icon="pi pi-external-link" iconPos="right" severity="contrast" @click="redirectTo(userStore.user.siteUniv)"/>
              </div>
            </div>
            <div class="flex flex-col gap-4" v-else>
              <div class="flex gap-2">
                <IftaLabel>
                  <InputText v-model="userStore.user.prenom" placeholder="Prénom" />
                  <label for="prenom">Prénom</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="userStore.user.nom" placeholder="Nom" />
                  <label for="nom">Nom</label>
                </IftaLabel>
              </div>
              <div class="flex gap-2">
                <IftaLabel>
                  <Select v-model="selectedStatut" :options="statuts" optionLabel="label" class="w-full md:w-56" />
                  <label for="statut">Statut</label>
                </IftaLabel>
                <IftaLabel class="w-full">
                  <InputText class="w-full" v-model="userStore.user.domaines" placeholder="Domaines" />
                  <label for="domaines">Domaines</label>
                  <div class="text-muted-color text-sm">Les domaines sont séparés par des virgules</div>
                </IftaLabel>
              </div>
              <div>
                <IftaLabel>
                  <InputText class="w-full" v-model="userStore.user.responsabilites" placeholder="Responsabilités" />
                  <label for="responsabilites">Responsabilités</label>
                  <div class="text-muted-color text-sm">Les responsabilités sont séparées par des virgules</div>
                </IftaLabel>
              </div>
              <div class="flex gap-2 flex-wrap">
                <IftaLabel>
                  <InputText v-model="userStore.user.numeroHarpege" placeholder="Numéro Harpege" />
                  <label for="numeroHarpege">Numéro Harpege</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="userStore.user.mailUniv" placeholder="Mail Universitaire" />
                  <label for="mailUniv">Mail Universitaire</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="userStore.user.telBureau" placeholder="Téléphone Bureau" />
                  <label for="telBureau">Téléphone Bureau</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="userStore.user.posteInterne" placeholder="Poste Interne" />
                  <label for="posteInterne">Poste Interne</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="userStore.user.bureau" placeholder="Bureau" />
                  <label for="bureau">Bureau</label>
                </IftaLabel>
              </div>
              <div class="flex gap-2">
                <IftaLabel>
                  <InputText v-model="userStore.user.sitePerso" placeholder="Site Personnel" />
                  <label for="sitePerso">Site Personnel</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="userStore.user.siteUniv" placeholder="Site Universitaire" />
                  <label for="siteUniv">Site Universitaire</label>
                </IftaLabel>
              </div>
              <Button label="Enregistrer les modifications" icon="pi pi-check" class="mt-4" @click="saveChanges" />
            </div>
          </div>
          <div class="w-full md:w-2/6 flex flex-col">
            <div class="flex flex-col gap-2">
              <div class="text-lg font-bold">Départements</div>
              <div class="flex flex-col gap-2 max-h-40 overflow-y-scroll">
                <Tag :value="userStore.departementDefaut.libelle" severity="info" rounded/>
                <Tag v-for="(departement, index) in userStore.departementsNotDefaut" :key="index" :value="departement.libelle" severity="primary" rounded/>
              </div>
            </div>
          </div>
        </div>
      </Fieldset>
    </template>
  </Card>
</template>

<style scoped>
/* Add any necessary styles here */
</style>
