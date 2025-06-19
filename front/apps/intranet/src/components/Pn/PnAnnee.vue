<script setup>
defineProps({
  annee: {
    type: Object,
    required: true
  },
  responsableDiplome: {
    type: String,
    default: 'Pas de responsable'
  }
})
</script>

<template>
  <div class="border-l-2 border-primary-500 pl-4">
    <div class="mb-1 text-lg">{{annee.libelleLong}}</div>
    <div class="text-muted-color mb-4">
      Responsable du diplome : {{responsableDiplome}}
    </div>

    <!-- Informations de l'année -->
    <div class="my-6">
      <table class="text-lg">
        <thead>
          <tr class="border-b">
            <th class="px-2 font-normal text-muted-color text-start">Année</th>
            <th class="px-2 font-normal text-muted-color text-start">Code étape</th>
            <th class="px-2 font-normal text-muted-color text-start">Code version</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="px-2 font-bold">{{ annee.libelle }}</td>
            <td class="px-2 font-bold">{{ annee.apogeeCodeEtape }}</td>
            <td class="px-2 font-bold">{{ annee.apogeeCodeVersion }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <slot></slot>
  </div>
</template> 