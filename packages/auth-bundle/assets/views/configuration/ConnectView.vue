<script setup>
import { HeaderComponent } from '@components'

</script>

<template>
  <HeaderComponent
      icon="pi pi-arrow-right-arrow-left"
      titre="Connexions à des outils tiers"
      description="Configurez les connexions à des outils tiers pour l'établissement."
  />

  <div class="card">
    <div class="flex flex-col md:flex-row justify-between items-start w-full card-header">
      <div>
        <p class="top-card-header">Paramétrage des outils externes</p>
        <div class="flex flex-col items-start">
          <h2 class="mt-0!">Outils externes utilisés dans l'application</h2>
        </div>
      </div>
    </div>

    <Tabs class="mt-4">
      <TabList>
        <Tab value="sso">ACTUS</Tab>
        <Tab value="api">API</Tab>
      </TabList>

      <TabPanels>
        <TabPanel value="sso">
          <!-- SSO content goes here -->
        </TabPanel>
        <TabPanel value="api">
          <!-- API content goes here -->
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>
