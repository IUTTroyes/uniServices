<script setup>

</script>

<template>
    <div class="container w-full h-full flex flex-col gap-4 justify-center items-center mx-auto">
        <div class="loader flex flex-row items-center justify-center gap-2 w-full h-14">
          <div class="loadItem bg-primary-800 w-2 h-1" style="animation-delay: 0s"></div>
          <div class="loadItem bg-primary-800 w-2 h-1" style="animation-delay: 0.1s"></div>
          <div class="loadItem bg-primary-500 w-2 h-1" style="animation-delay: 0.2s"></div>
          <div class="loadItem bg-primary-300 w-2 h-1" style="animation-delay: 0.3s"></div>
          <div class="loadItem bg-primary-300 w-2 h-1" style="animation-delay: 0.4s"></div>
          <div class="loadItem bg-primary-500 w-2 h-1" style="animation-delay: 0.5s"></div>
          <div class="loadItem bg-primary-800 w-2 h-1" style="animation-delay: 0.6s"></div>
          <div class="loadItem bg-primary-800 w-2 h-1" style="animation-delay: 0.7s"></div>
        </div>
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
