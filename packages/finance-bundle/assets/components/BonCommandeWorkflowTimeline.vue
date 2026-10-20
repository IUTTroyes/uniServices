<script setup>
import { computed } from 'vue';

const props = defineProps({
    commande: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['selectStep']);

const steps = computed(() => {
    const c = props.commande;
    const hasReception = c.receptions && c.receptions.length > 0;
    const rec = hasReception ? c.receptions[0] : null;

    const isStep1Done = c.avisDirecteur === 'FAVORABLE';
    const isStep2Done = isStep1Done && !!c.dateVerificationFinanciere;
    const isStep3Done = isStep2Done && !!c.dateVisaEngagement;
    const isStep4Done = isStep3Done && !!rec?.dateEffectiveReception;
    const isStep5Done = isStep4Done && (!!c.factureFinale || !!c.datePaiement || !!c.dateCertificationServiceFait);

    return [
        {
            id: '1',
            title: '1. Demande & Avis',
            desc: c.avisDirecteur === 'FAVORABLE'
                ? 'Avis favorable'
                : (c.avisDirecteur === 'DEFAVORABLE' ? 'Défavorable' : (c.pdfFilePath ? 'PDF signé déposé' : 'Demande en cours')),
            date: c.dateAvisDirecteur || c.dateDemande,
            completed: isStep1Done,
            active: !isStep1Done,
            icon: 'pi pi-file-edit',
            activeClass: 'bg-blue-600 text-white ring-4 ring-blue-100 dark:ring-blue-950',
            activeTextClass: 'text-blue-700 dark:text-blue-400',
        },
        {
            id: '2',
            title: '2. Vérification SIFAC',
            desc: c.numeroBonCommande ? `BC N° ${c.numeroBonCommande}` : (isStep1Done ? 'En attente vérification' : 'Verrouillé (Attente Étape 1)'),
            date: c.dateVerificationFinanciere,
            completed: isStep2Done,
            active: isStep1Done && !isStep2Done,
            locked: !isStep1Done,
            icon: 'pi pi-search',
            activeClass: 'bg-amber-600 text-white ring-4 ring-amber-100 dark:ring-amber-950',
            activeTextClass: 'text-amber-700 dark:text-amber-400',
        },
        {
            id: '3',
            title: '3. Visa Engagement',
            desc: c.dateVisaEngagement ? 'Visa accordé' : (isStep2Done ? 'En attente visa CSA' : 'Verrouillé (Attente Étape 2)'),
            date: c.dateVisaEngagement,
            completed: isStep3Done,
            active: isStep2Done && !isStep3Done,
            locked: !isStep2Done,
            icon: 'pi pi-check-circle',
            activeClass: 'bg-purple-600 text-white ring-4 ring-purple-100 dark:ring-purple-950',
            activeTextClass: 'text-purple-700 dark:text-purple-400',
        },
        {
            id: '4',
            title: '4. Constatation SF',
            desc: rec?.dateEffectiveReception ? `Reçu le ${new Date(rec.dateEffectiveReception).toLocaleDateString('fr-FR')}` : (isStep3Done ? 'En attente réception' : 'Verrouillé (Attente Étape 3)'),
            date: rec?.dateEffectiveReception,
            completed: isStep4Done,
            active: isStep3Done && !isStep4Done,
            locked: !isStep3Done,
            icon: 'pi pi-box',
            activeClass: 'bg-teal-600 text-white ring-4 ring-teal-100 dark:ring-teal-950',
            activeTextClass: 'text-teal-700 dark:text-teal-400',
        },
        {
            id: '5',
            title: '5. Facture & Paiement',
            desc: c.factureFinale ? 'Facture soldée' : (isStep4Done ? 'En cours de règlement' : 'Verrouillé (Attente Étape 4)'),
            date: c.datePaiement || c.dateCertificationServiceFait,
            completed: isStep5Done,
            active: isStep4Done && !isStep5Done,
            locked: !isStep4Done,
            icon: 'pi pi-wallet',
            activeClass: 'bg-rose-600 text-white ring-4 ring-rose-100 dark:ring-rose-950',
            activeTextClass: 'text-rose-700 dark:text-rose-400',
        },
    ];
});
</script>

<template>
    <div class="workflow-stepper py-2">
        <div class="flex items-center justify-between relative">
            <div class="absolute top-1/2 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 -translate-y-1/2 z-0"></div>

            <div
                v-for="(step, idx) in steps"
                :key="step.id"
                class="flex flex-col items-center relative z-10 text-center flex-1 cursor-pointer select-none group"
                @click="emit('selectStep', step.id)"
            >
                <div
                    class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all shadow-sm"
                    :class="[
                        step.completed
                            ? 'bg-emerald-600 text-white ring-4 ring-emerald-100 dark:ring-emerald-950'
                            : step.active
                            ? (step.activeClass + ' animate-pulse')
                            : step.locked
                            ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 border border-gray-300 dark:border-gray-700'
                            : 'bg-white dark:bg-gray-900 text-gray-500 border-2 border-gray-300 dark:border-gray-600'
                    ]"
                >
                    <i v-if="step.completed" class="pi pi-check text-base"></i>
                    <i v-else-if="step.locked" class="pi pi-lock text-sm"></i>
                    <i v-else :class="step.icon"></i>
                </div>

                <div class="mt-2">
                    <div
                        class="text-xs font-bold group-hover:underline"
                        :class="step.completed ? 'text-emerald-700 dark:text-emerald-400' : (step.active ? step.activeTextClass : 'text-gray-400 dark:text-gray-500')"
                    >
                        {{ step.title }}
                    </div>
                    <div class="text-[11px] text-gray-500 dark:text-gray-400 max-w-[130px] truncate mx-auto" :title="step.desc">
                        {{ step.desc }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.workflow-stepper {
    width: 100%;
}
</style>
