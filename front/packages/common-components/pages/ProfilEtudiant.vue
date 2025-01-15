<script setup>
import { onMounted, ref } from 'vue';
import { useUsersStore } from "@stores";

const store = useUsersStore();

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
        </template>
        <div class="flex flex-col gap-2">
          <div class="mt-4 gap-4 flex flex-col md:flex-row items-center">
            <div class="w-full md:w-1/6 flex justify-center">
              <img :src="store.userPhoto" alt="photo de profil" class="rounded-full w-24 h-24 md:w-auto md:h-auto">
            </div>
            <div class="w-full md:w-3/6 flex flex-col gap-4">
              <div class="flex flex-col gap-2">
                <div class="flex flex-col md:flex-row gap-2">
                  <div class="title text text-2xl font-bold">
                    {{ store.user.prenom }} {{ store.user.nom }}
                  </div>
                  <Tag v-if="store.scolariteActif.semestres" v-for="semestre in store.scolariteActif.semestres" :value="semestre.annee.libelle" severity="primary" rounded/>
                  <Tag v-if="store.scolariteActif.semestres" v-for="semestre in store.scolariteActif.semestres" :value="semestre.libelle" severity="info" rounded/>
                  <Tag v-if="store.scolariteActif.groupes" v-for="groupe in store.scolariteActif.groupes" :value="groupe.libelle" severity="secondary" rounded/>
                </div>
                <div class="text-sm opacity-80 pt-1 flex flex-row w-full flex-wrap gap-2">
                  <span v-if="store.user.tel1">tél. : {{store.user.tel1}} </span>
                  <span v-if="store.user.tel2">•</span>
                  <span v-if="store.user.tel2">{{store.user.tel2}} </span>
                  <span v-if="store.user.num_etudiant">•</span>
                  <span v-if="store.user.num_etudiant">n° etudiant : {{store.user.num_etudiant}} </span>
                  <span v-if="store.user.num_ine">•</span>
                  <span v-if="store.user.num_ine">INE : {{store.user.num_ine}} </span>
                </div>
              </div>
                <div class="flex flex-col md:flex-row gap-2">
                  <Button label="Contacter" icon="pi pi-envelope" severity="contrast"/>
                  <Button v-if="store.user.site_perso" label="Site Personnel" icon="pi pi-external-link" iconPos="right" severity="contrast" @click="redirectTo(store.user.site_perso)"/>
                  <Button v-if="store.user.site_univ" label="Portfolio Universitaire" icon="pi pi-external-link" iconPos="right" severity="contrast" @click="redirectTo(store.user.site_univ)"/>
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
