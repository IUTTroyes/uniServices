<script setup>

</script>

<template>
    <div class="container w-full h-full flex flex-col gap-4 justify-center items-center mx-auto">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="8" fill="transparent"
                       animationDuration=".5s" aria-label="Chargement en cours" />
        <div>Chargement en cours ...</div>
    </div>
</template>

<style scoped>
.loadItem {
  animation: loadItemWave 1.5s infinite ease-in-out;
}
@keyframes loadItemWave {
  0%, 100% {
    transform: scaleY(0);
  }
  50% {
    transform: scaleY(10);
  }
}
</style>
