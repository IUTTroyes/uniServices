<script setup>
import { ref, toRefs } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

const props = defineProps({
    model: {
        type: Array,
        required: true
    }
});

const { model } = toRefs(props);
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in model" :key="item">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-if="item.separator" class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>
