<script setup>
const props = defineProps({
  items: {
    type: Array,
    required: true
  }
});
</script>

<template>
  <Breadcrumb :model="items">
    <template #item="{ item, props }">
      <router-link v-if="item.route" v-slot="{ href, navigate }" :to="item.route" custom>
        <a :href="href" v-bind="props.action" @click="navigate">
          <span class="text-primary font-semibold">{{ item.label }}</span>
        </a>
      </router-link>
      <a v-else :href="item.url" :target="item.target" v-bind="props.action">
        <span class="text-surface-700 dark:text-surface-0">{{ item.label }}</span>
      </a>
    </template>
  </Breadcrumb>
</template>

<style scoped>
.p-breadcrumb {
  background-color: transparent;
}
</style>
