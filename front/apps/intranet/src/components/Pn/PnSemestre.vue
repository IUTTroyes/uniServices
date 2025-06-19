<script setup>
defineProps({
  semestre: {
    type: Object,
    required: true
  }
})

defineEmits(['getEnseignement'])
</script>

<template>
  <div class="ml-6 border-l-2 border-primary-300 pl-4">
    <div class="mt-6 mb-2 flex flex-row items-center gap-4">
      <table class="text-lg">
        <thead>
          <tr class="border-b">
            <th class="px-2 font-normal text-muted-color text-start">Semestre</th>
            <th class="px-2 font-normal text-muted-color text-start">Code élément</th>
            <th class="px-2 font-normal text-muted-color text-start">Nbr. d'UEs</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="px-2 font-bold">{{ semestre.libelle }}</td>
            <td class="px-2 font-bold">{{ semestre.codeElement }}</td>
            <td class="px-2 font-bold">{{ semestre.ues.length }}</td>
          </tr>
        </tbody>
      </table>
      <Button icon="pi pi-cog" rounded outlined severity="warn" @click="" v-tooltip.top="`Accéder aux paramètres`"/>
    </div>

    <div class="mb-4">
      <div>Nombre de groupes</div>
      <table class="text-lg">
        <thead>
          <tr class="border-b">
            <th class="px-2 font-normal text-muted-color text-start">CM</th>
            <th class="px-2 font-normal text-muted-color text-start">TD</th>
            <th class="px-2 font-normal text-muted-color text-start">TP</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="px-2 font-bold">{{ semestre.nbGroupesCm }}</td>
            <td class="px-2 font-bold">{{ semestre.nbGroupesTd }}</td>
            <td class="px-2 font-bold">{{ semestre.nbGroupesTp }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Fieldset v-for="ue in semestre.ues" 
              :key="ue.id"
              :toggleable="true" 
              :legend="`${ue.numero} . ${ue.displayApc}`" 
              class="ml-6 !border-l-2 !border-l-primary-200 !pl-4 !border-0" 
              :collapsed="true">
      <template #toggleicon>
        <i class="pi pi-angle-down"></i>
      </template>
      
      <div class="my-6 flex flex-row items-center gap-4">
        <table class="text-lg">
          <thead>
            <tr class="border-b">
              <th class="px-2 font-normal text-muted-color text-start">UE</th>
              <th class="px-2 font-normal text-muted-color text-start">Code élément</th>
              <th class="px-2 font-normal text-muted-color text-start">Nb. ECTS</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="px-2 font-bold">{{ue.libelle}}</td>
              <td class="px-2 font-bold">{{ ue.codeElement }}</td>
              <td class="px-2 font-bold !w-fit">{{ue.nbEcts}}</td>
            </tr>
          </tbody>
        </table>
        <Button icon="pi pi-cog" rounded outlined severity="warn" @click="" v-tooltip.top="`Accéder aux paramètres`"/>
      </div>

      <div v-for="enseignementUe in ue.enseignementUes" :key="enseignementUe.id">
        <Fieldset v-if="!enseignementUe.enseignement.parent" legend="" :toggleable="true">
          <template #toggleicon>
            <i class="pi pi-angle-down"></i>
            <div>{{ enseignementUe.enseignement.libelle }}</div>
            <Tag v-if="enseignementUe.enseignement.enfants?.length >= 1" severity="danger">Ressource parent</Tag>
          </template>
          
          <div class="my-6 flex flex-row items-center gap-4">
            <table class="text-lg">
              <thead>
                <tr class="border-b">
                  <th class="px-2 font-normal text-muted-color text-start">Code {{enseignementUe.enseignement.type}}</th>
                  <th class="px-2 font-normal text-muted-color text-start">Enseignement</th>
                  <th class="px-2 font-normal text-muted-color text-start">Code apogée</th>
                  <th class="px-2 font-normal text-muted-color text-start">Type</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="px-2 font-bold">{{ enseignementUe.enseignement.codeEnseignement }}</td>
                  <td class="px-2 font-bold">{{ enseignementUe.enseignement.libelle }}</td>
                  <td class="px-2 font-bold">{{ enseignementUe.enseignement.codeApogee }}</td>
                  <td class="px-2 font-bold">
                    <Tag v-if="enseignementUe.enseignement.type === 'sae'" severity="success">{{ enseignementUe.enseignement.type }}</Tag>
                    <Tag v-else severity="info">{{ enseignementUe.enseignement.type }}</Tag>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-if="enseignementUe.enseignement.bonification" class="px-2 font-bold">
              <Tag severity="danger">Bonif.</Tag>
            </div>

            <Button icon="pi pi-info-circle" rounded outlined severity="info" 
                    @click="$emit('getEnseignement', enseignementUe.enseignement.id)" 
                    v-tooltip.top="`Accéder au détail`"/>
            <Button icon="pi pi-book" rounded outlined severity="primary" @click="" 
                    v-tooltip.top="`Accéder au plan de cours`"/>
            <Button icon="pi pi-cog" rounded outlined severity="warn" @click="" 
                    v-tooltip.top="`Accéder aux paramètres`"/>
          </div>
        </Fieldset>
      </div>
    </Fieldset>
  </div>
</template> 