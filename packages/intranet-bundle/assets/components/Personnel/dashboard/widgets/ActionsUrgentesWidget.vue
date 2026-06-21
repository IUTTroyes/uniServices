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
               'border-red-200 bg-red-500/10': action.color === 'red',
               'border-orange-200 bg-orange-500/10': action.color === 'orange',
               'border-yellow-200 bg-yellow-500/10': action.color === 'yellow'
             }">
            <div class="flex items-center gap-3">
                <i :class="action.icon"></i>
                <div class="flex flex-col justify-center">
                    <p class="m-0! font-bold">{{ action.titre }}</p>
                    <p class="m-0! text-color-secondary">{{ action.detail }}</p>
                </div>
            </div>
            <Button :label="action.cta" size="small" :class="{
               'border-red-400! bg-red-500/60! text-white! hover:bg-red-500/70! hover:border-red-400!': action.color === 'red',
               'border-orange-400! bg-orange-500/60! text-white! hover:bg-orange-500/70! hover:border-orange-400!': action.color === 'orange',
               'border-yellow-400! bg-yellow-500/60! text-white! hover:bg-yellow-500/70! hover:border-yellow-400!': action.color === 'yellow'
             }"/>
        </div>
    </div>
</template>
