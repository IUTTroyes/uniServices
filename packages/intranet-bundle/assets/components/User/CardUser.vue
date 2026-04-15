<script setup>

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})
</script>

<template>
  <Card>
    <template #title>
      {{ user.prenom }} {{ user.nom }}
      <Tag severity="info" variant="simple" class="align-right">Type 1</Tag>
    </template>
    <template #content>
      <div class="flex flex-col items-center">
        <img :src="user.image" alt="user" class="rounded-full w-20 h-20" />
        <p>{{ user.email }}</p>
      </div>
    </template>
  </Card>
</template>

<style scoped>

</style>
