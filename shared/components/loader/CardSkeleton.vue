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
        <Skeleton width="100%" height="5rem" class="mb-2"></Skeleton>
        <Skeleton width="50%" class="mb-2"></Skeleton>
        <Skeleton height="3rem" class="mb-2"></Skeleton>
        <Skeleton width="100%" height="2rem"></Skeleton>
    </div>
</template>

<style scoped>

</style>
