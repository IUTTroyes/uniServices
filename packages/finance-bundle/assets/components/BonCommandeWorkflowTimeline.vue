<script setup>
import { computed } from 'vue';
import { ETAPES, etapeCourante, etapeTerminee, formatDate } from '@/utils/finance';

/** Frise des 5 étapes d'une demande, calculée à partir de son statut, aux couleurs des étapes. Cliquer une étape l'ouvre. */
const props = defineProps({
    commande: { type: Object, required: true },
    /** Étape affichée (onglet ouvert), soulignée. */
    selection: { type: Number, default: null },
});
const emit = defineEmits(['selectStep']);

const DATES = {
    1: c => c.dateAvisDirecteur || c.dateDemande,
    2: c => c.dateVerificationFinanciere,
    3: c => c.dateVisaEngagement,
    4: c => c.dateCertificationServiceFait || c.receptions?.at(-1)?.dateEffectiveReception,
    5: c => c.datePaiement || c.dateArriveeFacture,
};

const etapes = computed(() => {
    const statut = props.commande.statut;
    const courante = etapeCourante(statut);
    return ETAPES.map(e => {
        const terminee = etapeTerminee(statut, e.id);
        const active = !terminee && e.id === courante;
        const date = terminee || active ? formatDate(DATES[e.id](props.commande)) : '—';
        return { ...e, terminee, active, verrouillee: !terminee && !active, date: date === '—' ? '' : date };
    });
});
</script>

<template>
    <ol class="grid grid-cols-5 gap-1" aria-label="Étapes de la demande">
        <li v-for="e in etapes" :key="e.id" class="flex flex-col items-center text-center">
            <button
                type="button"
                class="flex flex-col items-center gap-1.5 rounded-lg p-1 w-full focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                :aria-current="e.active ? 'step' : undefined"
                :aria-pressed="selection === e.id"
                @click="emit('selectStep', e.id)"
            >
                <span
                    class="w-10 h-10 rounded-full flex items-center justify-center shadow-sm text-white"
                    :class="e.verrouillee ? 'bg-gray-100 dark:bg-gray-800 !text-gray-400 border border-gray-300 dark:border-gray-700' : [e.fond, e.active ? `ring-4 ${e.anneau}` : 'opacity-90']"
                >
                    <i :class="e.terminee ? 'pi pi-check' : (e.verrouillee ? 'pi pi-lock' : e.icone)" />
                </span>
                <span class="text-sm font-semibold" :class="[e.verrouillee ? 'text-gray-500 dark:text-gray-400' : e.texte, selection === e.id ? 'underline underline-offset-4' : '']">
                    {{ e.id }}. {{ e.titre }}
                </span>
                <span v-if="e.date" class="text-sm text-gray-500 dark:text-gray-400">{{ e.date }}</span>
            </button>
        </li>
    </ol>
</template>
