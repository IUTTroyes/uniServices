<script setup>
import { computed } from 'vue';
import Button from 'primevue/button';
import { ETAPE_DES_ACTIONS } from '@/utils/finance';

/** Boutons des actions du circuit possibles pour la personne connectée (fournies par le serveur), pour une étape. */
const props = defineProps({
    actions: { type: Array, default: () => [] },
    etape: { type: Number, default: null },
    envoi: { type: String, default: null },
});
const emit = defineEmits(['executer']);

const visibles = computed(() => props.actions.filter(a => props.etape === null || ETAPE_DES_ACTIONS[a.action] === props.etape));
</script>

<template>
    <div v-if="visibles.length" class="flex flex-wrap justify-end gap-2">
        <Button
            v-for="a in visibles"
            :key="a.action"
            :label="a.libelle"
            :severity="a.severite"
            :outlined="a.severite !== 'primary'"
            size="small"
            :loading="envoi === a.action"
            :disabled="envoi !== null && envoi !== a.action"
            @click="emit('executer', a)"
        />
    </div>
</template>
