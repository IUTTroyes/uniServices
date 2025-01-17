<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute } from 'vue-router';
import { useUsersStore } from "@stores";

const userStore = useUsersStore();
const route = useRoute();

const isLogin = ref(false);
const loading = ref(true);
const error = ref(null);

isLogin.value = computed(() => route.path === '/login');

// todo: pblm -> relance la méthode getUser() à chaque changement de route
watch(() => route.path, async (path) => {
  isLogin.value = path === '/login';
  console.log('isLogin', isLogin.value);

  if (!isLogin.value) {
    try {
      loading.value = true;
      await userStore.getUser();
    } catch (err) {
      error.value = err;
    } finally {
      loading.value = false;
    }
  }
});
</script>

<template>
  <router-view :loading="loading" :error="error" />
</template>

<style scoped></style>
