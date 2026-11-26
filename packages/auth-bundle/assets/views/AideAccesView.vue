<script setup>
import { computed, onMounted } from 'vue';
import Tag from 'primevue/tag';
import { ApplicationBadge, Card, EmptyState, HeaderComponent } from '@components';
import { useUsersStore } from '@stores';
import { libelleRole } from '@utils/aideAcces';
import CatalogueRoles from '../components/CatalogueRoles.vue';
import PrincipesAcces from '../components/PrincipesAcces.vue';
import TableauFinance from '../components/TableauFinance.vue';

/** Aide : rôles et accès de la personne connectée, puis catalogue. Source : docs/ROLES_ET_ACCES.md. */
const store = useUsersStore();

onMounted(async () => {
    if (!store.isLoaded) await store.getUser();
});

const estPersonnel = computed(() => store.userType === 'personnels');
const cleModule = (m) => (m === 'stages' ? 'stage' : m);

const affectations = computed(() => {
    const liste = Array.isArray(store.departements) ? store.departements : [];
    return liste.map((d) => {
        const aff = d.departementPersonnel ?? {};
        return {
            id: d.id,
            nom: d.libelle ?? d.nom ?? 'Département',
            actif: String(d.id) === String(store.departementDefaut?.id),
            roles: (Array.isArray(aff.permissions) ? aff.permissions : []).map(libelleRole),
            modules: Array.isArray(aff.packages) ? aff.packages : [],
        };
    });
});
</script>

<template>
    <HeaderComponent icon="pi pi-key" color="indigo" titre="Comprendre mes accès" description="Rôles, périmètres et droits dans le portail" back-url="/auth/profil" />

    <div class="space-y-6">
        <PrincipesAcces />

        <Card v-if="estPersonnel" title="Mes accès" subtitle="Vos rôles et modules, par département" icon="pi pi-user" color="violet">
            <EmptyState v-if="affectations.length === 0" title="Aucune affectation" description="Contactez l'administrateur de votre département pour obtenir des accès." icon="pi pi-building" color="gray" compact />
            <ul v-else class="space-y-3">
                <li v-for="a in affectations" :key="a.id" class="rounded-lg border border-surface p-3" data-test="affectation">
                    <p class="font-semibold flex items-center gap-2">
                        {{ a.nom }}
                        <Tag v-if="a.actif" value="Département actif" severity="primary" />
                    </p>
                    <p class="text-sm mt-1">
                        <span class="text-muted-color">Rôles : </span>
                        <span v-if="a.roles.length">{{ a.roles.join(', ') }}</span>
                        <span v-else>aucun rôle particulier</span>
                    </p>
                    <div v-if="a.modules.length" class="flex flex-wrap items-center gap-2 mt-2">
                        <span class="text-sm text-muted-color">Modules :</span>
                        <ApplicationBadge v-for="m in a.modules" :key="m" :app="cleModule(m)" show-icon />
                    </div>
                </li>
            </ul>
        </Card>
        <Card v-else title="Votre compte étudiant" icon="pi pi-graduation-cap" color="blue">
            <p class="text-sm">Vous voyez uniquement vos propres données : profil, notes visibles, absences, stage et questionnaires auxquels vous êtes invité.</p>
        </Card>

        <CatalogueRoles />
        <TableauFinance />
    </div>
</template>
