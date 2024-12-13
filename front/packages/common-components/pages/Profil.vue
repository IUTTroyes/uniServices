<script setup>
import { onMounted } from 'vue';
import { useUsersStore } from "common-stores";

const store = useUsersStore();

onMounted(async () => {
  await store.fetchUser();

  console.log(store.departements);
});
</script>

<template>
  <Card>
    <template #header>
      <div class="profile-header flex justify-start items-center">
        <img :src="store.user.photoName" alt="photo de profil" class="rounded-full w-36">
        <h1>{{store.user.prenom}} {{store.user.nom}}</h1>
      </div>
    </template>
    <template #content>
      <div class="profile-content">
        <div class="profile-content-item">
          <h2>Informations personnelles</h2>
          <div class="profile-content-item-row">
            <div class="profile-content-item-row-label">Nom</div>
            <div class="profile-content-item-row-value">{{store.user.nom}}</div>
          </div>
          <div class="profile-content-item-row">
            <div class="profile-content-item-row-label">Prénom</div>
            <div class="profile-content-item-row-value">{{store.user.prenom}}</div>
          </div>
          <div class="profile-content-item-row">
            <div class="profile-content-item-row-label">Email</div>
            <div class="profile-content-item-row-value">{{store.user.email}}</div>
          </div>
        </div>
        <div class="profile-content-item">
          <h2>Informations professionnelles</h2>
          <div class="profile-content-item-row">
            <div class="profile-content-item-row-label">Départements</div>
            <div class="profile-content-item-row-value" v-for="(departement, index) in store.departements">{{departement.departement.libelle}}</div>
          </div>
        </div>
      </div>
    </template>
  </Card>
</template>

<style scoped>

</style>
