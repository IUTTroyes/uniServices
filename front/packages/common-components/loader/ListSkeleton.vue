<script setup>
defineProps({
    width: {
        type: String,
        default: '100%'
    }
});
</script>

<template>
    <div :style="{ width }">
        <Skeleton width="100%" class="mb-2"></Skeleton>
        <Skeleton width="100%" class="mb-2"></Skeleton>
        <Skeleton width="100%" class="mb-2"></Skeleton>
        <Skeleton width="100%" class="mb-2"></Skeleton>
        <Skeleton width="100%" class="mb-2"></Skeleton>
    </div>
</template>

<style scoped>

</style>
