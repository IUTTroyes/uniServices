<script setup>
import { computed } from 'vue';
import { ErrorView } from '@components';

const debugMessage = computed(() => {
  try {
    return window.sessionStorage.getItem('lastServerErrorDebugMessage') || '';
  } catch (_) {
    return '';
  }
});
</script>

<template>
  <div class="flex flex-col items-center justify-center w-full h-screen px-4">
    <div class="w-full max-w-4xl">
      <ErrorView :debug-message="debugMessage" />
    </div>
  </div>
</template>
