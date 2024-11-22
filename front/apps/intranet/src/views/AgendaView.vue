<script setup>
import {useUsersStore} from "@/stores/users.js";
import {onMounted} from "vue";

const store = useUsersStore();

onMounted(() => {
  store.fetchUser();
});

console.log(store.user);
</script>

<template>
  <div>
    <div class="m-5">
        <p>Hello, {{ store.user?.prenom }}</p>
    </div>
  </div>
</template>
