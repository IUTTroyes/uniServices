<script setup>
import AppMenu from './AppMenu.vue';

const props = defineProps({
    menuItems: {
        type: Array,
        required: true
    },
    bottomItems: {
        type: Array,
        default: () => []
    }
});
</script>

<template>
    <div class="layout-sidebar">
        <div class="layout-sidebar-top">
            <app-menu :model="menuItems"></app-menu>
        </div>
        <div v-if="bottomItems && bottomItems.length > 0" class="layout-sidebar-bottom border-t border-surface-200 dark:border-surface-800 pt-2">
            <app-menu :model="bottomItems"></app-menu>
        </div>
    </div>
</template>

<style lang="scss" scoped></style>
