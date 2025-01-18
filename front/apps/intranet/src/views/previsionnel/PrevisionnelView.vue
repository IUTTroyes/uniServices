<script setup>

const items = [
  { label: 'Par semestre', icon: 'pi pi-list', route: '/administration/previsionnel/semestre' },
  { label: 'Par enseignant', icon: 'pi pi-clock', route: '/administration/previsionnel/personnel' },
  { label: 'Par matière', icon: 'pi pi-money-bill', route: '/administration/previsionnel/matiere' },
];
</script>

<template>
  <div class="card">
    <Tabs value="/administration/previsionnel/semestre" scrollable>
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
  </div>
</template>

<style scoped>

</style>
