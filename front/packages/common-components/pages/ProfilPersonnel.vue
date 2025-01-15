<script setup>
import { ref, onMounted } from 'vue';
import { useUsersStore } from "@stores";

const store = useUsersStore();
const statuts = ref([]);
const isEditMode = ref(false);
const selectedStatut = ref(null);

onMounted(async () => {
  await store.getStatuts();
  statuts.value = Object.entries(store.statuts).map(([key, value]) => ({ label: value, value: key }));
  statuts.value.push({ label: 'Autre', value: 'Autre' });
  selectedStatut.value = statuts.value.find(statut => statut.value === store.user.statut);
});


const toggleEditMode = () => {
  isEditMode.value = !isEditMode.value;
};

const saveChanges = async () => {
  try {
    if (selectedStatut.value.value === 'Autre') {
      store.user.statut = selectedStatut.value.label;
    } else {
      store.user.statut = selectedStatut.value.value;
    }
    await store.updateUser(store.user);
    isEditMode.value = false;
  } catch (error) {
    console.error(error);
  }
};

const redirectTo = (link) => {
  window.open(link, '_blank');
};
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
        <div class="mt-4 gap-4 flex flex-col md:flex-row items-center">
          <div class="w-full md:w-1/6 flex justify-center">
            <img :src="store.userPhoto" alt="photo de profil" class="rounded-full w-24 h-24 md:w-auto md:h-auto">
          </div>
          <div class="w-full md:w-3/6 flex flex-col gap-4">


            <div v-if="!isEditMode" class="flex flex-col gap-4">
              <div class="flex flex-col gap-2">
                <div class="flex flex-col md:flex-row gap-2">
                  <div class="title text text-2xl font-bold">
                    {{ store.user.prenom }} {{ store.user.nom }}
                  </div>
                  <Tag v-if="store.user.statut" :value="store.user.displayStatut" severity="info" rounded/>
                  <Tag v-for="domaine in store.user.domaines" v-if="store.user.domaines && store.user.domaines.length > 0" :value="domaine" severity="secondary" rounded class="capitalize"/>
                </div>
                <div v-if="store.user.responsabilites" class="text-lg border-b w-fit pr-6 pb-1">{{store.user.responsabilites}}</div>

                <div class="text-sm opacity-80 pt-1 flex flex-row w-full flex-wrap gap-2">
                  <span v-if="store.user.numeroHarpege">Numéro Harpege : {{store.user.numeroHarpege}} </span>
                  <span v-if="store.user.mailUniv">•</span>
                  <span v-if="store.user.mailUniv">{{store.user.mailUniv}} </span>
                  <template v-if="isEditMode">
                    <InputText v-model="store.user.mailUniv" placeholder="Mail Universitaire" />
                  </template>
                  <span v-if="store.user.telBureau || store.user.posteInterne">•</span>
                  <div>
                    <span v-if="store.user.telBureau">{{store.user.telBureau}} </span>
                    <span v-if="store.user.posteInterne"> ({{store.user.posteInterne}})</span>
                  </div>
                  <span v-if="store.user.bureau">•</span>
                  <span v-if="store.user.bureau">Bureau  : {{store.user.bureau}}</span>
                </div>
              </div>
              <div class="flex flex-col md:flex-row gap-2">
                <Button label="Contacter" icon="pi pi-envelope" severity="contrast"/>
                <Button v-if="store.user.sitePerso" label="Site Personnel" icon="pi pi-external-link" iconPos="right" severity="contrast" @click="redirectTo(store.user.sitePerso)"/>
                <Button v-if="store.user.siteUniv" label="Site Universitaire" icon="pi pi-external-link" iconPos="right" severity="contrast" @click="redirectTo(store.user.siteUniv)"/>
              </div>
            </div>
            <div class="flex flex-col gap-4" v-else>
              <div class="flex gap-2">
                <IftaLabel>
                  <InputText v-model="store.user.prenom" placeholder="Prénom" />
                  <label for="prenom">Prénom</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="store.user.nom" placeholder="Nom" />
                  <label for="nom">Nom</label>
                </IftaLabel>
              </div>
              <div class="flex gap-2">
                <IftaLabel>
                  <Select v-model="selectedStatut" :options="statuts" optionLabel="label" class="w-full md:w-56" />
                  <label for="statut">Statut</label>
                </IftaLabel>
                <IftaLabel class="w-full">
                  <InputText class="w-full" v-model="store.user.domaines" placeholder="Domaines" />
                  <label for="domaines">Domaines</label>
                  <help-text class="text-muted-color text-sm">Les domaines sont séparés par des virgules</help-text>
                </IftaLabel>
              </div>
              <div>
                <IftaLabel>
                  <InputText class="w-full" v-model="store.user.responsabilites" placeholder="Responsabilités" />
                  <label for="responsabilites">Responsabilités</label>
                  <help-text class="text-muted-color text-sm">Les responsabilités sont séparées par des virgules</help-text>
                </IftaLabel>
              </div>
              <div class="flex gap-2 flex-wrap">
                <IftaLabel>
                  <InputText v-model="store.user.numeroHarpege" placeholder="Numéro Harpege" />
                  <label for="numeroHarpege">Numéro Harpege</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="store.user.mailUniv" placeholder="Mail Universitaire" />
                  <label for="mailUniv">Mail Universitaire</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="store.user.telBureau" placeholder="Téléphone Bureau" />
                  <label for="telBureau">Téléphone Bureau</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="store.user.posteInterne" placeholder="Poste Interne" />
                  <label for="posteInterne">Poste Interne</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="store.user.bureau" placeholder="Bureau" />
                  <label for="bureau">Bureau</label>
                </IftaLabel>
              </div>
              <div class="flex gap-2">
                <IftaLabel>
                  <InputText v-model="store.user.sitePerso" placeholder="Site Personnel" />
                  <label for="sitePerso">Site Personnel</label>
                </IftaLabel>
                <IftaLabel>
                  <InputText v-model="store.user.siteUniv" placeholder="Site Universitaire" />
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
                <Tag :key="index" :value="store.departementDefaut.libelle" severity="info" rounded/>
                <Tag v-for="(departement, index) in store.departementsNotDefaut" :key="index" :value="departement.libelle" severity="primary" rounded/>
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
