<script setup>
import {useUsersStore} from "@/stores/users.js";
import {onMounted} from "vue";

const token = document.cookie.split('; ').find(row => row.startsWith('token'))?.split('=')[1];

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
    <div class="grid grid-cols-2 gap-4">
      <div class="card h-full">
        <div class="font-semibold text-xl mb-4">Card 1</div>
        <p>Use this page to start from scratch and place your custom content.</p>
      </div>
      <div class="card">
        <div class="font-semibold text-xl mb-4">Card 2</div>
        <p>Use this page to start from scratch and place your custom content.</p>
      </div>
    </div>
  </div>
</template>
