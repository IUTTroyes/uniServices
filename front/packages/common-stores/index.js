import { useAppStore } from "@stores/appStore";
import { useUsersStore } from './user_stores/userStore'
import { useSemestreStore } from './structure_stores/semestreStore'
import { useProfesseursStore } from './user_stores/personnelStore'
import { useEnseignementsStore } from './scol_stores/enseignementStore'
import { useAnneeUnivStore } from './structure_stores/anneeUnivStore'
import { useDiplomeStore} from "./structure_stores/diplomeStore";
import { useAnneeStore } from './structure_stores/anneeStore';
import { useDepartementStore } from './structure_stores/departementStore'

export {
  useAppStore,
  useUsersStore,
  useSemestreStore,
  useProfesseursStore,
  useEnseignementsStore,
  useDiplomeStore,
  useAnneeUnivStore,
  useAnneeStore,
  useDepartementStore
}
