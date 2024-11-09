<template>
  <h1>App 1</h1>
  <div>
    {{ formattedDate }}
    <InputText v-model="text" type="text" />
    {{ text }}
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { formatDate } from 'common-helpers';

const formattedDate = ref('');
const text = ref('');

onMounted(() => {
  formattedDate.value = formatDate(new Date());
});
</script>
