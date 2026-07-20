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
import ButtonDuplicate from "./components/Buttons/ButtonDuplicate.vue";
import WidgetCard from './components/Dashboard/WidgetCard.vue';
import DashboardWidgetsConfiguration from './components/Dashboard/DashboardWidgetsConfiguration.vue';
import { registerWidgetComponent, resolveWidgetComponent, widgetRegistry } from './components/Dashboard/widgets/widgetRegistry';
import HeaderComponent from './Header.vue'
import Kpi from './Kpi.vue'
import Card from './components/Card.vue'
import QuickActionCard from './components/QuickActionCard.vue'
import ActionButtonVertical from './components/ActionButtonVertical.vue'
import UserCard from "./components/User/UserCard.vue";
import EdtEventRow from "./components/Edt/EdtEventRow.vue";

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
  ButtonDuplicate,

  // Composants majeurs
  LayoutComponent,
  HeaderComponent,
  LoginComponent,
  FooterComponent,
  TopbarComponent,
  PersonnelsListe,
  ProfilEtudiant,
  ScolariteEtudiant,
  ProfilPersonnel,
  WidgetCard,
  DashboardWidgetsConfiguration,
  registerWidgetComponent,
  resolveWidgetComponent,
  widgetRegistry,
  UserCard,
  EdtEventRow,

  // Form validation exports
  FormValidator,
  ValidatedInput,
  AddressAutocomplete,
  ExampleValidatedForm,
  validationRules,
  validateField,

  Kpi,
  Card,
  QuickActionCard,
  ActionButtonVertical
};
