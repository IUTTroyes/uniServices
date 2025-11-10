<script setup lang="ts">

interface Props {
  competence: object;
}

const props = defineProps<Props>();
</script>

<template>
  <div class="d-flex align-items-stretch mt-3">
    <div class="col-3 competence ">
      <div :class="`p-3 comp competence${ competence.couleur }`">
        {{ competence.nomCourt }}
      </div>
    </div>
    <div class="col-3 competencelongue  ms-3">
      <div :class="`p-3 competencelongue${ competence.couleur }`">
        {{ competence.libelle }}
      </div>
    </div>
    <div :class="`col-6 composantesessentielles${ competence.couleur }`">
      <ul class="ms-2">
        <li v-for="composante in competence.composantesEssentielles">{{ composante.libelle }}</li>
      </ul>

    </div>
  </div>

  <div class="d-flex align-items-stretch mt-3">
    <div class="col-3">
      <div :class="`p-3 situationpro situation${ competence.couleur }`">Situations professionnelles</div>
    </div>
    <div class="col-9 ms-3 situationliste">
      <div :class="`p-3 situationliste${ competence.couleur }`">
        <p v-for="situation in competence.situationsProfessionnelles">{{ situation.libelle }}</p>
      </div>
    </div>
  </div>

  <div class="d-flex align-items-stretch mt-3">
    <div class="col-3">
      <div class="p-3 titreniveau">Niveaux</div>
    </div>
    <div class="col-9 ms-3">
      <div class="p-3 titreac">Apprentissages critiques
      </div>
    </div>
  </div>

  <template v-for="(niveau, index) in competence.niveaux">
    <div class="d-flex align-items-stretch mt-3" v-if="index === 0 && niveau.ordreAnnee > 1">
      <div :class="`col-flex-3 niveau  niveau1${ competence.couleur }`">
        <div class="p-3 niveaucourt ">
          &nbsp;
        </div>
        <div class="p-3 niveaulong">
          &nbsp;
        </div>
      </div>
      <div class="col-flex-9 ms-3">
        <div :class="`p-3 niveauliste niveau1${ competence.couleur }`">
          &nbsp;
        </div>
      </div>
    </div>

    <div class="d-flex align-items-stretch mt-3">
      <div :class="`col-flex-3 niveau niveau${ niveau.ordre }${ competence.couleur }`">
        <div class="p-3 niveaucourt ">
          B.U.T. {{ niveau.ordre }}
        </div>
        <div class="p-3 niveaulong">
          {{ niveau.libelle }}
        </div>
      </div>
      <div class="col-flex-9 ms-3">
        <div :class="`p-3 niveauliste niveau${ niveau.ordre }${ competence.couleur }`">
          <ul>
            <li v-for="ac in niveau.apprentissageCritique">
              {{ ac.code }} | {{ ac.libelle }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </template>
</template>

<style scoped>

</style>
