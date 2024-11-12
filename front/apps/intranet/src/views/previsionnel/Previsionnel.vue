<script setup>

const items = [
  { label: 'Intervenant', route: '/previsionnel/intervenant', icon: 'fas fa-user-tie' },
  { label: 'Matière', route: '/previsionnel/matiere', icon: 'fas fa-book' },
  { label: 'Semestre', route: '/previsionnel/semestre', icon: 'fas fa-calendar-alt' },
]
</script>

<template>
  <h1>Prévisionnel</h1>
  <Tabs value="/dashboard">
    <TabList>
      <Tab v-for="tab in items" :key="tab.label" :value="tab.route">
        <router-link v-if="tab.route" v-slot="{ href, navigate }" :to="tab.route" custom>
          <a v-ripple :href="href" @click="navigate" class="flex items-center gap-2 text-inherit">
            <i :class="tab.icon" />
            <span>{{ tab.label }}</span>
          </a>
        </router-link>
      </Tab>
    </TabList>
  </Tabs>

  <router-view></router-view>
</template>

<style scoped>

</style>
