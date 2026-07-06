<script setup>
</script>

<template>
  <div class="flex flex-col items-center justify-center w-full h-screen">
    <div class="w-1/2">
      <div class="w-full bg-surface-50 dark:bg-surface-950 py-20 px-8 sm:px-20 flex flex-col items-center border border-primary-400 rounded-2xl shadow-lg">
        <div class="gap-4 flex flex-col items-center">
          <div class="flex justify-center items-center border-2 border-primary-500 rounded-full" style="width: 3.2rem; height: 3.2rem">
            <i class="text-primary-500 pi pi-fw pi-search !text-2xl"></i>
          </div>
          <h1 class="text-surface-900 dark:text-surface-0 font-bold text-4xl lg:text-5xl mb-2">Page Introuvable</h1>
          <span class="text-muted-color text-center">La page que vous recherchez n'existe pas.</span>
          
          <!--  un bouton pour revenir à la page précédente  -->
          <Button @click="$router.go(-1)" class="mt-4" severity="primary">
            <span class="p-button-label">Retour</span>
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
