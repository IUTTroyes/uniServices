<script setup>
import { useVersion } from '@helpers/useVersion.js';

const { version } = useVersion();

</script>

<template>
  <div class="flex justify-between items-center border-t pt-6">
    <div class="flex items-center gap-2">
      <span class="font-bold">UniServices</span>
      <span>Copyright &copy; 2025</span>
      <span><a class="underline" href="">@David Annebicque</a></span>
      <span>-</span>
      <span><a class="underline" href="@mailto">@Cyndel Herolt</a></span>
      <span>All rights reserved</span>
      <span>|</span>
      <span>Version {{ version }}</span>
    </div>

    <div class="">
      <span>Contact : <a href="" class="underline">intranet.iut-troyes@univ-reims.fr</a></span>
    </div>
  </div>
</template>
