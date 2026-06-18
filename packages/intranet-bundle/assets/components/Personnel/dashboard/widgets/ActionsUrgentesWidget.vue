<script setup>
defineProps({
    data: {
        type: Object,
        default: () => ({items: []}),
    },
});
</script>

<template>
    <div class="flex flex-col gap-2.5">
        <div v-for="action in data.items || []" :key="action.titre" class="flex items-center justify-between gap-3 rounded-lg border p-2.5"
             :class="{
               'border-red-200 bg-red-50': action.color === 'red',
               'border-orange-200 bg-orange-50': action.color === 'orange',
               'border-yellow-200 bg-yellow-50': action.color === 'yellow'
             }">
            <div class="flex items-center gap-3">
                <i :class="action.icon"></i>
                <div>
                    <p class="m-0 font-bold">{{ action.titre }}</p>
                    <p class="mt-1 text-color-secondary">{{ action.detail }}</p>
                </div>
            </div>
            <button class="rounded-lg border border-violet-300 bg-transparent px-3 py-1.5 text-sm font-semibold text-violet-700" type="button">
                {{ action.cta }}
            </button>
        </div>
    </div>
</template>
