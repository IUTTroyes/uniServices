<script setup>
defineProps({
  title: {
    type: String,
    default: ''
  },
  bodyClass: {
    type: String,
    default: ''
  }
})
</script>

<template>
  <div class="card">
    <div v-if="title || $slots.header" class="card-header">
      <slot name="header">
        <h3>{{ title }}</h3>
      </slot>
    </div>
    <div :class="['card-body', bodyClass]">
      <slot />
    </div>
  </div>
</template>


<style scoped></style>
