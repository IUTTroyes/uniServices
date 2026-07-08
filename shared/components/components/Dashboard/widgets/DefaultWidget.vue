<script setup>
import { computed, defineProps } from 'vue';

const props = defineProps({
    data: {
        type: [Object, Array, String, Number, Boolean],
        default: null,
    },
});

const formattedData = computed(() => {
    if (props.data == null) {
        return 'Aucune donnée disponible';
    }

    if (typeof props.data === 'string') {
        return props.data;
    }

    try {
        return JSON.stringify(props.data, null, 2);
    } catch {
        return String(props.data);
    }
});
</script>

<template>
    <pre class="text-sm text-color-secondary whitespace-pre-wrap break-all m-0">{{ formattedData }}</pre>
</template>
