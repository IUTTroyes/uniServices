<script setup>
import {onMounted} from "vue";
import {useUsersStore} from "@stores";

const userStore = useUsersStore();

onMounted(() => {
  userStore.getUser();
});
</script>

<template>
    <router-view />
</template>

<style scoped></style>
