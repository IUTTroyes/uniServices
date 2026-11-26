<script setup>
import { Card, HeaderComponent, Kpi } from '@components';
import { GROUPES, MATRICE, MATRICE_COLONNES, POINTS_VIGILANCE, RESERVE_SUPER_ADMIN, ROLES } from '@utils/aideAcces';
import CatalogueRoles from '../../components/CatalogueRoles.vue';
import PrincipesAcces from '../../components/PrincipesAcces.vue';
import { estInstalle } from '@utils/distribution';
import TableauFinance from '../../components/TableauFinance.vue';

/** Vue globale des rôles et accès pour le super administrateur. Source : docs/ROLES_ET_ACCES.md. */
</script>

<template>
    <HeaderComponent icon="pi pi-key" color="indigo" titre="Rôles et accès (vue globale)" description="Tous les rôles, leur portée et les droits associés" back-url="/auth/configuration/gestion-acces" />

    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Kpi label="Rôles" :value="ROLES.length" icon="pi pi-key" color="indigo" />
            <Kpi label="Familles de rôles" :value="GROUPES.length" icon="pi pi-th-large" color="blue" />
            <Kpi label="Domaines couverts" :value="MATRICE.length" icon="pi pi-table" color="emerald" />
        </div>

        <PrincipesAcces />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card title="Réservé au super administrateur" icon="pi pi-shield" color="red">
                <ul class="list-disc pl-5 space-y-1 text-sm" data-test="reserve">
                    <li v-for="t in RESERVE_SUPER_ADMIN" :key="t">{{ t }}</li>
                </ul>
            </Card>
            <Card title="Points de vigilance" icon="pi pi-exclamation-triangle" color="amber">
                <ul class="list-disc pl-5 space-y-1 text-sm" data-test="vigilance">
                    <li v-for="t in POINTS_VIGILANCE" :key="t">{{ t }}</li>
                </ul>
            </Card>
        </div>

        <CatalogueRoles />

        <Card title="Matrice par domaine" subtitle="● écriture · ○ lecture · — aucun accès. Règles principales, toujours limitées au département de la ressource ; la référence reste le voter du domaine." icon="pi pi-table" color="purple">
            <div class="overflow-x-auto">
                <table class="w-full text-sm ui-table-compact" data-test="matrice">
                    <thead>
                        <tr class="text-left text-muted-color">
                            <th scope="col" class="py-1 pr-3">Domaine</th>
                            <th v-for="c in MATRICE_COLONNES" :key="c" scope="col" class="py-1 px-2 text-center">{{ c }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="l in MATRICE" :key="l.domaine" class="border-t border-surface">
                            <th scope="row" class="py-2 pr-3 font-medium text-left">{{ l.domaine }}</th>
                            <td v-for="(v, i) in l.valeurs" :key="i" class="py-2 px-2 text-center">{{ v }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <TableauFinance v-if="estInstalle('finance')" />
    </div>
</template>
