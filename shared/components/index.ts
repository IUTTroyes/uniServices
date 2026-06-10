import LayoutComponent from './components/layout/AppLayout.vue';
import LoginComponent from './pages/auth/Login.vue';
import FooterComponent from './components/layout/AppFooter.vue';
import TopbarComponent from './components/layout/AppTopbar.vue';
import SimpleSkeleton from './loader/SimpleSkeleton.vue';
import ArticleSkeleton from './loader/ArticleSkeleton.vue';
import ListSkeleton from './loader/ListSkeleton.vue';
import CardSkeleton from "./loader/CardSkeleton.vue";
import ApcCompetenceBadge from "./Apc/ApcCompetenceBadge.vue";
import ApcAcBadge from "./Apc/ApcAcBadge.vue";
import ErrorView from './components/ErrorView.vue';
import PhotoUser from './components/PhotoUser.vue';
import ProfilEtudiant from "./components/Etudiant/ProfilEtudiant/ProfilEtudiant.vue";
import ScolariteEtudiant from "./components/Etudiant/ProfilEtudiant/ScolariteEtudiant.vue";
import ProfilPersonnel from "./components/Personnel/ProfilPersonnel.vue";
import PermissionGuard from "./components/Permission/PermissionGuard.vue";
import MessageCard from "./components/Messages/MessageCard.vue";
import GlobalLoader from "./loader/GlobalLoader.vue";
import Access from "./pages/Access.vue";
import ButtonDelete from "./components/Buttons/ButtonDelete.vue";
import ButtonEdit from "./components/Buttons/ButtonEdit.vue";
import ButtonInfo from "./components/Buttons/ButtonInfo.vue";
import ButtonSave from "./components/Buttons/ButtonSave.vue";

import PersonnelsListe from "./components/Personnel/PersonnelsListe.vue";

// Form validation components
import FormValidator from './components/Forms/FormValidator.vue';
import ValidatedInput from './components/Forms/ValidatedInput.vue';
import AddressAutocomplete from './components/Forms/AddressAutocomplete.vue';
import ExampleValidatedForm from './components/Forms/ExampleValidatedForm.vue';
import { validationRules, validateField } from './utils/formValidation';

export {
  // Composants utilitaires
  PermissionGuard,


  // Composants mineurs
  MessageCard,
  Access,
  GlobalLoader,
  CardSkeleton,
  ApcCompetenceBadge,
  ApcAcBadge,
  ErrorView,
  PhotoUser,
  SimpleSkeleton,
  ArticleSkeleton,
  ListSkeleton,
  ButtonDelete,
  ButtonEdit,
  ButtonSave,
  ButtonInfo,


  // Composants majeurs
  LayoutComponent,
  LoginComponent,
  FooterComponent,
  TopbarComponent,
  PersonnelsListe,
  ProfilEtudiant,
  ScolariteEtudiant,
  ProfilPersonnel,

  // Form validation exports
  FormValidator,
  ValidatedInput,
  AddressAutocomplete,
  ExampleValidatedForm,
  validationRules,
  validateField,
};
