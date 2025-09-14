<script setup>
</script>

<template>
  <div class="error-container">
    <img src="@common-images/illu/maintenance.svg" alt="Maintenance" class="maintenance-image" />
    <h2 class="error-message">Une erreur est survenue, veuillez réessayer plus tard</h2>
    <a href="mailto:intranet.iut-troyes@univ-reims.fr" class="underline">Contacter les administrateurs du site &nbsp; <i class="pi pi-external-link !text-xs underline"></i></a>
<!--  un bouton pour revenir à la page précédente  -->
    <Button @click="$router.go(-1)" class="mt-4" severity="contrast">
      <span class="p-button-label">Retour</span>
    </Button>
  </div>
</template>


<style scoped>
.error-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  text-align: center;
  height: 100%;
  width: 100%;
}

.maintenance-image {
  max-width: 300px;
  margin-bottom: 2rem;
}

.error-message {
  font-size: 1.5rem;
  color: #666;
}
</style>
