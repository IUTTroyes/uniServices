<template>
  <div class="error-container">
    <img src="@common-images/illu/maintenance.svg" alt="Maintenance" class="maintenance-image" />
    <h2 class="error-message">Une erreur est survenue, veuillez réessayer plus tard</h2>
  </div>
</template>

<script setup>
// No props or logic needed for this simple component
</script>

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
