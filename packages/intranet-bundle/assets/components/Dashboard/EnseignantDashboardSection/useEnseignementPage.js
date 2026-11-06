import {computed} from 'vue';
import {useRouter} from 'vue-router';
import {hasPermission} from '@utils/permissions';
export function useEnseignementPage(type, enseignement) {
const router = useRouter();
const destination = computed(() => {
  const anneeId = enseignement()?.anneeId || enseignement()?.semestre?.annee?.id || enseignement()?.annee?.id;
  if (type === 'absences' || type === 'evaluations') {
    return anneeId ? {name: type === 'absences' ? 'liste-absences' : 'liste-evaluations', params: {anneeId}} : null;
  }
  if (type === 'previsionnel') return '/intranet/administration/previsionnel/personnel';
  return {path: '/intranet/pedagogie', query: {onglet: type === 'plan' ? 'plans' : 'enseignements'}};
});
const accessible = computed(() => destination.value && router.resolve(destination.value).matched.every(record => !record.meta.permission || hasPermission(record.meta.permission)));

return {destination, accessible};
}
