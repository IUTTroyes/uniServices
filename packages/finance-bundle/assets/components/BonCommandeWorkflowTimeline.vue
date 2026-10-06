<script setup>
import { computed } from 'vue';

const props = defineProps({
    commande: {
        type: Object,
        required: true,
    },
});

const steps = computed(() => {
    const c = props.commande;
    const hasReception = c.receptions && c.receptions.length > 0;
    const rec = hasReception ? c.receptions[0] : null;

    return [
        {
            id: 1,
            title: '1. Demande & Avis',
            desc: c.avisDirecteur ? `Avis: ${c.avisDirecteur}` : 'Demande initiée',
            date: c.dateAvisDirecteur || c.dateDemande,
            completed: !!c.avisDirecteur || !!c.dateSignatureResponsable,
            active: !c.dateVerificationFinanciere,
            icon: 'pi pi-file-edit',
            color: '#1e3a8a',
        },
        {
            id: 2,
            title: '2. Vérification SIFAC',
            desc: c.numeroBonCommande ? `BC N° ${c.numeroBonCommande}` : 'En attente vérification',
            date: c.dateVerificationFinanciere,
            completed: !!c.numeroBonCommande && !!c.dateVerificationFinanciere,
            active: !!c.avisDirecteur && !c.dateVerificationFinanciere,
            icon: 'pi pi-search',
            color: '#b45309',
        },
        {
            id: 3,
            title: '3. Visa Engagement',
            desc: c.dateVisaEngagement ? 'Visa accordé' : 'En attente visa CSA',
            date: c.dateVisaEngagement,
            completed: !!c.dateVisaEngagement,
            active: !!c.dateVerificationFinanciere && !c.dateVisaEngagement,
            icon: 'pi pi-check-circle',
            color: '#7c3aed',
        },
        {
            id: 4,
            title: '4. Constatation Service Fait',
            desc: rec?.dateEffectiveReception ? `Reçu le ${new Date(rec.dateEffectiveReception).toLocaleDateString('fr-FR')}` : 'En attente livraison',
            date: rec?.dateEffectiveReception,
            completed: !!rec?.dateEffectiveReception,
            active: !!c.dateVisaEngagement && !rec?.dateEffectiveReception,
            icon: 'pi pi-box',
            color: '#047857',
        },
        {
            id: 5,
            title: '5. Certification & Paiement',
            desc: c.factureFinale ? 'Facture soldée' : (c.dateCertificationServiceFait ? 'Certifié' : 'En attente'),
            date: c.datePaiement || c.dateCertificationServiceFait,
            completed: !!c.factureFinale || !!c.datePaiement,
            active: !!rec?.dateEffectiveReception && !c.factureFinale,
            icon: 'pi pi-wallet',
            color: '#881337',
        },
    ];
});
</script>

<template>
    <div class="workflow-stepper py-3">
        <div class="flex items-center justify-between relative">
            <div class="absolute top-1/2 left-0 right-0 h-1 bg-gray-200 -translate-y-1/2 z-0"></div>

            <div
                v-for="(step, idx) in steps"
                :key="step.id"
                class="flex flex-col items-center relative z-10 text-center flex-1"
            >
                <div
                    class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition-all shadow-sm"
                    :class="[
                        step.completed
                            ? 'bg-emerald-600 text-white ring-4 ring-emerald-100'
                            : step.active
                            ? 'bg-blue-600 text-white ring-4 ring-blue-100'
                            : 'bg-white text-gray-400 border-2 border-gray-300'
                    ]"
                >
                    <i v-if="step.completed" class="pi pi-check text-base"></i>
                    <i v-else :class="step.icon"></i>
                </div>

                <div class="mt-2">
                    <div class="text-xs font-bold" :class="step.completed || step.active ? 'text-gray-900' : 'text-gray-400'">
                        {{ step.title }}
                    </div>
                    <div class="text-[11px] text-gray-500 max-w-[130px] truncate" :title="step.desc">
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
