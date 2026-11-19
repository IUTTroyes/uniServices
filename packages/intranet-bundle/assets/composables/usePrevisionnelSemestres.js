import { onMounted, ref } from 'vue';
import { getSemestresService } from '@requests';
import { useUsersStore } from '@stores';

export function usePrevisionnelSemestres() {
  const departementId = useUsersStore().departementDefaut?.id;
  const semestres = ref([]);
  const semestre = ref(null);
  const chargementSemestres = ref(true);
  const erreurSemestres = ref(false);

  async function chargerSemestres() {
    try {
      if (!departementId) return;
      semestres.value = await getSemestresService({ departement: departementId, pagination: false }, '/mini') ?? [];
      semestre.value = semestres.value[0] ?? null;
    } catch {
      erreurSemestres.value = true;
    } finally {
      chargementSemestres.value = false;
    }
  }

  onMounted(chargerSemestres);
  return { semestres, semestre, chargementSemestres, erreurSemestres };
}
