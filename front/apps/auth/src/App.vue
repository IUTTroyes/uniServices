<template>
  <h1>App 1</h1>
  <div>
    {{ formattedDate }}
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { formatDate } from 'common-helpers';

const formattedDate = ref('');

onMounted(() => {
  formattedDate.value = formatDate(new Date());
});
</script>
