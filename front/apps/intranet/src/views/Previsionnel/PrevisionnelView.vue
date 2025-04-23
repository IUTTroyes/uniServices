<script setup>
import {onMounted} from "vue";
import router from "@/router";

const items = [
  { label: 'Par semestre', icon: 'pi pi-list', route: '/administration/previsionnel/semestre' },
  { label: 'Par semestre test', icon: 'pi pi-list', route: '/administration/previsionnel/semestre_test' },
  { label: 'Par enseignant', icon: 'pi pi-clock', route: '/administration/previsionnel/personnel' },
  { label: 'Par matière', icon: 'pi pi-money-bill', route: '/administration/previsionnel/matiere' },
];

onMounted(() => {
  if (items.length > 0) {
    router.push(items[0].route);
  }
});
</script>

<template>
  <div class="card">
    <Tabs value="/administration/previsionnel/semestre" scrollable>
      <TabList>
        <Tab v-for="tab in items" :key="tab.label" :value="tab.route">
          <router-link v-if="tab.route" v-slot="{ href, navigate }" :to="tab.route" custom>
            <a v-ripple :href="href" @click="navigate" class="flex items-center gap-2 text-inherit uppercase">
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
