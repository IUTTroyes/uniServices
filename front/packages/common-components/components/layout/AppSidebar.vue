<script setup>
import AppMenu from './AppMenu.vue';

const props = defineProps({
    menuItems: {
        type: Array,
        required: true
    }
});
</script>

<template>
    <div class="layout-sidebar">
        <app-menu :model="menuItems"></app-menu>
    </div>
</template>

<style lang="scss" scoped></style>
