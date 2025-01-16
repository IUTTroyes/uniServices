<script setup>
import {ref, computed, onMounted, watch} from "vue";
import { useRoute } from 'vue-router';
import {useUsersStore} from "@stores";

const userStore = useUsersStore();
const route = useRoute();
const appName = ref('App');
const logoUrl = ref('/assets/logo.png');

const isLogin = ref(false);
isLogin.value = computed(() => route.path === '/login');

watch(() => route.path, (path) => {
  isLogin.value = path === '/login';
  console.log('isLogin', isLogin.value);

  if (!isLogin.value) {
    userStore.getUser();
  }
});

</script>

<template>
  <router-view />
</template>

<style scoped></style>
