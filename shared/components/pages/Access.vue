<script setup>
</script>

<template>
  <div class="flex flex-col items-center justify-center">
    <div class="w-full">
      <div class="w-full bg-surface-50 dark:bg-surface-950 py-20 px-8 sm:px-20 flex flex-col items-center border border-primary-400 rounded-2xl shadow-lg">
        <div class="gap-4 flex flex-col items-center">
          <div class="flex justify-center items-center border-2 border-primary-500 rounded-full" style="width: 3.2rem; height: 3.2rem">
            <i class="text-primary-500 pi pi-fw pi-lock !text-2xl"></i>
          </div>
          <h1 class="text-surface-900 dark:text-surface-0 font-bold text-4xl lg:text-5xl mb-2">Accès Refusé</h1>
          <span class="text-muted-color text-center">Vous n'avez pas les permissions d'accéder à ce contenu. <br> Si vous estimez que c'est une erreur, contactez les administrateurs du site.</span>
          <a href="mailto:intranet.iut-troyes@univ-reims.fr" class="underline">Contacter les administrateurs du site &nbsp; <i class="pi pi-external-link !text-xs underline"></i></a>

          <img src="/demo/images/access/asset-access.svg" alt="Access denied" class="mb-8" width="80%" />
          <!--  un bouton pour revenir à la page précédente  -->
          <Button @click="$router.go(-1)" class="mt-4" severity="primary">
            <span class="p-button-label">Retour</span>
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
