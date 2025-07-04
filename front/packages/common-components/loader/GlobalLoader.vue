<script setup>

</script>

<template>
    <div class="container">
        <div class="loader">
          <div class="loadItem bg-primary-800" style="animation-delay: 0s"></div>
          <div class="loadItem bg-primary-800" style="animation-delay: 0.1s"></div>
          <div class="loadItem bg-primary-500" style="animation-delay: 0.2s"></div>
          <div class="loadItem bg-primary-300" style="animation-delay: 0.3s"></div>
          <div class="loadItem bg-primary-300" style="animation-delay: 0.4s"></div>
          <div class="loadItem bg-primary-500" style="animation-delay: 0.5s"></div>
          <div class="loadItem bg-primary-800" style="animation-delay: 0.6s"></div>
          <div class="loadItem bg-primary-800" style="animation-delay: 0.7s"></div>
        </div>
        <div>Chargement en cours ...</div>
    </div>
</template>

<style scoped>
.container{
  width: 100%;
  margin: 250px auto;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}
.loader {
  position: relative;
  width: 100%;
  height: 40px;
  margin-bottom: 10px;
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
}
.loadItem {
  width: 5px;
  height: 1px;
  margin: 5px;
  border-radius: 10px;
  animation: loadItemWave 1.5s infinite ease-in-out;
}
@keyframes loadItemWave {
  0%, 100% {
    transform: scaleY(0);
  }
  50% {
    transform: scaleY(40);
  }
}
</style>
