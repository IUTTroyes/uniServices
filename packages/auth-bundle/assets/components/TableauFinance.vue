<script setup>
import { Card } from '@components';
import { FINANCE_ACTIONS } from '@utils/aideAcces';
</script>

<template>
    <Card title="Demandes d'achat : qui fait quoi" subtitle="Le service financier couvre tout l'établissement ; une demande est imputée à un département ou à un service, jamais aux deux." icon="pi pi-wallet" color="emerald">
        <div class="overflow-x-auto">
            <table class="w-full text-sm ui-table-compact">
                <thead>
                    <tr class="text-left text-muted-color">
                        <th scope="col" class="py-1 pr-3">Action</th>
                        <th scope="col" class="py-1 pr-3">Demandeur</th>
                        <th scope="col" class="py-1 pr-3">Valideur</th>
                        <th scope="col" class="py-1">Service financier</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="l in FINANCE_ACTIONS" :key="l.action" class="border-t border-surface align-top">
                        <th scope="row" class="py-2 pr-3 font-medium text-left">{{ l.action }}</th>
                        <td class="py-2 pr-3">{{ l.demandeur }}</td>
                        <td class="py-2 pr-3">{{ l.valideur }}</td>
                        <td class="py-2">{{ l.financier }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </Card>
</template>
