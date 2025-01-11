<script setup>
import { onMounted, ref } from 'vue';
import { useUsersStore } from "@stores";

const store = useUsersStore();
const statut = ref([]);

onMounted(async () => {
  await store.getUser();
  statut.value = store.user.displayStatut;

  console.log(store.departementsNotDefaut);
});

const redirectTo = (link) => {
  window.open(link, '_blank')
}
</script>

<template>
  <Card class="p-6">
    <template #header>
      <Fieldset class="w-full h-full">
        <template #legend>
          <div class="flex items-center pl-2">
            <i class="pi pi-user bg-primary-400 bg-opacity-20 rounded-full p-4 text-primary"/>
            <div class="flex flex-col">
              <span class="font-bold px-2 capitalize">Mon profil</span>
            </div>
          </div>
        </template>
        <div class="mt-4 flex gap-4 items-center">
          <div class="w-1/6 flex justify-center">
            <img :src="store.user.photoName" alt="photo de profil" class="rounded-full">
          </div>
          <div class="w-3/6 flex flex-col gap-4">
            <div class="flex flex-col gap-4">
              <div class="flex flex-col gap-2">
                <div class="flex gap-2">
                  <div class="title text text-2xl font-bold">{{ store.user.prenom }} {{ store.user.nom }}</div>
                  <Tag v-if="store.user.statut" :value="store.user.statut" severity="info" rounded/>
                  <Tag v-for="domaine in store.user.domaines" v-if="store.user.domaines" :value="domaine" severity="secondary" rounded class="capitalize"/>
                </div>
                <div v-if="store.user.responsabilites" class="text-lg border-b w-fit pr-6 pb-1">{{store.user.responsabilites}}</div>
                <div class="text-sm opacity-80 pt-1 flex gap-2">
                  <span v-if="store.user.numeroHarpege">Numéro Harpege : {{store.user.numeroHarpege}} </span>
                  <span v-if="store.user.mailUniv">•</span>
                  <span v-if="store.user.mailUniv">{{store.user.mailUniv}} </span>
                  <span v-if="store.user.telBureau || store.user.posteInterne">•</span>
                  <div>
                    <span v-if="store.user.telBureau">{{store.user.telBureau}} </span>
                    <span v-if="store.user.posteInterne"> ({{store.user.posteInterne}})</span>
                  </div>
                  <span v-if="store.user.bureau">•</span>
                  <span v-if="store.user.bureau">Bureau  : {{store.user.bureau}}</span></div>
              </div>
              <div class="flex flex-row gap-2">
                <Button label="Contacter" icon="pi pi-envelope" severity="contrast"/>
                <Button v-if="store.user.sitePerso" label="Site Personnel" icon="pi pi-external-link" iconPos="right" severity="contrast" @click="redirectTo(store.user.sitePerso)"/>
                <Button v-if="store.user.siteUniv" label="Site Universitaire" icon="pi pi-external-link" iconPos="right" severity="contrast" @click="redirectTo(store.user.siteUniv)"/>
              </div>
            </div>
          </div>
          <div class="w-2/6 flex flex-col">
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

</style>
