<script setup>
import { computed, ref } from 'vue';
import Tag from 'primevue/tag';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { Card, EmptyState } from '@components';
import { GROUPES, filtrerRoles } from '@utils/aideAcces';

/** Catalogue des rôles (groupés, avec recherche). Partagé par l'aide personnelle et l'aide globale. */
const recherche = ref('');
const groupes = computed(() => GROUPES
    .map(g => ({ ...g, roles: filtrerRoles(recherche.value).filter(r => r.groupe === g.id) }))
    .filter(g => g.roles.length > 0));
</script>

<template>
    <Card title="Les rôles" subtitle="Ce que chaque rôle permet, et où il s'applique" icon="pi pi-key" color="indigo">
        <template #header-actions>
            <div>
                <label for="catalogue-recherche" class="sr-only">Rechercher un rôle</label>
                <IconField>
                    <InputIcon><i class="pi pi-search" /></InputIcon>
                    <InputText id="catalogue-recherche" v-model="recherche" type="search" placeholder="Rechercher un rôle ou un droit" class="text-sm" />
                </IconField>
            </div>
        </template>

        <EmptyState v-if="groupes.length === 0" title="Aucun rôle trouvé" description="Aucun rôle ne correspond à la recherche." icon="pi pi-search" color="gray" compact />
        <div v-for="g in groupes" :key="g.id" class="mb-6 last:mb-0" data-test="groupe">
            <h3 class="font-semibold text-sm mb-2 flex items-center gap-2"><i :class="g.icone" aria-hidden="true"></i>{{ g.titre }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm ui-table-compact">
                    <thead>
                        <tr class="text-left text-muted-color">
                            <th scope="col" class="py-1 pr-3">Rôle</th>
                            <th scope="col" class="py-1 pr-3">Portée</th>
                            <th scope="col" class="py-1">Ce qu'il permet</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in g.roles" :key="r.role" class="border-t border-surface align-top" data-test="role">
                            <th scope="row" class="py-2 pr-3 font-medium text-left">{{ r.libelle }}<br><code class="text-sm text-muted-color">{{ r.role }}</code></th>
                            <td class="py-2 pr-3 whitespace-nowrap"><Tag :value="r.portee" :severity="r.portee.startsWith('Tout') || r.portee.startsWith('Établ') ? 'danger' : 'secondary'" /></td>
                            <td class="py-2">{{ r.permet }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </Card>
</template>
