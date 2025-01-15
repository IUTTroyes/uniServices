<script setup>
import { TopbarComponent } from "@components";
import {ref, computed, onMounted} from "vue";
import { useRoute } from 'vue-router';
import {useUsersStore} from "@stores";

const userStore = useUsersStore();


const appName = ref('App');
const logoUrl = ref('/assets/logo.png');

const route = useRoute();
const isLogin = computed(() => route.path === '/login');

onMounted(async () => {
  // if (userStore.user.length < 1) {
    await userStore.getUser();
    console.log('userStore.user', userStore.user);
  // } else {
  //   console.log('persistUserStore.user', userStore.user);
  // }
});
</script>

<template>
<!--  <div v-if="!isLogin">-->
<!--    <TopbarComponent :app-name="appName" :logo-url="logoUrl"/>-->
<!--  </div>-->
  <router-view />
</template>

<style scoped></style>
