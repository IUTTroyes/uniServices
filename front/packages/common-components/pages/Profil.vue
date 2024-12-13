<script setup>
import { onMounted, ref } from 'vue';
import { useUsersStore } from "common-stores";

const store = useUsersStore();
const roles = ref([]);

onMounted(async () => {
  await store.fetchUser();
  roles.value = store.user.displayRoles;
  console.log(roles.value);
});
</script>

<template>
  <Card>
    <template #header>
      <div class="profile-header flex justify-start items-start gap-10 rounded-t-md w-full">
        <div class="flex flex-col" style="width: 20%">
          <img :src="store.user.photoName" alt="photo de profil" class="rounded-full w-full">
          <div class="flex flex-col items-center gap-2">
            <div class="role text-sm text-center rounded-md w-full font-bold" v-for="(role, index) in roles">{{role}}</div>
          </div>
        </div>
        <div class="profile-header-infos" style="width: 80%">
          <div>
            <div class="title">{{ store.user.prenom }} {{ store.user.nom }}</div>
            <div class="text-muted-color">{{store.user.mailUniv}}</div>
          </div>
          <div>
            <Button label="Contacter" icon="pi pi-envelope" severity="contrast"/>
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
          <h2>Informations personnelles</h2>
          <div class="profile-content-item-row">
            <div class="profile-content-item-row-label">Nom</div>
            <div class="profile-content-item-row-value">{{ store.user.nom }}</div>
          </div>
          <div class="profile-content-item-row">
            <div class="profile-content-item-row-label">Prénom</div>
            <div class="profile-content-item-row-value">{{ store.user.prenom }}</div>
          </div>
          <div class="profile-content-item-row">
            <div class="profile-content-item-row-label">Email</div>
            <div class="profile-content-item-row-value">{{ store.user.email }}</div>
          </div>
        </div>
        <div class="profile-content-item">
          <h2>Informations professionnelles</h2>
          <div class="profile-content-item-row">
            <div class="profile-content-item-row-label">Départements</div>
            <div class="profile-content-item-row-value" v-for="(departement, index) in store.departements" :key="index">{{ departement.departement.libelle }}</div>
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

  .role {
    background-color: white;
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
