<script setup>
import { onMounted } from "vue";
import router from "@/router";

const items = [
  { label: 'Par semestre', icon: 'pi pi-list', route: '/administration/previsionnel/semestre' },
  { label: 'Par enseignant', icon: 'pi pi-user', route: '/administration/previsionnel/personnel' },
  { label: 'Par matière', icon: 'pi pi-book', route: '/administration/previsionnel/matiere' },
  { label: 'Vue d\'ensemble', icon: 'pi pi-eye', route: '/administration/previsionnel/semestre_test' },
  { label: 'HRS/primes', icon: 'pi pi-money-bill', route: '/administration/previsionnel/primes' },
  { label: 'Actions', icon: 'pi pi-cog', route: '/administration/previsionnel/actions' },
];

const navigateTo = (route) => {
  router.push(route);
};

onMounted(() => {
  if (items.length > 0) {
    router.push(items[0].route);
  }
});
</script>

<template>
  <div class="card">
    <h2 class="text-2xl font-bold">Gestion des prévisionnels</h2>
    <Divider/>
    <Tabs value="/administration/previsionnel/semestre" scrollable>
      <TabList>
        <router-link
          v-for="tab in items"
          :key="tab.label"
          :to="tab.route"
          custom
        >
          <Tab :value="tab.route" @click="navigateTo(tab.route)">
            <div class="flex items-center gap-2 text-inherit uppercase">
              <i :class="tab.icon" />
              <span>{{ tab.label }}</span>
            </div>
          </Tab>
        </router-link>
      </TabList>
    </Tabs>
    <router-view class="mt-6"></router-view>
  </div>
</template>
