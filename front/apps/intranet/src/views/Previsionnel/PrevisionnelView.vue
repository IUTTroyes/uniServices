<script setup>
import { onMounted } from "vue";
import router from "@/router";

const items = [
  { label: 'Par semestre', icon: 'pi pi-list', route: '/administration/previsionnel/semestre' },
  { label: 'Par semestre test', icon: 'pi pi-list', route: '/administration/previsionnel/semestre_test' },
  { label: 'Par enseignant', icon: 'pi pi-clock', route: '/administration/previsionnel/personnel' },
  { label: 'Par matière', icon: 'pi pi-money-bill', route: '/administration/previsionnel/matiere' },
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
    <router-view></router-view>
  </div>
</template>
