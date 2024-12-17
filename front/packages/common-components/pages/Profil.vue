<script setup>
import { onMounted, ref } from 'vue';
import { useUsersStore } from "common-stores";

const store = useUsersStore();
const statut = ref([]);

onMounted(async () => {
  await store.getUser();
  statut.value = store.user.displayStatut;
});

const redirectTo = (link) => {
  window.open(link, '_blank')
}
</script>

<template>
  <Card>
    <template #header>
      <div class="profile-header flex justify-start items-start gap-10 rounded-t-md w-full">
        <Button class="editBtn" label="Editer mes informations" icon="pi pi-external-link" iconPos="right" @click=""/>
        <div class="flex flex-col" style="width: 20%">
          <img :src="store.user.photoName" alt="photo de profil" class="rounded-full w-full">
          <div class="flex flex-col items-center gap-2">
            <div v-if="statut > 1" class="statut text-sm text-center rounded-md w-full font-bold bg-white px-4 py-2">{{statut}}</div>
            <div v-else class="statut text-sm text-center rounded-md w-full font-bold py-2">Pas de statut</div>
            <div v-if="store.user.responsabilites" class="responsabilites text-sm text-center rounded-md w-full font-bold">{{store.user.responsabilites}}</div>
          </div>
        </div>
        <div class="profile-header-infos" style="width: 80%">
          <div>
            <div class="title">{{ store.user.prenom }} {{ store.user.nom }}</div>
            <div class="text-muted-color">{{store.user.mailUniv}} <span v-if="store.user.telBureau">| {{store.user.telBureau}} </span><span v-if="store.user.posteInterne"> ({{store.user.posteInterne}})</span></div>
          </div>
          <div class="flex flex-row gap-2">
            <Button label="Contacter" icon="pi pi-envelope" severity="contrast"/>
            <Button v-if="store.user.sitePerso" label="Site Personnel" icon="pi pi-external-link" iconPos="right" severity="contrast" @click="redirectTo(store.user.sitePerso)"/>
            <Button v-if="store.user.siteUniv" label="Site Universitaire" icon="pi pi-external-link" iconPos="right" severity="contrast" @click="redirectTo(store.user.siteUniv)"/>
          </div>
          <div class="w-1/2 h-10">
            <ul>
              <li class="rounded-full text-sm" v-for="(departement, index) in store.departements" :key="index">{{ departement.departement.libelle }}</li>
            </ul>
          </div>
        </div>
      </div>
    </template>
    <template #content>
      <div class="profile-content">
        <div class="profile-content-item">
          <h2 >Informations personnelles</h2>
          <div class="profile-content-item-row">
            <div class="profile-content-item-row-label">Nom</div>
            <div class="profile-content-item-row-value">{{ store.user.nom }}</div>
          </div>
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>
.profile-header {
  padding-top: 5rem;
  padding-bottom: 5rem;
  padding-left: 10rem;
  padding-right: 10rem;
  background-color: var(--p-primary-100);
  color: black;
  position: relative;

  .editBtn {
    position: absolute;
    right: 2rem;
    top: 2rem;
  }

  .statut {
    position: relative;
    top: -2rem;
    color: var(--text-color);
    background-color: var(--surface-card);
  }

  .responsabilites {
    background-color: var(--p-yellow-400);
    padding: 0.5rem 2rem;
    position: relative;
    top: -2rem;
  }

  .profile-header-infos {
    margin-top: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;

    .title {
      font-size: 2.5rem;
      line-height: normal;
    }

    ul {
      display: flex;
      flex-direction: row;
      flex-wrap: wrap;
      gap: 1rem;

      li {
        background-color: var(--p-primary-200);
        padding: 0.5rem 2rem;
      }
    }
  }
}
</style>
