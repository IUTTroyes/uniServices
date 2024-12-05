<script setup>
import { defineProps } from 'vue';

const props = defineProps({
    data: Object
});
</script>

<template>
    <div class="grid grid-cols-1 gap-4">
        <p>Type: {{ data.type }}</p>
        <p>Info: {{ data.info }}</p>
    </div>
</template>

<style scoped lang="scss">

</style>
