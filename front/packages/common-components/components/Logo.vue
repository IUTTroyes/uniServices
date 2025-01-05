<script setup>

const props = defineProps({
  logoUrl: {
    type: String,
    required: true
  },
  alt: {
    type: String,
    default: 'Logo'
  },
  class: {
    type: String,
    default: ''
  }
})
</script>

<template>
  <img :src="logoUrl" :alt="alt" :class="class" />
</template>

<style scoped>
  .logo_menu {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 10px;
  }

  .logo_login {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background-color: rgb(255, 255, 255);
  }
</style>
