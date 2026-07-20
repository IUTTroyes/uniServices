<script setup>
import { defineProps } from 'vue';
import {useUsersStore} from '@stores';

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
});

const userStore = useUsersStore();

</script>

<template>
<div class="card card-body flex flex-col justify-start items-center gap-2">
  <div class="w-20 h-20 bg-primary-400 rounded-full flex items-center justify-center shrink-0">
    <template v-if="userStore.userPhoto">
      <img :src="userStore.userPhoto" alt="photo de profil" class="rounded-full" />
    </template>
    <template v-else>
      <span class="text-gray-700 text-xl">{{ initiales }}</span>
    </template>
  </div>
  <p class="font-bold text-center">{{ user.display }}</p>
</div>
</template>

<style scoped>

</style>
