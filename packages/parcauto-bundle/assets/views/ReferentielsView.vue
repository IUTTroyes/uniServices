<script setup>
import { computed, ref } from 'vue';
import { Card, EmptyState, PermissionGuard } from '@components';
import PageParcauto from '../components/PageParcauto.vue';
import FormulaireParcauto from '../components/FormulaireParcauto.vue';
import { messageErreur } from '../components/parcautoDates';
import { enregistrerParcautoService, supprimerValeurReferentielService } from '../service/parcautoService';

const groupes = [
  { label: 'Énergies', value: 'energie' }, { label: 'Types d’équipement', value: 'equipement' },
  { label: 'Plans d’entretien', value: 'plan' }, { label: 'Types de journal', value: 'journal' },
];
const groupe = ref('energie');
const action = ref(null);
const formulaire = ref({});
const erreur = ref('');
const enCours = ref(false);
const champs = computed(() => [
  { nom: 'code', label: 'Code (minuscules, chiffres et tirets bas)', max: 20, requis: true, lecture: action.value === 'Modifier' },
  { nom: 'libelle', label: 'Libellé', max: 120, requis: true },
]);

function ouvrir(operation, valeur = {}) {
  formulaire.value = { ...valeur };
  action.value = operation;
  erreur.value = '';
}

async function enregistrer(actualiser) {
  enCours.value = true;
  erreur.value = '';
  try {
    const { code, libelle } = formulaire.value;
    if (action.value === 'Supprimer') {
      await supprimerValeurReferentielService(groupe.value, code);
    } else {
      const modifier = action.value === 'Modifier';
      const chemin = '/referentiels/' + groupe.value + (modifier ? '/' + encodeURIComponent(code) : '');
      await enregistrerParcautoService(chemin, { code, libelle }, modifier);
    }
    action.value = null;
    await actualiser();
  } catch (e) {
    erreur.value = messageErreur(e);
  } finally {
    enCours.value = false;
  }
}
</script>

<template>
  <PageParcauto v-slot="{ parc, actualiser }" titre="Référentiels" icon="pi pi-list">
    <PermissionGuard :permission="parc.gestion && 'canManageParcauto'">
      <Card title="Valeurs du parc automobile" icon="pi pi-list">
        <template #header-actions><Button label="Ajouter une valeur" icon="pi pi-plus" @click="ouvrir('Ajouter')" /></template>
        <Select v-model="groupe" :options="groupes" option-label="label" option-value="value" aria-label="Référentiel" class="mb-4" />
        <DataTable :value="parc.referentiels.filter(v => v.groupe === groupe)" data-key="code">
          <template #empty><EmptyState title="Aucune valeur" compact /></template>
          <Column field="libelle" header="Libellé" sortable />
          <Column field="code" header="Code" />
          <Column header="Actions"><template #body="{ data }">
            <Button text label="Modifier" @click="ouvrir('Modifier', data)" />
            <Button text severity="danger" label="Supprimer" :disabled="data.protegee" @click="ouvrir('Supprimer', data)" />
          </template></Column>
        </DataTable>
      </Card>
      <Dialog :visible="Boolean(action)" modal :header="action + ' une valeur'" :closable="!enCours" @update:visible="!$event && (action = null)">
        <template v-if="action === 'Supprimer'">
          <p>Supprimer « {{ formulaire.libelle }} » ?</p>
          <Message v-if="erreur" severity="error">{{ erreur }}</Message>
          <Button label="Supprimer" severity="danger" :loading="enCours" @click="enregistrer(actualiser)" />
        </template>
        <FormulaireParcauto v-else v-model="formulaire" :champs="champs" :sauvegarde="enCours" :erreur="erreur" @submit="enregistrer(actualiser)" />
      </Dialog>
    </PermissionGuard>
  </PageParcauto>
</template>
